<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Company;
use App\Models\GroupRegistration;
use App\Models\GroupRegistrationParticipant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Student;
use App\Models\User;
use App\Services\Payment\InvoiceNumberService;
use App\Services\PaymentGateway\XenditService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class GroupRegistrationService
{
    private const WHT_RATE = 2.00;
    private const VAT_RATE = 11.00;

    public function __construct(
        protected InvoiceNumberService $invoiceNumberService,
        protected XenditService $xenditService
    ) {
    }

    public function create(array $data, User $actor): GroupRegistration
    {
        $result = DB::transaction(function () use ($data, $actor) {
            $batch = Batch::query()
                ->with('program:id,name')
                ->lockForUpdate()
                ->findOrFail($data['batch_id']);

            $company = $this->resolveCompany($data);
            $buyer = $this->resolveBuyer($data, $company);
            $amounts = $this->calculateAmounts($data, $batch);
            $paymentTerms = $this->validateAndNormalizeTerms($data, $amounts['service_amount']);

            $groupRegistration = GroupRegistration::create([
                'registration_number' => $this->generateRegistrationNumber(),
                'buyer_type' => $data['buyer_type'],
                'buyer_student_id' => $buyer['student_id'],
                'company_id' => $company?->id,
                'batch_id' => $batch->id,
                'buyer_name' => $buyer['name'],
                'buyer_email' => $buyer['email'],
                'buyer_phone' => $buyer['phone'],
                'quantity' => (int) $data['quantity'],
                'price_per_seat' => $amounts['price_per_seat'],
                'original_price' => $amounts['original_price'],
                'discount' => $amounts['discount'],
                'service_amount' => $amounts['service_amount'],
                'wht_rate' => $amounts['wht_rate'],
                'wht_amount' => $amounts['wht_amount'],
                'invoice_total' => $amounts['invoice_total'],
                'net_payable' => $amounts['net_payable'],
                'wht_status' => $data['buyer_type'] === GroupRegistration::BUYER_COMPANY
                    ? GroupRegistration::WHT_PENDING
                    : GroupRegistration::WHT_NOT_APPLICABLE,
                'status' => GroupRegistration::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->createParticipants(
                $groupRegistration,
                $data['participants'] ?? [],
                (int) $data['quantity']
            );

            $order = Order::create([
                'student_id' => null,
                'group_registration_id' => $groupRegistration->id,
                'order_type' => 'program',
                'batch_id' => $batch->id,
                'workshop_id' => null,
                'original_price' => $amounts['original_price'],
                'discount' => $amounts['discount'],
                // final_price adalah nilai kas/net yang harus diterima FlexLabs.
                'final_price' => $amounts['net_payable'],
                'status' => 'pending',
                'notes' => $data['payment_notes'] ?? $data['notes'] ?? null,
            ]);

            $order->setRelation('batch', $batch);
            $payments = $this->createSchedulesAndPayments(
                $groupRegistration,
                $order,
                $paymentTerms,
                $data
            );

            return compact('groupRegistration', 'order', 'payments');
        }, 3);

        $groupRegistration = $result['groupRegistration'];
        $order = $result['order'];
        $order->loadMissing([
            'batch:id,program_id,name',
            'batch.program:id,name',
            'groupRegistration.company',
        ]);

        foreach ($result['payments'] as $payment) {
            $this->attachXenditPaymentLink($payment, $order, $payment->paymentSchedule);
        }

        return $groupRegistration->fresh([
            'company',
            'buyerStudent',
            'batch.program',
            'participants.student',
            'order.paymentSchedules.payments',
        ]);
    }

    public function updateMetadata(GroupRegistration $groupRegistration, array $data, User $actor): GroupRegistration
    {
        $groupRegistration->update([
            'buyer_name' => $data['buyer_name'] ?? $groupRegistration->buyer_name,
            'buyer_email' => array_key_exists('buyer_email', $data)
                ? $data['buyer_email']
                : $groupRegistration->buyer_email,
            'buyer_phone' => array_key_exists('buyer_phone', $data)
                ? $data['buyer_phone']
                : $groupRegistration->buyer_phone,
            'notes' => array_key_exists('notes', $data)
                ? $data['notes']
                : $groupRegistration->notes,
            'updated_by' => $actor->id,
        ]);

        return $groupRegistration->fresh([
            'company',
            'buyerStudent',
            'batch.program',
            'participants.student',
            'order.paymentSchedules.payments',
        ]);
    }

    public function cancel(GroupRegistration $groupRegistration, User $actor): GroupRegistration
    {
        return DB::transaction(function () use ($groupRegistration, $actor) {
            $lockedRegistration = GroupRegistration::query()
                ->with('order')
                ->lockForUpdate()
                ->findOrFail($groupRegistration->id);

            $order = $lockedRegistration->order;

            if ($order && $order->payments()->where('status', Payment::STATUS_PAID)->exists()) {
                throw ValidationException::withMessages([
                    'group_registration' => [
                        'Group Registration yang sudah memiliki payment paid tidak dapat dibatalkan.',
                    ],
                ]);
            }

            $lockedRegistration->update([
                'status' => GroupRegistration::STATUS_CANCELLED,
                'updated_by' => $actor->id,
            ]);

            $lockedRegistration->participants()
                ->where('status', '!=', GroupRegistrationParticipant::STATUS_ENROLLED)
                ->update(['status' => GroupRegistrationParticipant::STATUS_CANCELLED]);

            if ($order) {
                $order->update(['status' => 'cancelled']);
                $order->paymentSchedules()
                    ->where('status', '!=', 'paid')
                    ->update(['status' => 'cancelled']);
                $order->payments()
                    ->where('status', '!=', Payment::STATUS_PAID)
                    ->update(['status' => Payment::STATUS_CANCELLED]);
            }

            return $lockedRegistration->fresh();
        }, 3);
    }

    private function resolveCompany(array $data): ?Company
    {
        if ($data['buyer_type'] !== GroupRegistration::BUYER_COMPANY) {
            return null;
        }

        if (!empty($data['company_id'])) {
            return Company::query()->where('is_active', true)->findOrFail($data['company_id']);
        }

        $companyData = $data['company'] ?? [];

        return Company::create([
            'name' => $companyData['name'],
            'tax_id' => $companyData['tax_id'] ?? null,
            'email' => $companyData['email'] ?? null,
            'phone' => $companyData['phone'] ?? null,
            'address' => $companyData['address'] ?? null,
            'pic_name' => $companyData['pic_name'] ?? null,
            'pic_email' => $companyData['pic_email'] ?? null,
            'pic_phone' => $companyData['pic_phone'] ?? null,
            'notes' => $companyData['notes'] ?? null,
            'is_active' => true,
        ]);
    }

    private function resolveBuyer(array $data, ?Company $company): array
    {
        if ($data['buyer_type'] === GroupRegistration::BUYER_COMPANY) {
            return [
                'student_id' => null,
                'name' => $company->name,
                'email' => $company->pic_email ?: $company->email,
                'phone' => $company->pic_phone ?: $company->phone,
            ];
        }

        $student = !empty($data['buyer_student_id'])
            ? Student::query()->findOrFail($data['buyer_student_id'])
            : null;

        $buyerName = trim((string) ($data['buyer_name'] ?? ''));

        if ($buyerName === '') {
            throw ValidationException::withMessages([
                'buyer_name' => ['Buyer name is required for an individual buyer.'],
            ]);
        }

        $buyerEmail = trim((string) ($data['buyer_email'] ?? ''));
        $buyerPhone = trim((string) ($data['buyer_phone'] ?? ''));

        return [
            // Link ke student hanya opsional. Buyer/payer tidak harus menjadi peserta.
            'student_id' => $student?->id,
            'name' => $buyerName,
            'email' => $buyerEmail !== '' ? $buyerEmail : $student?->email,
            'phone' => $buyerPhone !== '' ? $buyerPhone : $student?->phone,
        ];
    }

    private function calculateAmounts(array $data, Batch $batch): array
    {
        $quantity = (int) $data['quantity'];
        $pricePerSeat = round((float) $batch->price, 2);
        $originalPrice = round($pricePerSeat * $quantity, 2);
        $discountValue = round((float) ($data['discount_value'] ?? 0), 2);

        $discount = match ($data['discount_type']) {
            'percentage' => round($originalPrice * min($discountValue, 100) / 100, 2),
            'fixed' => min($discountValue, $originalPrice),
            default => 0.0,
        };

        $serviceAmount = round(max($originalPrice - $discount, 0), 2);

        if ($serviceAmount <= 0) {
            throw ValidationException::withMessages([
                'discount_value' => ['Final service amount must be greater than zero.'],
            ]);
        }

        $usesWht = $data['buyer_type'] === GroupRegistration::BUYER_COMPANY;

        // Harga batch/service amount sudah termasuk PPN 11%.
        // PPh 23 di-gross-up dari DPP (amount before VAT), bukan dari nilai
        // pembayaran yang sudah termasuk PPN.
        $amountBeforeVat = round(
            $serviceAmount / (1 + (self::VAT_RATE / 100)),
            2
        );
        $whtTaxBase = $usesWht
            ? round($amountBeforeVat / (1 - (self::WHT_RATE / 100)), 2)
            : $amountBeforeVat;
        $whtAmount = $usesWht
            ? round($whtTaxBase - $amountBeforeVat, 2)
            : 0.0;
        // Mengikuti format invoice accounting FlexLabs:
        // Total Invoice = Amount sebelum VAT yang sudah di-gross-up PPh 23.
        // Grand Total / nominal ke Xendit tetap service amount termasuk VAT.
        $invoiceTotal = $usesWht ? $whtTaxBase : $serviceAmount;

        return [
            'price_per_seat' => $pricePerSeat,
            'original_price' => $originalPrice,
            'discount' => $discount,
            'service_amount' => $serviceAmount,
            'wht_rate' => $usesWht ? self::WHT_RATE : 0.0,
            'wht_amount' => $whtAmount,
            'invoice_total' => $invoiceTotal,
            'net_payable' => $serviceAmount,
        ];
    }

    private function validateAndNormalizeTerms(array $data, float $netPayable): array
    {
        $terms = array_values($data['payment_terms']);

        if ($data['payment_scheme'] === 'full' && count($terms) !== 1) {
            throw ValidationException::withMessages([
                'payment_terms' => ['Full payment must contain exactly one schedule.'],
            ]);
        }

        if ($data['payment_scheme'] === 'installment' && count($terms) < 2) {
            throw ValidationException::withMessages([
                'payment_terms' => ['Installment must contain at least two schedules.'],
            ]);
        }

        $termTotal = round(collect($terms)->sum(
            fn (array $term) => (float) $term['amount']
        ), 2);

        if (abs($termTotal - $netPayable) > 0.009) {
            throw ValidationException::withMessages([
                'payment_terms' => ['Total payment schedules must equal the net payable amount.'],
            ]);
        }

        $dueDates = collect($terms)
            ->pluck('due_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values();

        if ($dueDates->sort()->values()->all() !== $dueDates->all()) {
            throw ValidationException::withMessages([
                'payment_terms' => ['Payment due dates must be ordered from earliest to latest.'],
            ]);
        }

        return $terms;
    }

    private function createParticipants(
        GroupRegistration $groupRegistration,
        array $participants,
        int $quantity
    ): void {
        $studentIds = collect($participants)
            ->pluck('student_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($studentIds->count() > $quantity) {
            throw ValidationException::withMessages([
                'participants' => ['Assigned participants cannot exceed purchased seats.'],
            ]);
        }

        foreach ($studentIds as $studentId) {
            GroupRegistrationParticipant::create([
                'group_registration_id' => $groupRegistration->id,
                'student_id' => $studentId,
                'student_enrollment_id' => null,
                'status' => GroupRegistrationParticipant::STATUS_ASSIGNED,
                'enrolled_at' => null,
                'notes' => null,
            ]);
        }
    }

    private function createSchedulesAndPayments(
        GroupRegistration $groupRegistration,
        Order $order,
        array $paymentTerms,
        array $data
    ): Collection {
        $payments = collect();
        $allocatedGross = 0.0;
        $lastIndex = count($paymentTerms) - 1;

        foreach ($paymentTerms as $index => $term) {
            $netAmount = round((float) $term['amount'], 2);

            // Setiap termin mengikuti formula yang sama dengan total registrasi:
            // DPP = pembayaran / 1.11
            // dasar gross-up PPh 23 = DPP / 0.98
            // WHT = dasar gross-up - DPP
            // gross_amount = Total Invoice sebelum VAT (DPP yang sudah gross-up WHT).
            $amountBeforeVat = round(
                $netAmount / (1 + (self::VAT_RATE / 100)),
                2
            );
            $whtTaxBase = $groupRegistration->usesWht()
                ? round(
                    $amountBeforeVat
                        / (1 - ((float) $groupRegistration->wht_rate / 100)),
                    2
                )
                : $amountBeforeVat;
            $grossAmount = $groupRegistration->usesWht()
                ? ($index === $lastIndex
                    ? round((float) $groupRegistration->invoice_total - $allocatedGross, 2)
                    : $whtTaxBase)
                : $netAmount;
            $whtAmount = $groupRegistration->usesWht()
                ? round($grossAmount - $amountBeforeVat, 2)
                : 0.0;
            $allocatedGross = round($allocatedGross + $grossAmount, 2);

            $label = match (true) {
                $data['payment_scheme'] === 'full' => 'Full Payment',
                $index === 0 => 'Down Payment (DP)',
                default => 'Installment ' . ($index + 1),
            };

            $manualNotes = trim((string) ($data['payment_notes'] ?? ''));
            $paymentNotes = $manualNotes !== '' ? $label . ' - ' . $manualNotes : $label;

            $schedule = PaymentSchedule::create([
                'order_id' => $order->id,
                'title' => $label,
                'amount' => $netAmount,
                'gross_amount' => $grossAmount,
                'wht_rate' => (float) $groupRegistration->wht_rate,
                'wht_amount' => $whtAmount,
                'net_amount' => $netAmount,
                'due_date' => Carbon::parse($term['due_date'])->toDateString(),
                'status' => 'pending',
                'notes' => $data['payment_notes'] ?? null,
            ]);

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_schedule_id' => $schedule->id,
                'invoice_number' => $this->invoiceNumberService->generate($order),
                'public_token' => Str::uuid()->toString(),
                'payment_url' => null,
                'amount' => $netAmount,
                'payment_date' => null,
                'payment_method' => null,
                'reference_number' => null,
                'gateway_transaction_id' => null,
                'gateway_provider' => null,
                'gateway_payload' => null,
                'status' => Payment::STATUS_PENDING,
                'expired_at' => Carbon::parse($term['due_date'])
                    ->endOfDay()
                    ->addDays((int) $data['invoice_expiry_days']),
                'notes' => $paymentNotes,
                'paid_at' => null,
            ]);

            $payment->setRelation('paymentSchedule', $schedule);
            $payments->push($payment);
        }

        return $payments;
    }

    private function attachXenditPaymentLink(
        Payment $payment,
        Order $order,
        PaymentSchedule $schedule
    ): void {
        try {
            $registration = $order->groupRegistration;
            $batch = $order->batch;
            $program = $batch?->program;

            $result = $this->xenditService->createPaymentLink($payment, [
                'full_name' => $registration?->buyer_name,
                'email' => $registration?->buyer_email,
                'phone' => $registration?->buyer_phone,
                'program_name' => $program?->name,
                'batch_name' => $batch?->name,
                'item_name' => $schedule->title,
            ]);

            $payment->update([
                'payment_url' => $result['payment_url'] ?? null,
                'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
                'gateway_provider' => $result['gateway_provider'] ?? 'xendit',
                'gateway_payload' => $result['gateway_payload'] ?? null,
                'expired_at' => !empty($result['expired_at'])
                    ? Carbon::parse($result['expired_at'])
                    : $payment->expired_at,
            ]);
        } catch (Throwable $e) {
            report($e);

            $payment->update([
                'gateway_provider' => 'xendit',
                'gateway_payload' => [
                    'error' => $e->getMessage(),
                    'retryable' => true,
                ],
            ]);
        }
    }

    private function generateRegistrationNumber(): string
    {
        $prefix = 'GR-FLX-' . now()->format('Ym') . '-';
        $maxSequence = GroupRegistration::query()
            ->where('registration_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->pluck('registration_number')
            ->map(function ($number) use ($prefix) {
                return (int) Str::after((string) $number, $prefix);
            })
            ->max() ?: 0;

        return $prefix . str_pad((string) ($maxSequence + 1), 3, '0', STR_PAD_LEFT);
    }
}