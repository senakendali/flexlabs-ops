<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Student;
use App\Models\Workshop;
use App\Models\WorkshopParticipant;
use App\Models\WorkshopSchedule;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkshopParticipantController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkshopParticipant::query()
            ->with([
                'workshop',
                'workshopSchedule',
                'student',
                'order.paymentSchedules',
            ])
            ->latest();

        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
        }

        if ($request->filled('workshop_schedule_id')) {
            $query->where('workshop_schedule_id', $request->workshop_schedule_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);

            $query->where(function ($q) use ($keyword) {
                $q->whereHas('student', function ($studentQuery) use ($keyword) {
                    $studentQuery
                        ->where('full_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                })
                ->orWhereHas('workshop', function ($workshopQuery) use ($keyword) {
                    $workshopQuery
                        ->where('title', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%");
                })
                ->orWhereHas('workshopSchedule', function ($scheduleQuery) use ($keyword) {
                    $scheduleQuery
                        ->where('title', 'like', "%{$keyword}%")
                        ->orWhere('location', 'like', "%{$keyword}%");
                });
            });
        }

        $participants = $query->paginate(15)->withQueryString();

        $workshops = Workshop::query()
            ->latest()
            ->get();

        $workshopSchedules = WorkshopSchedule::query()
            ->with('workshop')
            ->where('is_active', true)
            ->whereIn('status', ['open', 'draft'])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->get();

        return view('academic.workshop-participants.index', compact(
            'participants',
            'workshops',
            'workshopSchedules'
        ));
    }

    public function create(Request $request)
    {
        $workshops = Workshop::query()
            ->latest()
            ->get();

        $workshopSchedules = WorkshopSchedule::query()
            ->with('workshop')
            ->where('is_active', true)
            ->whereIn('status', ['open', 'draft'])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->get();

        $selectedWorkshop = null;

        if ($request->filled('workshop_id')) {
            $selectedWorkshop = Workshop::find($request->workshop_id);
        }

        return view('academic.workshop-participants.create', compact(
            'workshops',
            'workshopSchedules',
            'selectedWorkshop'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateStoreRequest($request);

        try {
            $participant = DB::transaction(function () use ($validated) {
                [$workshop, $workshopSchedule] = $this->resolveWorkshopAndSchedule(
                    (int) $validated['workshop_id'],
                    (int) $validated['workshop_schedule_id']
                );

                $student = $this->findOrCreateStudent($validated);

                $this->ensureStudentIsNotRegistered(
                    $workshopSchedule->id,
                    $student->id
                );

                $price = $this->getWorkshopSchedulePrice($workshopSchedule, $workshop);
                $discount = (float) ($validated['discount'] ?? 0);
                $finalPrice = max($price - $discount, 0);

                $participant = WorkshopParticipant::create([
                    'workshop_id' => $workshop->id,
                    'workshop_schedule_id' => $workshopSchedule->id,
                    'student_id' => $student->id,
                    'status' => $finalPrice <= 0 ? 'confirmed' : 'pending_payment',
                    'registered_at' => now(),
                    'paid_at' => $finalPrice <= 0 ? now() : null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $order = Order::create([
                    'student_id' => $student->id,
                    'order_type' => 'workshop',
                    'batch_id' => null,
                    'workshop_id' => $workshop->id,
                    'original_price' => $price,
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                    'status' => $finalPrice <= 0 ? 'paid' : 'pending',
                    'notes' => $this->makeOrderNotes($workshop, $workshopSchedule),
                ]);

                $participant->update([
                    'order_id' => $order->id,
                ]);

                PaymentSchedule::create([
                    'order_id' => $order->id,
                    'title' => $this->makePaymentTitle($workshop, $workshopSchedule),
                    'amount' => $finalPrice,
                    'due_date' => $validated['due_date'] ?? now()->toDateString(),
                    'status' => $finalPrice <= 0 ? 'paid' : 'pending',
                    'notes' => $validated['payment_notes'] ?? null,
                ]);

                $this->incrementScheduleRegisteredCount($workshopSchedule);

                return $participant->fresh([
                    'workshop',
                    'workshopSchedule',
                    'student',
                    'order.paymentSchedules',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Peserta workshop berhasil ditambahkan.',
                'data' => [
                    'participant' => $participant,
                ],
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta kemungkinan sudah terdaftar di jadwal workshop ini.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan peserta workshop.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function show(WorkshopParticipant $workshopParticipant)
    {
        $workshopParticipant->load([
            'workshop',
            'workshopSchedule',
            'student',
            'order.paymentSchedules',
        ]);

        return view('academic.workshop-participants.show', [
            'participant' => $workshopParticipant,
        ]);
    }

    public function edit(WorkshopParticipant $workshopParticipant)
    {
        $workshopParticipant->load([
            'workshop',
            'workshopSchedule',
            'student',
            'order.paymentSchedules',
        ]);

        $workshops = Workshop::query()
            ->latest()
            ->get();

        $workshopSchedules = WorkshopSchedule::query()
            ->with('workshop')
            ->where('is_active', true)
            ->whereIn('status', ['open', 'draft'])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->get();

        return view('academic.workshop-participants.edit', [
            'participant' => $workshopParticipant,
            'workshops' => $workshops,
            'workshopSchedules' => $workshopSchedules,
        ]);
    }

    public function update(Request $request, WorkshopParticipant $workshopParticipant): JsonResponse
    {
        $validated = $this->validateUpdateRequest($request, $workshopParticipant);

        try {
            $participant = DB::transaction(function () use ($validated, $workshopParticipant) {
                $workshopParticipant->load([
                    'student',
                    'workshop',
                    'workshopSchedule',
                    'order.paymentSchedules',
                ]);

                $student = $workshopParticipant->student;

                if ($student) {
                    $student->update([
                        'full_name' => $validated['full_name'] ?? $student->full_name,
                        'email' => $validated['email'] ?? $student->email,
                        'phone' => $validated['phone'] ?? $student->phone,
                        'city' => $validated['city'] ?? $student->city,
                        'goal' => $validated['goal'] ?? $student->goal,
                        'current_status' => 'Workshop Participant',
                        'source' => 'workshop',
                    ]);
                }

                $oldWorkshopId = (int) $workshopParticipant->workshop_id;
                $oldScheduleId = $workshopParticipant->workshop_schedule_id
                    ? (int) $workshopParticipant->workshop_schedule_id
                    : null;

                $newWorkshopId = (int) ($validated['workshop_id'] ?? $oldWorkshopId);
                $newScheduleId = (int) ($validated['workshop_schedule_id'] ?? $oldScheduleId);

                [$workshop, $workshopSchedule] = $this->resolveWorkshopAndSchedule(
                    $newWorkshopId,
                    $newScheduleId
                );

                if (
                    $newScheduleId !== $oldScheduleId
                    || $newWorkshopId !== $oldWorkshopId
                ) {
                    $this->ensureStudentIsNotRegistered(
                        $workshopSchedule->id,
                        $workshopParticipant->student_id,
                        $workshopParticipant->id
                    );
                }

                $newStatus = $validated['status'] ?? $workshopParticipant->status;

                $workshopParticipant->update([
                    'workshop_id' => $workshop->id,
                    'workshop_schedule_id' => $workshopSchedule->id,
                    'status' => $newStatus,
                    'notes' => $validated['notes'] ?? $workshopParticipant->notes,
                    'paid_at' => $this->resolvePaidAt(
                        $newStatus,
                        $workshopParticipant->paid_at
                    ),
                    'attended_at' => $this->resolveAttendedAt(
                        $newStatus,
                        $workshopParticipant->attended_at
                    ),
                ]);

                if ($oldScheduleId && $oldScheduleId !== $workshopSchedule->id) {
                    $this->decrementScheduleRegisteredCountById($oldScheduleId);
                    $this->incrementScheduleRegisteredCount($workshopSchedule);
                }

                if ($workshopParticipant->order) {
                    $price = $this->getWorkshopSchedulePrice($workshopSchedule, $workshop);
                    $discount = array_key_exists('discount', $validated)
                        ? (float) $validated['discount']
                        : (float) $workshopParticipant->order->discount;

                    $finalPrice = max($price - $discount, 0);

                    $workshopParticipant->order->update([
                        'workshop_id' => $workshop->id,
                        'original_price' => $price,
                        'discount' => $discount,
                        'final_price' => $finalPrice,
                        'status' => $this->resolveOrderStatus(
                            $newStatus,
                            $workshopParticipant->order->status,
                            $finalPrice
                        ),
                        'notes' => $this->makeOrderNotes($workshop, $workshopSchedule),
                    ]);

                    $paymentSchedule = $workshopParticipant->order
                        ->paymentSchedules()
                        ->oldest()
                        ->first();

                    if ($paymentSchedule && $paymentSchedule->status !== 'paid') {
                        $paymentSchedule->update([
                            'title' => $this->makePaymentTitle($workshop, $workshopSchedule),
                            'amount' => $finalPrice,
                            'due_date' => $validated['due_date'] ?? $paymentSchedule->due_date,
                            'status' => $finalPrice <= 0 ? 'paid' : $paymentSchedule->status,
                            'notes' => $validated['payment_notes'] ?? $paymentSchedule->notes,
                        ]);
                    }
                }

                return $workshopParticipant->fresh([
                    'workshop',
                    'workshopSchedule',
                    'student',
                    'order.paymentSchedules',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Peserta workshop berhasil diperbarui.',
                'data' => [
                    'participant' => $participant,
                ],
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data peserta tidak valid atau peserta sudah terdaftar di jadwal workshop tersebut.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui peserta workshop.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(WorkshopParticipant $workshopParticipant): JsonResponse
    {
        try {
            DB::transaction(function () use ($workshopParticipant) {
                $workshopParticipant->load([
                    'order.paymentSchedules',
                ]);

                $order = $workshopParticipant->order;
                $scheduleId = $workshopParticipant->workshop_schedule_id;

                if ($order) {
                    $hasPaidPayment = Payment::query()
                        ->where('order_id', $order->id)
                        ->where('status', 'paid')
                        ->exists();

                    if ($hasPaidPayment || $order->status === 'paid') {
                        if ($workshopParticipant->status !== 'cancelled') {
                            $workshopParticipant->update([
                                'status' => 'cancelled',
                            ]);

                            if ($scheduleId) {
                                $this->decrementScheduleRegisteredCountById((int) $scheduleId);
                            }
                        }

                        $order->update([
                            'status' => 'cancelled',
                        ]);

                        $order->paymentSchedules()
                            ->where('status', '!=', 'paid')
                            ->update([
                                'status' => 'cancelled',
                            ]);

                        return;
                    }

                    Payment::query()
                        ->where('order_id', $order->id)
                        ->whereIn('status', ['pending', 'failed', 'expired', 'cancelled'])
                        ->delete();

                    $order->paymentSchedules()->delete();

                    $workshopParticipant->delete();

                    if ($scheduleId) {
                        $this->decrementScheduleRegisteredCountById((int) $scheduleId);
                    }

                    $order->delete();

                    return;
                }

                $workshopParticipant->delete();

                if ($scheduleId) {
                    $this->decrementScheduleRegisteredCountById((int) $scheduleId);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Peserta workshop berhasil dihapus.',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus peserta workshop.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function byWorkshop(Workshop $workshop)
    {
        $participants = WorkshopParticipant::query()
            ->with([
                'student',
                'workshopSchedule',
                'order.paymentSchedules',
            ])
            ->where('workshop_id', $workshop->id)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('academic.workshop-participants.by-workshop', compact(
            'workshop',
            'participants'
        ));
    }

    public function createForWorkshop(Workshop $workshop)
    {
        $workshopSchedules = WorkshopSchedule::query()
            ->with('workshop')
            ->where('workshop_id', $workshop->id)
            ->where('is_active', true)
            ->whereIn('status', ['open', 'draft'])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->get();

        return view('academic.workshop-participants.create', [
            'workshops' => collect([$workshop]),
            'workshopSchedules' => $workshopSchedules,
            'selectedWorkshop' => $workshop,
        ]);
    }

    public function storeForWorkshop(Request $request, Workshop $workshop): JsonResponse
    {
        $request->merge([
            'workshop_id' => $workshop->id,
        ]);

        return $this->store($request);
    }

    private function validateStoreRequest(Request $request): array
    {
        return $request->validate([
            'workshop_id' => [
                'required',
                'integer',
                Rule::exists('workshops', 'id'),
            ],
            'workshop_schedule_id' => [
                'required',
                'integer',
                Rule::exists('workshop_schedules', 'id'),
            ],
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => [
                'required',
                'string',
                'max:30',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'goal' => [
                'nullable',
                'string',
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'due_date' => [
                'nullable',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'payment_notes' => [
                'nullable',
                'string',
            ],
        ], [
            'workshop_id.required' => 'Workshop wajib dipilih.',
            'workshop_schedule_id.required' => 'Jadwal workshop wajib dipilih.',
            'workshop_schedule_id.exists' => 'Jadwal workshop yang dipilih tidak valid.',
            'full_name.required' => 'Nama peserta wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);
    }

    private function validateUpdateRequest(Request $request, WorkshopParticipant $participant): array
    {
        return $request->validate([
            'workshop_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('workshops', 'id'),
            ],
            'workshop_schedule_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('workshop_schedules', 'id'),
            ],
            'full_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:30',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'goal' => [
                'nullable',
                'string',
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in([
                    'registered',
                    'pending_payment',
                    'confirmed',
                    'attended',
                    'cancelled',
                ]),
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'due_date' => [
                'nullable',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'payment_notes' => [
                'nullable',
                'string',
            ],
        ], [
            'workshop_id.required' => 'Workshop wajib dipilih.',
            'workshop_schedule_id.required' => 'Jadwal workshop wajib dipilih.',
            'workshop_schedule_id.exists' => 'Jadwal workshop yang dipilih tidak valid.',
            'full_name.required' => 'Nama peserta wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);
    }

    private function resolveWorkshopAndSchedule(int $workshopId, int $workshopScheduleId): array
    {
        $workshop = Workshop::query()->findOrFail($workshopId);

        $workshopSchedule = WorkshopSchedule::query()
            ->where('id', $workshopScheduleId)
            ->where('workshop_id', $workshop->id)
            ->first();

        if (! $workshopSchedule) {
            throw ValidationException::withMessages([
                'workshop_schedule_id' => 'Jadwal yang dipilih tidak sesuai dengan workshop.',
            ]);
        }

        return [$workshop, $workshopSchedule];
    }

    private function findOrCreateStudent(array $data): Student
    {
        $student = Student::query()
            ->when(! empty($data['email']), function ($query) use ($data) {
                $query->where('email', $data['email']);
            })
            ->when(empty($data['email']) && ! empty($data['phone']), function ($query) use ($data) {
                $query->where('phone', $data['phone']);
            })
            ->first();

        if ($student) {
            $student->update([
                'full_name' => $data['full_name'] ?? $student->full_name,
                'email' => $data['email'] ?? $student->email,
                'phone' => $data['phone'] ?? $student->phone,
                'city' => $data['city'] ?? $student->city,
                'goal' => $data['goal'] ?? $student->goal,
                'current_status' => 'Workshop Participant',
                'source' => 'workshop',
            ]);

            return $student;
        }

        return Student::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'city' => $data['city'] ?? null,
            'goal' => $data['goal'] ?? null,
            'current_status' => 'Workshop Participant',
            'source' => 'workshop',
            'status' => 'lead',
        ]);
    }

    private function ensureStudentIsNotRegistered(int $workshopScheduleId, int $studentId, ?int $ignoreParticipantId = null): void
    {
        $exists = WorkshopParticipant::query()
            ->where('workshop_schedule_id', $workshopScheduleId)
            ->where('student_id', $studentId)
            ->when($ignoreParticipantId, function ($query) use ($ignoreParticipantId) {
                $query->where('id', '!=', $ignoreParticipantId);
            })
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'workshop_schedule_id' => 'Peserta ini sudah terdaftar di jadwal workshop tersebut.',
            ]);
        }
    }

    private function getWorkshopTitle(Workshop $workshop): string
    {
        return $workshop->title
            ?? $workshop->name
            ?? 'Workshop FlexLabs';
    }

    private function getWorkshopScheduleTitle(WorkshopSchedule $schedule): string
    {
        $date = $schedule->schedule_date
            ? $schedule->schedule_date->format('d M Y')
            : null;

        $time = trim(implode(' - ', array_filter([
            $schedule->start_time ? substr((string) $schedule->start_time, 0, 5) : null,
            $schedule->end_time ? substr((string) $schedule->end_time, 0, 5) : null,
        ])));

        return trim(implode(' - ', array_filter([
            $schedule->title,
            $date,
            $time,
        ]))) ?: 'Jadwal Workshop';
    }

    private function makeOrderNotes(Workshop $workshop, WorkshopSchedule $schedule): string
    {
        return 'Workshop: '
            . $this->getWorkshopTitle($workshop)
            . ' | Jadwal: '
            . $this->getWorkshopScheduleTitle($schedule);
    }

    private function makePaymentTitle(Workshop $workshop, WorkshopSchedule $schedule): string
    {
        return 'Pembayaran Workshop: '
            . $this->getWorkshopTitle($workshop)
            . ' - '
            . $this->getWorkshopScheduleTitle($schedule);
    }

    private function getWorkshopPrice(Workshop $workshop): float
    {
        return (float) (
            $workshop->price
            ?? $workshop->final_price
            ?? $workshop->registration_fee
            ?? 0
        );
    }

    private function getWorkshopSchedulePrice(WorkshopSchedule $schedule, Workshop $workshop): float
    {
        return (float) (
            $schedule->price
            ?? $workshop->price
            ?? $workshop->final_price
            ?? $workshop->registration_fee
            ?? 0
        );
    }

    private function incrementScheduleRegisteredCount(WorkshopSchedule $schedule): void
    {
        $schedule->increment('registered_count');
    }

    private function decrementScheduleRegisteredCountById(int $scheduleId): void
    {
        WorkshopSchedule::query()
            ->where('id', $scheduleId)
            ->where('registered_count', '>', 0)
            ->decrement('registered_count');
    }

    private function resolvePaidAt(string $status, $currentPaidAt)
    {
        if (in_array($status, ['confirmed', 'attended'], true)) {
            return $currentPaidAt ?: now();
        }

        if (in_array($status, ['registered', 'pending_payment', 'cancelled'], true)) {
            return null;
        }

        return $currentPaidAt;
    }

    private function resolveAttendedAt(string $status, $currentAttendedAt)
    {
        if ($status === 'attended') {
            return $currentAttendedAt ?: now();
        }

        if ($status !== 'attended') {
            return null;
        }

        return $currentAttendedAt;
    }

    private function resolveOrderStatus(string $participantStatus, string $currentOrderStatus, float $finalPrice = 0): string
    {
        if ($finalPrice <= 0 && in_array($participantStatus, ['registered', 'pending_payment', 'confirmed', 'attended'], true)) {
            return 'paid';
        }

        return match ($participantStatus) {
            'confirmed', 'attended' => 'paid',
            'cancelled' => 'cancelled',
            'registered', 'pending_payment' => $currentOrderStatus === 'paid'
                ? 'paid'
                : 'pending',
            default => $currentOrderStatus,
        };
    }
}
