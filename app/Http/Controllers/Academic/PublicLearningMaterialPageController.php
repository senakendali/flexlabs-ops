<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\PublicLearningMaterial;

class PublicLearningMaterialPageController extends Controller
{
    public function show(string $token, string $slug)
    {
        $material = PublicLearningMaterial::query()
            ->with([
                'activeBlocks',
                'images',
            ])
            ->where('public_token', $token)
            ->where('slug', $slug)
            ->firstOrFail();

        if ($material->status !== 'published') {
            abort(404);
        }

        $now = now();

        if ($material->access_starts_at && $now->lt($material->access_starts_at)) {
            return view('public-learning-materials.locked', [
                'material' => $material,
                'reason' => 'not_started',
            ]);
        }

        if ($material->access_ends_at && $now->gt($material->access_ends_at)) {
            return view('public-learning-materials.expired', [
                'material' => $material,
            ]);
        }

        return view('public-learning-materials.show', [
            'material' => $material,
        ]);
    }
}