<?php

namespace App\Http\Controllers;

use App\Models\Workshop;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicWorkshopController extends Controller
{
    public function index()
    {
        $workshops = Workshop::query()
            ->where('is_active', true)
            ->with([
                'benefits' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn (Workshop $workshop) => $this->transformWorkshop($workshop));

        return view('public.workshop.index', [
            'workshops' => $workshops,
        ]);
    }

    public function show(string $slug)
    {
        $workshop = Workshop::query()
            ->where('is_active', true)
            ->with([
                'benefits' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('public.workshop.show', [
            'workshop' => $this->transformWorkshop($workshop),
        ]);
    }

    private function transformWorkshop(Workshop $workshop): array
    {
        $imageUrl = $this->resolveImageUrl($workshop->image);

        $introVideoType = $workshop->intro_video_type ?: 'youtube';
        $introVideoUrl = $this->resolveIntroVideoUrl(
            $workshop->intro_video_url,
            $introVideoType
        );

        return [
            'id' => $workshop->id,
            'slug' => $workshop->slug,
            'title' => $workshop->title,
            'badge' => $workshop->badge,
            'short_description' => $workshop->short_description,
            'overview' => $workshop->overview,
            'price' => (float) $workshop->price,
            'old_price' => $workshop->old_price !== null ? (float) $workshop->old_price : null,
            'rating' => (int) $workshop->rating,
            'rating_count' => (int) $workshop->rating_count,
            'duration' => $workshop->duration,
            'level' => $workshop->level,
            'category' => $workshop->category,
            'audience' => $workshop->audience,

            /*
            |--------------------------------------------------------------------------
            | Image
            |--------------------------------------------------------------------------
            | image_raw = value asli dari database
            | image     = URL final yang siap dipakai di Blade
            | image_url = alias, kalau Blade lama pakai image_url tetap aman
            */
            'image_raw' => $workshop->image,
            'image' => $imageUrl,
            'image_url' => $imageUrl,

            'intro_video_type' => $introVideoType,
            'intro_video_url' => $introVideoUrl,

            'benefits' => $workshop->benefits
                ->pluck('content')
                ->values()
                ->all(),
        ];
    }

    private function resolveImageUrl(?string $image): string
    {
        $fallbackImage = asset('images/universal.png');

        $image = trim((string) $image);

        if ($image === '') {
            return $fallbackImage;
        }

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        if (Str::startsWith($image, '//')) {
            return 'https:' . $image;
        }

        $image = ltrim($image, '/');

        if (Str::startsWith($image, ['storage/'])) {
            return asset($image);
        }

        if (Str::startsWith($image, ['images/', 'assets/', 'uploads/'])) {
            return asset($image);
        }

        if (Storage::disk('public')->exists($image)) {
            return Storage::disk('public')->url($image);
        }

        return asset('storage/' . $image);
    }

    private function resolveIntroVideoUrl(?string $url, ?string $type = 'youtube'): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $type = strtolower((string) $type);

        if ($type !== 'youtube') {
            return $url;
        }

        if (Str::contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        if (Str::contains($url, 'youtu.be/')) {
            $videoId = Str::after($url, 'youtu.be/');
            $videoId = Str::before($videoId, '?');

            return $videoId
                ? 'https://www.youtube.com/embed/' . $videoId
                : $url;
        }

        if (Str::contains($url, 'youtube.com/watch')) {
            $query = parse_url($url, PHP_URL_QUERY);

            if (!$query) {
                return $url;
            }

            parse_str($query, $params);

            $videoId = $params['v'] ?? null;

            return $videoId
                ? 'https://www.youtube.com/embed/' . $videoId
                : $url;
        }

        if (Str::contains($url, 'youtube.com/shorts/')) {
            $videoId = Str::after($url, 'youtube.com/shorts/');
            $videoId = Str::before($videoId, '?');

            return $videoId
                ? 'https://www.youtube.com/embed/' . $videoId
                : $url;
        }

        return $url;
    }
}