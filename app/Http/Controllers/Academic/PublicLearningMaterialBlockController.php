<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\PublicLearningMaterial;
use App\Models\PublicLearningMaterialBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PublicLearningMaterialBlockController extends Controller
{
    public function store(Request $request, PublicLearningMaterial $material)
    {
        $validated = $this->validateBlock($request);

        unset($validated['image']);

        $validated['public_learning_material_id'] = $material->id;

        if (! isset($validated['sort_order'])) {
            $validated['sort_order'] = ((int) $material->blocks()->max('sort_order')) + 1;
        }

        $validated['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : true;

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')
                ->store('public-learning-materials/blocks', 'public');
        }

        $block = PublicLearningMaterialBlock::create($validated);

        return response()->json([
            'message' => 'Block berhasil ditambahkan.',
            'data' => $block,
        ]);
    }

    public function update(Request $request, PublicLearningMaterialBlock $block)
    {
        $validated = $this->validateBlock($request);

        unset($validated['image']);

        $validated['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : false;

        if ($request->hasFile('image')) {
            if ($block->image_path) {
                Storage::disk('public')->delete($block->image_path);
            }

            $validated['image_path'] = $request->file('image')
                ->store('public-learning-materials/blocks', 'public');
        }

        $block->update($validated);

        return response()->json([
            'message' => 'Block berhasil diperbarui.',
            'data' => $block->fresh(),
        ]);
    }

    public function destroy(PublicLearningMaterialBlock $block)
    {
        if ($block->image_path) {
            Storage::disk('public')->delete($block->image_path);
        }

        $block->delete();

        return response()->json([
            'message' => 'Block berhasil dihapus.',
        ]);
    }

    public function reorder(Request $request, PublicLearningMaterial $material)
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:public_learning_material_blocks,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($validated['items'] as $item) {
            PublicLearningMaterialBlock::query()
                ->where('id', $item['id'])
                ->where('public_learning_material_id', $material->id)
                ->update([
                    'sort_order' => $item['sort_order'],
                ]);
        }

        return response()->json([
            'message' => 'Urutan block berhasil diperbarui.',
        ]);
    }

    private function validateBlock(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['heading', 'text', 'code', 'image', 'note', 'task'])],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],

            'code_language' => ['nullable', 'string', 'max:50'],
            'code_content' => ['nullable', 'string'],

            'image' => ['nullable', 'image', 'max:4096'],
            'image_caption' => ['nullable', 'string', 'max:255'],

            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}