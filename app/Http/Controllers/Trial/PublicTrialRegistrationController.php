<?php

namespace App\Http\Controllers\Trial;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\TrialParticipant;
use App\Models\TrialSchedule;
use App\Models\TrialTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class PublicTrialRegistrationController extends Controller
{
    public function index(): View
    {
        $schedules = TrialSchedule::query()
            ->with([
                'program:id,name',
                'trialTheme:id,name',
            ])
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
                'description',
                'is_active',
            ]);

        $themes = TrialTheme::query()
            ->with('program:id,name')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'program_id',
                'name',
                'slug',
                'description',
                'sort_order',
                'is_active',
            ]);

        $programs = Program::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);

        return view('trial.public.index', compact(
            'schedules',
            'themes',
            'programs'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trial_schedule_id' => ['required', 'integer', 'exists:trial_schedules,id'],
            'trial_theme_id' => ['nullable', 'integer', 'exists:trial_themes,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'domicile_city' => ['required', 'string', 'max:255'],
            'current_activity' => ['required', 'string', 'max:255'],
            'goal' => ['required', 'string'],
            'input_source' => ['nullable', Rule::in(['admin', 'self_registration'])],
            'status' => ['nullable', Rule::in([
                'registered',
                'contacted',
                'confirmed',
                'attended',
                'cancelled',
                'no_show',
            ])],
        ], [
            'trial_schedule_id.required' => 'Jadwal trial wajib dipilih.',
            'trial_schedule_id.exists' => 'Jadwal trial yang dipilih tidak valid.',
            'trial_theme_id.exists' => 'Tema trial yang dipilih tidak valid.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'domicile_city.required' => 'Domisili wajib diisi.',
            'current_activity.required' => 'Aktivitas saat ini wajib diisi.',
            'goal.required' => 'Tujuan mengikuti trial wajib diisi.',
        ]);

        $schedule = TrialSchedule::query()
            ->select('id', 'trial_theme_id')
            ->findOrFail($validated['trial_schedule_id']);

        if (empty($validated['trial_theme_id'])) {
            $validated['trial_theme_id'] = $schedule->trial_theme_id;
        }

        $validated['input_source'] = 'self_registration';
        $validated['status'] = 'registered';

        $participant = TrialParticipant::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran trial class berhasil dikirim. Tim FlexLabs akan segera menghubungi kamu.',
            'data' => $participant,
        ]);
    }
}