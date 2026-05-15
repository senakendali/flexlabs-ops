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
            'image' => ['required', 'image', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sortOrder = $validated['sort_order']
            ?? (((int) $material->images()->max('sort_order')) + 1);

        $image = PublicLearningMaterialImage::create([
            'public_learning_material_id' => $material->id,
            'image_path' => $request->file('image')->store('public-learning-materials/gallery', 'public'),
            'caption' => $validated['caption'] ?? null,
            'sort_order' => $sortOrder,
            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gallery image berhasil diupload.',
            'data' => $this->formatImage($image->fresh()),
        ]);
    }

    public function update(Request $request, PublicLearningMaterialImage $image)
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        unset($validated['image']);

        $validated['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : false;

        if ($request->hasFile('image')) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }

            $validated['image_path'] = $request->file('image')
                ->store('public-learning-materials/gallery', 'public');
        }

        $image->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Gallery image berhasil diperbarui.',
            'data' => $this->formatImage($image->fresh()),
        ]);
    }

    public function destroy(PublicLearningMaterialImage $image)
    {
        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gallery image berhasil dihapus.',
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
            'success' => true,
            'message' => 'Urutan gallery image berhasil diperbarui.',
        ]);
    }

    private function formatImage(PublicLearningMaterialImage $image): array
    {
        return [
            'id' => $image->id,
            'public_learning_material_id' => $image->public_learning_material_id,
            'image_path' => $image->image_path,
            'image_url' => $image->image_path ? asset('storage/' . $image->image_path) : null,
            'url' => $image->image_path ? asset('storage/' . $image->image_path) : null,
            'caption' => $image->caption,
            'sort_order' => $image->sort_order,
            'is_active' => (bool) ($image->is_active ?? true),
        ];
    }
}