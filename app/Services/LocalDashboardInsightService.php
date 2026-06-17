<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class LocalDashboardInsightService
{
    public function generate(array $context): array
    {
        $context = $this->normalizeContext($context);

        $sales = $context['sales_insight'] ?? [];
        $finance = $context['finance_insight'] ?? [];
        $orders = $context['order_insight'] ?? [];
        $batch = $context['batch_capacity'] ?? [];
        $academic = $context['academic_stats'] ?? [];
        $revenueChart = $context['revenue_chart'] ?? [];

        $trialStats = $context['trial_stats'] ?? [];
        $trialStatus = $context['trial_status_counts'] ?? [];
        $trialProgress = $this->int($context['trial_follow_up_progress'] ?? 0);

        $workshopInsight = $context['workshop_insight'] ?? [];
        $workshopStats = array_merge($workshopInsight, $context['workshop_stats'] ?? []);
        $workshopStatus = $context['workshop_status_counts'] ?? [];
        $workshopProgress = $this->int($context['workshop_follow_up_progress'] ?? 0);

        $upcomingBatches = $this->countableCount($context['upcoming_batches'] ?? []);
        $upcomingWorkshopSchedules = $this->countableCount($context['upcoming_workshop_schedules'] ?? []);

        $items = [];

        $items = array_merge(
            $items,
            $this->buildFinanceItems($finance, $sales, $orders, $revenueChart),
            $this->buildSalesItems($sales, $finance, $orders, $batch),
            $this->buildTrialItems($trialStats, $trialStatus, $trialProgress),
            $this->buildWorkshopItems($workshopStats, $workshopStatus, $workshopProgress, $upcomingWorkshopSchedules),
            $this->buildAcademicItems($academic, $batch, $upcomingBatches, $sales)
        );

        $items = $this->prioritizeItems($items);

        if (empty($items)) {
            $items[] = $this->item(
                'info',
                'Dashboard siap dipantau',
                $this->pick('default_neutral', [
                    'Dashboard sudah siap dibaca. Belum ada sinyal besar yang perlu diprioritaskan, tapi monitoring sales, payment, trial, workshop, dan kapasitas tetap perlu dijaga.',
                    'Data utama sudah tersedia. Saat ini kondisi terlihat cukup stabil, jadi tim bisa tetap memantau perubahan funnel, revenue, dan kapasitas secara berkala.',
                    'Belum ada kondisi khusus yang menonjol. Tetap pantau performa bulan berjalan agar peluang sales, trial, workshop, dan pembayaran tidak terlewat.',
                ]),
                300
            );
        }

        $items = array_values($items);
        $headlineItem = $items[0] ?? $items[array_key_first($items)];

        return [
            'generated_at' => now()->format('d M Y H:i'),
            'source' => 'local',
            'source_label' => 'Smart Local Insight',
            'mode' => 'local_smart',
            'headline' => $this->buildHeadline($items, $context),
            'summary_text' => $this->buildSummaryText($items, $context),
            'items' => array_slice($items, 0, 6),
            'focus' => array_slice($this->buildFocus($items), 0, 4),
        ];
    }

    protected function buildFinanceItems(array $finance, array $sales, array $orders, array $revenueChart): array
    {
        $items = [];

        $revenueToday = $this->float($finance['revenue_today'] ?? 0);
        $revenueThisMonth = $this->float($finance['revenue_this_month'] ?? 0);
        $revenueLastMonth = $this->float($finance['revenue_last_month'] ?? 0);
        $revenueMonthDiff = $this->float($finance['revenue_month_diff'] ?? ($revenueThisMonth - $revenueLastMonth));
        $growthPercent = $this->float($finance['revenue_month_growth_percent'] ?? 0);

        $paidToday = $this->int($finance['paid_count_today'] ?? 0);
        $paidThisMonth = $this->int($finance['paid_count_this_month'] ?? $sales['paid_this_month'] ?? 0);
        $paidLastMonth = $this->int($finance['paid_count_last_month'] ?? $sales['paid_last_month'] ?? 0);

        $lastPaymentAmount = $this->float($finance['last_payment_amount'] ?? 0);
        $daysSinceLastPayment = $this->nullableInt($finance['days_since_last_payment'] ?? null);

        $pendingPaymentCount = $this->int($finance['pending_payment_count'] ?? 0);
        $pendingPaymentTotal = $this->float($finance['pending_payment_total'] ?? 0);

        $expiredPaymentCount = $this->int($finance['expired_payment_count'] ?? 0);
        $expiredPaymentTotal = $this->float($finance['expired_payment_total'] ?? 0);

        $overdueScheduleCount = $this->int($finance['overdue_schedule_count'] ?? 0);
        $overdueScheduleTotal = $this->float($finance['overdue_schedule_total'] ?? 0);

        $potentialRevenue = $this->float($orders['potential_revenue'] ?? 0);

        if ($revenueToday > 0 || $paidToday > 0) {
            $items[] = $this->item(
                'good',
                'Kabar bagus, payment baru masuk hari ini',
                $this->pick('finance_payment_today', [
                    'Mantap, ada payment baru yang terkonfirmasi hari ini. Ini sinyal positif karena revenue sedang bergerak, dan momentum follow-up perlu dijaga supaya pipeline berikutnya ikut terbentuk.',
                    'Good news, hari ini sudah ada pembayaran masuk. Tim bisa menjaga momentum ini dengan mengecek leads hangat, order yang masih berjalan, dan follow-up calon peserta.',
                    'Kabar baik, payment hari ini sudah terkonfirmasi. Ini menunjukkan funnel dari minat ke pembayaran berjalan, tinggal dorong peluang terdekat agar cashflow makin stabil.',
                    'Nice, pemasukan baru sudah masuk hari ini. Momentum ini bisa dipakai untuk memperkuat follow-up calon peserta yang sudah dekat ke tahap pembayaran.',
                ]),
                1200,
                ['metric' => $this->money($revenueToday), 'amount' => $revenueToday]
            );
        } elseif (($revenueThisMonth > 0 || $paidThisMonth > 0) && $daysSinceLastPayment !== null && $daysSinceLastPayment >= 7) {
            $items[] = $this->item(
                'warning',
                'Payment bulan ini ada, tapi momentum perlu dijaga',
                $this->pick('finance_payment_month_stale', [
                    'Bulan ini sudah ada pembayaran terkonfirmasi, namun belum ada payment baru dalam beberapa hari terakhir. Fokusnya adalah mendorong peluang berikutnya agar revenue bulan ini terus bertambah.',
                    'Revenue bulan ini sudah tercatat, namun beberapa hari terakhir belum ada payment baru. Tim perlu kembali mendorong leads hangat, order pending, dan reminder pembayaran.',
                    'Pemasukan bulan ini sudah ada, tapi momentumnya mulai perlu dijaga karena belum ada payment baru dalam beberapa hari terakhir.',
                    'Payment bulan ini sudah pernah masuk, namun pipeline berikutnya tetap perlu dikawal agar performa bulan berjalan tidak berhenti di satu transaksi.',
                ]),
                870,
                ['metric' => $this->money($revenueThisMonth), 'amount' => $revenueThisMonth, 'days_since_last_payment' => $daysSinceLastPayment]
            );
        } elseif (($revenueThisMonth > 0 || $paidThisMonth > 0) && $daysSinceLastPayment !== null && $daysSinceLastPayment >= 4) {
            $items[] = $this->item(
                'info',
                'Payment bulan ini sudah tercatat',
                $this->pick('finance_payment_month_needs_momentum', [
                    'Pembayaran bulan ini sudah tercatat, tapi belum ada payment baru dalam beberapa hari terakhir. Momentum follow-up tetap perlu dijaga.',
                    'Revenue bulan berjalan sudah ada, namun tim tetap perlu mendorong peluang berikutnya agar pemasukan tidak berhenti terlalu lama.',
                    'Payment bulan ini sudah masuk, tapi pipeline berikutnya tetap perlu dikawal supaya revenue bisa bertambah lebih konsisten.',
                ]),
                720,
                ['metric' => $this->money($revenueThisMonth), 'amount' => $revenueThisMonth, 'days_since_last_payment' => $daysSinceLastPayment]
            );
        } elseif ($revenueThisMonth > 0 || $paidThisMonth > 0) {
            $items[] = $this->item(
                'good',
                'Payment bulan ini sudah masuk',
                $this->pick('finance_payment_month', [
                    'Bulan ini sudah ada pembayaran yang berhasil dikonfirmasi. Revenue mulai bergerak, dan ini menjadi sinyal positif untuk performa bisnis bulan berjalan.',
                    'Pemasukan bulan ini sudah terbentuk. Tim bisa menjaga momentum dengan follow-up leads aktif dan memastikan payment pending tidak terlalu lama tertahan.',
                    'Revenue bulan berjalan sudah mulai masuk lewat payment berstatus paid. Ini cukup baik, tapi pipeline tetap perlu didorong agar tidak berhenti di satu titik.',
                    'Pembayaran bulan ini sudah mulai terkonfirmasi. Ini menunjukkan funnel dari closing ke payment berjalan dan perlu terus dijaga momentumnya.',
                ]),
                980,
                ['metric' => $this->money($revenueThisMonth), 'amount' => $revenueThisMonth]
            );
        } else {
            $items[] = $this->item(
                'critical',
                'Belum ada pemasukan bulan ini',
                $this->pick('finance_no_revenue', [
                    'Revenue bulan ini belum bergerak. Fokus utama perlu diarahkan ke leads aktif, order pending, payment reminder, dan peluang yang paling dekat menjadi paid.',
                    'Belum ada pemasukan baru yang terkonfirmasi bulan ini. Tim perlu mengecek pipeline sales, status invoice, dan kendala pembayaran yang mungkin terjadi.',
                    'Pemasukan bulan berjalan masih kosong. Ini perlu jadi perhatian management agar proses follow-up, closing, dan payment tidak tertunda terlalu lama.',
                    'Belum ada payment paid bulan ini. Prioritas terdekat adalah memastikan prospek yang sudah hangat bisa diarahkan ke tahap pembayaran.',
                ]),
                1100,
                ['metric' => $this->money($revenueThisMonth), 'amount' => $revenueThisMonth]
            );
        }

        if ($revenueThisMonth > 0 && $revenueLastMonth > 0) {
            if ($revenueThisMonth > $revenueLastMonth) {
                $items[] = $this->item(
                    'good',
                    'Revenue bulan ini naik',
                    $this->pick('finance_revenue_up', [
                        'Revenue bulan ini sudah lebih tinggi dari bulan lalu. Ini sinyal sehat, dan tim tinggal menjaga ritme follow-up sampai akhir periode.',
                        'Performa pemasukan bulan ini bergerak positif dibanding bulan sebelumnya. Channel dan pola follow-up yang berhasil sebaiknya dipertahankan.',
                        'Ada peningkatan revenue dibanding bulan lalu. Momentum ini bisa diperkuat dengan memprioritaskan leads yang paling dekat untuk bayar.',
                    ]),
                    760,
                    ['metric' => '+' . $this->money(abs($revenueMonthDiff)), 'growth_percent' => $growthPercent]
                );
            } elseif ($revenueThisMonth < $revenueLastMonth) {
                $items[] = $this->item(
                    'warning',
                    'Revenue bulan ini turun',
                    $this->pick('finance_revenue_down', [
                        'Revenue bulan ini masih di bawah bulan lalu. Perlu dicek apakah penurunan terjadi karena leads baru rendah, payment pending, atau follow-up yang belum selesai.',
                        'Pemasukan bulan ini belum mengejar performa bulan sebelumnya. Fokuskan perhatian pada peluang yang paling dekat menjadi payment.',
                        'Revenue bulan berjalan turun dibanding bulan lalu. Management perlu memastikan tidak ada peluang pembayaran yang tertahan terlalu lama.',
                    ]),
                    740,
                    ['metric' => '-' . $this->money(abs($revenueMonthDiff)), 'growth_percent' => $growthPercent]
                );
            }
        }

        if ($pendingPaymentCount > 0 || $pendingPaymentTotal > 0) {
            $items[] = $this->item(
                'warning',
                'Ada payment pending yang bisa diprioritaskan',
                $this->pick('finance_pending_payment', [
                    'Masih ada payment pending yang berpotensi menjadi revenue terdekat. Tim perlu memastikan reminder dan bantuan pembayaran berjalan jelas.',
                    'Payment pending bisa menjadi peluang pemasukan paling dekat. Cek invoice, payment URL, dan pastikan calon peserta tidak terkendala proses pembayaran.',
                    'Ada potensi revenue dari payment yang belum selesai. Prioritasnya adalah mempercepat konfirmasi dan membantu peserta menyelesaikan pembayaran.',
                ]),
                820,
                ['metric' => $this->money($pendingPaymentTotal), 'count' => $pendingPaymentCount]
            );
        }

        if ($overdueScheduleCount > 0 || $overdueScheduleTotal > 0) {
            $items[] = $this->item(
                'critical',
                'Ada jadwal pembayaran overdue',
                $this->pick('finance_overdue_schedule', [
                    'Ada jadwal pembayaran yang melewati jatuh tempo. Ini perlu dicek agar tidak menjadi hambatan cashflow dan akses belajar peserta.',
                    'Beberapa jadwal pembayaran terdeteksi overdue. Tim finance atau sales perlu memastikan status invoice, bukti transfer, dan follow-up terakhir.',
                    'Payment schedule overdue perlu segera ditindaklanjuti. Prioritasnya adalah memvalidasi apakah benar belum dibayar atau status pembayaran belum tersinkron.',
                ]),
                1000,
                ['metric' => $this->money($overdueScheduleTotal), 'count' => $overdueScheduleCount]
            );
        }

        if ($expiredPaymentCount > 0 || $expiredPaymentTotal > 0) {
            $items[] = $this->item(
                'warning',
                'Ada payment expired',
                $this->pick('finance_expired_payment', [
                    'Ada payment yang expired. Tim perlu follow-up ulang dan memastikan calon peserta mendapat link pembayaran baru jika masih berminat.',
                    'Beberapa payment sudah expired. Ini bisa jadi sinyal calon peserta butuh reminder atau proses pembayaran yang lebih dibantu.',
                    'Payment expired perlu dipantau karena bisa membuat peluang revenue hilang. Follow-up ulang bisa membantu menghidupkan kembali prospek.',
                ]),
                720,
                ['metric' => $this->money($expiredPaymentTotal), 'count' => $expiredPaymentCount]
            );
        }

        if ($daysSinceLastPayment !== null && $daysSinceLastPayment >= 7) {
            $items[] = $this->item(
                'warning',
                'Belum ada payment baru beberapa hari terakhir',
                $this->pick('finance_last_payment_old', [
                    'Payment terakhir sudah lebih dari beberapa hari lalu. Tim perlu menjaga pipeline agar pemasukan tidak berhenti terlalu lama.',
                    'Belum ada payment baru dalam beberapa hari terakhir. Cek kembali leads aktif, order pending, dan reminder pembayaran.',
                    'Ritme pemasukan berikutnya perlu dijaga. Fokus berikutnya adalah mendorong peluang yang paling dekat menjadi paid.',
                ]),
                min(790, 610 + $daysSinceLastPayment),
                ['metric' => $daysSinceLastPayment . ' hari', 'amount' => $lastPaymentAmount]
            );
        }

        if ($potentialRevenue > 0 && $revenueThisMonth <= 0) {
            $items[] = $this->item(
                'warning',
                'Ada potensi revenue yang belum jadi cash',
                $this->pick('finance_potential_revenue', [
                    'Ada potensi revenue dari order pending atau partial, tapi belum berubah menjadi payment paid. Ini bisa jadi prioritas follow-up cepat.',
                    'Pipeline revenue sudah ada, namun cash belum masuk. Tim perlu mengecek status order, invoice, dan kendala pembayaran.',
                    'Potensi pemasukan terlihat dari order yang belum lunas. Fokuskan follow-up ke transaksi yang paling dekat menjadi paid.',
                ]),
                785,
                ['metric' => $this->money($potentialRevenue)]
            );
        }

        return $items;
    }

    protected function buildSalesItems(array $sales, array $finance, array $orders, array $batch): array
    {
        $items = [];

        $leadsThisMonth = $this->int($sales['leads_this_month'] ?? 0);
        $interactedThisMonth = $this->int($sales['interacted_this_month'] ?? 0);
        $consultationThisMonth = $this->int($sales['consultation_this_month'] ?? 0);
        $hotLeadsThisMonth = $this->int($sales['hot_leads_this_month'] ?? 0);
        $closingThisMonth = $this->int($sales['closing_this_month'] ?? $sales['closed_deal_this_month'] ?? 0);
        $paidThisMonth = $this->int($sales['paid_this_month'] ?? 0);

        $interactionRate = $this->float($sales['interaction_rate'] ?? 0);
        $closingRate = $this->float($sales['closing_rate'] ?? $sales['deal_rate'] ?? 0);

        $remainingSeats = $this->int($batch['remaining_seats'] ?? 0);

        if ($leadsThisMonth <= 0) {
            $items[] = $this->item(
                'warning',
                'Leads bulan ini belum masuk',
                $this->pick('sales_no_leads', [
                    'Belum ada leads baru yang tercatat bulan ini. Fokus awal perlu diarahkan ke campaign, referral, dan follow-up database lama.',
                    'Pipeline awal bulan ini masih kosong dari sisi leads. Tim marketing dan sales perlu membuka sumber leads baru agar funnel tidak berhenti.',
                    'Leads baru belum terlihat bulan ini. Ini saat yang tepat untuk mengecek channel acquisition dan materi promosi yang sedang berjalan.',
                ]),
                760
            );
        } elseif ($interactedThisMonth <= 0) {
            $items[] = $this->item(
                'warning',
                'Leads belum berinteraksi',
                $this->pick('sales_no_interaction', [
                    'Leads sudah masuk, tapi belum ada interaksi tercatat. Tim sales perlu mempercepat kontak awal agar leads tidak dingin.',
                    'Ada leads baru, namun belum terlihat interaksi. Prioritasnya adalah respon cepat dan follow-up pertama yang jelas.',
                    'Pipeline sudah punya leads, tetapi engagement belum terbentuk. Pastikan setiap leads mendapat kontak awal dan pencatatan status.',
                ]),
                730,
                ['metric' => number_format($leadsThisMonth) . ' leads']
            );
        } elseif ($paidThisMonth <= 0 && $closingThisMonth <= 0) {
            $items[] = $this->item(
                'critical',
                'Interaksi ada, tapi belum jadi payment',
                $this->pick('sales_interaction_no_payment', [
                    'Leads dan interaksi sudah berjalan, tapi belum ada payment terkonfirmasi. Fokus perlu diarahkan ke consultation, handling objection, dan follow-up closing.',
                    'Sales funnel mulai bergerak dari sisi interaksi, namun belum berubah menjadi pembayaran. Tim perlu mendorong leads paling hangat sampai tahap payment.',
                    'Interaksi sudah ada, tapi revenue belum terbentuk. Prioritasnya adalah memperjelas offer, deadline, dan next action untuk calon peserta.',
                ]),
                920,
                ['metric' => number_format($interactedThisMonth) . ' interaksi']
            );
        } elseif ($paidThisMonth > 0) {
            $items[] = $this->item(
                'good',
                'Funnel sales mulai menghasilkan',
                $this->pick('sales_payment_exists', [
                    'Sales funnel bulan ini sudah menghasilkan payment. Langkah berikutnya adalah menjaga follow-up agar pipeline tidak berhenti di satu transaksi.',
                    'Ada pembayaran yang berhasil masuk dari aktivitas sales. Momentum ini bisa diperkuat dengan follow-up leads aktif dan referral.',
                    'Funnel sales menunjukkan hasil karena sudah ada payment terkonfirmasi. Tim dapat menggunakan pola yang berhasil untuk peluang berikutnya.',
                ]),
                700,
                ['metric' => number_format($paidThisMonth) . ' paid']
            );
        }

        if ($hotLeadsThisMonth > 0 && $paidThisMonth <= 0) {
            $items[] = $this->item(
                'warning',
                'Hot leads perlu segera dikonversi',
                $this->pick('sales_hot_leads', [
                    'Hot leads sudah ada, tapi payment belum masuk. Follow-up personal dan penawaran yang lebih jelas perlu diprioritaskan.',
                    'Ada hot leads yang bisa diprioritaskan. Karena belum ada payment, tim sales perlu memperjelas next step dan urgensi keputusan.',
                    'Hot leads bulan ini menjadi peluang paling dekat untuk revenue. Prioritaskan komunikasi yang membantu mereka mengambil keputusan.',
                ]),
                780,
                ['metric' => number_format($hotLeadsThisMonth) . ' hot leads']
            );
        }

        if ($consultationThisMonth > 0 && $paidThisMonth <= 0) {
            $items[] = $this->item(
                'warning',
                'Consultation belum berubah jadi payment',
                $this->pick('sales_consultation_gap', [
                    'Consultation sudah terjadi, tapi belum berubah menjadi payment. Cek kembali objection, pricing concern, dan follow-up setelah konsultasi.',
                    'Ada consultation bulan ini, namun payment belum terbentuk. Ini area yang perlu dipantau karena calon peserta sudah berada di tahap cukup hangat.',
                    'Tahap consultation sudah berjalan. Fokus berikutnya adalah mengunci komitmen, deadline pembayaran, dan pilihan program yang paling sesuai.',
                ]),
                690,
                ['metric' => number_format($consultationThisMonth) . ' consultation']
            );
        }

        if ($leadsThisMonth > 0 && $interactionRate > 0 && $interactionRate < 35) {
            $items[] = $this->item(
                'warning',
                'Interaction rate masih rendah',
                $this->pick('sales_low_interaction_rate', [
                    'Interaction rate masih rendah. Tim perlu mempercepat respon awal agar leads tidak terlalu lama menunggu.',
                    'Leads sudah masuk, tapi rasio interaksi belum kuat. Cek waktu respon, channel komunikasi, dan script follow-up.',
                    'Interaksi masih perlu ditingkatkan. Semakin cepat leads dihubungi, semakin besar peluang masuk ke consultation atau payment.',
                ]),
                640,
                ['metric' => number_format($interactionRate, 1) . '%']
            );
        }

        if ($remainingSeats > 0 && ($paidThisMonth <= 0 || $closingRate < 10)) {
            $items[] = $this->item(
                'info',
                'Seat masih bisa didorong oleh sales',
                $this->pick('sales_capacity_gap', [
                    'Masih ada seat aktif yang tersedia. Sales bisa memprioritaskan leads yang sudah berinteraksi untuk mengisi kapasitas batch.',
                    'Kapasitas batch masih punya ruang. Ini peluang untuk mendorong campaign dan follow-up agar seat tidak kosong terlalu lama.',
                    'Seat tersedia masih bisa dimanfaatkan. Hubungkan demand dari trial, workshop, atau leads aktif ke batch yang relevan.',
                ]),
                520,
                ['metric' => number_format($remainingSeats) . ' seat']
            );
        }

        return $items;
    }

    protected function buildTrialItems(array $trialStats, array $trialStatus, int $progress): array
    {
        $items = [];

        $participantsThisMonth = $this->int($trialStats['participants_this_month'] ?? $trialStats['participants_new_this_month'] ?? $trialStats['participants_total'] ?? 0);
        $schedulesActiveThisMonth = $this->int($trialStats['schedules_active_this_month'] ?? $trialStats['schedules_active'] ?? 0);

        $registered = $this->int($trialStatus['registered'] ?? 0);
        $contacted = $this->int($trialStatus['contacted'] ?? 0);
        $confirmed = $this->int($trialStatus['confirmed'] ?? 0);
        $attended = $this->int($trialStatus['attended'] ?? 0);
        $cancelled = $this->int($trialStatus['cancelled'] ?? 0);
        $noShow = $this->int($trialStatus['no_show'] ?? 0);

        if ($participantsThisMonth <= 0 && $schedulesActiveThisMonth > 0) {
            $items[] = $this->item(
                'warning',
                'Trial bulan ini belum punya peserta',
                $this->pick('trial_no_participants_with_schedule', [
                    'Jadwal trial bulan ini sudah ada, tapi peserta belum masuk. Distribusi landing page, campaign, dan reminder jadwal perlu didorong.',
                    'Trial schedule aktif bulan ini belum menghasilkan peserta. Cek visibility form, traffic source, dan materi promosi.',
                    'Ada jadwal trial, namun belum ada peserta baru. Ini sinyal campaign trial perlu diperkuat agar jadwal tidak kosong.',
                ]),
                650
            );
        } elseif ($participantsThisMonth <= 0) {
            $items[] = $this->item(
                'info',
                'Trial bulan ini belum punya peserta baru',
                $this->pick('trial_no_participants', [
                    'Belum ada peserta trial baru bulan ini. Jika trial menjadi channel akuisisi, perlu dorongan campaign atau publikasi jadwal.',
                    'Trial bulan berjalan belum menghasilkan peserta baru. Tim bisa mengecek visibility landing page dan channel promosi.',
                    'Demand trial belum terlihat bulan ini. Ini bisa jadi sinyal untuk memperkuat distribusi konten dan reminder jadwal trial.',
                ]),
                380
            );
        } elseif ($progress < 50) {
            $items[] = $this->item(
                'warning',
                'Follow-up trial perlu dipercepat',
                $this->pick('trial_low_followup', [
                    'Peserta trial sudah masuk, tapi progress follow-up masih rendah. Prioritasnya adalah mendorong status registered ke contacted, confirmed, atau attended.',
                    'Trial punya potensi bulan ini, namun follow-up belum cukup kuat. Pastikan peserta yang sudah daftar segera dihubungi.',
                    'Data trial menunjukkan demand awal, tapi follow-up perlu dipercepat agar calon peserta tidak kehilangan momentum.',
                ]),
                700,
                ['metric' => $progress . '%']
            );
        } elseif ($confirmed > 0 || $attended > 0) {
            $items[] = $this->item(
                'good',
                'Trial mulai menunjukkan kualitas leads',
                $this->pick('trial_good_progress', [
                    'Trial bulan ini mulai bergerak positif karena sudah ada peserta yang confirmed atau attended. Ini bisa menjadi sumber leads yang lebih hangat.',
                    'Progress trial cukup baik. Peserta yang sudah confirmed atau attended perlu diarahkan ke program yang paling relevan.',
                    'Trial menunjukkan sinyal positif. Fokus berikutnya adalah mengubah peserta attended menjadi konsultasi atau pembayaran.',
                ]),
                560,
                ['metric' => $progress . '%']
            );
        }

        if ($registered > $contacted && $registered > 0) {
            $items[] = $this->item(
                'warning',
                'Masih ada trial registered yang belum dihubungi',
                $this->pick('trial_registered_gap', [
                    'Jumlah registered masih lebih tinggi dari contacted. Tim perlu memprioritaskan kontak awal agar peserta trial tidak menjadi cold lead.',
                    'Ada gap antara registered dan contacted. Follow-up cepat akan membantu meningkatkan peluang confirmed dan attended.',
                    'Peserta trial yang baru registered perlu segera diproses. Semakin cepat kontak awal dilakukan, semakin besar peluang konversinya.',
                ]),
                660,
                ['metric' => number_format(max(0, $registered - $contacted)) . ' peserta']
            );
        }

        if ($confirmed > 0 && $attended <= 0) {
            $items[] = $this->item(
                'info',
                'Trial confirmed perlu reminder attendance',
                $this->pick('trial_confirmed_no_attendance', [
                    'Peserta trial sudah confirmed, tapi attended belum terlihat. Reminder H-1 dan hari-H perlu diperkuat.',
                    'Konfirmasi trial sudah ada. Pastikan peserta mendapat reminder, link, dan informasi jadwal yang jelas agar attendance naik.',
                    'Trial confirmed perlu dikawal sampai hadir. Reminder personal bisa membantu mengurangi risiko no-show.',
                ]),
                500,
                ['metric' => number_format($confirmed) . ' confirmed']
            );
        }

        if ($noShow > 0) {
            $items[] = $this->item(
                'warning',
                'Ada peserta trial no-show',
                $this->pick('trial_no_show', [
                    'Ada peserta trial yang no-show. Evaluasi reminder, jadwal, dan komunikasi sebelum sesi bisa membantu menekan no-show berikutnya.',
                    'No-show trial perlu dipantau karena bisa mengurangi peluang konversi. Reminder H-1 dan H-0 bisa diperkuat.',
                    'Peserta no-show menunjukkan ada risiko kehilangan leads. Tim bisa melakukan re-engagement dan menawarkan jadwal pengganti.',
                ]),
                620,
                ['metric' => number_format($noShow) . ' no-show']
            );
        }

        if ($cancelled > 0) {
            $items[] = $this->item(
                'info',
                'Ada trial cancelled',
                $this->pick('trial_cancelled', [
                    'Ada peserta trial yang cancelled. Data ini bisa dipakai untuk evaluasi jadwal, komunikasi benefit, atau alasan batal.',
                    'Trial cancelled perlu dicatat sebagai bahan evaluasi. Cek apakah masalahnya waktu, topik, atau proses follow-up.',
                    'Beberapa peserta trial batal. Re-engagement atau penawaran jadwal lain bisa membantu menyelamatkan demand yang masih relevan.',
                ]),
                360,
                ['metric' => number_format($cancelled) . ' cancelled']
            );
        }

        return $items;
    }

    protected function buildWorkshopItems(array $workshopStats, array $workshopStatus, int $progress, int $upcomingWorkshopSchedules): array
    {
        $items = [];

        $participantsThisMonth = $this->int($workshopStats['participants_this_month'] ?? 0);
        $participantsAllTime = $this->int($workshopStats['participants_all_time'] ?? $workshopStats['participants_total'] ?? 0);
        $schedulesActiveThisMonth = $this->int($workshopStats['schedules_active_this_month'] ?? 0);
        $upcomingSchedules = $this->int($workshopStats['upcoming_schedules'] ?? $upcomingWorkshopSchedules);

        $revenueThisMonth = $this->float($workshopStats['revenue_this_month'] ?? 0);
        $paidCount = $this->int($workshopStats['paid_count_this_month'] ?? $workshopStats['paid_this_month'] ?? 0);

        $registered = $this->int($workshopStatus['registered'] ?? $workshopStats['registered'] ?? $workshopStats['registered_this_month'] ?? 0);
        $pending = $this->int($workshopStatus['pending_payment'] ?? $workshopStats['pending_payment'] ?? $workshopStats['pending_payment_this_month'] ?? 0);
        $confirmed = $this->int($workshopStatus['confirmed'] ?? $workshopStats['confirmed'] ?? $workshopStats['confirmed_this_month'] ?? 0);
        $attended = $this->int($workshopStatus['attended'] ?? $workshopStats['attended'] ?? $workshopStats['attended_this_month'] ?? 0);
        $cancelled = $this->int($workshopStatus['cancelled'] ?? $workshopStats['cancelled'] ?? $workshopStats['cancelled_this_month'] ?? 0);

        $conversionPercent = $this->float($workshopStats['conversion_percent'] ?? $progress);
        $attendancePercent = $this->float($workshopStats['attendance_percent'] ?? 0);
        $topSource = (string) ($workshopStats['top_source'] ?? '');
        $topSourceTotal = $this->int($workshopStats['top_source_total'] ?? 0);

        if ($revenueThisMonth > 0 || $paidCount > 0) {
            $items[] = $this->item(
                'good',
                'Workshop mulai menghasilkan revenue',
                $this->pick('workshop_revenue', [
                    'Workshop bulan ini menunjukkan sinyal positif karena sudah menghasilkan revenue. Channel ini bisa terus didorong sebagai sumber pemasukan tambahan.',
                    'Performa workshop mulai bergerak dari sisi peserta dan pembayaran. Momentum ini bisa diperkuat dengan follow-up peserta pending payment.',
                    'Workshop sudah memberi kontribusi revenue bulan ini. Evaluasi source terbaik agar promosi berikutnya lebih tepat sasaran.',
                    'Kabar baik dari workshop: sudah ada pembayaran yang terkonfirmasi. Ini menunjukkan tema dan offer mulai diterima market.',
                ]),
                850,
                ['metric' => $this->money($revenueThisMonth), 'count' => $paidCount]
            );
        } elseif ($participantsThisMonth > 0) {
            $items[] = $this->item(
                'warning',
                'Workshop ada demand, tapi revenue belum masuk',
                $this->pick('workshop_demand_no_revenue', [
                    'Peserta workshop sudah masuk, tapi revenue belum terbentuk. Tim perlu mengecek status pembayaran dan follow-up peserta pending.',
                    'Workshop mulai mendapatkan demand, namun belum terlihat pembayaran paid. Fokus terdekat adalah mengubah pendaftar menjadi confirmed atau paid.',
                    'Ada peserta workshop bulan ini, tapi pemasukan belum masuk. Pastikan payment link, reminder, dan komunikasi benefit berjalan jelas.',
                ]),
                680,
                ['metric' => number_format($participantsThisMonth) . ' peserta']
            );
        } elseif ($schedulesActiveThisMonth > 0 || $upcomingSchedules > 0) {
            $items[] = $this->item(
                'warning',
                'Workshop punya jadwal, tapi peserta belum masuk',
                $this->pick('workshop_schedule_no_participants', [
                    'Ada jadwal workshop aktif, tapi belum ada peserta baru bulan ini. Campaign, distribusi landing page, dan reminder perlu didorong.',
                    'Workshop schedule sudah tersedia, namun pendaftar belum terlihat. Cek channel promosi, headline topic, dan CTA landing page.',
                    'Jadwal workshop sudah ada, tapi demand belum masuk. Ini saatnya memperkuat publikasi dan targeting audience.',
                ]),
                610,
                ['metric' => number_format($schedulesActiveThisMonth ?: $upcomingSchedules) . ' jadwal']
            );
        } elseif ($participantsAllTime <= 0) {
            $items[] = $this->item(
                'info',
                'Workshop belum punya histori peserta',
                $this->pick('workshop_no_history', [
                    'Workshop belum memiliki histori peserta. Jika channel ini ingin didorong, mulai dari tema yang dekat dengan kebutuhan market dan distribusi landing page.',
                    'Belum ada data peserta workshop yang tercatat. Ini bisa menjadi baseline awal untuk menguji tema, pricing, dan channel promosi.',
                    'Data workshop masih kosong dari sisi peserta. Fokus awal adalah validasi demand dan kejelasan benefit workshop.',
                ]),
                330
            );
        }

        if ($pending > 0) {
            $items[] = $this->item(
                'warning',
                'Workshop pending payment perlu ditindaklanjuti',
                $this->pick('workshop_pending_payment', [
                    'Ada peserta workshop yang masih pending payment. Ini peluang revenue dekat jika follow-up pembayaran dilakukan cepat.',
                    'Workshop punya peserta pending payment yang perlu dipantau. Pastikan mereka menerima payment link dan reminder yang jelas.',
                    'Pending payment workshop bisa menjadi pemasukan tambahan. Tim perlu memastikan tidak ada kendala teknis atau informasi pembayaran.',
                ]),
                760,
                ['metric' => number_format($pending) . ' peserta']
            );
        }

        if ($confirmed > 0 || $attended > 0) {
            $items[] = $this->item(
                'good',
                'Workshop conversion mulai terbentuk',
                $this->pick('workshop_conversion', [
                    'Workshop sudah memiliki peserta confirmed atau attended. Ini sinyal bahwa tema dan penawaran mulai diterima market.',
                    'Conversion workshop mulai terbentuk. Peserta confirmed dan attended bisa menjadi bahan evaluasi untuk tema workshop berikutnya.',
                    'Workshop menunjukkan progres baik dari sisi confirmation atau attendance. Tim bisa menjaga pengalaman peserta agar berdampak ke program berikutnya.',
                ]),
                560,
                ['metric' => number_format($conversionPercent, 0) . '%']
            );
        }

        if ($attended > 0 && $attendancePercent > 0) {
            $items[] = $this->item(
                'good',
                'Attendance workshop sudah terlihat',
                $this->pick('workshop_attendance', [
                    'Attendance workshop sudah mulai terlihat. Setelah sesi berjalan, peserta bisa diarahkan ke offer lanjutan atau program yang relevan.',
                    'Peserta attended menunjukkan workshop berjalan sampai delivery. Ini bisa menjadi peluang untuk testimonial, upsell, atau nurturing.',
                    'Workshop sudah menghasilkan attendance. Jaga pengalaman peserta agar channel ini bisa memberi efek ke brand dan revenue berikutnya.',
                ]),
                500,
                ['metric' => number_format($attendancePercent, 0) . '%']
            );
        }

        if ($registered > 0 && $confirmed <= 0 && $attended <= 0) {
            $items[] = $this->item(
                'warning',
                'Workshop registered belum terkonfirmasi',
                $this->pick('workshop_registered_gap', [
                    'Peserta workshop sudah registered, tapi belum ada confirmed atau attended. Cek follow-up dan status payment agar demand tidak hilang.',
                    'Registered workshop sudah ada namun belum terkonfirmasi. Tim perlu mempercepat komunikasi dan memastikan proses pembayaran jelas.',
                    'Ada peserta workshop yang masuk, tapi belum sampai confirmed. Ini perlu dikawal supaya pendaftar tidak berhenti di awal funnel.',
                ]),
                650,
                ['metric' => number_format($registered) . ' registered']
            );
        }

        if ($cancelled > 0) {
            $items[] = $this->item(
                'info',
                'Ada workshop cancelled',
                $this->pick('workshop_cancelled', [
                    'Ada peserta workshop yang cancelled. Catatan ini bisa membantu mengevaluasi jadwal, tema, harga, atau komunikasi benefit.',
                    'Workshop cancelled perlu dicatat sebagai sinyal evaluasi. Cek apakah penyebabnya waktu, pembayaran, atau ekspektasi peserta.',
                    'Beberapa peserta workshop batal. Re-engagement bisa dilakukan jika masih ada jadwal alternatif atau topik lain yang relevan.',
                ]),
                350,
                ['metric' => number_format($cancelled) . ' cancelled']
            );
        }

        if (! empty($topSource) && $topSource !== 'unknown' && $topSourceTotal > 0) {
            $items[] = $this->item(
                'info',
                'Source workshop mulai terbaca',
                $this->pick('workshop_top_source', [
                    'Source workshop paling menonjol sudah terlihat. Data ini bisa dipakai untuk menentukan channel promosi yang perlu diperkuat.',
                    'Ada sumber peserta workshop yang mulai dominan. Gunakan insight source ini untuk menyusun campaign berikutnya.',
                    'Top source workshop memberi sinyal channel mana yang paling efektif bulan ini. Fokuskan optimasi pada channel tersebut.',
                ]),
                420,
                ['metric' => $topSource . ' • ' . number_format($topSourceTotal)]
            );
        }

        return $items;
    }

    protected function buildAcademicItems(array $academic, array $batch, int $upcomingBatches, array $sales): array
    {
        $items = [];

        $activeBatches = $this->int($academic['active_batches'] ?? 0);
        $upcomingBatchCount = $this->int($academic['upcoming_batches'] ?? $upcomingBatches);
        $filledSeats = $this->int($batch['filled_seats'] ?? $academic['filled_seats'] ?? 0);
        $remainingSeats = $this->int($batch['remaining_seats'] ?? 0);
        $totalCapacity = $this->int($batch['total_capacity'] ?? 0);
        $utilizationPercent = $this->float($batch['utilization_percent'] ?? 0);
        $paidThisMonth = $this->int($sales['paid_this_month'] ?? 0);

        if ($activeBatches > 0 && $utilizationPercent >= 80) {
            $items[] = $this->item(
                'good',
                'Utilisasi batch cukup sehat',
                $this->pick('academic_high_utilization', [
                    'Utilisasi batch sudah cukup tinggi. Fokus berikutnya adalah menjaga delivery quality dan memastikan student aktif tetap termonitor.',
                    'Kapasitas batch terpakai dengan baik. Management perlu menjaga kualitas akademik seiring meningkatnya jumlah seat terisi.',
                    'Batch aktif menunjukkan utilisasi sehat. Pastikan monitoring progress dan instructor readiness tetap berjalan.',
                ]),
                520,
                ['metric' => number_format($utilizationPercent, 0) . '%']
            );
        } elseif ($activeBatches > 0 && $totalCapacity > 0 && $utilizationPercent < 50) {
            $items[] = $this->item(
                'warning',
                'Utilisasi batch masih rendah',
                $this->pick('academic_low_utilization', [
                    'Utilisasi batch masih rendah. Masih ada ruang besar untuk mendorong akuisisi student dan mengisi kapasitas yang tersedia.',
                    'Kapasitas batch belum terpakai optimal. Sales perlu menghubungkan leads, trial, atau workshop participant ke batch yang relevan.',
                    'Batch aktif masih punya banyak seat kosong. Ini bisa menjadi target utama untuk follow-up pipeline bulan berjalan.',
                ]),
                690,
                ['metric' => number_format($utilizationPercent, 0) . '%']
            );
        } elseif ($activeBatches > 0 && $remainingSeats > 0) {
            $items[] = $this->item(
                'info',
                'Masih ada kapasitas batch',
                $this->pick('academic_remaining_capacity', [
                    'Batch aktif masih memiliki seat tersedia. Kapasitas ini bisa dimaksimalkan melalui follow-up leads, trial, dan workshop participant.',
                    'Masih ada ruang di batch aktif. Ini bisa menjadi target sales agar kapasitas program lebih optimal.',
                    'Sisa seat masih tersedia dan perlu dipantau. Dorong calon peserta yang sudah warm agar masuk ke batch yang relevan.',
                ]),
                430,
                ['metric' => number_format($remainingSeats) . ' seat']
            );
        }

        if ($upcomingBatchCount > 0 && $remainingSeats > 0 && $paidThisMonth <= 0) {
            $items[] = $this->item(
                'info',
                'Batch mendatang perlu didorong',
                $this->pick('academic_upcoming_batch_push', [
                    'Ada batch mendatang dan seat masih tersedia. Pastikan sales push dan readiness akademik berjalan paralel.',
                    'Batch mendatang perlu mulai dipantau dari sisi seat dan pipeline. Tim perlu memastikan demand cukup sebelum jadwal mulai.',
                    'Upcoming batch sudah terlihat, tapi kapasitas masih bisa didorong. Hubungkan campaign, trial, dan workshop ke batch yang paling relevan.',
                ]),
                410,
                ['metric' => number_format($upcomingBatchCount) . ' batch']
            );
        }

        if ($filledSeats > 0 && $activeBatches > 0) {
            $items[] = $this->item(
                'info',
                'Student aktif perlu tetap dimonitor',
                $this->pick('academic_student_monitoring', [
                    'Seat yang sudah terisi perlu tetap dipantau dari sisi progress belajar, attendance, dan readiness akademik.',
                    'Student aktif sudah ada di batch berjalan. Pastikan monitoring progress dan komunikasi akademik tetap konsisten.',
                    'Kapasitas yang sudah terisi perlu dijaga kualitas delivery-nya agar experience student tetap baik.',
                ]),
                320,
                ['metric' => number_format($filledSeats) . ' filled']
            );
        }

        return $items;
    }

    protected function buildHeadline(array $items, array $context): string
    {
        $finance = $context['finance_insight'] ?? [];
        $workshopStats = array_merge($context['workshop_insight'] ?? [], $context['workshop_stats'] ?? []);

        $revenueToday = $this->float($finance['revenue_today'] ?? 0);
        $revenueThisMonth = $this->float($finance['revenue_this_month'] ?? 0);
        $pendingPaymentCount = $this->int($finance['pending_payment_count'] ?? 0);
        $overdueScheduleCount = $this->int($finance['overdue_schedule_count'] ?? 0);
        $daysSinceLastPayment = $this->nullableInt($finance['days_since_last_payment'] ?? null);
        $workshopPending = $this->int($workshopStats['pending_payment'] ?? $workshopStats['pending_payment_this_month'] ?? 0);

        if ($revenueThisMonth > 0 && $revenueToday <= 0 && $daysSinceLastPayment !== null && $daysSinceLastPayment >= 7) {
            return $this->pick('headline_payment_stale', [
                'Revenue bulan ini tercatat, pipeline perlu dijaga',
                'Revenue sudah ada, pipeline berikutnya perlu dijaga',
                'Pemasukan bulan ini ada, follow-up berikutnya perlu dipercepat',
            ]);
        }

        if (($revenueToday > 0 || ($revenueThisMonth > 0 && ($daysSinceLastPayment === null || $daysSinceLastPayment < 7))) && ($overdueScheduleCount > 0 || $pendingPaymentCount > 0 || $workshopPending > 0)) {
            return $this->pick('headline_payment_with_followup', [
                'Payment masuk, follow-up tetap perlu dijaga',
                'Revenue mulai bergerak, ada payment yang perlu dicek',
                'Payment sudah masuk, masih ada follow-up pembayaran',
            ]);
        }

        if ($revenueToday > 0) {
            return $this->pick('headline_payment_today', [
                'Kabar baik, payment baru masuk hari ini',
                'Payment hari ini sudah terkonfirmasi',
                'Revenue hari ini mulai bergerak',
            ]);
        }

        if ($revenueThisMonth > 0) {
            return $this->pick('headline_payment_month', [
                'Payment bulan ini sudah mulai masuk',
                'Revenue bulan ini mulai bergerak',
                'Pemasukan bulan ini sudah terbentuk',
            ]);
        }

        if ($pendingPaymentCount > 0 || $workshopPending > 0) {
            return $this->pick('headline_pending_without_revenue', [
                'Ada payment pending yang perlu ditindaklanjuti',
                'Revenue belum masuk, payment pending perlu difollow-up',
                'Peluang revenue masih tertahan di payment pending',
            ]);
        }

        return (string) ($items[0]['title'] ?? 'Management Insight');
    }

    protected function buildSummaryText(array $items, array $context): string
    {
        if (empty($items)) {
            return 'Dashboard sudah siap dipantau. Belum ada insight khusus yang perlu diprioritaskan saat ini.';
        }

        $sales = $context['sales_insight'] ?? [];
        $finance = $context['finance_insight'] ?? [];
        $orders = $context['order_insight'] ?? [];
        $batch = $context['batch_capacity'] ?? [];
        $trialStats = $context['trial_stats'] ?? [];
        $trialStatus = $context['trial_status_counts'] ?? [];
        $trialProgress = $this->int($context['trial_follow_up_progress'] ?? 0);
        $workshopStats = array_merge($context['workshop_insight'] ?? [], $context['workshop_stats'] ?? []);
        $workshopStatus = $context['workshop_status_counts'] ?? [];

        $revenueToday = $this->float($finance['revenue_today'] ?? 0);
        $revenueThisMonth = $this->float($finance['revenue_this_month'] ?? 0);
        $revenueLastMonth = $this->float($finance['revenue_last_month'] ?? 0);
        $paidToday = $this->int($finance['paid_count_today'] ?? 0);
        $paidThisMonth = $this->int($finance['paid_count_this_month'] ?? $sales['paid_this_month'] ?? 0);
        $pendingPaymentCount = $this->int($finance['pending_payment_count'] ?? 0);
        $pendingPaymentTotal = $this->float($finance['pending_payment_total'] ?? 0);
        $overdueScheduleCount = $this->int($finance['overdue_schedule_count'] ?? 0);
        $overdueScheduleTotal = $this->float($finance['overdue_schedule_total'] ?? 0);
        $expiredPaymentCount = $this->int($finance['expired_payment_count'] ?? 0);
        $potentialRevenue = $this->float($orders['potential_revenue'] ?? 0);
        $daysSinceLastPayment = $this->nullableInt($finance['days_since_last_payment'] ?? null);
        $hasPaymentFollowUp = $pendingPaymentCount > 0
            || $pendingPaymentTotal > 0
            || $overdueScheduleCount > 0
            || $overdueScheduleTotal > 0
            || $expiredPaymentCount > 0
            || $potentialRevenue > 0;

        $leadsThisMonth = $this->int($sales['leads_this_month'] ?? 0);
        $interactedThisMonth = $this->int($sales['interacted_this_month'] ?? 0);
        $hotLeadsThisMonth = $this->int($sales['hot_leads_this_month'] ?? 0);
        $consultationThisMonth = $this->int($sales['consultation_this_month'] ?? 0);

        $remainingSeats = $this->int($batch['remaining_seats'] ?? 0);
        $utilizationPercent = $this->float($batch['utilization_percent'] ?? 0);

        $trialParticipants = $this->int($trialStats['participants_this_month'] ?? $trialStats['participants_new_this_month'] ?? 0);
        $trialRegistered = $this->int($trialStatus['registered'] ?? 0);
        $trialContacted = $this->int($trialStatus['contacted'] ?? 0);
        $trialNoShow = $this->int($trialStatus['no_show'] ?? 0);

        $workshopParticipants = $this->int($workshopStats['participants_this_month'] ?? 0);
        $workshopRevenue = $this->float($workshopStats['revenue_this_month'] ?? 0);
        $workshopPending = $this->int($workshopStatus['pending_payment'] ?? $workshopStats['pending_payment'] ?? $workshopStats['pending_payment_this_month'] ?? 0);
        $workshopConfirmed = $this->int($workshopStatus['confirmed'] ?? $workshopStats['confirmed'] ?? $workshopStats['confirmed_this_month'] ?? 0);
        $workshopAttended = $this->int($workshopStatus['attended'] ?? $workshopStats['attended'] ?? $workshopStats['attended_this_month'] ?? 0);
        $hasOperationalPaymentFollowUp = $hasPaymentFollowUp || $workshopPending > 0;

        $sentences = [];

        if ($revenueToday > 0 || $paidToday > 0) {
            $sentences[] = $this->pick('summary_open_payment_today', [
                'Kabar baik, hari ini ada pembayaran baru yang sudah terkonfirmasi.',
                'Nice, payment baru hari ini sudah masuk dan revenue mulai bergerak.',
                'Hari ini ada pemasukan baru yang sudah tercatat, jadi momentum revenue sedang positif.',
            ]);
        } elseif (($revenueThisMonth > 0 || $paidThisMonth > 0) && $daysSinceLastPayment !== null && $daysSinceLastPayment >= 7) {
            $sentences[] = $this->pick('summary_open_payment_month_stale', [
                'Revenue bulan ini sudah tercatat, namun belum ada payment baru dalam sekitar ' . number_format($daysSinceLastPayment) . ' hari terakhir.',
                'Pemasukan bulan ini memang sudah ada, namun belum ada payment baru dalam sekitar ' . number_format($daysSinceLastPayment) . ' hari terakhir.',
                'Revenue bulan ini sudah tercatat, namun belum ada pemasukan baru dalam beberapa hari terakhir sehingga pipeline perlu dikawal lagi.',
                'Bulan ini sudah ada revenue, namun ritme pipeline tetap perlu dijaga agar pemasukan tidak melambat.',
            ]);
        } elseif (($revenueThisMonth > 0 || $paidThisMonth > 0) && $daysSinceLastPayment !== null && $daysSinceLastPayment >= 4) {
            $sentences[] = $this->pick('summary_open_payment_month_needs_momentum', [
                'Pembayaran bulan ini sudah tercatat, tapi belum ada payment baru dalam beberapa hari terakhir.',
                'Revenue bulan ini sudah mulai terbentuk, namun pipeline berikutnya tetap perlu dikawal.',
                'Pemasukan bulan berjalan sudah ada, tapi pipeline berikutnya masih perlu dikawal supaya revenue terus bertambah.',
            ]);
        } elseif ($revenueThisMonth > 0 || $paidThisMonth > 0) {
            $sentences[] = $this->pick('summary_open_payment_month', [
                'Kabar baik, pembayaran bulan ini sudah mulai masuk dan revenue sudah bergerak.',
                'Payment bulan ini sudah terkonfirmasi, jadi pemasukan sudah mulai terbentuk.',
                'Revenue bulan ini sudah mulai berjalan dari pembayaran yang berstatus paid.',
                'Pemasukan bulan ini sudah ada, ini sinyal positif untuk performa bisnis bulan berjalan.',
            ]);
        } elseif ($leadsThisMonth > 0 || $interactedThisMonth > 0 || $potentialRevenue > 0) {
            $sentences[] = $this->pick('summary_open_no_revenue_with_pipeline', [
                'Revenue bulan ini memang belum masuk, tapi pipeline masih punya peluang yang bisa diprioritaskan.',
                'Pemasukan bulan ini belum terbentuk, namun masih ada aktivitas funnel yang bisa diprioritaskan.',
                'Belum ada payment paid bulan ini, jadi fokus utama perlu diarahkan ke peluang yang paling dekat menjadi pembayaran.',
            ]);
        } else {
            $sentences[] = $this->pick('summary_open_no_revenue', [
                'Revenue bulan ini belum bergerak, jadi tim perlu membuka kembali pipeline dari sisi leads, trial, workshop, dan payment reminder.',
                'Belum ada pemasukan baru bulan ini. Fokus awalnya adalah memastikan sumber leads dan peluang payment mulai bergerak lagi.',
                'Pemasukan bulan berjalan masih kosong, sehingga follow-up dan campaign perlu dibuat lebih aktif dalam waktu dekat.',
            ]);
        }

        $paymentRisks = [];

        if ($overdueScheduleCount > 0) {
            $paymentRisks[] = number_format($overdueScheduleCount) . ' jadwal pembayaran overdue' . ($overdueScheduleTotal > 0 ? ' senilai sekitar ' . $this->money($overdueScheduleTotal) : '');
        }

        if ($pendingPaymentCount > 0) {
            $paymentRisks[] = number_format($pendingPaymentCount) . ' payment pending' . ($pendingPaymentTotal > 0 ? ' dengan potensi ' . $this->money($pendingPaymentTotal) : '');
        }

        if ($expiredPaymentCount > 0) {
            $paymentRisks[] = number_format($expiredPaymentCount) . ' payment expired';
        }

        if (! empty($paymentRisks)) {
            $sentences[] = $this->pick('summary_payment_risk_prefix', [
                'Namun masih ada ' . $this->joinHuman($paymentRisks) . ' yang perlu dicek agar peluang revenue tidak tertahan.',
                'Di sisi pembayaran, masih ada ' . $this->joinHuman($paymentRisks) . ' yang sebaiknya ditindaklanjuti lebih dulu.',
                'Catatan pentingnya, ' . $this->joinHuman($paymentRisks) . ' masih perlu divalidasi supaya status pembayaran tetap rapi.',
            ]);
        } elseif ($revenueThisMonth > 0) {
            $sentences[] = $this->pick('summary_payment_clean', [
                'Sejauh ini tidak ada sinyal besar dari sisi overdue atau pending payment, jadi tim bisa fokus menjaga pipeline dan peluang baru.',
                'Dari sisi payment, kondisi terlihat lebih rapi; fokus berikutnya adalah menjaga pipeline agar pemasukan tetap berlanjut.',
                'Karena revenue sudah mulai masuk, langkah berikutnya adalah menjaga ritme follow-up agar peluang baru tetap bergerak.',
            ]);
        }

        $ops = [];

        if ($workshopPending > 0) {
            $ops[] = 'peserta workshop yang masih pending payment';
        } elseif ($workshopRevenue > 0) {
            $ops[] = 'workshop juga sudah mulai memberi kontribusi revenue';
        } elseif ($workshopParticipants > 0 && ($workshopConfirmed + $workshopAttended) <= 0) {
            $ops[] = 'peserta workshop yang sudah masuk tapi belum confirmed atau attended';
        }

        if ($trialParticipants > 0 && $trialProgress < 50) {
            $ops[] = 'follow-up trial bulan ini yang masih perlu dipercepat';
        } elseif ($trialRegistered > $trialContacted && $trialRegistered > 0) {
            $ops[] = 'peserta trial registered yang belum banyak tersentuh';
        } elseif ($trialNoShow > 0) {
            $ops[] = 'trial no-show yang perlu dievaluasi dari sisi reminder';
        }

        if ($hotLeadsThisMonth > 0 && $revenueThisMonth <= 0) {
            $ops[] = 'hot leads yang bisa diprioritaskan untuk dikonversi';
        } elseif ($consultationThisMonth > 0 && $revenueThisMonth <= 0) {
            $ops[] = 'consultation yang belum berubah menjadi payment';
        } elseif ($leadsThisMonth > 0 && $interactedThisMonth <= 0) {
            $ops[] = 'leads baru yang belum masuk tahap interaksi';
        }

        if ($remainingSeats > 0 && $utilizationPercent < 80) {
            $ops[] = 'seat batch yang masih tersedia';
        }

        if (! empty($ops)) {
            $sentences[] = $this->pick('summary_ops_context', [
                'Selain itu, perhatikan juga ' . $this->joinHuman(array_slice($ops, 0, 2)) . ' karena area ini bisa langsung memengaruhi konversi berikutnya.',
                'Area lain yang perlu dikawal adalah ' . $this->joinHuman(array_slice($ops, 0, 2)) . ' supaya peluang yang sudah ada tidak hilang.',
                'Selain itu, ' . $this->joinHuman(array_slice($ops, 0, 2)) . ' juga perlu dikawal agar peluang yang sudah ada tidak berhenti di tengah jalan.',
            ]);
        }

        if (! empty($paymentRisks)) {
            $sentences[] = $this->pick('summary_action_payment', [
                'Fokus berikutnya adalah validasi status invoice, kirim reminder yang jelas, dan bantu peserta menyelesaikan pembayaran tanpa hambatan.',
                'Prioritas tim adalah memastikan payment link diterima, status pembayaran tersinkron, dan peserta yang belum menyelesaikan pembayaran mendapat follow-up yang jelas.',
                'Action terdekatnya: cek payment yang tertahan, rapikan status transaksi, lalu follow-up peserta dengan pesan yang lebih jelas.',
            ]);
        } elseif ($revenueThisMonth > 0 && $daysSinceLastPayment !== null && $daysSinceLastPayment >= 7) {
            $sentences[] = $this->pick('summary_action_payment_stale', [
                'Fokus berikutnya adalah mengaktifkan lagi pipeline dari leads hangat, order yang masih berjalan, dan peserta workshop yang butuh follow-up.',
                'Prioritas tim adalah menggerakkan lagi momentum revenue dengan follow-up yang lebih spesifik ke peluang paling dekat bayar.',
                'Action terdekatnya: cek pipeline yang sudah hangat, perjelas next step untuk calon peserta, dan pastikan peluang baru tidak berhenti di tengah jalan.',
            ]);
        } elseif ($revenueThisMonth > 0) {
            $sentences[] = $this->pick('summary_action_positive', [
                'Fokus berikutnya adalah menjaga momentum ini dengan follow-up leads aktif, trial yang sudah hangat, dan peserta workshop yang berpotensi lanjut bayar.',
                'Tim tinggal menjaga ritme follow-up agar pipeline tetap bergerak dan kapasitas program bisa terisi lebih optimal.',
                'Langkah berikutnya adalah memperkuat pipeline yang sudah hangat agar pemasukan bulan ini terus bertambah.',
            ]);
        } else {
            $sentences[] = $this->pick('summary_action_no_revenue', [
                'Fokus berikutnya adalah dorong leads aktif, cek order pending, dan pastikan calon peserta punya next action yang jelas menuju pembayaran.',
                'Prioritas terdekat adalah menghidupkan pipeline, mempercepat follow-up, dan memastikan payment reminder berjalan konsisten.',
                'Action utama saat ini adalah membuka peluang baru sekaligus mengubah prospek hangat menjadi payment paid.',
            ]);
        }

        return $this->limitWords(implode(' ', array_filter($sentences)), 95);
    }

    protected function buildFocus(array $items): array
    {
        $focus = [];

        foreach ($items as $item) {
            $title = strtolower((string) ($item['title'] ?? ''));
            $type = (string) ($item['type'] ?? $item['level'] ?? 'info');
            $message = (string) ($item['message'] ?? '');

            if (str_contains($title, 'payment baru') || str_contains($title, 'payment bulan ini')) {
                $focus[] = $this->focusItem($type, 'Jaga momentum payment', 'Follow-up leads hangat dan peluang aktif supaya pemasukan berikutnya bisa terbentuk.');
            } elseif (str_contains($title, 'pemasukan') || str_contains($title, 'revenue')) {
                $focus[] = $this->focusItem($type, 'Prioritaskan peluang revenue', 'Cek leads aktif, order pending, dan invoice yang paling dekat menjadi paid.');
            } elseif (str_contains($title, 'pending')) {
                $focus[] = $this->focusItem($type, 'Kejar payment pending', 'Pastikan payment link, reminder, dan bantuan pembayaran sudah diterima calon peserta.');
            } elseif (str_contains($title, 'overdue')) {
                $focus[] = $this->focusItem($type, 'Validasi overdue payment', 'Cek apakah payment benar belum dibayar atau status pembayaran belum tersinkron.');
            } elseif (str_contains($title, 'leads') || str_contains($title, 'interaksi') || str_contains($title, 'hot leads') || str_contains($title, 'consultation')) {
                $focus[] = $this->focusItem($type, 'Perkuat follow-up sales', 'Prioritaskan leads yang sudah hangat dan pastikan next action menuju payment jelas.');
            } elseif (str_contains($title, 'trial')) {
                $focus[] = $this->focusItem($type, 'Percepat follow-up trial', 'Dorong peserta trial dari registered ke contacted, confirmed, attended, lalu program.');
            } elseif (str_contains($title, 'workshop')) {
                $focus[] = $this->focusItem($type, 'Dorong konversi workshop', 'Pastikan peserta workshop pending/registered bergerak ke confirmed, attended, atau paid.');
            } elseif (str_contains($title, 'seat') || str_contains($title, 'batch') || str_contains($title, 'kapasitas')) {
                $focus[] = $this->focusItem($type, 'Isi kapasitas batch', 'Hubungkan demand dari leads, trial, dan workshop ke batch yang masih punya seat.');
            }
        }

        if (empty($focus)) {
            $focus = [
                $this->focusItem('info', 'Pantau revenue bulan berjalan', 'Pastikan payment paid, pending, dan overdue dicek secara berkala.'),
                $this->focusItem('info', 'Pantau funnel akuisisi', 'Review leads, trial, workshop, dan kapasitas batch agar peluang tidak terlewat.'),
            ];
        }

        return $this->uniqueFocus($focus);
    }

    protected function prioritizeItems(array $items): array
    {
        usort($items, function (array $a, array $b) {
            $scoreA = (int) ($a['score'] ?? 0);
            $scoreB = (int) ($b['score'] ?? 0);

            if ($scoreA === $scoreB) {
                return $this->severityWeight($b['type'] ?? $b['level'] ?? 'info') <=> $this->severityWeight($a['type'] ?? $a['level'] ?? 'info');
            }

            return $scoreB <=> $scoreA;
        });

        return $items;
    }

    protected function item(string $type, string $title, string $message, int $score = 0, array $meta = []): array
    {
        return array_merge([
            'type' => $type,
            'level' => $type,
            'title' => $title,
            'message' => $message,
            'description' => $message,
            'score' => $score,
        ], $meta);
    }

    protected function focusItem(string $type, string $title, string $message): array
    {
        return [
            'type' => $type,
            'level' => $type,
            'title' => $title,
            'message' => $message,
            'description' => $message,
        ];
    }

    protected function severityWeight(string $type): int
    {
        return match ($type) {
            'critical' => 5,
            'warning' => 4,
            'action' => 3,
            'good' => 2,
            'info' => 1,
            default => 0,
        };
    }

    protected function pick(string $key, array $templates): string
    {
        if (empty($templates)) {
            return '';
        }

        $seed = now()->format('Y-m-d') . '|' . $key;
        $index = abs(crc32($seed)) % count($templates);

        return $templates[$index];
    }

    protected function normalizeContext(array $context): array
    {
        foreach ($context as $key => $value) {
            if ($value instanceof Collection) {
                $context[$key] = $value->toArray();
                continue;
            }

            if (is_object($value) && method_exists($value, 'toArray')) {
                $context[$key] = $value->toArray();
            }
        }

        return $context;
    }

    protected function countableCount(mixed $value): int
    {
        if ($value instanceof Collection) {
            return $value->count();
        }

        if (is_array($value) || $value instanceof \Countable) {
            return count($value);
        }

        return 0;
    }

    protected function joinHuman(array $items): string
    {
        $items = array_values(array_filter(array_map(fn ($item) => trim((string) $item), $items)));

        if (count($items) === 0) {
            return '';
        }

        if (count($items) === 1) {
            return $items[0];
        }

        if (count($items) === 2) {
            return $items[0] . ' dan ' . $items[1];
        }

        $last = array_pop($items);

        return implode(', ', $items) . ', dan ' . $last;
    }

    protected function uniqueFocus(array $focus): array
    {
        $seen = [];
        $unique = [];

        foreach ($focus as $item) {
            $title = (string) ($item['title'] ?? '');

            if ($title === '' || isset($seen[$title])) {
                continue;
            }

            $seen[$title] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    protected function int(mixed $value): int
    {
        return (int) ($value ?? 0);
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function float(mixed $value): float
    {
        return (float) ($value ?? 0);
    }

    protected function money(float $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    protected function limitWords(string $text, int $limit): string
    {
        $words = preg_split('/\s+/', trim($text));

        if (! $words || count($words) <= $limit) {
            return trim($text);
        }

        return implode(' ', array_slice($words, 0, $limit)) . '...';
    }
}
