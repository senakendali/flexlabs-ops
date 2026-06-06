<?php

namespace App\Http\Controllers\Trial;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\TrialParticipant;
use App\Models\TrialSchedule;
use App\Models\TrialTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicTrialRegistrationController extends Controller
{
    private const DEFAULT_THEME_IMAGE = 'images/triall-hero.png';

    public function index(): View
    {
        $today = now()->toDateString();

        $schedules = $this->getUpcomingSchedules();

        $themeScheduleCounts = TrialSchedule::query()
            ->where('is_active', true)
            ->whereDate('schedule_date', '>=', $today)
            ->whereNotNull('trial_theme_id')
            ->selectRaw('trial_theme_id, COUNT(*) as total')
            ->groupBy('trial_theme_id')
            ->pluck('total', 'trial_theme_id');

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
                'image',
                'sort_order',
                'is_active',
            ])
            ->map(function (TrialTheme $theme) use ($themeScheduleCounts) {
                $theme->setAttribute('image_url', $this->getThemeImageUrl($theme));
                $theme->setAttribute('upcoming_schedules_count', (int) ($themeScheduleCounts[$theme->id] ?? 0));

                return $theme;
            });

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

    public function show(string $slug): View
    {
        $theme = TrialTheme::query()
            ->with('program:id,name')
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail([
                'id',
                'program_id',
                'name',
                'slug',
                'description',
                'image',
                'sort_order',
                'is_active',
            ]);

        $theme->setAttribute('image_url', $this->getThemeImageUrl($theme));

        $schedules = $this->getUpcomingSchedules($theme->id);

        $programs = Program::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);

        return view('trial.public.show', compact(
            'theme',
            'schedules',
            'programs'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $today = now()->toDateString();

        $validated = $request->validate([
            'trial_schedule_id' => [
                'required',
                'integer',
                Rule::exists('trial_schedules', 'id')->where(function ($query) use ($today) {
                    $query
                        ->where('is_active', true)
                        ->whereDate('schedule_date', '>=', $today);
                }),
            ],
            'trial_theme_id' => [
                'nullable',
                'integer',
                Rule::exists('trial_themes', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'domicile_city' => ['required', 'string', 'max:255'],
            'current_activity' => ['required', 'string', 'max:255'],
            'goal' => ['required', 'string'],
            'input_source' => ['nullable', Rule::in(['admin', 'self_registration'])],
            'status' => [
                'nullable',
                Rule::in([
                    'registered',
                    'contacted',
                    'confirmed',
                    'attended',
                    'cancelled',
                    'no_show',
                ]),
            ],
        ], [
            'trial_schedule_id.required' => 'Jadwal webinar wajib dipilih.',
            'trial_schedule_id.exists' => 'Jadwal webinar yang dipilih tidak valid atau sudah tidak tersedia.',
            'trial_theme_id.exists' => 'Tema webinar yang dipilih tidak valid atau sedang tidak aktif.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'domicile_city.required' => 'Domisili wajib diisi.',
            'current_activity.required' => 'Aktivitas saat ini wajib diisi.',
            'goal.required' => 'Tujuan mengikuti webinar wajib diisi.',
        ]);

        $schedule = TrialSchedule::query()
            ->where('is_active', true)
            ->whereDate('schedule_date', '>=', $today)
            ->select([
                'id',
                'trial_theme_id',
            ])
            ->findOrFail($validated['trial_schedule_id']);

        /*
        |--------------------------------------------------------------------------
        | Theme Consistency
        |--------------------------------------------------------------------------
        | Kalau jadwal punya trial_theme_id, data peserta akan mengikuti tema
        | dari jadwal tersebut. Ini mencegah user memilih tema A tapi jadwal B.
        |--------------------------------------------------------------------------
        */
        if ($schedule->trial_theme_id) {
            $validated['trial_theme_id'] = $schedule->trial_theme_id;
        }

        $validated['input_source'] = 'self_registration';
        $validated['status'] = 'registered';

        $participant = TrialParticipant::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran webinar berhasil dikirim. Tim FlexLabs akan segera menghubungi kamu.',
            'data' => $participant,
        ]);
    }

    private function getUpcomingSchedules(?int $themeId = null)
    {
        return TrialSchedule::query()
            ->with([
                'program:id,name',
                'trialTheme:id,program_id,name,slug,image',
            ])
            ->where('is_active', true)
            ->whereDate('schedule_date', '>=', now()->toDateString())
            ->when($themeId, function ($query) use ($themeId) {
                $query->where('trial_theme_id', $themeId);
            })
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
            ])
            ->map(function (TrialSchedule $schedule) {
                if ($schedule->trialTheme) {
                    $schedule->trialTheme->setAttribute(
                        'image_url',
                        $this->getThemeImageUrl($schedule->trialTheme)
                    );
                }

                return $schedule;
            });
    }

    private function getThemeImageUrl(TrialTheme $theme): string
    {
        if ($theme->image) {
            return Storage::disk('public')->url($theme->image);
        }

        return asset(self::DEFAULT_THEME_IMAGE);
    }
}
