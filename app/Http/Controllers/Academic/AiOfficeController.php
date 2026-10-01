<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiOfficeController extends Controller
{
    /**
     * Tampilkan halaman utama Academic AI Office.
     */
    public function index(): View
    {
        return view('academic.ai-office.index');
    }

    /**
     * Ambil current state Academic AI Office.
     *
     * Untuk tahap awal masih static.
     * Nanti bisa dipindahkan ke service / database.
     */
    public function state(): JsonResponse
    {
        return response()->json([
            'office' => [
                'name' => 'Academic AI Office',
                'division' => 'Academic Division',
                'status' => 'active',
            ],

            'primary_agent' => [
                'name' => 'Luna',
                'role' => 'Academic Secretary',
                'status' => 'idle',
                'greeting' => 'Halo, Mas! Saya Luna, Virtual Academic Secretary.',
            ],

            'agents' => [
                [
                    'name' => 'Luna',
                    'role' => 'Academic Secretary',
                    'status' => 'idle',
                ],
            ],
        ]);
    }

    /**
     * Handle pesan dari user ke Luna.
     *
     * Untuk tahap awal response masih placeholder.
     * Nanti akan diteruskan ke Academic AI Agent Service.
     */
    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        return response()->json([
            'success' => true,

            'message' => [
                'from' => 'Luna',
                'role' => 'Academic Secretary',
                'content' => sprintf(
                    'Saya menerima pesan Mas: "%s"',
                    $validated['message']
                ),
            ],
        ]);
    }
}