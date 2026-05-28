<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LearningVideoController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Storage Location
    |--------------------------------------------------------------------------
    |
    | Final path:
    | storage/app/private/learning-videos/sub-topics
    |
    */
    private const DIRECTORY = 'learning-videos/sub-topics';

    private const MAX_UPLOAD_KB = 524288; // 512 MB

    private const ALLOWED_EXTENSIONS = [
        'mp4',
        'mov',
        'webm',
        'mkv',
        'avi',
        'm4v',
    ];

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): View|JsonResponse
    {
        $this->ensureAcademicAccess($request);

        $videos = $this->getVideos();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $videos,
            ]);
        }

        return view('academic.learning-videos.index', [
            'videos' => $videos,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */
    public function store(Request $request): JsonResponse
    {
        $this->ensureAcademicAccess($request);

        $validated = $request->validate([
            'video' => [
                'required',
                'file',
                'mimes:mp4,mov,webm,mkv,avi,m4v',
                'max:' . self::MAX_UPLOAD_KB,
            ],
        ], [
            'video.required' => 'File video wajib dipilih.',
            'video.file' => 'File yang diupload tidak valid.',
            'video.mimes' => 'Format video harus mp4, mov, webm, mkv, avi, atau m4v.',
            'video.max' => 'Ukuran video maksimal 512 MB.',
        ]);

        $this->ensureDirectoryExists();

        $file = $validated['video'];

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension());

        $safeOriginalName = Str::slug($originalName);

        if (! $safeOriginalName) {
            $safeOriginalName = 'sub-topic-video';
        }

        $filename = now()->format('Ymd_His')
            . '_'
            . Str::random(8)
            . '_'
            . $safeOriginalName
            . '.'
            . $extension;

        $file->move($this->basePath(), $filename);

        return response()->json([
            'success' => true,
            'message' => 'Video berhasil diupload.',
            'data' => $this->formatVideo($filename),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Stream
    |--------------------------------------------------------------------------
    |
    | Karena file ada di private storage, video tidak diakses langsung dari URL.
    | Preview/play akan lewat route ini.
    |
    */
    public function stream(Request $request, string $filename): BinaryFileResponse
    {
        $this->ensureAcademicAccess($request);

        $filename = basename($filename);
        $path = $this->filePath($filename);

        abort_unless(File::exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        abort_unless(in_array($extension, self::ALLOWED_EXTENSIONS, true), 404);

        return response()->file($path, [
            'Content-Type' => $this->guessVideoMimeType($extension),
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request, string $filename): JsonResponse|RedirectResponse
    {
        $this->ensureAcademicAccess($request);

        $filename = basename($filename);
        $path = $this->filePath($filename);

        if (! File::exists($path)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video tidak ditemukan.',
                ], 404);
            }

            return back()->with('error', 'Video tidak ditemukan.');
        }

        File::delete($path);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Video berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Video berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Videos
    |--------------------------------------------------------------------------
    */
    private function getVideos(): array
    {
        $this->ensureDirectoryExists();

        return collect(File::files($this->basePath()))
            ->filter(function ($file) {
                return in_array(
                    strtolower($file->getExtension()),
                    self::ALLOWED_EXTENSIONS,
                    true
                );
            })
            ->map(fn ($file) => $this->formatVideo($file->getFilename()))
            ->sortByDesc('last_modified_timestamp')
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Format Video
    |--------------------------------------------------------------------------
    */
    private function formatVideo(string $filename): array
    {
        $filename = basename($filename);
        $path = $this->filePath($filename);

        $lastModified = File::lastModified($path);
        $size = File::size($path);

        return [
            'filename' => $filename,
            'name' => pathinfo($filename, PATHINFO_FILENAME),
            'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),

            /*
            |--------------------------------------------------------------------------
            | Internal path info
            |--------------------------------------------------------------------------
            */
            'path' => self::DIRECTORY . '/' . $filename,
            'storage_path' => 'storage/app/private/' . self::DIRECTORY . '/' . $filename,

            /*
            |--------------------------------------------------------------------------
            | Stream URL
            |--------------------------------------------------------------------------
            | Dipakai oleh blade untuk preview/play video.
            */
            'stream_url' => route('academic.learning-videos.stream', [
                'filename' => $filename,
            ]),

            'size' => $size,
            'size_label' => $this->formatBytes($size),
            'last_modified' => Carbon::createFromTimestamp($lastModified)->format('d M Y H:i'),
            'last_modified_timestamp' => $lastModified,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Path Helpers
    |--------------------------------------------------------------------------
    */
    private function basePath(): string
    {
        return storage_path('app/private/' . self::DIRECTORY);
    }

    private function filePath(string $filename): string
    {
        return $this->basePath() . DIRECTORY_SEPARATOR . basename($filename);
    }

    private function ensureDirectoryExists(): void
    {
        if (! File::exists($this->basePath())) {
            File::makeDirectory($this->basePath(), 0755, true);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Format Helpers
    |--------------------------------------------------------------------------
    */
    private function formatBytes(int|float $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }

    private function guessVideoMimeType(string $extension): string
    {
        return match ($extension) {
            'mp4', 'm4v' => 'video/mp4',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'avi' => 'video/x-msvideo',
            default => 'application/octet-stream',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Access Guard
    |--------------------------------------------------------------------------
    */
    private function ensureAcademicAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless($user, 403);

        abort_unless(
            in_array($user->role, ['admin', 'academic'], true),
            403
        );
    }
}