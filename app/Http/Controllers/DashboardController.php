<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Instructor;
use App\Models\Program;
use App\Models\TrialParticipant;
use App\Models\TrialSchedule;
use App\Models\TrialTheme;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();

        $stats = [
            'programs' => [
                'total' => Program::count(),
                'active' => Program::query()->when(
                    $this->hasColumn((new Program())->getTable(), 'is_active'),
                    fn ($query) => $query->where('is_active', true)
                )->count(),
            ],
            'instructors' => [
                'total' => Instructor::count(),
                'active' => Instructor::query()->when(
                    $this->hasColumn((new Instructor())->getTable(), 'is_active'),
                    fn ($query) => $query->where('is_active', true)
                )->count(),
            ],
            'equipments' => [
                'total' => Equipment::count(),
                'active' => Equipment::query()->when(
                    $this->hasColumn((new Equipment())->getTable(), 'is_active'),
                    fn ($query) => $query->where('is_active', true)
                )->count(),
            ],
            'trialThemes' => [
                'total' => TrialTheme::count(),
                'active' => TrialTheme::query()->when(
                    $this->hasColumn((new TrialTheme())->getTable(), 'is_active'),
                    fn ($query) => $query->where('is_active', true)
                )->count(),
            ],
            'trialSchedules' => [
                'total' => TrialSchedule::count(),
                'active' => TrialSchedule::query()->when(
                    $this->hasColumn((new TrialSchedule())->getTable(), 'is_active'),
                    fn ($query) => $query->where('is_active', true)
                )->count(),
            ],
            'trialParticipants' => [
                'total' => TrialParticipant::count(),
                'new_this_month' => TrialParticipant::query()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ],
        ];

        $participantStatusCounts = collect([
            'registered' => 0,
            'contacted' => 0,
            'confirmed' => 0,
            'attended' => 0,
            'cancelled' => 0,
            'no_show' => 0,
        ])->merge(
            TrialParticipant::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
        );

        $followUpStatuses = ['contacted', 'confirmed', 'attended'];
        $followedUpCount = TrialParticipant::query()
            ->whereIn('status', $followUpStatuses)
            ->count();

        $totalParticipants = max((int) $stats['trialParticipants']['total'], 1);
        $followUpProgress = (int) round(($followedUpCount / $totalParticipants) * 100);

        $programThemeSummary = TrialParticipant::query()
            ->with([
                'trialTheme:id,name',
                'trialSchedule:id,program_id',
                'trialSchedule.program:id,name',
            ])
            ->latest()
            ->get()
            ->groupBy(function ($participant) {
                $programName = $participant->trialSchedule?->program?->name ?? 'Tanpa Program';
                $themeName = $participant->trialTheme?->name ?? 'Tanpa Tema';

                return $programName . '||' . $themeName;
            })
            ->map(function ($group, $key) {
                [$programName, $themeName] = explode('||', $key);

                $participantCount = $group->count();
                $attendedCount = $group->where('status', 'attended')->count();
                $confirmedCount = $group->where('status', 'confirmed')->count();

                $label = 'Perlu follow up';
                $badgeClass = 'bg-warning-subtle text-warning';

                if ($attendedCount > 0) {
                    $label = 'Baik';
                    $badgeClass = 'bg-success-subtle text-success';
                } elseif ($confirmedCount > 0) {
                    $label = 'Stabil';
                    $badgeClass = 'bg-primary-subtle text-primary';
                }

                return [
                    'program_name' => $programName,
                    'theme_name' => $themeName,
                    'participant_count' => $participantCount,
                    'conversion_label' => $label,
                    'conversion_badge_class' => $badgeClass,
                ];
            })
            ->sortByDesc('participant_count')
            ->take(3)
            ->values();

        $upcomingSchedules = TrialSchedule::query()
            ->with([
                'trialTheme:id,name',
                'program:id,name',
            ])
            ->when(
                $this->hasColumn((new TrialSchedule())->getTable(), 'is_active'),
                fn ($query) => $query->where('is_active', true)
            )
            ->whereDate('schedule_date', '>=', $today)
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->take(5)
            ->get();

        $scheduleParticipantCounts = TrialParticipant::query()
            ->selectRaw('trial_schedule_id, COUNT(*) as total')
            ->whereNotNull('trial_schedule_id')
            ->groupBy('trial_schedule_id')
            ->pluck('total', 'trial_schedule_id');

        $equipmentOverview = [
            'available' => Equipment::query()
                ->when(
                    $this->hasColumn((new Equipment())->getTable(), 'is_active'),
                    fn ($query) => $query->where('is_active', true)
                )
                ->when(
                    method_exists(new Equipment(), 'activeBorrowing'),
                    fn ($query) => $query->whereDoesntHave('activeBorrowing')
                )
                ->when(
                    $this->hasColumn((new Equipment())->getTable(), 'status'),
                    fn ($query) => $query->where('status', '!=', 'maintenance')
                )
                ->count(),

            'borrowed' => method_exists(new Equipment(), 'activeBorrowing')
                ? Equipment::query()->whereHas('activeBorrowing')->count()
                : 0,

            'maintenance' => $this->hasColumn((new Equipment())->getTable(), 'status')
                ? Equipment::query()->where('status', 'maintenance')->count()
                : 0,

            'inactive' => $this->hasColumn((new Equipment())->getTable(), 'is_active')
                ? Equipment::query()->where('is_active', false)->count()
                : 0,
        ];

        $recentParticipantActivities = TrialParticipant::query()
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($participant) {
                return [
                    'time' => $participant->created_at,
                    'icon' => 'bi-person-plus',
                    'icon_class' => 'bg-primary-subtle text-primary',
                    'title' => 'Peserta baru ditambahkan ke Trial Participant',
                    'subtitle' => ($participant->full_name ?? 'Peserta') . ' • ' . $participant->created_at->diffForHumans(),
                ];
            });

        $recentScheduleActivities = TrialSchedule::query()
            ->latest()
            ->take(2)
            ->get()
            ->map(function ($schedule) {
                return [
                    'time' => $schedule->created_at,
                    'icon' => 'bi-calendar-check',
                    'icon_class' => 'bg-success-subtle text-success',
                    'title' => 'Jadwal trial baru berhasil dibuat',
                    'subtitle' => ($schedule->name ?? 'Jadwal Trial') . ' • ' . $schedule->created_at->diffForHumans(),
                ];
            });

        $recentThemeActivities = TrialTheme::query()
            ->latest()
            ->take(2)
            ->get()
            ->map(function ($theme) {
                return [
                    'time' => $theme->created_at,
                    'icon' => 'bi-brush',
                    'icon_class' => 'bg-info-subtle text-info',
                    'title' => 'Theme trial berhasil diperbarui / ditambahkan',
                    'subtitle' => ($theme->name ?? 'Theme Trial') . ' • ' . $theme->created_at->diffForHumans(),
                ];
            });

        $recentActivities = $recentParticipantActivities
            ->concat($recentScheduleActivities)
            ->concat($recentThemeActivities)
            ->sortByDesc('time')
            ->take(6)
            ->values();

        return view('dashboard', compact(
            'stats',
            'participantStatusCounts',
            'followUpProgress',
            'programThemeSummary',
            'upcomingSchedules',
            'scheduleParticipantCounts',
            'equipmentOverview',
            'recentActivities'
        ));
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return \Schema::hasColumn($table, $column);
    }
}