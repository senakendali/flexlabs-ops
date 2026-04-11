<?php

namespace App\Http\Controllers\Trial;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\TrialSchedule;
use App\Models\TrialTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrialScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $schedules = TrialSchedule::with(['program', 'trialTheme'])
            ->whereDate('schedule_date', '>=', now()->toDateString())
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->paginate($perPage)
            ->withQueryString();

        $programs = Program::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $themes = TrialTheme::query()
            ->with('program:id,name')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'program_id', 'name']);

        return view('trial.schedules.index', compact(
            'schedules',
            'programs',
            'themes'
        ));
    }

    public function show(TrialSchedule $trialSchedule): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $trialSchedule->load(['program:id,name', 'trialTheme:id,program_id,name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $schedule = TrialSchedule::create($validated)
            ->load(['program:id,name', 'trialTheme:id,program_id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Trial schedule berhasil ditambahkan.',
            'data' => $schedule,
        ]);
    }

    public function update(Request $request, TrialSchedule $trialSchedule): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $trialSchedule->update($validated);
        $trialSchedule->load(['program:id,name', 'trialTheme:id,program_id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Trial schedule berhasil diperbarui.',
            'data' => $trialSchedule,
        ]);
    }

    public function destroy(TrialSchedule $trialSchedule): JsonResponse
    {
        $trialSchedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trial schedule berhasil dihapus.',
        ]);
    }

    private function validateRequest(Request $request): array
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'trial_theme_id' => ['nullable', 'exists:trial_themes,id'],
            'name' => ['required', 'string', 'max:255'],
            'schedule_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['trial_theme_id'])) {
            $theme = TrialTheme::query()
                ->select('id', 'program_id')
                ->find($validated['trial_theme_id']);

            if ($theme && (int) $theme->program_id !== (int) $validated['program_id']) {
                return abort(response()->json([
                    'message' => 'Theme yang dipilih tidak sesuai dengan program.',
                    'errors' => [
                        'trial_theme_id' => ['Theme yang dipilih tidak sesuai dengan program.'],
                    ],
                ], 422));
            }
        }

        $validated['trial_theme_id'] = $validated['trial_theme_id'] ?? null;
        $validated['end_time'] = $validated['end_time'] ?? null;
        $validated['quota'] = $validated['quota'] ?? null;
        $validated['description'] = $validated['description'] ?? null;
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}