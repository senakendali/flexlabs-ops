<?php

namespace App\Http\Controllers\Trial;

use App\Http\Controllers\Controller;
use App\Models\TrialParticipant;
use App\Models\TrialSchedule;
use App\Models\TrialTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrialParticipantController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $search = trim((string) $request->get('search'));
        $scheduleId = $request->get('trial_schedule_id');
        $status = $request->get('status');

        $participants = TrialParticipant::query()
            ->with([
                'trialSchedule:id,program_id,trial_theme_id,name,schedule_date,start_time,end_time,quota,is_active',
                'trialTheme:id,name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('domicile_city', 'like', "%{$search}%")
                        ->orWhere('current_activity', 'like', "%{$search}%");
                });
            })
            ->when($scheduleId, function ($query) use ($scheduleId) {
                $query->where('trial_schedule_id', $scheduleId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $trialSchedules = TrialSchedule::query()
            ->where('is_active', true)
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get([
                'id',
                'program_id',
                'trial_theme_id',
                'name',
                'schedule_date',
                'start_time',
                'end_time',
                'quota',
                'is_active',
            ]);

        $trialThemes = TrialTheme::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $statusOptions = $this->statusOptions();
        $inputSourceOptions = $this->inputSourceOptions();

        return view('trial.participants.index', compact(
            'participants',
            'trialSchedules',
            'trialThemes',
            'statusOptions',
            'inputSourceOptions'
        ));
    }

    public function show(TrialParticipant $trialParticipant): JsonResponse
    {
        $trialParticipant->load([
            'trialSchedule:id,program_id,trial_theme_id,name,schedule_date,start_time,end_time,quota,is_active',
            'trialTheme:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => $trialParticipant,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        if (empty($validated['trial_theme_id']) && !empty($validated['trial_schedule_id'])) {
            $schedule = TrialSchedule::find($validated['trial_schedule_id']);
            $validated['trial_theme_id'] = $schedule?->trial_theme_id;
        }

        $participant = TrialParticipant::create($validated);

        $participant->load([
            'trialSchedule:id,program_id,trial_theme_id,name,schedule_date,start_time,end_time,quota,is_active',
            'trialTheme:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trial participant created successfully.',
            'data' => $participant,
        ]);
    }

    public function update(Request $request, TrialParticipant $trialParticipant): JsonResponse
    {
        $validated = $this->validateRequest($request);

        if (empty($validated['trial_theme_id']) && !empty($validated['trial_schedule_id'])) {
            $schedule = TrialSchedule::find($validated['trial_schedule_id']);
            $validated['trial_theme_id'] = $schedule?->trial_theme_id;
        }

        $trialParticipant->update($validated);

        $trialParticipant->load([
            'trialSchedule:id,program_id,trial_theme_id,name,schedule_date,start_time,end_time,quota,is_active',
            'trialTheme:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trial participant updated successfully.',
            'data' => $trialParticipant,
        ]);
    }

    public function destroy(TrialParticipant $trialParticipant): JsonResponse
    {
        $trialParticipant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trial participant deleted successfully.',
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        return $request->validate([
            'trial_schedule_id' => ['required', 'integer', 'exists:trial_schedules,id'],
            'trial_theme_id' => ['nullable', 'integer', 'exists:trial_themes,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'domicile_city' => ['nullable', 'string', 'max:255'],
            'current_activity' => ['nullable', 'string', 'max:255'],
            'goal' => ['nullable', 'string'],
            'input_source' => ['nullable', Rule::in(array_keys($this->inputSourceOptions()))],
            'status' => ['nullable', Rule::in(array_keys($this->statusOptions()))],
            'notes' => ['nullable', 'string'],
        ], [
            'trial_schedule_id.required' => 'Trial schedule is required.',
            'trial_schedule_id.exists' => 'Selected trial schedule is invalid.',
            'trial_theme_id.exists' => 'Selected trial theme is invalid.',
        ]);
    }

    protected function statusOptions(): array
    {
        return [
            'registered' => 'Registered',
            'contacted' => 'Contacted',
            'confirmed' => 'Confirmed',
            'attended' => 'Attended',
            'cancelled' => 'Cancelled',
            'no_show' => 'No Show',
        ];
    }

    protected function inputSourceOptions(): array
    {
        return [
            'admin' => 'Admin',
            'self_registration' => 'Self Registration',
        ];
    }
}