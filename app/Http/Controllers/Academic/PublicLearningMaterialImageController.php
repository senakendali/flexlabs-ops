<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\PublicLearningMaterial;
use App\Models\PublicLearningMaterialImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicLearningMaterialImageController extends Controller
{
    public function store(Request $request, PublicLearningMaterial $material)
    {
        $validated = $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['required', 'image', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $createdImages = [];

        foreach ($request->file('images', []) as $imageFile) {
            $sortOrder = ((int) $material->images()->max('sort_order')) + 1;

            $createdImages[] = PublicLearningMaterialImage::create([
                'public_learning_material_id' => $material->id,
                'image_path' => $imageFile->store('public-learning-materials/gallery', 'public'),
                'caption' => $validated['caption'] ?? null,
                'sort_order' => $sortOrder,
            ]);
        }

        return response()->json([
            'message' => 'Image berhasil diupload.',
            'data' => $createdImages,
        ]);
    }

    public function update(Request $request, PublicLearningMaterialImage $image)
    {
        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $image->update($validated);

        return response()->json([
            'message' => 'Image berhasil diperbarui.',
            'data' => $image->fresh(),
        ]);
    }

    public function destroy(PublicLearningMaterialImage $image)
    {
        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json([
            'message' => 'Image berhasil dihapus.',
        ]);
    }

    public function reorder(Request $request, PublicLearningMaterial $material)
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:public_learning_material_images,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($validated['items'] as $item) {
            PublicLearningMaterialImage::query()
                ->where('id', $item['id'])
                ->where('public_learning_material_id', $material->id)
                ->update([
                    'sort_order' => $item['sort_order'],
                ]);
        }

        return response()->json([
            'message' => 'Urutan image berhasil diperbarui.',
        ]);
    }
}