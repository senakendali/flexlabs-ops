<?php

namespace App\Http\Controllers;

use App\Models\Workshop;

class PublicWorkshopController extends Controller
{
    public function index()
    {
        $workshops = Workshop::query()
            ->where('is_active', true)
            ->with(['benefits' => fn ($query) => $query->orderBy('sort_order')])
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
            ->with(['benefits' => fn ($query) => $query->orderBy('sort_order')])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('public.workshop.show', [
            'workshop' => $this->transformWorkshop($workshop),
        ]);
    }

    private function transformWorkshop(Workshop $workshop): array
    {
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
            'image' => $workshop->image ?: 'images/hero.png',
            'intro_video_type' => $workshop->intro_video_type ?: 'youtube',
            'intro_video_url' => $workshop->intro_video_url,
            'benefits' => $workshop->benefits
                ->pluck('content')
                ->values()
                ->all(),
        ];
    }
}