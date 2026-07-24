@extends('layouts.app-dashboard')

@section('title', 'Management Dashboard')

@section('content')

@php
    $salesInsight = $salesInsight ?? [];
    $academicStats = $academicStats ?? [];
    $batchCapacity = $batchCapacity ?? [];
    $trialStats = $trialStats ?? [];
    $revenueChart = $revenueChart ?? [
        'labels' => [],
        'data' => [],
        'year' => now()->year,
        'total' => 0,
    ];

    $trialParticipantStatusCounts = collect($trialParticipantStatusCounts ?? [
        'registered' => 0,
        'contacted' => 0,
        'confirmed' => 0,
        'attended' => 0,
        'cancelled' => 0,
        'no_show' => 0,
    ]);
    $trialFollowUpProgress = (int) ($trialFollowUpProgress ?? 0);
    $upcomingTrialSchedules = $upcomingTrialSchedules ?? collect();

    $workshopInsight = $workshopInsight ?? [];
    $workshopStats = $workshopStats ?? [];
    $workshopParticipantStatusCounts = collect($workshopParticipantStatusCounts ?? [
        'registered' => 0,
        'pending_payment' => 0,
        'confirmed' => 0,
        'attended' => 0,
        'cancelled' => 0,
    ]);
    $workshopFollowUpProgress = (int) ($workshopFollowUpProgress ?? 0);
    $upcomingWorkshopSchedules = $upcomingWorkshopSchedules ?? collect();

    $financeInsight = $financeInsight ?? [];
    $orderInsight = $orderInsight ?? [];
    $managementSummary = $managementSummary ?? [];
    $upcomingBatches = $upcomingBatches ?? collect();

    /*
    |--------------------------------------------------------------------------
    | Work Progress: Academic, Marketing & SEI
    |--------------------------------------------------------------------------
    | Data disiapkan dari DashboardController via TrelloDashboardStatsService.
    | Academic, Marketing, dan SEI dibaca dari variable masing-masing, dengan fallback
    | ke array trelloDashboardStats agar Blade tetap aman kalau controller lama.
    */
    $trelloDashboardStats = $trelloDashboardStats ?? [];
    $trelloAcademicStats = $trelloAcademicStats ?? ($trelloDashboardStats['academic'] ?? []);
    $trelloAcademicSummary = $trelloAcademicStats['summary'] ?? [];
    $trelloAcademicStatuses = $trelloAcademicStats['statuses'] ?? [];

    $trelloAcademicTotalOpenCards = max((int) ($trelloAcademicSummary['total_open_cards'] ?? 0), 0);
    $trelloAcademicActiveWork = max((int) ($trelloAcademicSummary['active_work'] ?? 0), 0);
    $trelloAcademicCompleted = max((int) ($trelloAcademicSummary['completed'] ?? 0), 0);
    $trelloAcademicDueToday = max((int) ($trelloAcademicSummary['due_today'] ?? 0), 0);
    $trelloAcademicOverdue = max((int) ($trelloAcademicSummary['overdue'] ?? 0), 0);
    $trelloAcademicUnmapped = max((int) ($trelloAcademicSummary['unmapped'] ?? 0), 0);
    $trelloAcademicCompletionRate = min(max((int) ($trelloAcademicSummary['completion_rate'] ?? 0), 0), 100);
    $trelloAcademicActiveWorkRate = min(max((int) ($trelloAcademicSummary['active_work_rate'] ?? 0), 0), 100);

    $trelloAcademicDueTodayCards = collect($trelloAcademicStats['due_today_cards'] ?? []);
    $trelloAcademicOverdueCards = collect($trelloAcademicStats['overdue_cards'] ?? []);
    $trelloAcademicPriorityCards = $trelloAcademicOverdueCards
        ->merge($trelloAcademicDueTodayCards)
        ->unique(fn ($card) => $card['trello_card_id'] ?? $card['id'] ?? $card['name'] ?? uniqid())
        ->values();

    $trelloAcademicActiveCards = collect($trelloAcademicStats['active_cards'] ?? []);
    $trelloAcademicRecentCards = collect($trelloAcademicStats['recent_cards'] ?? []);

    $trelloAcademicWebhookStatus = (string) ($trelloAcademicStats['webhook_status'] ?? 'inactive');
    $trelloAcademicIsSynced = in_array($trelloAcademicWebhookStatus, ['active', 'synced'], true);
    $trelloAcademicBoardName = $trelloAcademicStats['board_name'] ?? 'Academic Trello';
    $trelloAcademicInsight = $trelloAcademicStats['insight'] ?? 'Academic Trello insight belum tersedia.';

    $trelloAcademicLastSyncedRaw = $trelloAcademicStats['last_synced_at'] ?? null;
    $trelloAcademicLastWebhookRaw = $trelloAcademicStats['last_webhook_at'] ?? null;

    $trelloAcademicLastSyncedText = $trelloAcademicLastSyncedRaw
        ? \Carbon\Carbon::parse($trelloAcademicLastSyncedRaw)->format('d M Y H:i')
        : '-';

    $trelloAcademicLastWebhookText = $trelloAcademicLastWebhookRaw
        ? \Carbon\Carbon::parse($trelloAcademicLastWebhookRaw)->format('d M Y H:i')
        : '-';

    $trelloAcademicProgressClass = $trelloAcademicCompletionRate >= 80
        ? 'bg-success'
        : ($trelloAcademicCompletionRate >= 50 ? 'bg-warning' : 'bg-danger');

    $trelloAcademicOverdueClass = $trelloAcademicOverdue > 0 ? 'text-danger' : 'text-success';
    $trelloAcademicDueTodayClass = $trelloAcademicDueToday > 0 ? 'text-warning' : 'text-success';

    $trelloMarketingStats = $trelloMarketingStats ?? ($trelloDashboardStats['marketing'] ?? []);
    $trelloMarketingSummary = $trelloMarketingStats['summary'] ?? [];
    $trelloMarketingStatuses = $trelloMarketingStats['statuses'] ?? [];

    $trelloMarketingTotalOpenCards = max((int) ($trelloMarketingSummary['total_open_cards'] ?? 0), 0);
    $trelloMarketingActiveWork = max((int) ($trelloMarketingSummary['active_work'] ?? 0), 0);
    $trelloMarketingCompleted = max((int) ($trelloMarketingSummary['completed'] ?? 0), 0);
    $trelloMarketingDueToday = max((int) ($trelloMarketingSummary['due_today'] ?? 0), 0);
    $trelloMarketingOverdue = max((int) ($trelloMarketingSummary['overdue'] ?? 0), 0);
    $trelloMarketingUnmapped = max((int) ($trelloMarketingSummary['unmapped'] ?? 0), 0);
    $trelloMarketingCompletionRate = min(max((int) ($trelloMarketingSummary['completion_rate'] ?? 0), 0), 100);
    $trelloMarketingActiveWorkRate = min(max((int) ($trelloMarketingSummary['active_work_rate'] ?? 0), 0), 100);

    $trelloMarketingDueTodayCards = collect($trelloMarketingStats['due_today_cards'] ?? []);
    $trelloMarketingOverdueCards = collect($trelloMarketingStats['overdue_cards'] ?? []);
    $trelloMarketingPriorityCards = $trelloMarketingOverdueCards
        ->merge($trelloMarketingDueTodayCards)
        ->unique(fn ($card) => $card['trello_card_id'] ?? $card['id'] ?? $card['name'] ?? uniqid())
        ->values();

    $trelloMarketingActiveCards = collect($trelloMarketingStats['active_cards'] ?? []);
    $trelloMarketingRecentCards = collect($trelloMarketingStats['recent_cards'] ?? []);

    $trelloMarketingWebhookStatus = (string) ($trelloMarketingStats['webhook_status'] ?? 'inactive');
    $trelloMarketingIsSynced = in_array($trelloMarketingWebhookStatus, ['active', 'synced'], true);
    $trelloMarketingBoardName = $trelloMarketingStats['board_name'] ?? 'Marketing Trello';
    $trelloMarketingInsight = $trelloMarketingStats['insight'] ?? 'Marketing Work insight belum tersedia.';

    $trelloMarketingLastSyncedRaw = $trelloMarketingStats['last_synced_at'] ?? null;
    $trelloMarketingLastWebhookRaw = $trelloMarketingStats['last_webhook_at'] ?? null;

    $trelloMarketingLastSyncedText = $trelloMarketingLastSyncedRaw
        ? \Carbon\Carbon::parse($trelloMarketingLastSyncedRaw)->format('d M Y H:i')
        : '-';

    $trelloMarketingLastWebhookText = $trelloMarketingLastWebhookRaw
        ? \Carbon\Carbon::parse($trelloMarketingLastWebhookRaw)->format('d M Y H:i')
        : '-';

    $trelloMarketingProgressClass = $trelloMarketingCompletionRate >= 80
        ? 'bg-success'
        : ($trelloMarketingCompletionRate >= 50 ? 'bg-warning' : 'bg-danger');

    $trelloMarketingOverdueClass = $trelloMarketingOverdue > 0 ? 'text-danger' : 'text-success';
    $trelloMarketingDueTodayClass = $trelloMarketingDueToday > 0 ? 'text-warning' : 'text-success';

    $trelloSeiStats = $trelloSeiStats ?? ($trelloDashboardStats['sei'] ?? []);
    $trelloSeiSummary = $trelloSeiStats['summary'] ?? [];
    $trelloSeiStatuses = $trelloSeiStats['statuses'] ?? [];

    $trelloSeiTotalOpenCards = max((int) ($trelloSeiSummary['total_open_cards'] ?? 0), 0);
    $trelloSeiActiveWork = max((int) ($trelloSeiSummary['active_work'] ?? 0), 0);
    $trelloSeiCompleted = max((int) ($trelloSeiSummary['completed'] ?? 0), 0);
    $trelloSeiDueToday = max((int) ($trelloSeiSummary['due_today'] ?? 0), 0);
    $trelloSeiOverdue = max((int) ($trelloSeiSummary['overdue'] ?? 0), 0);
    $trelloSeiUnmapped = max((int) ($trelloSeiSummary['unmapped'] ?? 0), 0);
    $trelloSeiCompletionRate = min(max((int) ($trelloSeiSummary['completion_rate'] ?? 0), 0), 100);
    $trelloSeiActiveWorkRate = min(max((int) ($trelloSeiSummary['active_work_rate'] ?? 0), 0), 100);

    $trelloSeiDueTodayCards = collect($trelloSeiStats['due_today_cards'] ?? []);
    $trelloSeiOverdueCards = collect($trelloSeiStats['overdue_cards'] ?? []);
    $trelloSeiPriorityCards = $trelloSeiOverdueCards
        ->merge($trelloSeiDueTodayCards)
        ->unique(fn ($card) => $card['trello_card_id'] ?? $card['id'] ?? $card['name'] ?? uniqid())
        ->values();

    $trelloSeiActiveCards = collect($trelloSeiStats['active_cards'] ?? []);
    $trelloSeiRecentCards = collect($trelloSeiStats['recent_cards'] ?? []);

    $trelloSeiWebhookStatus = (string) ($trelloSeiStats['webhook_status'] ?? 'inactive');
    $trelloSeiIsSynced = in_array($trelloSeiWebhookStatus, ['active', 'synced'], true);
    $trelloSeiBoardName = $trelloSeiStats['board_name'] ?? 'SEI Trello';
    $trelloSeiInsight = $trelloSeiStats['insight'] ?? 'SEI Work insight belum tersedia.';

    $trelloSeiLastSyncedRaw = $trelloSeiStats['last_synced_at'] ?? null;
    $trelloSeiLastWebhookRaw = $trelloSeiStats['last_webhook_at'] ?? null;

    $trelloSeiLastSyncedText = $trelloSeiLastSyncedRaw
        ? \Carbon\Carbon::parse($trelloSeiLastSyncedRaw)->format('d M Y H:i')
        : '-';

    $trelloSeiLastWebhookText = $trelloSeiLastWebhookRaw
        ? \Carbon\Carbon::parse($trelloSeiLastWebhookRaw)->format('d M Y H:i')
        : '-';

    $trelloSeiProgressClass = $trelloSeiCompletionRate >= 80
        ? 'bg-success'
        : ($trelloSeiCompletionRate >= 50 ? 'bg-warning' : 'bg-danger');

    $trelloSeiOverdueClass = $trelloSeiOverdue > 0 ? 'text-danger' : 'text-success';
    $trelloSeiDueTodayClass = $trelloSeiDueToday > 0 ? 'text-warning' : 'text-success';

    $trelloStatusLabels = [
        'notes' => 'Notes',
        'todo' => 'To Do',
        'in_progress' => 'Doing',
        'review' => 'Review',
        'scheduled' => 'Scheduled',
        'done' => 'Done',
        'archived' => 'Archived',
        'ignored' => 'Ignored',
    ];

    $trelloStatusIcons = [
        'notes' => 'bi-journal-text',
        'todo' => 'bi-list-check',
        'in_progress' => 'bi-lightning-charge-fill',
        'review' => 'bi-eye-fill',
        'scheduled' => 'bi-calendar-event-fill',
        'done' => 'bi-check2-circle',
        'archived' => 'bi-archive-fill',
        'ignored' => 'bi-slash-circle',
    ];

    $trelloStatusBadgeClasses = [
        'notes' => 'bg-light text-muted',
        'todo' => 'bg-primary-subtle text-primary',
        'in_progress' => 'bg-warning-subtle text-warning',
        'review' => 'bg-info-subtle text-info',
        'scheduled' => 'bg-purple-subtle text-purple',
        'done' => 'bg-success-subtle text-success',
        'archived' => 'bg-secondary-subtle text-secondary',
        'ignored' => 'bg-secondary-subtle text-secondary',
    ];

    $salesLeads = (int) ($salesInsight['leads'] ?? 0);
    $salesInteracted = (int) ($salesInsight['interacted'] ?? $salesInsight['trial'] ?? 0);
    $salesClosing = (int) ($salesInsight['closing'] ?? $salesInsight['closed_deal'] ?? $salesInsight['join'] ?? 0);
    $salesPaid = (int) ($salesInsight['paid'] ?? 0);

    $salesInteractionRate = (float) ($salesInsight['interaction_rate'] ?? $salesInsight['conversion_trial'] ?? 0);
    $salesClosingRate = (float) ($salesInsight['closing_rate'] ?? $salesInsight['deal_rate'] ?? $salesInsight['conversion_join'] ?? 0);
    $salesPaidRate = (float) ($salesInsight['paid_rate'] ?? $salesInsight['conversion_paid'] ?? 0);


    $kommoTodayLeadInsight = $kommoTodayLeadInsight ?? [];
    $kommoAvailable = (bool) ($kommoTodayLeadInsight['is_available'] ?? false);
    $kommoTotalLeads = max((int) ($kommoTodayLeadInsight['total_leads'] ?? 0), 0);

    /*
    |--------------------------------------------------------------------------
    | Kommo Dashboard Values
    |--------------------------------------------------------------------------
    | Source of truth: DashboardController / KommoService.
    |
    | Final FlexLabs definition:
    | - Total Leads       = all Kommo leads created today.
    | - Incoming Leads    = leads still waiting in Incoming/Lead Masuk.
    | - Not Followed Up   = Incoming Leads.
    | - Need Action       = Incoming Leads.
    | - Followed Up       = Total Leads - Incoming Leads.
    | - Filtered Leads    = breakdown only; still considered processed/followed-up.
    |
    | Blade only reads the prepared values. Fallback below exists only to keep the
    | dashboard safe if the service/controller response is incomplete.
    */
    $kommoIncomingLeads = max((int) (
        $kommoTodayLeadInsight['incoming_leads']
        ?? $kommoTodayLeadInsight['lead_masuk']
        ?? 0
    ), 0);

    $kommoLeadMasuk = max((int) (
        $kommoTodayLeadInsight['lead_masuk']
        ?? $kommoTodayLeadInsight['incoming_leads']
        ?? 0
    ), 0);

    $kommoInitialContact = max((int) ($kommoTodayLeadInsight['initial_contact'] ?? 0), 0);
    $kommoNewLeads = max((int) ($kommoTodayLeadInsight['new_leads'] ?? 0), 0);
    $kommoInteracted = max((int) ($kommoTodayLeadInsight['interacted'] ?? 0), 0);
    $kommoIgnored = max((int) ($kommoTodayLeadInsight['ignored'] ?? 0), 0);
    $kommoClosedLost = max((int) ($kommoTodayLeadInsight['closed_lost'] ?? 0), 0);
    $kommoNotRelated = max((int) ($kommoTodayLeadInsight['not_related'] ?? 0), 0);
    $kommoWarmLeads = max((int) ($kommoTodayLeadInsight['warm_leads'] ?? 0), 0);
    $kommoHotLeads = max((int) ($kommoTodayLeadInsight['hot_leads'] ?? 0), 0);
    $kommoTrialClass = max((int) ($kommoTodayLeadInsight['trial_class'] ?? 0), 0);
    $kommoWaFirstBubble = max((int) ($kommoTodayLeadInsight['wa_first_bubble'] ?? 0), 0);
    $kommoConsultation = max((int) ($kommoTodayLeadInsight['consultation'] ?? 0), 0);
    $kommoRegister = max((int) ($kommoTodayLeadInsight['register'] ?? 0), 0);
    $kommoDataStorage = max((int) ($kommoTodayLeadInsight['data_storage'] ?? 0), 0);
    $kommoPaid = max((int) ($kommoTodayLeadInsight['paid'] ?? 0), 0);

    $kommoFilteredLeads = max((int) (
        $kommoTodayLeadInsight['filtered_out']
        ?? ($kommoIgnored + $kommoClosedLost + $kommoNotRelated)
    ), 0);

    $kommoFollowedUp = max((int) (
        $kommoTodayLeadInsight['followed_up']
        ?? max($kommoTotalLeads - min($kommoIncomingLeads, $kommoTotalLeads), 0)
    ), 0);
    $kommoFollowedUp = min($kommoFollowedUp, $kommoTotalLeads);

    $kommoNotFollowedUp = max((int) (
        $kommoTodayLeadInsight['not_followed_up']
        ?? $kommoIncomingLeads
    ), 0);
    $kommoNotFollowedUp = min($kommoNotFollowedUp, $kommoTotalLeads);

    $kommoNeedAction = max((int) (
        $kommoTodayLeadInsight['need_action']
        ?? $kommoTodayLeadInsight['needs_attention']
        ?? $kommoNotFollowedUp
    ), 0);
    $kommoNeedAction = min($kommoNeedAction, $kommoTotalLeads);

    $kommoFollowUpRate = max((int) (
        $kommoTodayLeadInsight['follow_up_rate']
        ?? ($kommoTotalLeads > 0 ? round(($kommoFollowedUp / $kommoTotalLeads) * 100) : 0)
    ), 0);
    $kommoFollowUpRate = min($kommoFollowUpRate, 100);

    // Progress wajib baca dari controller/service jika tersedia. Kalau belum ada,
    // fallback-nya ikut Follow-up Rate karena definition-nya sama: processed = non Incoming.
    $kommoProcessedLeads = max((int) (
        $kommoTodayLeadInsight['processed_leads']
        ?? $kommoFollowedUp
    ), 0);
    $kommoProcessedLeads = min($kommoProcessedLeads, $kommoTotalLeads);

    $kommoProcessingProgress = max((int) (
        $kommoTodayLeadInsight['processing_progress']
        ?? $kommoTodayLeadInsight['follow_up_rate']
        ?? ($kommoTotalLeads > 0 ? round(($kommoProcessedLeads / $kommoTotalLeads) * 100) : 0)
    ), 0);
    $kommoProcessingProgress = min($kommoProcessingProgress, 100);

    $kommoStatusBreakdown = collect($kommoTodayLeadInsight['status_breakdown'] ?? [
        [
            'status' => 'Incoming Leads',
            'total' => $kommoIncomingLeads,
            'category' => 'Need Action',
            'badge_class' => 'bg-warning-subtle text-warning',
            'description' => 'Lead baru yang masih berada di Incoming Leads dan perlu dicek sales.',
        ],
        [
            'status' => 'Initial Contact',
            'total' => $kommoInitialContact,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead sudah mulai dicek atau dikontak oleh sales.',
        ],
        [
            'status' => 'New Leads',
            'total' => $kommoNewLeads,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead valid yang sudah masuk proses follow-up awal.',
        ],
        [
            'status' => 'Interacted',
            'total' => $kommoInteracted,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead sudah memiliki interaksi awal dengan sales.',
        ],
        [
            'status' => 'Warm Leads',
            'total' => $kommoWarmLeads,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead mulai menunjukkan minat dan perlu follow-up lanjutan.',
        ],
        [
            'status' => 'Hot Leads',
            'total' => $kommoHotLeads,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead prioritas tinggi dengan potensi closing lebih kuat.',
        ],
        [
            'status' => 'Trial Class',
            'total' => $kommoTrialClass,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead sudah diarahkan ke tahap trial class.',
        ],
        [
            'status' => 'WA First Bubble',
            'total' => $kommoWaFirstBubble,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead sudah masuk alur komunikasi WhatsApp awal.',
        ],
        [
            'status' => 'Consultation',
            'total' => $kommoConsultation,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead sudah masuk tahap konsultasi atau appointment.',
        ],
        [
            'status' => 'Register',
            'total' => $kommoRegister,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead sudah bergerak ke tahap registrasi.',
        ],
        [
            'status' => 'Data Storage',
            'total' => $kommoDataStorage,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead sudah masuk penyimpanan data atau proses administrasi lanjutan.',
        ],
        [
            'status' => 'Ignored',
            'total' => $kommoIgnored,
            'category' => 'Filtered Leads',
            'badge_class' => 'bg-secondary-subtle text-secondary',
            'description' => 'Lead sudah dicek tetapi tidak dilanjutkan untuk saat ini.',
        ],
        [
            'status' => 'Closed Lost',
            'total' => $kommoClosedLost,
            'category' => 'Filtered Leads',
            'badge_class' => 'bg-secondary-subtle text-secondary',
            'description' => 'Lead sudah diproses tetapi tidak lanjut.',
        ],
        [
            'status' => 'Not Related',
            'total' => $kommoNotRelated,
            'category' => 'Filtered Leads',
            'badge_class' => 'bg-secondary-subtle text-secondary',
            'description' => 'Lead tidak relevan dengan program atau penawaran FlexLabs.',
        ],
        [
            'status' => 'Paid',
            'total' => $kommoPaid,
            'category' => 'Followed Up',
            'badge_class' => 'bg-success-subtle text-success',
            'description' => 'Lead sudah menjadi pembayaran/closing terkonfirmasi di Kommo.',
        ],
    ])->map(function ($item) {
        $category = $item['category'] ?? 'Info';

        $badgeClass = $item['badge_class'] ?? match ($category) {
            'Need Action', 'Incoming Leads', 'Incoming Queue' => 'bg-warning-subtle text-warning',
            'Filtered Leads', 'Filtered' => 'bg-secondary-subtle text-secondary',
            'Followed Up', 'Processed' => 'bg-success-subtle text-success',
            default => 'bg-primary-subtle text-primary',
        };

        return array_merge($item, [
            'status' => $item['status'] ?? $item['label'] ?? $item['name'] ?? '-',
            'total' => max((int) ($item['total'] ?? $item['value'] ?? 0), 0),
            'category' => $category,
            'badge_class' => $badgeClass,
            'description' => $item['description'] ?? '-',
        ]);
    });

    $kommoProgressClass = $kommoProcessingProgress >= 80
        ? 'bg-success'
        : ($kommoProcessingProgress >= 50 ? 'bg-warning' : 'bg-danger');
    $kommoAttentionClass = $kommoNeedAction > 0 ? 'text-warning' : 'text-success';
    $kommoStatusBadgeClass = $kommoAvailable ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
    $kommoStatusBadgeText = $kommoAvailable ? 'Synced' : 'Not Synced';

    $dashboardAiSummaryText = (string) ($dashboardAiSummaryText ?? ($managementSummary['summary_text'] ?? ''));

    if (blank($dashboardAiSummaryText)) {
        $dashboardAiSummaryText = 'Summary dashboard belum tersedia karena data utama masih kosong.';
    }

    $currentUser = auth()->user();

    $canManageCurriculum = $currentUser
        && method_exists($currentUser, 'canAccess')
        && $currentUser->canAccess('curriculum.view')
        && Route::has('curriculum.index');
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Overview Dashboard</div>
                <h1 class="page-title mb-2">Business & Academic Overview</h1>
                <p class="page-subtitle mb-0">
                    Pantau performa bisnis dan operasional dari sisi sales performance, academic, kapasitas batch,
                    trial performance, serta pendapatan dalam satu dashboard.
                </p>
            </div>

            @if($canManageCurriculum)
                <div class="page-header-actions d-flex gap-2 flex-wrap">
                    <a href="{{ route('curriculum.index') }}" class="btn btn-light btn-modern">
                        <i class="bi bi-gear-fill"></i> Manage Curriculum
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Sales Overview</div>
        <h4 class="dashboard-section-title mb-1">Sales Performance Summary</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring performa sales berdasarkan total leads, interaksi, closing, dan pembayaran yang sudah berhasil dikonfirmasi.
        </p>
    </div>

    {{-- Sales Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Leads</div>
                        <div class="funnel-value">{{ number_format($salesLeads) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Total leads yang tercatat dari sales daily report.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-chat-left-text"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Interacted</div>
                        <div class="funnel-value">{{ number_format($salesInteracted) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Interaction rate:
                    <strong>{{ number_format($salesInteractionRate, 1) }}%</strong>
                    dari total leads.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Closing</div>
                        <div class="funnel-value">{{ number_format($salesClosing) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Closing rate:
                    <strong>{{ number_format($salesClosingRate, 1) }}%</strong>
                    dari total leads.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Paid</div>
                        <div class="funnel-value">{{ number_format($salesPaid) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Paid rate:
                    <strong>{{ number_format($salesPaidRate, 1) }}%</strong>
                    dari total closing.
                </div>
            </div>
        </div>
    </div>

    {{-- Sales Performance Chart --}}
    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Sales Performance Overview</h5>
                <p class="content-card-subtitle mb-0">
                    Perkembangan leads, interaction, consultation, hot leads, dan closed deal untuk membaca performa sales secara cepat.
                </p>
            </div>

            <div class="revenue-total-box sales-chart-summary-box">
                <div class="revenue-total-label">Closed Deal</div>
                <div class="revenue-total-value" id="salesPerformanceClosedDealValue">{{ number_format($salesClosing) }}</div>
            </div>
        </div>

        <div class="content-card-body">
            <div class="chart-wrap" style="height: 360px;">
                <canvas id="salesPerformanceChart"></canvas>
            </div>
        </div>
    </div>

    @include('dashboard.components.marketing-performance-tabs', [
        'metaAdsDashboardInsight' => $metaAdsDashboardInsight ?? [],
    ])

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Kommo Leads Overview</div>
        <h4 class="dashboard-section-title mb-1">Today’s Kommo Lead Follow-up</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitor lead hari ini dari Kommo: total lead masuk, lead yang sudah diproses sales, incoming lead yang masih perlu action, dan follow-up rate.
        </p>
    </div>

    {{-- Kommo Leads Today Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-inboxes-fill"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Today’s Leads</div>
                        <div class="funnel-value">{{ number_format($kommoTotalLeads) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Total lead yang masuk dari Kommo hari ini.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-chat-dots-fill"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Followed Up</div>
                        <div class="funnel-value text-success">{{ number_format($kommoFollowedUp) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Lead yang sudah keluar dari Incoming Leads atau sudah masuk proses sales.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Incoming Lead</div>
                        <div class="funnel-value {{ $kommoAttentionClass }}">{{ number_format($kommoNotFollowedUp) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Lead yang masih berada di Incoming Leads dan perlu dicek sales.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Follow-up Rate</div>
                        <div class="funnel-value">{{ number_format($kommoFollowUpRate) }}%</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Persentase lead hari ini yang sudah diproses dari total lead masuk.
                </div>
            </div>
        </div>
    </div>

    {{-- Kommo Leads Today Progress --}}
    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Kommo Follow-up Progress</h5>
                <p class="content-card-subtitle mb-0">
                    Progress mengikuti nilai dari controller/service. Rule final: semua lead selain Incoming Leads dihitung sudah diproses.
                </p>
            </div>

            <span class="badge rounded-pill {{ $kommoStatusBadgeClass }}">
                {{ $kommoStatusBadgeText }}
            </span>
        </div>

        <div class="content-card-body">
            <div class="trial-progress-card kommo-progress-row-card">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
                    <div>
                        <div class="trial-progress-value">{{ number_format($kommoProcessingProgress) }}%</div>
                        <div class="trial-progress-label">Follow-up Progress Today</div>
                    </div>

                    <div class="text-lg-end">
                        <div class="small text-muted">Last updated</div>
                        <div class="fw-semibold text-dark">
                            {{ $kommoTodayLeadInsight['last_synced_at'] ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="progress progress-modern mb-4">
                    <div
                        class="progress-bar {{ $kommoProgressClass }}"
                        role="progressbar"
                        style="width: {{ $kommoProcessingProgress }}%;"
                        aria-valuenow="{{ $kommoProcessingProgress }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>

                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon bg-primary-subtle text-primary">
                                    <i class="bi bi-diagram-3"></i>
                                </div>
                                <span>Total Lead</span>
                            </div>
                            <strong>{{ number_format($kommoTotalLeads) }}</strong>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon bg-success-subtle text-success">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                                <span>Followed Up</span>
                            </div>
                            <strong class="text-success">{{ number_format($kommoFollowedUp) }}</strong>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon bg-warning-subtle text-warning">
                                    <i class="bi bi-lightning-charge"></i>
                                </div>
                                <span>Need Action</span>
                            </div>
                            <strong class="{{ $kommoAttentionClass }}">{{ number_format($kommoNeedAction) }}</strong>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon {{ $kommoAvailable ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                    <i class="bi {{ $kommoAvailable ? 'bi-cloud-check' : 'bi-cloud-slash' }}"></i>
                                </div>
                                <span>Sync Status</span>
                            </div>
                            <strong class="kommo-sync-value {{ $kommoAvailable ? 'text-success' : 'text-warning' }}">
                                {{ $kommoAvailable ? 'Synced' : 'Not Synced' }}
                            </strong>
                        </div>
                    </div>
                </div>

                @if(! $kommoAvailable && ! empty($kommoTodayLeadInsight['error_message']))
                    <div class="alert alert-warning mt-3 mb-0">
                        {{ $kommoTodayLeadInsight['error_message'] }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Kommo Leads Today Breakdown --}}
    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Kommo Lead Status Breakdown</h5>
                <p class="content-card-subtitle mb-0">
                    Detail status lead hari ini. Filtered Leads tetap dihitung sebagai sudah diproses, bukan data yang hilang.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <div class="kommo-insight-box mb-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="kommo-insight-icon">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Kommo Lead Insight</div>
                        <p class="text-muted mb-0">
                            {{ $kommoTodayLeadInsight['summary_text'] ?? 'Kommo lead insight is not available yet.' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="text-center">Total</th>
                            <th>Category</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody class="auto-expand-list kommo-auto-expand-list is-collapsed" data-initial-visible="4">
                        @forelse($kommoStatusBreakdown as $statusItem)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $statusItem['status'] }}</td>
                                <td class="text-center">{{ number_format($statusItem['total']) }}</td>
                                <td>
                                    <span class="badge rounded-pill {{ $statusItem['badge_class'] }}">
                                        {{ $statusItem['category'] }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $statusItem['description'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Belum ada breakdown status lead Kommo hari ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($kommoStatusBreakdown->count() > 4)
                <div class="auto-expand-trigger kommo-auto-expand-trigger" aria-hidden="true"></div>
            @endif
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Academic Overview</div>
        <h4 class="dashboard-section-title mb-1">Capacity, Delivery & Readiness</h4>
        <p class="dashboard-section-subtitle mb-0">Evaluasi kapasitas dan kesiapan delivery program yang terdiri dari kapasitas, delivery, dan readiness.</p>
    </div>

    {{-- Academic Main Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-journal-richtext"></i>
                    </div>
                    <div>
                        <div class="stat-title">Programs</div>
                        <div class="stat-value">{{ number_format($academicStats['programs'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total program akademik yang terdaftar.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-collection-play"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active Batches</div>
                        <div class="stat-value">{{ number_format($academicStats['active_batches'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Batch aktif yang sedang berjalan atau dibuka.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="stat-title">Filled Seats</div>
                        <div class="stat-value">{{ number_format($academicStats['filled_seats'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total seat yang sudah terisi di seluruh batch.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div>
                        <div class="stat-title">Upcoming Batches</div>
                        <div class="stat-value">{{ number_format($academicStats['upcoming_batches'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Batch yang akan dimulai dalam waktu dekat.</div>
            </div>
        </div>
    </div>

    {{-- Batch Capacity Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-grid-1x2-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Kapasitas Batch</div>
                        <div class="stat-value">{{ number_format($batchCapacity['total_capacity'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Akumulasi seluruh seat dari batch aktif.</div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Sudah Terisi</div>
                        <div class="stat-value">{{ number_format($batchCapacity['filled_seats'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Utilisasi {{ number_format($batchCapacity['utilization_percent'] ?? 0) }}% dari kapasitas aktif.
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Sisa Seat</div>
                        <div class="stat-value">{{ number_format($batchCapacity['remaining_seats'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Seat yang masih tersedia untuk diisi.</div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Team Overview</div>
        <h4 class="dashboard-section-title mb-1">Work Progress</h4>
        <p class="dashboard-section-subtitle mb-0">
            Pantau progres pekerjaan tiap tim berdasarkan status pengerjaan, prioritas, dan aktivitas terbaru.
        </p>
    </div>

    {{-- Work Progress Tabs --}}
    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Work Progress</h5>
                <p class="content-card-subtitle mb-0">
                    Ringkasan pekerjaan operasional berdasarkan board yang sudah tersambung ke dashboard.
                </p>
            </div>

            <ul class="nav nav-pills work-progress-tabs" id="workProgressTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link active"
                        id="academic-work-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#academic-work-pane"
                        type="button"
                        role="tab"
                        aria-controls="academic-work-pane"
                        aria-selected="true"
                    >
                        <i class="bi bi-mortarboard-fill me-1"></i>
                        Academic
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link"
                        id="marketing-work-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#marketing-work-pane"
                        type="button"
                        role="tab"
                        aria-controls="marketing-work-pane"
                        aria-selected="false"
                    >
                        <i class="bi bi-megaphone-fill me-1"></i>
                        Marketing
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link"
                        id="sei-work-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#sei-work-pane"
                        type="button"
                        role="tab"
                        aria-controls="sei-work-pane"
                        aria-selected="false"
                    >
                        <i class="bi bi-building-fill me-1"></i>
                        SEI
                    </button>
                </li>
            </ul>
        </div>

        <div class="content-card-body">
            <div class="tab-content" id="workProgressTabsContent">
                <div
                    class="tab-pane fade show active"
                    id="academic-work-pane"
                    role="tabpanel"
                    aria-labelledby="academic-work-tab"
                    tabindex="0"
                >
                    <div class="trello-insight-box mb-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="trello-insight-icon">
                                <i class="bi bi-kanban-fill"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark mb-1">Academic Work Insight</div>
                                <p class="text-muted mb-0">{{ $trelloAcademicInsight }}</p>
                                <div class="small text-muted mt-2">
                                    Last sync: <strong>{{ $trelloAcademicLastSyncedText }}</strong>
                                    <span class="mx-1">•</span>
                                    Last webhook: <strong>{{ $trelloAcademicLastWebhookText }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="work-progress-completion-card mb-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
                            <div>
                                <div class="work-progress-completion-eyebrow">Academic Progress</div>
                                <div class="work-progress-completion-value">{{ number_format($trelloAcademicCompletionRate) }}%</div>
                                <div class="work-progress-completion-label">
                                    {{ number_format($trelloAcademicCompleted) }} dari {{ number_format($trelloAcademicTotalOpenCards) }} card sudah selesai.
                                </div>
                            </div>

                            <div class="work-progress-completion-meta text-lg-end">
                                <div class="small text-muted">Active Work</div>
                                <div class="fw-semibold text-dark">
                                    {{ number_format($trelloAcademicActiveWork) }} card berjalan
                                </div>
                            </div>
                        </div>

                        <div class="progress progress-modern work-progress-completion-track mb-3">
                            <div
                                class="progress-bar {{ $trelloAcademicProgressClass }}"
                                role="progressbar"
                                style="width: {{ $trelloAcademicCompletionRate }}%;"
                                aria-valuenow="{{ $trelloAcademicCompletionRate }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-xl-4 col-md-4">
                                <div class="work-progress-mini-metric">
                                    <span>Due Today</span>
                                    <strong class="{{ $trelloAcademicDueTodayClass }}">{{ number_format($trelloAcademicDueToday) }}</strong>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="work-progress-mini-metric">
                                    <span>Overdue</span>
                                    <strong class="{{ $trelloAcademicOverdueClass }}">{{ number_format($trelloAcademicOverdue) }}</strong>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="work-progress-mini-metric">
                                    <span>Unmapped</span>
                                    <strong>{{ number_format($trelloAcademicUnmapped) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        @foreach(['todo', 'in_progress', 'review', 'done'] as $statusKey)
                            @php
                                $statusTotal = (int) ($trelloAcademicStatuses[$statusKey] ?? 0);
                                $statusLabel = $trelloStatusLabels[$statusKey] ?? $statusKey;
                                $statusClass = $trelloStatusBadgeClasses[$statusKey] ?? 'bg-light text-muted';
                                $statusIcon = $trelloStatusIcons[$statusKey] ?? 'bi-circle';
                                $statusDescription = match ($statusKey) {
                                    'todo' => 'Task yang sudah masuk antrean kerja dan menunggu eksekusi.',
                                    'in_progress' => 'Task yang sedang dikerjakan oleh tim Academic.',
                                    'review' => 'Task yang sudah dikerjakan dan menunggu pengecekan.',
                                    'done' => 'Task yang sudah selesai dan tercatat sebagai completed.',
                                    default => 'Status pekerjaan Academic.',
                                };
                            @endphp
                            <div class="col-xl-3 col-md-6">
                                <div class="stat-card h-100 work-progress-stat-card">
                                    <div class="stat-card-top">
                                        <div class="stat-icon-wrap {{ $statusClass }}">
                                            <i class="bi {{ $statusIcon }}"></i>
                                        </div>
                                        <div>
                                            <div class="stat-title">{{ $statusLabel }}</div>
                                            <div class="stat-value">{{ number_format($statusTotal) }}</div>
                                        </div>
                                    </div>
                                    <div class="stat-description">
                                        {{ $statusDescription }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($trelloAcademicUnmapped > 0)
                        <div class="alert alert-warning mb-4">
                            Ada {{ number_format($trelloAcademicUnmapped) }} card yang belum punya status dashboard. Jalankan mapping list sebelum angka dipakai untuk keputusan operasional.
                        </div>
                    @endif

                    <div class="row g-3 trello-table-row">
                        <div class="col-12 d-flex flex-column trello-table-column">
                            <div class="trello-table-card flex-fill">
                                <div class="trello-table-header">
                                    <div>
                                        <div class="fw-semibold text-dark">Priority Cards</div>
                                        <div class="small text-muted">Card dengan deadline hari ini atau sudah melewati deadline.</div>
                                    </div>

                                    <span class="badge rounded-pill bg-danger-subtle text-danger">
                                        {{ number_format($trelloAcademicPriorityCards->count()) }} card
                                    </span>
                                </div>

                                @if($trelloAcademicPriorityCards->count())
                                    <div class="table-responsive trello-table-scroll">
                                        <table class="table table-modern align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Card</th>
                                                    <th>PIC</th>
                                                    <th>Status</th>
                                                    <th>Due</th>
                                                    <th class="text-end">Link</th>
                                                </tr>
                                            </thead>
                                            <tbody class="trello-load-more-list auto-expand-list is-collapsed" data-initial-visible="4">
                                                @foreach($trelloAcademicPriorityCards as $card)
                                                    @php
                                                        $cardStatus = $card['normalized_status'] ?? '-';
                                                        $cardDueAt = $card['due_at'] ?? null;
                                                        $cardDueText = $cardDueAt ? \Carbon\Carbon::parse($cardDueAt)->format('d M H:i') : '-';
                                                        $cardUrl = $card['short_url'] ?? $card['url'] ?? null;
                                                        $cardMembers = collect($card['members'] ?? []);
                                                        $cardMemberNames = $cardMembers
                                                            ->pluck('name')
                                                            ->filter()
                                                            ->implode(', ');
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($card['name'] ?? '-', 48) }}</div>
                                                            <div class="small text-muted">{{ $card['list_name'] ?? '-' }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="work-card-pic">
                                                                <div class="work-card-avatar-stack">
                                                                    @forelse($cardMembers->take(3) as $member)
                                                                        <div class="work-card-avatar" title="{{ $member['name'] ?? 'PIC' }}">
                                                                            @if(!empty($member['avatar_url']))
                                                                                <img
                                                                                    src="{{ $member['avatar_url'] }}"
                                                                                    alt="{{ $member['name'] ?? 'PIC' }}"
                                                                                    loading="lazy"
                                                                                    referrerpolicy="no-referrer"
                                                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                                                >
                                                                                <span class="work-card-avatar-fallback">{{ $member['initials'] ?? '?' }}</span>
                                                                            @else
                                                                                <span>{{ $member['initials'] ?? '?' }}</span>
                                                                            @endif
                                                                        </div>
                                                                    @empty
                                                                        <div class="work-card-avatar is-empty" title="No PIC">
                                                                            <span>?</span>
                                                                        </div>
                                                                    @endforelse

                                                                    @if($cardMembers->count() > 3)
                                                                        <div class="work-card-avatar is-more" title="{{ $cardMembers->count() - 3 }} PIC lainnya">
                                                                            <span>+{{ $cardMembers->count() - 3 }}</span>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="work-card-pic-name">
                                                                    {{ $cardMemberNames ? \Illuminate\Support\Str::limit($cardMemberNames, 24) : 'No PIC' }}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill {{ $trelloStatusBadgeClasses[$cardStatus] ?? 'bg-light text-muted' }}">
                                                                {{ $trelloStatusLabels[$cardStatus] ?? $cardStatus }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $cardDueText }}</td>
                                                        <td class="text-end">
                                                            @if($cardUrl)
                                                                <a href="{{ $cardUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">
                                                                    Open
                                                                </a>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="empty-state-box my-0">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-check2-circle"></i>
                                        </div>
                                        <h5 class="empty-state-title">Tidak ada priority card</h5>
                                        <p class="empty-state-text mb-0">
                                            Belum ada card Academic dengan deadline hari ini atau overdue.
                                        </p>
                                    </div>
                                @endif
                            </div>
                            @if($trelloAcademicPriorityCards->count() > 4)
                                <div class="auto-expand-trigger trello-auto-expand-trigger" data-auto-expand-key="trello-academic-priority" aria-hidden="true"></div>
                            @endif

                        </div>

                        <div class="col-12 d-flex flex-column trello-table-column">
                            <div class="trello-table-card flex-fill">
                                <div class="trello-table-header">
                                    <div>
                                        <div class="fw-semibold text-dark">Active Work Queue</div>
                                        <div class="small text-muted">Card aktif yang berada di To Do, Doing, Review, atau Scheduled.</div>
                                    </div>

                                    <span class="badge rounded-pill bg-primary-subtle text-primary">
                                        {{ number_format($trelloAcademicActiveCards->count()) }} card
                                    </span>
                                </div>

                                @if($trelloAcademicActiveCards->count())
                                    <div class="table-responsive trello-table-scroll">
                                        <table class="table table-modern align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Card</th>
                                                    <th>PIC</th>
                                                    <th>Status</th>
                                                    <th>Last Activity</th>
                                                    <th class="text-end">Link</th>
                                                </tr>
                                            </thead>
                                            <tbody class="trello-load-more-list auto-expand-list is-collapsed" data-initial-visible="4">
                                                @foreach($trelloAcademicActiveCards as $card)
                                                    @php
                                                        $cardStatus = $card['normalized_status'] ?? '-';
                                                        $cardLastActivity = $card['last_activity_at'] ?? null;
                                                        $cardLastActivityText = $cardLastActivity ? \Carbon\Carbon::parse($cardLastActivity)->format('d M H:i') : '-';
                                                        $cardUrl = $card['short_url'] ?? $card['url'] ?? null;
                                                        $cardMembers = collect($card['members'] ?? []);
                                                        $cardMemberNames = $cardMembers
                                                            ->pluck('name')
                                                            ->filter()
                                                            ->implode(', ');
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($card['name'] ?? '-', 48) }}</div>
                                                            <div class="small text-muted">{{ $card['list_name'] ?? '-' }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="work-card-pic">
                                                                <div class="work-card-avatar-stack">
                                                                    @forelse($cardMembers->take(3) as $member)
                                                                        <div class="work-card-avatar" title="{{ $member['name'] ?? 'PIC' }}">
                                                                            @if(!empty($member['avatar_url']))
                                                                                <img
                                                                                    src="{{ $member['avatar_url'] }}"
                                                                                    alt="{{ $member['name'] ?? 'PIC' }}"
                                                                                    loading="lazy"
                                                                                    referrerpolicy="no-referrer"
                                                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                                                >
                                                                                <span class="work-card-avatar-fallback">{{ $member['initials'] ?? '?' }}</span>
                                                                            @else
                                                                                <span>{{ $member['initials'] ?? '?' }}</span>
                                                                            @endif
                                                                        </div>
                                                                    @empty
                                                                        <div class="work-card-avatar is-empty" title="No PIC">
                                                                            <span>?</span>
                                                                        </div>
                                                                    @endforelse

                                                                    @if($cardMembers->count() > 3)
                                                                        <div class="work-card-avatar is-more" title="{{ $cardMembers->count() - 3 }} PIC lainnya">
                                                                            <span>+{{ $cardMembers->count() - 3 }}</span>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="work-card-pic-name">
                                                                    {{ $cardMemberNames ? \Illuminate\Support\Str::limit($cardMemberNames, 24) : 'No PIC' }}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill {{ $trelloStatusBadgeClasses[$cardStatus] ?? 'bg-light text-muted' }}">
                                                                {{ $trelloStatusLabels[$cardStatus] ?? $cardStatus }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $cardLastActivityText }}</td>
                                                        <td class="text-end">
                                                            @if($cardUrl)
                                                                <a href="{{ $cardUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">
                                                                    Open
                                                                </a>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="empty-state-box my-0">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-kanban"></i>
                                        </div>
                                        <h5 class="empty-state-title">Tidak ada active work</h5>
                                        <p class="empty-state-text mb-0">
                                            Belum ada card Academic di status To Do, Doing, Review, atau Scheduled.
                                        </p>
                                    </div>
                                @endif
                            </div>
                            @if($trelloAcademicActiveCards->count() > 4)
                                <div class="auto-expand-trigger trello-auto-expand-trigger" data-auto-expand-key="trello-academic-active" aria-hidden="true"></div>
                            @endif

                        </div>
                    </div>
                </div>

                <div
                    class="tab-pane fade"
                    id="marketing-work-pane"
                    role="tabpanel"
                    aria-labelledby="marketing-work-tab"
                    tabindex="0"
                >
                    <div class="trello-insight-box mb-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="trello-insight-icon">
                                <i class="bi bi-megaphone-fill"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark mb-1">Marketing Work Insight</div>
                                <p class="text-muted mb-0">{{ $trelloMarketingInsight }}</p>
                                <div class="small text-muted mt-2">
                                    Last sync: <strong>{{ $trelloMarketingLastSyncedText }}</strong>
                                    <span class="mx-1">•</span>
                                    Last webhook: <strong>{{ $trelloMarketingLastWebhookText }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="work-progress-completion-card mb-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
                            <div>
                                <div class="work-progress-completion-eyebrow">Marketing Progress</div>
                                <div class="work-progress-completion-value">{{ number_format($trelloMarketingCompletionRate) }}%</div>
                                <div class="work-progress-completion-label">
                                    {{ number_format($trelloMarketingCompleted) }} dari {{ number_format($trelloMarketingTotalOpenCards) }} card sudah selesai.
                                </div>
                            </div>

                            <div class="work-progress-completion-meta text-lg-end">
                                <div class="small text-muted">Active Work</div>
                                <div class="fw-semibold text-dark">
                                    {{ number_format($trelloMarketingActiveWork) }} card berjalan
                                </div>
                            </div>
                        </div>

                        <div class="progress progress-modern work-progress-completion-track mb-3">
                            <div
                                class="progress-bar {{ $trelloMarketingProgressClass }}"
                                role="progressbar"
                                style="width: {{ $trelloMarketingCompletionRate }}%;"
                                aria-valuenow="{{ $trelloMarketingCompletionRate }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-xl-4 col-md-4">
                                <div class="work-progress-mini-metric">
                                    <span>Due Today</span>
                                    <strong class="{{ $trelloMarketingDueTodayClass }}">{{ number_format($trelloMarketingDueToday) }}</strong>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="work-progress-mini-metric">
                                    <span>Overdue</span>
                                    <strong class="{{ $trelloMarketingOverdueClass }}">{{ number_format($trelloMarketingOverdue) }}</strong>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="work-progress-mini-metric">
                                    <span>Unmapped</span>
                                    <strong>{{ number_format($trelloMarketingUnmapped) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        @foreach(['todo', 'in_progress', 'review', 'done'] as $statusKey)
                            @php
                                $statusTotal = (int) ($trelloMarketingStatuses[$statusKey] ?? 0);
                                $statusLabel = $trelloStatusLabels[$statusKey] ?? $statusKey;
                                $statusClass = $trelloStatusBadgeClasses[$statusKey] ?? 'bg-light text-muted';
                                $statusIcon = $trelloStatusIcons[$statusKey] ?? 'bi-circle';
                                $statusDescription = match ($statusKey) {
                                    'todo' => 'Task yang sudah masuk antrean kerja dan menunggu eksekusi.',
                                    'in_progress' => 'Task yang sedang dikerjakan oleh tim Marketing.',
                                    'review' => 'Task yang sudah dikerjakan dan menunggu pengecekan.',
                                    'done' => 'Task yang sudah selesai dan tercatat sebagai completed.',
                                    default => 'Status pekerjaan Marketing.',
                                };
                            @endphp
                            <div class="col-xl-3 col-md-6">
                                <div class="stat-card h-100 work-progress-stat-card">
                                    <div class="stat-card-top">
                                        <div class="stat-icon-wrap {{ $statusClass }}">
                                            <i class="bi {{ $statusIcon }}"></i>
                                        </div>
                                        <div>
                                            <div class="stat-title">{{ $statusLabel }}</div>
                                            <div class="stat-value">{{ number_format($statusTotal) }}</div>
                                        </div>
                                    </div>
                                    <div class="stat-description">
                                        {{ $statusDescription }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($trelloMarketingUnmapped > 0)
                        <div class="alert alert-warning mb-4">
                            Ada {{ number_format($trelloMarketingUnmapped) }} card yang belum punya status dashboard. Jalankan mapping list sebelum angka dipakai untuk keputusan operasional.
                        </div>
                    @endif

                    <div class="row g-3 trello-table-row">
                        <div class="col-12 d-flex flex-column trello-table-column">
                            <div class="trello-table-card flex-fill">
                                <div class="trello-table-header">
                                    <div>
                                        <div class="fw-semibold text-dark">Priority Cards</div>
                                        <div class="small text-muted">Card dengan deadline hari ini atau sudah melewati deadline.</div>
                                    </div>

                                    <span class="badge rounded-pill bg-danger-subtle text-danger">
                                        {{ number_format($trelloMarketingPriorityCards->count()) }} card
                                    </span>
                                </div>

                                @if($trelloMarketingPriorityCards->count())
                                    <div class="table-responsive trello-table-scroll">
                                        <table class="table table-modern align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Card</th>
                                                    <th>PIC</th>
                                                    <th>Status</th>
                                                    <th>Due</th>
                                                    <th class="text-end">Link</th>
                                                </tr>
                                            </thead>
                                            <tbody class="trello-load-more-list auto-expand-list is-collapsed" data-initial-visible="4">
                                                @foreach($trelloMarketingPriorityCards as $card)
                                                    @php
                                                        $cardStatus = $card['normalized_status'] ?? '-';
                                                        $cardDueAt = $card['due_at'] ?? null;
                                                        $cardDueText = $cardDueAt ? \Carbon\Carbon::parse($cardDueAt)->format('d M H:i') : '-';
                                                        $cardUrl = $card['short_url'] ?? $card['url'] ?? null;
                                                        $cardMembers = collect($card['members'] ?? []);
                                                        $cardMemberNames = $cardMembers
                                                            ->pluck('name')
                                                            ->filter()
                                                            ->implode(', ');
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($card['name'] ?? '-', 48) }}</div>
                                                            <div class="small text-muted">{{ $card['list_name'] ?? '-' }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="work-card-pic">
                                                                <div class="work-card-avatar-stack">
                                                                    @forelse($cardMembers->take(3) as $member)
                                                                        <div class="work-card-avatar" title="{{ $member['name'] ?? 'PIC' }}">
                                                                            @if(!empty($member['avatar_url']))
                                                                                <img
                                                                                    src="{{ $member['avatar_url'] }}"
                                                                                    alt="{{ $member['name'] ?? 'PIC' }}"
                                                                                    loading="lazy"
                                                                                    referrerpolicy="no-referrer"
                                                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                                                >
                                                                                <span class="work-card-avatar-fallback">{{ $member['initials'] ?? '?' }}</span>
                                                                            @else
                                                                                <span>{{ $member['initials'] ?? '?' }}</span>
                                                                            @endif
                                                                        </div>
                                                                    @empty
                                                                        <div class="work-card-avatar is-empty" title="No PIC">
                                                                            <span>?</span>
                                                                        </div>
                                                                    @endforelse

                                                                    @if($cardMembers->count() > 3)
                                                                        <div class="work-card-avatar is-more" title="{{ $cardMembers->count() - 3 }} PIC lainnya">
                                                                            <span>+{{ $cardMembers->count() - 3 }}</span>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="work-card-pic-name">
                                                                    {{ $cardMemberNames ? \Illuminate\Support\Str::limit($cardMemberNames, 24) : 'No PIC' }}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill {{ $trelloStatusBadgeClasses[$cardStatus] ?? 'bg-light text-muted' }}">
                                                                {{ $trelloStatusLabels[$cardStatus] ?? $cardStatus }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $cardDueText }}</td>
                                                        <td class="text-end">
                                                            @if($cardUrl)
                                                                <a href="{{ $cardUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">
                                                                    Open
                                                                </a>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="empty-state-box my-0">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-check2-circle"></i>
                                        </div>
                                        <h5 class="empty-state-title">Tidak ada priority card</h5>
                                        <p class="empty-state-text mb-0">
                                            Belum ada card Marketing dengan deadline hari ini atau overdue.
                                        </p>
                                    </div>
                                @endif
                            </div>
                            @if($trelloMarketingPriorityCards->count() > 4)
                                <div class="auto-expand-trigger trello-auto-expand-trigger" data-auto-expand-key="trello-marketing-priority" aria-hidden="true"></div>
                            @endif

                        </div>

                        <div class="col-12 d-flex flex-column trello-table-column">
                            <div class="trello-table-card flex-fill">
                                <div class="trello-table-header">
                                    <div>
                                        <div class="fw-semibold text-dark">Active Work Queue</div>
                                        <div class="small text-muted">Card aktif yang berada di To Do, Doing, Review, atau Scheduled.</div>
                                    </div>

                                    <span class="badge rounded-pill bg-primary-subtle text-primary">
                                        {{ number_format($trelloMarketingActiveCards->count()) }} card
                                    </span>
                                </div>

                                @if($trelloMarketingActiveCards->count())
                                    <div class="table-responsive trello-table-scroll">
                                        <table class="table table-modern align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Card</th>
                                                    <th>PIC</th>
                                                    <th>Status</th>
                                                    <th>Last Activity</th>
                                                    <th class="text-end">Link</th>
                                                </tr>
                                            </thead>
                                            <tbody class="trello-load-more-list auto-expand-list is-collapsed" data-initial-visible="4">
                                                @foreach($trelloMarketingActiveCards as $card)
                                                    @php
                                                        $cardStatus = $card['normalized_status'] ?? '-';
                                                        $cardLastActivity = $card['last_activity_at'] ?? null;
                                                        $cardLastActivityText = $cardLastActivity ? \Carbon\Carbon::parse($cardLastActivity)->format('d M H:i') : '-';
                                                        $cardUrl = $card['short_url'] ?? $card['url'] ?? null;
                                                        $cardMembers = collect($card['members'] ?? []);
                                                        $cardMemberNames = $cardMembers
                                                            ->pluck('name')
                                                            ->filter()
                                                            ->implode(', ');
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($card['name'] ?? '-', 48) }}</div>
                                                            <div class="small text-muted">{{ $card['list_name'] ?? '-' }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="work-card-pic">
                                                                <div class="work-card-avatar-stack">
                                                                    @forelse($cardMembers->take(3) as $member)
                                                                        <div class="work-card-avatar" title="{{ $member['name'] ?? 'PIC' }}">
                                                                            @if(!empty($member['avatar_url']))
                                                                                <img
                                                                                    src="{{ $member['avatar_url'] }}"
                                                                                    alt="{{ $member['name'] ?? 'PIC' }}"
                                                                                    loading="lazy"
                                                                                    referrerpolicy="no-referrer"
                                                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                                                >
                                                                                <span class="work-card-avatar-fallback">{{ $member['initials'] ?? '?' }}</span>
                                                                            @else
                                                                                <span>{{ $member['initials'] ?? '?' }}</span>
                                                                            @endif
                                                                        </div>
                                                                    @empty
                                                                        <div class="work-card-avatar is-empty" title="No PIC">
                                                                            <span>?</span>
                                                                        </div>
                                                                    @endforelse

                                                                    @if($cardMembers->count() > 3)
                                                                        <div class="work-card-avatar is-more" title="{{ $cardMembers->count() - 3 }} PIC lainnya">
                                                                            <span>+{{ $cardMembers->count() - 3 }}</span>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="work-card-pic-name">
                                                                    {{ $cardMemberNames ? \Illuminate\Support\Str::limit($cardMemberNames, 24) : 'No PIC' }}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill {{ $trelloStatusBadgeClasses[$cardStatus] ?? 'bg-light text-muted' }}">
                                                                {{ $trelloStatusLabels[$cardStatus] ?? $cardStatus }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $cardLastActivityText }}</td>
                                                        <td class="text-end">
                                                            @if($cardUrl)
                                                                <a href="{{ $cardUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">
                                                                    Open
                                                                </a>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="empty-state-box my-0">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-kanban"></i>
                                        </div>
                                        <h5 class="empty-state-title">Tidak ada active work</h5>
                                        <p class="empty-state-text mb-0">
                                            Belum ada card Marketing di status To Do, Doing, Review, atau Scheduled.
                                        </p>
                                    </div>
                                @endif
                            </div>
                            @if($trelloMarketingActiveCards->count() > 4)
                                <div class="auto-expand-trigger trello-auto-expand-trigger" data-auto-expand-key="trello-marketing-active" aria-hidden="true"></div>
                            @endif

                        </div>
                    </div>
                </div>
                <div
                    class="tab-pane fade"
                    id="sei-work-pane"
                    role="tabpanel"
                    aria-labelledby="sei-work-tab"
                    tabindex="0"
                >
                    <div class="trello-insight-box mb-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="trello-insight-icon">
                                <i class="bi bi-megaphone-fill"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark mb-1">SEI Work Insight</div>
                                <p class="text-muted mb-0">{{ $trelloSeiInsight }}</p>
                                <div class="small text-muted mt-2">
                                    Last sync: <strong>{{ $trelloSeiLastSyncedText }}</strong>
                                    <span class="mx-1">•</span>
                                    Last webhook: <strong>{{ $trelloSeiLastWebhookText }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="work-progress-completion-card mb-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
                            <div>
                                <div class="work-progress-completion-eyebrow">SEI Progress</div>
                                <div class="work-progress-completion-value">{{ number_format($trelloSeiCompletionRate) }}%</div>
                                <div class="work-progress-completion-label">
                                    {{ number_format($trelloSeiCompleted) }} dari {{ number_format($trelloSeiTotalOpenCards) }} card sudah selesai.
                                </div>
                            </div>

                            <div class="work-progress-completion-meta text-lg-end">
                                <div class="small text-muted">Active Work</div>
                                <div class="fw-semibold text-dark">
                                    {{ number_format($trelloSeiActiveWork) }} card berjalan
                                </div>
                            </div>
                        </div>

                        <div class="progress progress-modern work-progress-completion-track mb-3">
                            <div
                                class="progress-bar {{ $trelloSeiProgressClass }}"
                                role="progressbar"
                                style="width: {{ $trelloSeiCompletionRate }}%;"
                                aria-valuenow="{{ $trelloSeiCompletionRate }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-xl-4 col-md-4">
                                <div class="work-progress-mini-metric">
                                    <span>Due Today</span>
                                    <strong class="{{ $trelloSeiDueTodayClass }}">{{ number_format($trelloSeiDueToday) }}</strong>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="work-progress-mini-metric">
                                    <span>Overdue</span>
                                    <strong class="{{ $trelloSeiOverdueClass }}">{{ number_format($trelloSeiOverdue) }}</strong>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-4">
                                <div class="work-progress-mini-metric">
                                    <span>Unmapped</span>
                                    <strong>{{ number_format($trelloSeiUnmapped) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        @foreach(['todo', 'in_progress', 'review', 'done'] as $statusKey)
                            @php
                                $statusTotal = (int) ($trelloSeiStatuses[$statusKey] ?? 0);
                                $statusLabel = $trelloStatusLabels[$statusKey] ?? $statusKey;
                                $statusClass = $trelloStatusBadgeClasses[$statusKey] ?? 'bg-light text-muted';
                                $statusIcon = $trelloStatusIcons[$statusKey] ?? 'bi-circle';
                                $statusDescription = match ($statusKey) {
                                    'todo' => 'Task yang sudah masuk antrean kerja dan menunggu eksekusi.',
                                    'in_progress' => 'Task yang sedang dikerjakan oleh tim SEI.',
                                    'review' => 'Task yang sudah dikerjakan dan menunggu pengecekan.',
                                    'done' => 'Task yang sudah selesai dan tercatat sebagai completed.',
                                    default => 'Status pekerjaan SEI.',
                                };
                            @endphp
                            <div class="col-xl-3 col-md-6">
                                <div class="stat-card h-100 work-progress-stat-card">
                                    <div class="stat-card-top">
                                        <div class="stat-icon-wrap {{ $statusClass }}">
                                            <i class="bi {{ $statusIcon }}"></i>
                                        </div>
                                        <div>
                                            <div class="stat-title">{{ $statusLabel }}</div>
                                            <div class="stat-value">{{ number_format($statusTotal) }}</div>
                                        </div>
                                    </div>
                                    <div class="stat-description">
                                        {{ $statusDescription }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($trelloSeiUnmapped > 0)
                        <div class="alert alert-warning mb-4">
                            Ada {{ number_format($trelloSeiUnmapped) }} card yang belum punya status dashboard. Jalankan mapping list sebelum angka dipakai untuk keputusan operasional.
                        </div>
                    @endif

                    <div class="row g-3 trello-table-row">
                        <div class="col-12 d-flex flex-column trello-table-column">
                            <div class="trello-table-card flex-fill">
                                <div class="trello-table-header">
                                    <div>
                                        <div class="fw-semibold text-dark">Priority Cards</div>
                                        <div class="small text-muted">Card dengan deadline hari ini atau sudah melewati deadline.</div>
                                    </div>

                                    <span class="badge rounded-pill bg-danger-subtle text-danger">
                                        {{ number_format($trelloSeiPriorityCards->count()) }} card
                                    </span>
                                </div>

                                @if($trelloSeiPriorityCards->count())
                                    <div class="table-responsive trello-table-scroll">
                                        <table class="table table-modern align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Card</th>
                                                    <th>PIC</th>
                                                    <th>Status</th>
                                                    <th>Due</th>
                                                    <th class="text-end">Link</th>
                                                </tr>
                                            </thead>
                                            <tbody class="trello-load-more-list auto-expand-list is-collapsed" data-initial-visible="4">
                                                @foreach($trelloSeiPriorityCards as $card)
                                                    @php
                                                        $cardStatus = $card['normalized_status'] ?? '-';
                                                        $cardDueAt = $card['due_at'] ?? null;
                                                        $cardDueText = $cardDueAt ? \Carbon\Carbon::parse($cardDueAt)->format('d M H:i') : '-';
                                                        $cardUrl = $card['short_url'] ?? $card['url'] ?? null;
                                                        $cardMembers = collect($card['members'] ?? []);
                                                        $cardMemberNames = $cardMembers
                                                            ->pluck('name')
                                                            ->filter()
                                                            ->implode(', ');
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($card['name'] ?? '-', 48) }}</div>
                                                            <div class="small text-muted">{{ $card['list_name'] ?? '-' }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="work-card-pic">
                                                                <div class="work-card-avatar-stack">
                                                                    @forelse($cardMembers->take(3) as $member)
                                                                        <div class="work-card-avatar" title="{{ $member['name'] ?? 'PIC' }}">
                                                                            @if(!empty($member['avatar_url']))
                                                                                <img
                                                                                    src="{{ $member['avatar_url'] }}"
                                                                                    alt="{{ $member['name'] ?? 'PIC' }}"
                                                                                    loading="lazy"
                                                                                    referrerpolicy="no-referrer"
                                                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                                                >
                                                                                <span class="work-card-avatar-fallback">{{ $member['initials'] ?? '?' }}</span>
                                                                            @else
                                                                                <span>{{ $member['initials'] ?? '?' }}</span>
                                                                            @endif
                                                                        </div>
                                                                    @empty
                                                                        <div class="work-card-avatar is-empty" title="No PIC">
                                                                            <span>?</span>
                                                                        </div>
                                                                    @endforelse

                                                                    @if($cardMembers->count() > 3)
                                                                        <div class="work-card-avatar is-more" title="{{ $cardMembers->count() - 3 }} PIC lainnya">
                                                                            <span>+{{ $cardMembers->count() - 3 }}</span>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="work-card-pic-name">
                                                                    {{ $cardMemberNames ? \Illuminate\Support\Str::limit($cardMemberNames, 24) : 'No PIC' }}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill {{ $trelloStatusBadgeClasses[$cardStatus] ?? 'bg-light text-muted' }}">
                                                                {{ $trelloStatusLabels[$cardStatus] ?? $cardStatus }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $cardDueText }}</td>
                                                        <td class="text-end">
                                                            @if($cardUrl)
                                                                <a href="{{ $cardUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">
                                                                    Open
                                                                </a>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="empty-state-box my-0">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-check2-circle"></i>
                                        </div>
                                        <h5 class="empty-state-title">Tidak ada priority card</h5>
                                        <p class="empty-state-text mb-0">
                                            Belum ada card SEI dengan deadline hari ini atau overdue.
                                        </p>
                                    </div>
                                @endif
                            </div>
                            @if($trelloSeiPriorityCards->count() > 4)
                                <div class="auto-expand-trigger trello-auto-expand-trigger" data-auto-expand-key="trello-sei-priority" aria-hidden="true"></div>
                            @endif

                        </div>

                        <div class="col-12 d-flex flex-column trello-table-column">
                            <div class="trello-table-card flex-fill">
                                <div class="trello-table-header">
                                    <div>
                                        <div class="fw-semibold text-dark">Active Work Queue</div>
                                        <div class="small text-muted">Card aktif yang berada di To Do, Doing, Review, atau Scheduled.</div>
                                    </div>

                                    <span class="badge rounded-pill bg-primary-subtle text-primary">
                                        {{ number_format($trelloSeiActiveCards->count()) }} card
                                    </span>
                                </div>

                                @if($trelloSeiActiveCards->count())
                                    <div class="table-responsive trello-table-scroll">
                                        <table class="table table-modern align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Card</th>
                                                    <th>PIC</th>
                                                    <th>Status</th>
                                                    <th>Last Activity</th>
                                                    <th class="text-end">Link</th>
                                                </tr>
                                            </thead>
                                            <tbody class="trello-load-more-list auto-expand-list is-collapsed" data-initial-visible="4">
                                                @foreach($trelloSeiActiveCards as $card)
                                                    @php
                                                        $cardStatus = $card['normalized_status'] ?? '-';
                                                        $cardLastActivity = $card['last_activity_at'] ?? null;
                                                        $cardLastActivityText = $cardLastActivity ? \Carbon\Carbon::parse($cardLastActivity)->format('d M H:i') : '-';
                                                        $cardUrl = $card['short_url'] ?? $card['url'] ?? null;
                                                        $cardMembers = collect($card['members'] ?? []);
                                                        $cardMemberNames = $cardMembers
                                                            ->pluck('name')
                                                            ->filter()
                                                            ->implode(', ');
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($card['name'] ?? '-', 48) }}</div>
                                                            <div class="small text-muted">{{ $card['list_name'] ?? '-' }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="work-card-pic">
                                                                <div class="work-card-avatar-stack">
                                                                    @forelse($cardMembers->take(3) as $member)
                                                                        <div class="work-card-avatar" title="{{ $member['name'] ?? 'PIC' }}">
                                                                            @if(!empty($member['avatar_url']))
                                                                                <img
                                                                                    src="{{ $member['avatar_url'] }}"
                                                                                    alt="{{ $member['name'] ?? 'PIC' }}"
                                                                                    loading="lazy"
                                                                                    referrerpolicy="no-referrer"
                                                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                                                >
                                                                                <span class="work-card-avatar-fallback">{{ $member['initials'] ?? '?' }}</span>
                                                                            @else
                                                                                <span>{{ $member['initials'] ?? '?' }}</span>
                                                                            @endif
                                                                        </div>
                                                                    @empty
                                                                        <div class="work-card-avatar is-empty" title="No PIC">
                                                                            <span>?</span>
                                                                        </div>
                                                                    @endforelse

                                                                    @if($cardMembers->count() > 3)
                                                                        <div class="work-card-avatar is-more" title="{{ $cardMembers->count() - 3 }} PIC lainnya">
                                                                            <span>+{{ $cardMembers->count() - 3 }}</span>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="work-card-pic-name">
                                                                    {{ $cardMemberNames ? \Illuminate\Support\Str::limit($cardMemberNames, 24) : 'No PIC' }}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill {{ $trelloStatusBadgeClasses[$cardStatus] ?? 'bg-light text-muted' }}">
                                                                {{ $trelloStatusLabels[$cardStatus] ?? $cardStatus }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $cardLastActivityText }}</td>
                                                        <td class="text-end">
                                                            @if($cardUrl)
                                                                <a href="{{ $cardUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">
                                                                    Open
                                                                </a>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="empty-state-box my-0">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-kanban"></i>
                                        </div>
                                        <h5 class="empty-state-title">Tidak ada active work</h5>
                                        <p class="empty-state-text mb-0">
                                            Belum ada card SEI di status To Do, Doing, Review, atau Scheduled.
                                        </p>
                                    </div>
                                @endif
                            </div>
                            @if($trelloSeiActiveCards->count() > 4)
                                <div class="auto-expand-trigger trello-auto-expand-trigger" data-auto-expand-key="trello-sei-active" aria-hidden="true"></div>
                            @endif

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Trial Overview</div>
        <h4 class="dashboard-section-title mb-1">Trial Performance This Month</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring performa trial bulan berjalan berdasarkan jadwal, peserta baru, progress follow-up, dan status kehadiran.
        </p>
    </div>

    {{-- Trial Stats This Month --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-brush"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active Trial Themes</div>
                        <div class="stat-value">{{ number_format($trialStats['themes_active'] ?? $trialStats['themes_total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Theme trial aktif yang tersedia di sistem.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Trial Schedules This Month</div>
                        <div class="stat-value">{{ number_format($trialStats['schedules_active_this_month'] ?? $trialStats['schedules_active'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Jadwal trial aktif untuk bulan berjalan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Trial Participants This Month</div>
                        <div class="stat-value">{{ number_format($trialStats['participants_this_month'] ?? $trialStats['participants_total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta trial yang masuk pada bulan berjalan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div>
                        <div class="stat-title">Trial Participants All Time</div>
                        <div class="stat-value">{{ number_format($trialStats['participants_all_time'] ?? $trialStats['participants_total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total peserta trial yang tercatat sejak awal.</div>
            </div>
        </div>
    </div>

    {{-- Trial Progress + Upcoming Trial Schedule --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Trial Follow Up Progress This Month</h5>
                        <p class="content-card-subtitle mb-0">
                            Persentase peserta trial bulan ini yang sudah masuk tahap contacted, confirmed, atau attended.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="trial-progress-card">
                        <div class="trial-progress-value">{{ $trialFollowUpProgress ?? 0 }}%</div>
                        <div class="trial-progress-label">Follow Up Progress Bulan Ini</div>

                        <div class="progress progress-modern mt-3 mb-4">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: {{ $trialFollowUpProgress ?? 0 }}%;"
                                aria-valuenow="{{ $trialFollowUpProgress ?? 0 }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>

                        <div class="trial-status-grid">
                            <div class="trial-status-item">
                                <span>Registered</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['registered'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Contacted</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['contacted'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Confirmed</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['confirmed'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Attended</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['attended'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Cancelled</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['cancelled'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>No Show</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['no_show'] ?? 0) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Upcoming Trial Schedules</h5>
                        <p class="content-card-subtitle mb-0">
                            Jadwal trial terdekat yang aktif di sistem.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @if(($upcomingTrialSchedules ?? collect())->count())
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Schedule</th>
                                        <th>Program</th>
                                        <th>Theme</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingTrialSchedules as $schedule)
                                        <tr>
                                            <td class="fw-semibold text-dark">{{ $schedule->name }}</td>
                                            <td>{{ $schedule->program->name ?? '-' }}</td>
                                            <td>{{ $schedule->trialTheme->name ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d M Y') }}</td>
                                            <td>
                                                {{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '-' }}
                                                @if(!empty($schedule->end_time))
                                                    - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <h5 class="empty-state-title">Belum ada trial schedule mendatang</h5>
                            <p class="empty-state-text mb-0">
                                Data jadwal trial aktif yang akan datang belum tersedia.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Workshop Overview</div>
        <h4 class="dashboard-section-title mb-1">Workshop Performance This Month</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring performa workshop berdasarkan jadwal, peserta, status pembayaran, attendance, dan revenue bulan berjalan.
        </p>
    </div>

    {{-- Workshop Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-easel2-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active Workshops</div>
                        <div class="stat-value">{{ number_format($workshopStats['workshops_active'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Dari total {{ number_format($workshopStats['workshops_total'] ?? 0) }} workshop yang terdaftar.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar2-week"></i>
                    </div>
                    <div>
                        <div class="stat-title">Schedules This Month</div>
                        <div class="stat-value">{{ number_format($workshopStats['schedules_active_this_month'] ?? $workshopStats['schedules_this_month'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Jadwal workshop aktif pada bulan berjalan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div>
                        <div class="stat-title">Participants This Month</div>
                        <div class="stat-value">{{ number_format($workshopStats['participants_this_month'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    @if(!empty($workshopStats['top_source']))
                        Top source: <strong>{{ $workshopStats['top_source'] }}</strong>
                        ({{ number_format($workshopStats['top_source_total'] ?? 0) }} peserta).
                    @else
                        Peserta workshop yang masuk pada bulan berjalan.
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="stat-title">Workshop Revenue</div>
                        <div class="stat-value stat-value-currency">Rp {{ number_format($workshopStats['revenue_this_month'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Dari {{ number_format($workshopStats['paid_count_this_month'] ?? 0) }} pembayaran workshop bulan ini.
                </div>
            </div>
        </div>
    </div>

    {{-- Workshop Progress + Upcoming Workshop Schedule --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Workshop Conversion Progress This Month</h5>
                        <p class="content-card-subtitle mb-0">
                            Persentase peserta workshop bulan ini yang sudah confirmed atau attended.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="trial-progress-card">
                        <div class="trial-progress-value">{{ $workshopFollowUpProgress ?? 0 }}%</div>
                        <div class="trial-progress-label">Conversion Progress Bulan Ini</div>

                        <div class="progress progress-modern mt-3 mb-4">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: {{ $workshopFollowUpProgress ?? 0 }}%;"
                                aria-valuenow="{{ $workshopFollowUpProgress ?? 0 }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>

                        <div class="trial-status-grid">
                            <div class="trial-status-item">
                                <span>Registered</span>
                                <strong>{{ number_format($workshopParticipantStatusCounts['registered'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Pending Payment</span>
                                <strong>{{ number_format($workshopParticipantStatusCounts['pending_payment'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Confirmed</span>
                                <strong>{{ number_format($workshopParticipantStatusCounts['confirmed'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Attended</span>
                                <strong>{{ number_format($workshopParticipantStatusCounts['attended'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Cancelled</span>
                                <strong>{{ number_format($workshopParticipantStatusCounts['cancelled'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Workshop Orders</span>
                                <strong>{{ number_format($orderInsight['workshop_orders_this_month'] ?? 0) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Upcoming Workshop Schedules</h5>
                        <p class="content-card-subtitle mb-0">
                            Jadwal workshop terdekat yang aktif di sistem.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @if(($upcomingWorkshopSchedules ?? collect())->count())
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Schedule</th>
                                        <th>Workshop</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th class="text-center">Seat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingWorkshopSchedules as $schedule)
                                        @php
                                            $workshopScheduleQuota = (int) ($schedule->quota ?? 0);
                                            $workshopScheduleRegistered = (int) ($schedule->registered_count ?? 0);
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold text-dark">{{ $schedule->title ?? 'Workshop Schedule' }}</td>
                                            <td>{{ $schedule->workshop_title ?? '-' }}</td>
                                            <td>{{ !empty($schedule->schedule_date) ? \Carbon\Carbon::parse($schedule->schedule_date)->format('d M Y') : '-' }}</td>
                                            <td>
                                                {{ !empty($schedule->start_time) ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '-' }}
                                                @if(!empty($schedule->end_time))
                                                    - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($workshopScheduleRegistered) }}
                                                @if($workshopScheduleQuota > 0)
                                                    / {{ number_format($workshopScheduleQuota) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <h5 class="empty-state-title">Belum ada workshop schedule mendatang</h5>
                            <p class="empty-state-text mb-0">
                                Data jadwal workshop aktif yang akan datang belum tersedia.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Finance Overview</div>
        <h4 class="dashboard-section-title mb-1">Revenue & Business Result</h4>
        <p class="dashboard-section-subtitle mb-0">Analisis hasil finansial dari aktivitas operasional.</p>
    </div>

    {{-- Monthly Revenue Chart --}}
    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Revenue Overview</h5>
                <p class="content-card-subtitle mb-0">
                    Total pendapatan pembayaran student selama tahun {{ $revenueChart['year'] ?? now()->year }}.
                </p>
            </div>

            <div class="revenue-total-box">
                <div class="revenue-total-label">Total Tahun Ini</div>
                <div class="revenue-total-value">Rp {{ number_format($revenueChart['total'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="content-card-body">
            <div class="chart-wrap">
                <canvas id="monthlyRevenueChart" height="110"></canvas>
            </div>
        </div>
    </div>

    {{-- Upcoming Batches --}}
    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Upcoming Batches</h5>
                <p class="content-card-subtitle mb-0">
                    Batch mendatang lengkap dengan nama program, kapasitas, seat terisi, dan sisa seat.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($upcomingBatches ?? collect())->count())
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Program / Batch</th>
                                <th>Start Date</th>
                                <th class="text-center">Capacity</th>
                                <th class="text-center">Filled</th>
                                <th class="text-center">Remaining</th>
                                <th width="220">Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingBatches as $batch)
                                @php
                                    $capacity = (int) ($batch->capacity ?? 0);
                                    $filled = (int) ($batch->filled_seats ?? 0);
                                    $remaining = (int) ($batch->remaining_seats ?? 0);
                                    $percent = $capacity > 0 ? min(100, round(($filled / $capacity) * 100)) : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $batch->name }}</div>
                                        <div class="small text-muted">{{ $batch->program_name ?? '-' }}</div>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($batch->start_date)->format('d M Y') }}</td>
                                    <td class="text-center">{{ number_format($capacity) }}</td>
                                    <td class="text-center">{{ number_format($filled) }}</td>
                                    <td class="text-center">{{ number_format($remaining) }}</td>
                                    <td>
                                        <div class="progress progress-modern">
                                            <div
                                                class="progress-bar"
                                                role="progressbar"
                                                style="width: {{ $percent }}%;"
                                                aria-valuenow="{{ $percent }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                            >
                                                {{ $percent }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada batch mendatang</h5>
                    <p class="empty-state-text mb-0">
                        Data batch yang akan datang belum tersedia atau tabel batch belum terisi.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<x-ai-insight-widget
    title="AI Dashboard Summary"
    :insight="$managementSummary ?? []"
    :summary="$dashboardAiSummaryText ?? null"
/>
@endsection

@push('styles')
<style>
    .kommo-insight-box {
        border: 1px solid rgba(91, 62, 142, 0.12);
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(91, 62, 142, 0.06), rgba(255, 190, 4, 0.08));
        padding: 16px;
    }

    .kommo-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: #5B3E8E;
        background: rgba(91, 62, 142, 0.12);
        font-size: 1.15rem;
    }
</style>
@endpush


@push('styles')
<style>
    .kommo-progress-row-card {
        background: linear-gradient(135deg, rgba(91, 62, 142, 0.08), rgba(255, 190, 4, 0.08));
        border: 1px solid rgba(91, 62, 142, 0.10);
        border-radius: 20px;
        padding: 1.25rem;
    }

    .kommo-progress-metric {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .kommo-progress-metric-left {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
    }

    .kommo-progress-metric-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        font-size: 1rem;
    }

    .kommo-progress-metric span {
        color: #6b7280;
        font-size: .85rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .kommo-progress-metric strong {
        color: #111827;
        font-size: 1.2rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .kommo-progress-metric .kommo-sync-value {
        font-size: .95rem;
        font-weight: 700;
    }

    .kommo-insight-box {
        background: rgba(91, 62, 142, 0.06);
        border: 1px solid rgba(91, 62, 142, 0.10);
        border-radius: 18px;
        padding: 1rem;
    }

    .kommo-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: rgba(91, 62, 142, 0.12);
        color: #5B3E8E;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.15rem;
    }

    .trello-insight-box {
        background: linear-gradient(135deg, rgba(0, 121, 191, 0.08), rgba(91, 62, 142, 0.06));
        border: 1px solid rgba(0, 121, 191, 0.12);
        border-radius: 18px;
        padding: 1rem;
    }

    .trello-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: rgba(0, 121, 191, 0.12);
        color: #0079BF;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.15rem;
    }

    .trello-progress-card {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 20px;
        padding: 1.25rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .trello-status-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .75rem;
    }

    .trello-status-item {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        padding: .9rem;
        background: rgba(248, 250, 252, 0.78);
        min-height: 108px;
        display: flex;
        flex-direction: column;
        gap: .55rem;
    }

    .trello-status-item-top {
        display: flex;
        align-items: center;
        gap: .65rem;
        min-width: 0;
    }

    .trello-status-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        font-size: .95rem;
    }

    .trello-status-item span {
        color: #6b7280;
        font-size: .82rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .trello-status-item strong {
        color: #111827;
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1;
    }

    .trello-status-item em {
        align-self: flex-start;
        font-style: normal;
        font-size: .68rem;
    }

    .trello-table-row {
        align-items: stretch;
    }

    .trello-table-row > [class*="col-"] {
        display: flex;
        align-items: stretch;
    }

    .trello-table-column {
        gap: .8rem;
    }

    .trello-table-column + .trello-table-column {
        margin-top: 1.35rem;
    }

    .trello-table-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 18px;
        background: #ffffff;
        overflow: hidden;
        width: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
    }

    .trello-table-header {
        padding: 1rem 1rem .85rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex: 0 0 auto;
        min-height: 78px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        background: linear-gradient(180deg, #ffffff 0%, rgba(248, 250, 252, 0.82) 100%);
    }

    .trello-table-header .badge {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .trello-table-scroll {
        flex: 0 0 auto;
        overflow-x: visible;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .trello-table-scroll table {
        width: 100%;
        min-width: 0;
        table-layout: fixed;
    }

    .trello-table-scroll th,
    .trello-table-scroll td {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .trello-table-scroll th:nth-child(1),
    .trello-table-scroll td:nth-child(1) {
        width: 38%;
    }

    .trello-table-scroll th:nth-child(2),
    .trello-table-scroll td:nth-child(2) {
        width: 20%;
    }

    .trello-table-scroll th:nth-child(3),
    .trello-table-scroll td:nth-child(3) {
        width: 16%;
    }

    .trello-table-scroll th:nth-child(4),
    .trello-table-scroll td:nth-child(4) {
        width: 16%;
    }

    .trello-table-scroll th:nth-child(5),
    .trello-table-scroll td:nth-child(5) {
        width: 10%;
    }

    .trello-table-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        box-shadow: inset 0 -1px 0 rgba(15, 23, 42, 0.08);
    }

    .trello-table-scroll tbody tr {
        transition: background-color .18s ease, transform .18s ease;
    }

    .trello-table-scroll tbody tr:hover {
        background: rgba(91, 62, 142, 0.035);
    }

    .trello-table-scroll tbody tr:last-child td {
        border-bottom: 0;
    }


    @media (max-width: 767.98px) {
        .trello-table-header {
            flex-direction: column;
            align-items: flex-start;
            min-height: auto;
        }

        .trello-table-scroll table {
            table-layout: auto;
        }

        .trello-table-scroll th,
        .trello-table-scroll td {
            font-size: .78rem;
        }

        .trello-table-scroll th:nth-child(2),
        .trello-table-scroll td:nth-child(2) {
            display: none;
        }

        .trello-table-scroll th:nth-child(1),
        .trello-table-scroll td:nth-child(1) {
            width: 44%;
        }

        .trello-table-scroll th:nth-child(3),
        .trello-table-scroll td:nth-child(3) {
            width: 22%;
        }

        .trello-table-scroll th:nth-child(4),
        .trello-table-scroll td:nth-child(4) {
            width: 22%;
        }

        .trello-table-scroll th:nth-child(5),
        .trello-table-scroll td:nth-child(5) {
            width: 12%;
        }
    }

    .auto-expand-list.is-collapsed tr:nth-child(n+5),
    .trello-load-more-list.is-collapsed tr:nth-child(n+5),
    .kommo-auto-expand-list.is-collapsed tr:nth-child(n+5) {
        display: none;
    }

    .auto-expand-list tr {
        transition: background-color .18s ease;
    }

    .auto-expand-trigger {
        width: 100%;
        height: 1px;
        pointer-events: none;
        opacity: 0;
    }

    .auto-expand-list:not(.is-collapsed) tr:nth-child(n+5) {
        animation: autoExpandFadeIn .22s ease both;
    }

    @keyframes autoExpandFadeIn {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .trello-table-card .empty-state-box {
        flex: 1 1 auto;
        min-height: 310px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .bg-purple-subtle {
        background-color: rgba(91, 62, 142, 0.12) !important;
    }

    .text-purple {
        color: #5B3E8E !important;
    }



    .work-progress-tabs {
        background: rgba(91, 62, 142, 0.06);
        border: 1px solid rgba(91, 62, 142, 0.10);
        border-radius: 999px;
        padding: .25rem;
        gap: .25rem;
    }

    .work-progress-tabs .nav-link {
        border-radius: 999px;
        color: #6b7280;
        font-size: .85rem;
        font-weight: 700;
        padding: .45rem .85rem;
    }

    .work-progress-tabs .nav-link.active {
        background: #5B3E8E;
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(91, 62, 142, 0.18);
    }

    .work-progress-stat-card .stat-icon-wrap {
        background: rgba(91, 62, 142, 0.10);
        color: #5B3E8E;
    }

    .work-progress-completion-card {
        border: 1px solid rgba(91, 62, 142, 0.12);
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(91, 62, 142, 0.07), rgba(255, 190, 4, 0.08));
        padding: 1.25rem;
    }

    .work-progress-completion-card.is-empty {
        background: linear-gradient(135deg, rgba(107, 114, 128, 0.06), rgba(91, 62, 142, 0.04));
        border-color: rgba(107, 114, 128, 0.12);
    }

    .work-progress-completion-eyebrow {
        color: #5B3E8E;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: .35rem;
    }

    .work-progress-completion-value {
        color: #111827;
        font-size: 2.25rem;
        font-weight: 900;
        line-height: 1;
    }

    .work-progress-completion-label {
        color: #6b7280;
        font-size: .92rem;
        font-weight: 600;
        margin-top: .35rem;
    }

    .work-progress-completion-meta {
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 16px;
        padding: .75rem 1rem;
    }

    .work-progress-completion-track {
        height: 10px;
        background: rgba(91, 62, 142, 0.10);
    }

    .work-progress-mini-metric {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .work-progress-mini-metric span {
        color: #6b7280;
        font-size: .85rem;
        font-weight: 700;
    }

    .work-progress-mini-metric strong {
        color: #111827;
        font-size: 1.2rem;
        font-weight: 900;
    }


    .work-card-pic {
        min-width: 112px;
    }

    .work-card-avatar-stack {
        display: flex;
        align-items: center;
        margin-bottom: .35rem;
        min-height: 30px;
    }

    .work-card-avatar {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(91, 62, 142, 0.12);
        color: #5B3E8E;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        border: 2px solid #ffffff;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.10);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .02em;
    }

    .work-card-avatar + .work-card-avatar {
        margin-left: -8px;
    }

    .work-card-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .work-card-avatar span {
        width: 100%;
        height: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .work-card-avatar img + .work-card-avatar-fallback {
        display: none;
    }

    .work-card-avatar.is-empty {
        background: rgba(107, 114, 128, 0.12);
        color: #6b7280;
    }

    .work-card-avatar.is-more {
        background: #111827;
        color: #ffffff;
        font-size: .65rem;
    }

    .work-card-pic-name {
        color: #6b7280;
        font-size: .76rem;
        font-weight: 700;
        line-height: 1.2;
        max-width: 132px;
        word-break: break-word;
    }

    .meta-ads-dashboard-card .content-card-body {
        padding-top: 1rem;
    }

    .meta-ads-insight-box {
        border: 1px solid rgba(91, 62, 142, 0.12);
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(91, 62, 142, 0.06), rgba(255, 190, 4, 0.08));
        padding: 16px;
    }

    .meta-ads-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: #5B3E8E;
        background: rgba(91, 62, 142, 0.12);
        font-size: 1.15rem;
    }

    .meta-ads-kpi-card,
    .meta-ads-detail-card {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 18px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
    }

    .meta-ads-kpi-card {
        min-height: 122px;
        padding: 1rem;
    }

    .meta-ads-detail-card {
        padding: 1rem;
    }

    .meta-ads-kpi-label {
        color: #64748b;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-bottom: .55rem;
    }

    .meta-ads-kpi-value {
        color: #0f172a;
        font-size: 1.35rem;
        font-weight: 950;
        line-height: 1.2;
    }

    .meta-ads-kpi-help {
        color: #64748b;
        font-size: .78rem;
        margin-top: .55rem;
        line-height: 1.35;
    }

    .meta-ads-mini-metric {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 14px;
        padding: .85rem;
        background: #f8fafc;
        display: grid;
        gap: .25rem;
    }

    .meta-ads-mini-metric span {
        color: #64748b;
        font-size: .74rem;
        font-weight: 800;
    }

    .meta-ads-mini-metric strong {
        color: #0f172a;
        font-size: .95rem;
    }

    .meta-ads-funnel-list {
        display: grid;
        gap: .65rem;
    }

    .meta-ads-funnel-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        background: #f8fafc;
        padding: .9rem;
    }

    .meta-ads-ai-summary,
    .meta-ads-bottleneck-box,
    .meta-ads-factor-box {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        background: #f8fafc;
        padding: .95rem;
    }

    .meta-ads-action-table {
        max-height: 280px;
        overflow: auto;
    }

    .meta-ads-action-table thead {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #ffffff;
    }


    .marketing-performance-tabs,
    .meta-ads-campaign-tabs {
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        flex-wrap: nowrap;
        scrollbar-width: thin;
    }

    .marketing-performance-tabs::-webkit-scrollbar,
    .meta-ads-campaign-tabs::-webkit-scrollbar {
        height: 5px;
    }

    .marketing-performance-tabs::-webkit-scrollbar-thumb,
    .meta-ads-campaign-tabs::-webkit-scrollbar-thumb {
        background: rgba(91, 62, 142, 0.24);
        border-radius: 999px;
    }

    .meta-ads-campaign-tabs {
        display: inline-flex;
        width: auto;
        max-width: 100%;
        margin-bottom: 1rem;
    }

    .marketing-performance-card .tab-pane {
        min-height: 180px;
    }

    .meta-ads-dashboard-card {
        width: 100%;
    }

    .meta-ads-dashboard-card > .content-card-header {
        padding: 0 0 1rem;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }

    .meta-ads-dashboard-card > .content-card-body {
        padding: 1rem 0 0;
    }

    .marketing-empty-panel {
        border: 1px dashed rgba(91, 62, 142, 0.22);
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(91, 62, 142, 0.04), rgba(255, 190, 4, 0.06));
        padding: 1rem;
    }

    .marketing-placeholder-grid {
        width: 100%;
        max-width: 780px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .75rem;
        margin: 0 auto;
    }

    .marketing-placeholder-item {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        background: #ffffff;
        padding: .9rem;
        display: grid;
        gap: .3rem;
        text-align: left;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .marketing-placeholder-item span {
        color: #6b7280;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .marketing-placeholder-item strong {
        color: #111827;
        font-size: 1.15rem;
        font-weight: 900;
    }


    @media (max-width: 1199.98px) {
        .trello-status-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .trello-status-grid,
        .marketing-placeholder-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart');
    const salesPerformanceCtx = document.getElementById('salesPerformanceChart');

    const expandList = function (list) {
        if (!list || !list.classList.contains('is-collapsed')) {
            return;
        }

        list.classList.remove('is-collapsed');
    };

    const expandSectionFromTrigger = function (trigger) {
        const body = trigger.closest('.content-card-body') || trigger.closest('.trello-table-column') || trigger.parentElement;
        let list = null;

        if (trigger.classList.contains('kommo-auto-expand-trigger')) {
            const cardBody = trigger.closest('.content-card-body');
            list = cardBody ? cardBody.querySelector('.kommo-auto-expand-list') : null;
        } else {
            const column = trigger.closest('.trello-table-column');
            list = column ? column.querySelector('.auto-expand-list') : null;
        }

        expandList(list);
    };

    if ('IntersectionObserver' in window) {
        const autoExpandObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                expandSectionFromTrigger(entry.target);
                observer.unobserve(entry.target);
            });
        }, {
            root: null,
            threshold: 0.01,
            rootMargin: '0px 0px -8% 0px'
        });

        document.querySelectorAll('.auto-expand-trigger').forEach(function (trigger) {
            autoExpandObserver.observe(trigger);
        });
    } else {
        document.querySelectorAll('.auto-expand-trigger').forEach(expandSectionFromTrigger);
    }

    if (monthlyRevenueCtx) {
        const labels = @json($revenueChart['labels'] ?? []);
        const values = @json($revenueChart['data'] ?? []);

        new Chart(monthlyRevenueCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: values,
                    borderRadius: 8,
                    maxBarThickness: 42,
                    backgroundColor: 'rgba(91, 62, 142, 0.82)',
                    borderColor: 'rgba(91, 62, 142, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = Number(context.raw || 0);
                                return ' Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#6b7280',
                            callback: function(value) {
                                return 'Rp ' + Number(value).toLocaleString('id-ID');
                            }
                        },
                        grid: {
                            color: 'rgba(107, 114, 128, 0.08)'
                        }
                    }
                }
            }
        });
    }

    if (salesPerformanceCtx) {
        try {
            const response = await fetch(@json(route('sales-performance.chart-data')), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load sales performance data.');
            }

            const result = await response.json();

            const summaryValue = document.getElementById('salesPerformanceClosedDealValue');
            if (summaryValue && result?.summary?.closed_deal !== undefined) {
                summaryValue.textContent = Number(result.summary.closed_deal || 0).toLocaleString('id-ID');
            }

            new Chart(salesPerformanceCtx, {
                type: 'line',
                data: {
                    labels: result.labels || [],
                    datasets: [
                        {
                            label: 'Total Leads',
                            data: result.datasets?.total_leads || [],
                            tension: 0.35,
                            borderWidth: 2
                        },
                        {
                            label: 'Interacted',
                            data: result.datasets?.interacted || [],
                            tension: 0.35,
                            borderWidth: 2
                        },
                        {
                            label: 'Consultation',
                            data: result.datasets?.consultation || [],
                            tension: 0.35,
                            borderWidth: 2
                        },
                        {
                            label: 'Hot Leads',
                            data: result.datasets?.hot_leads || [],
                            tension: 0.35,
                            borderWidth: 2
                        },
                        {
                            label: 'Closed Deal',
                            data: result.datasets?.closed_deal || [],
                            tension: 0.35,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                color: '#6b7280'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6b7280'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#6b7280'
                            },
                            grid: {
                                color: 'rgba(107, 114, 128, 0.08)'
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error(error);
        }
    }
});
</script>
@endpush