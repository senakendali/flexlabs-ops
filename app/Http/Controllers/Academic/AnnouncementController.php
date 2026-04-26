<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Batch;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $search = $request->get('search');
        $status = $request->get('status');
        $targetType = $request->get('target_type');
        $programId = $request->get('program_id');
        $batchId = $request->get('batch_id');

        $announcements = Announcement::query()
            ->with([
                'program',
                'batch.program',
                'creator',
                'updater',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('title', 'like', '%' . $search . '%')
                        ->orWhere('content', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($targetType, fn ($query) => $query->where('target_type', $targetType))
            ->when($programId, fn ($query) => $query->where('program_id', $programId))
            ->when($batchId, fn ($query) => $query->where('batch_id', $batchId))
            ->orderByDesc('is_pinned')
            ->latest('publish_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $programs = Program::query()
            ->when(
                Schema::hasColumn('programs', 'is_active'),
                fn ($query) => $query->where('is_active', true)
            )
            ->orderBy('name')
            ->get();

        $batches = Batch::query()
            ->with('program')
            ->when(
                Schema::hasColumn('batches', 'is_active'),
                fn ($query) => $query->where('is_active', true)
            )
            ->latest()
            ->get();

        $stats = [
            'total' => Announcement::count(),
            'published' => Announcement::where('status', 'published')->count(),
            'draft' => Announcement::where('status', 'draft')->count(),
            'pinned' => Announcement::where('is_pinned', true)->count(),
            'active' => Announcement::where('is_active', true)->count(),
            'archived' => Announcement::where('status', 'archived')->count(),
        ];

        return view('academic.announcements.index', compact(
            'announcements',
            'programs',
            'batches',
            'stats',
            'search',
            'status',
            'targetType',
            'programId',
            'batchId'
        ));
    }

    public function show(Announcement $announcement): JsonResponse
    {
        $announcement->load([
            'program',
            'batch.program',
            'creator',
            'updater',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Announcement berhasil diambil.',
            'data' => $this->formatAnnouncementResponse($announcement),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $payload = $this->buildPayload($request, $validated);
        $payload['created_by'] = auth()->id();
        $payload['updated_by'] = auth()->id();

        $announcement = Announcement::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Announcement berhasil ditambahkan.',
            'data' => $this->formatAnnouncementResponse(
                $announcement->fresh()->load([
                    'program',
                    'batch.program',
                    'creator',
                    'updater',
                ])
            ),
        ], 201);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $this->validateRequest($request, $announcement->id);

        $payload = $this->buildPayload($request, $validated, $announcement);
        $payload['updated_by'] = auth()->id();

        $announcement->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Announcement berhasil diupdate.',
            'data' => $this->formatAnnouncementResponse(
                $announcement->fresh()->load([
                    'program',
                    'batch.program',
                    'creator',
                    'updater',
                ])
            ),
        ]);
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Announcement berhasil dihapus.',
        ]);
    }

    private function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('announcements', 'slug')->ignore($ignoreId),
            ],

            'content' => ['nullable', 'string'],

            'target_type' => [
                'required',
                Rule::in([
                    'all',
                    'program',
                    'batch',
                ]),
            ],

            'program_id' => [
                'nullable',
                'integer',
                Rule::exists('programs', 'id'),
            ],

            'batch_id' => [
                'nullable',
                'integer',
                Rule::exists('batches', 'id'),
            ],

            'publish_at' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date', 'after_or_equal:publish_at'],

            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'published',
                    'archived',
                ]),
            ],

            'is_pinned' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function buildPayload(Request $request, array $validated, ?Announcement $announcement = null): array
    {
        $targetType = $validated['target_type'];

        $programId = $validated['program_id'] ?? null;
        $batchId = $validated['batch_id'] ?? null;

        if ($targetType === 'all') {
            $programId = null;
            $batchId = null;
        }

        if ($targetType === 'program') {
            if (!$programId) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Program wajib dipilih untuk target Program.',
                    'errors' => [
                        'program_id' => ['Program wajib dipilih untuk target Program.'],
                    ],
                ], 422));
            }

            $batchId = null;
        }

        if ($targetType === 'batch') {
            if (!$batchId) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Batch wajib dipilih untuk target Batch.',
                    'errors' => [
                        'batch_id' => ['Batch wajib dipilih untuk target Batch.'],
                    ],
                ], 422));
            }

            $batch = Batch::query()->find($batchId);

            if (!$batch) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Batch tidak ditemukan.',
                    'errors' => [
                        'batch_id' => ['Batch tidak ditemukan.'],
                    ],
                ], 422));
            }

            if (!$programId) {
                $programId = $batch->program_id;
            }

            if (
                Schema::hasColumn('batches', 'program_id')
                && $batch->program_id
                && (int) $batch->program_id !== (int) $programId
            ) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Batch tidak sesuai dengan program yang dipilih.',
                    'errors' => [
                        'batch_id' => ['Batch tidak sesuai dengan program yang dipilih.'],
                    ],
                ], 422));
            }
        }

        return [
            'title' => $validated['title'],

            'slug' => $this->generateUniqueSlug(
                $validated['slug'] ?? null,
                $validated['title'],
                $announcement?->id
            ),

            'content' => $validated['content'] ?? null,

            'target_type' => $targetType,
            'program_id' => $programId,
            'batch_id' => $batchId,

            'publish_at' => $this->parseNullableDateTime($validated['publish_at'] ?? null),
            'expired_at' => $this->parseNullableDateTime($validated['expired_at'] ?? null),

            'status' => $validated['status'],

            'is_pinned' => $request->boolean('is_pinned'),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function parseNullableDateTime(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value);
    }

    private function formatAnnouncementResponse(Announcement $announcement): array
    {
        return [
            'id' => $announcement->id,

            'title' => $announcement->title,
            'slug' => $announcement->slug,
            'content' => $announcement->content,

            'target_type' => $announcement->target_type,
            'target_label' => $announcement->target_label,

            'program_id' => $announcement->program_id,
            'program_name' => $announcement->program?->name,

            'batch_id' => $announcement->batch_id,
            'batch_name' => $announcement->batch?->name,
            'batch_program_id' => $announcement->batch?->program_id,
            'batch_program_name' => $announcement->batch?->program?->name,

            'publish_at' => $announcement->publish_at?->format('Y-m-d H:i:s'),
            'publish_at_input' => $announcement->publish_at?->format('Y-m-d\TH:i'),
            'publish_at_label' => $announcement->publish_at?->format('d M Y H:i'),

            'expired_at' => $announcement->expired_at?->format('Y-m-d H:i:s'),
            'expired_at_input' => $announcement->expired_at?->format('Y-m-d\TH:i'),
            'expired_at_label' => $announcement->expired_at?->format('d M Y H:i'),

            'status' => $announcement->status,
            'status_label' => $announcement->status_label,

            'is_pinned' => (bool) $announcement->is_pinned,
            'is_active' => (bool) $announcement->is_active,
            'is_visible' => (bool) $announcement->is_visible,

            'created_by' => $announcement->created_by,
            'created_by_name' => $announcement->creator?->name,

            'updated_by' => $announcement->updated_by,
            'updated_by_name' => $announcement->updater?->name,

            'created_at' => $announcement->created_at?->format('Y-m-d H:i:s'),
            'created_at_label' => $announcement->created_at?->format('d M Y H:i'),

            'updated_at' => $announcement->updated_at?->format('Y-m-d H:i:s'),
            'updated_at_label' => $announcement->updated_at?->format('d M Y H:i'),
        ];
    }

    private function generateUniqueSlug(?string $slug, string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $title);

        if (blank($baseSlug)) {
            $baseSlug = 'announcement';
        }

        $finalSlug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($finalSlug, $ignoreId)) {
            $finalSlug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $finalSlug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Announcement::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();
    }
}