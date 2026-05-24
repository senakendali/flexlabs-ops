<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Student;
use App\Models\Workshop;
use App\Models\WorkshopParticipant;
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
                'student',
                'order.paymentSchedules',
            ])
            ->latest();

        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
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
                });
            });
        }

        $participants = $query->paginate(15)->withQueryString();

        $workshops = Workshop::query()
            ->latest()
            ->get();

        return view('academic.workshop-participants.index', compact(
            'participants',
            'workshops'
        ));
    }

    public function create(Request $request)
    {
        $workshops = Workshop::query()
            ->latest()
            ->get();

        $selectedWorkshop = null;

        if ($request->filled('workshop_id')) {
            $selectedWorkshop = Workshop::find($request->workshop_id);
        }

        return view('academic.workshop-participants.create', compact(
            'workshops',
            'selectedWorkshop'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateStoreRequest($request);

        try {
            $participant = DB::transaction(function () use ($validated) {
                $workshop = Workshop::query()->findOrFail($validated['workshop_id']);

                $student = $this->findOrCreateStudent($validated);

                $this->ensureStudentIsNotRegistered($workshop->id, $student->id);

                $price = $this->getWorkshopPrice($workshop);
                $discount = (float) ($validated['discount'] ?? 0);
                $finalPrice = max($price - $discount, 0);

                $participant = WorkshopParticipant::create([
                    'workshop_id' => $workshop->id,
                    'student_id' => $student->id,
                    'status' => 'pending_payment',
                    'registered_at' => now(),
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
                    'notes' => 'Workshop: ' . $this->getWorkshopTitle($workshop),
                ]);

                $participant->update([
                    'order_id' => $order->id,
                    'status' => $finalPrice <= 0 ? 'confirmed' : 'pending_payment',
                    'paid_at' => $finalPrice <= 0 ? now() : null,
                ]);

                PaymentSchedule::create([
                    'order_id' => $order->id,
                    'title' => 'Pembayaran Workshop: ' . $this->getWorkshopTitle($workshop),
                    'amount' => $finalPrice,
                    'due_date' => $validated['due_date'] ?? now()->toDateString(),
                    'status' => $finalPrice <= 0 ? 'paid' : 'pending',
                    'notes' => $validated['payment_notes'] ?? null,
                ]);

                return $participant->fresh([
                    'workshop',
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
                'message' => 'Peserta kemungkinan sudah terdaftar di workshop ini.',
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
            'student',
            'order.paymentSchedules',
        ]);

        $workshops = Workshop::query()
            ->latest()
            ->get();

        return view('academic.workshop-participants.edit', [
            'participant' => $workshopParticipant,
            'workshops' => $workshops,
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

                $oldWorkshopId = $workshopParticipant->workshop_id;
                $newWorkshopId = (int) ($validated['workshop_id'] ?? $oldWorkshopId);

                if ($newWorkshopId !== (int) $oldWorkshopId) {
                    $this->ensureStudentIsNotRegistered(
                        $newWorkshopId,
                        $workshopParticipant->student_id,
                        $workshopParticipant->id
                    );
                }

                $workshop = Workshop::query()->findOrFail($newWorkshopId);

                $workshopParticipant->update([
                    'workshop_id' => $workshop->id,
                    'status' => $validated['status'] ?? $workshopParticipant->status,
                    'notes' => $validated['notes'] ?? $workshopParticipant->notes,
                    'paid_at' => $this->resolvePaidAt(
                        $validated['status'] ?? $workshopParticipant->status,
                        $workshopParticipant->paid_at
                    ),
                    'attended_at' => $this->resolveAttendedAt(
                        $validated['status'] ?? $workshopParticipant->status,
                        $workshopParticipant->attended_at
                    ),
                ]);

                if ($workshopParticipant->order) {
                    $price = $this->getWorkshopPrice($workshop);
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
                            $validated['status'] ?? $workshopParticipant->status,
                            $workshopParticipant->order->status
                        ),
                        'notes' => 'Workshop: ' . $this->getWorkshopTitle($workshop),
                    ]);

                    $paymentSchedule = $workshopParticipant->order
                        ->paymentSchedules()
                        ->oldest()
                        ->first();

                    if ($paymentSchedule && $paymentSchedule->status !== 'paid') {
                        $paymentSchedule->update([
                            'title' => 'Pembayaran Workshop: ' . $this->getWorkshopTitle($workshop),
                            'amount' => $finalPrice,
                            'due_date' => $validated['due_date'] ?? $paymentSchedule->due_date,
                            'status' => $finalPrice <= 0 ? 'paid' : $paymentSchedule->status,
                            'notes' => $validated['payment_notes'] ?? $paymentSchedule->notes,
                        ]);
                    }
                }

                return $workshopParticipant->fresh([
                    'workshop',
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
                'message' => 'Data peserta tidak valid atau peserta sudah terdaftar di workshop tersebut.',
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

                if ($order) {
                    $hasPaidPayment = Payment::query()
                        ->where('order_id', $order->id)
                        ->where('status', 'paid')
                        ->exists();

                    if ($hasPaidPayment || $order->status === 'paid') {
                        $workshopParticipant->update([
                            'status' => 'cancelled',
                        ]);

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

                    $order->delete();

                    return;
                }

                $workshopParticipant->delete();
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
        return view('academic.workshop-participants.create', [
            'workshops' => collect([$workshop]),
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
        ]);
    }

    private function findOrCreateStudent(array $data): Student
    {
        $student = Student::query()
            ->when(!empty($data['email']), function ($query) use ($data) {
                $query->where('email', $data['email']);
            })
            ->when(empty($data['email']) && !empty($data['phone']), function ($query) use ($data) {
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

    private function ensureStudentIsNotRegistered(int $workshopId, int $studentId, ?int $ignoreParticipantId = null): void
    {
        $exists = WorkshopParticipant::query()
            ->where('workshop_id', $workshopId)
            ->where('student_id', $studentId)
            ->when($ignoreParticipantId, function ($query) use ($ignoreParticipantId) {
                $query->where('id', '!=', $ignoreParticipantId);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'student' => 'Peserta ini sudah terdaftar di workshop tersebut.',
            ]);
        }
    }

    private function getWorkshopTitle(Workshop $workshop): string
    {
        return $workshop->title
            ?? $workshop->name
            ?? 'Workshop FlexLabs';
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

    private function resolveOrderStatus(string $participantStatus, string $currentOrderStatus): string
    {
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