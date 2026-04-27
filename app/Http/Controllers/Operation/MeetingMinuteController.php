<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use App\Models\MeetingMinute;
use App\Models\MeetingMinuteActionItem;
use App\Models\MeetingMinuteAgenda;
use App\Models\MeetingMinuteParticipant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MeetingMinuteController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $meetingType = $request->input('meeting_type');
        $department = $request->input('department');
        $organizerId = $request->input('organizer_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $meetingMinutes = MeetingMinute::query()
            ->with(['organizer'])
            ->withCount([
                'participants',
                'agendas',
                'actionItems',
                'pendingActionItems',
                'completedActionItems',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('meeting_no', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhere('related_project', 'like', '%' . $search . '%')
                        ->orWhere('summary', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($meetingType, fn ($query) => $query->where('meeting_type', $meetingType))
            ->when($department, fn ($query) => $query->where('department', $department))
            ->when($organizerId, fn ($query) => $query->where('organizer_id', $organizerId))
            ->when($dateFrom, fn ($query) => $query->whereDate('meeting_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('meeting_date', '<=', $dateTo))
            ->latest('meeting_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $users = User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => MeetingMinute::count(),
            'draft' => MeetingMinute::where('status', 'draft')->count(),
            'completed' => MeetingMinute::where('status', 'completed')->count(),
            'pending_action_items' => MeetingMinuteActionItem::whereIn('status', [
                'pending',
                'in_progress',
                'blocked',
            ])->count(),
        ];

        return view('operation.meeting-minutes.index', compact(
            'meetingMinutes',
            'users',
            'stats',
            'search',
            'status',
            'meetingType',
            'department',
            'organizerId',
            'dateFrom',
            'dateTo'
        ));
    }

    public function create(): View
    {
        $users = User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $meetingMinute = new MeetingMinute([
            'meeting_date' => now()->toDateString(),
            'meeting_type' => 'internal',
            'department' => 'operation',
            'status' => 'draft',
            'is_active' => true,
        ]);

        return view('operation.meeting-minutes.create', compact('meetingMinute', 'users'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::beginTransaction();

        try {
            $meetingMinute = MeetingMinute::create([
                'meeting_no' => $this->generateMeetingNo($validated['meeting_date']),
                'title' => $validated['title'],
                'meeting_type' => $validated['meeting_type'] ?? 'internal',
                'meeting_date' => $validated['meeting_date'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'location' => $validated['location'] ?? null,
                'platform' => $validated['platform'] ?? null,
                'department' => $validated['department'] ?? null,
                'related_project' => $validated['related_project'] ?? null,
                'organizer_id' => $validated['organizer_id'] ?? null,
                'summary' => $validated['summary'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'is_active' => $request->boolean('is_active', true),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->syncParticipants($meetingMinute, $validated['participants'] ?? []);
            $this->syncAgendas($meetingMinute, $validated['agendas'] ?? []);
            $this->syncActionItems($meetingMinute, $validated['action_items'] ?? []);

            DB::commit();

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'MOM berhasil dibuat.',
                    'data' => [
                        'id' => $meetingMinute->id,
                        'meeting_no' => $meetingMinute->meeting_no,
                        'redirect_url' => route('operation.meeting-minutes.show', $meetingMinute),
                    ],
                ]);
            }

            return redirect()
                ->route('operation.meeting-minutes.show', $meetingMinute)
                ->with('success', 'MOM berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat MOM.',
                    'error' => app()->environment('local') ? $e->getMessage() : null,
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Gagal membuat MOM.');
        }
    }

    public function show(MeetingMinute $meetingMinute): View
    {
        $meetingMinute->load([
            'organizer',
            'creator',
            'updater',
            'participants.user',
            'agendas',
            'actionItems.picUser',
        ]);

        $stats = [
            'participants' => $meetingMinute->participants->count(),
            'agendas' => $meetingMinute->agendas->count(),
            'action_items' => $meetingMinute->actionItems->count(),
            'pending_action_items' => $meetingMinute->actionItems
                ->whereIn('status', ['pending', 'in_progress', 'blocked'])
                ->count(),
            'completed_action_items' => $meetingMinute->actionItems
                ->where('status', 'done')
                ->count(),
        ];

        return view('operation.meeting-minutes.show', compact('meetingMinute', 'stats'));
    }

    public function downloadPdf(MeetingMinute $meetingMinute)
    {
        $meetingMinute->load([
            'organizer',
            'creator',
            'updater',
            'participants.user',
            'agendas',
            'actionItems.picUser',
        ]);

        $fileName = Str::slug($meetingMinute->meeting_no ?: 'mom') . '.pdf';

        $pdf = Pdf::loadView('operation.meeting-minutes.pdf', [
            'meetingMinute' => $meetingMinute,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }

    public function edit(MeetingMinute $meetingMinute): View
    {
        $meetingMinute->load([
            'participants.user',
            'agendas',
            'actionItems.picUser',
        ]);

        $users = User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('operation.meeting-minutes.edit', compact('meetingMinute', 'users'));
    }

    public function update(Request $request, MeetingMinute $meetingMinute): JsonResponse|RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::beginTransaction();

        try {
            $meetingMinute->update([
                'title' => $validated['title'],
                'meeting_type' => $validated['meeting_type'] ?? 'internal',
                'meeting_date' => $validated['meeting_date'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'location' => $validated['location'] ?? null,
                'platform' => $validated['platform'] ?? null,
                'department' => $validated['department'] ?? null,
                'related_project' => $validated['related_project'] ?? null,
                'organizer_id' => $validated['organizer_id'] ?? null,
                'summary' => $validated['summary'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'is_active' => $request->boolean('is_active', true),
                'updated_by' => auth()->id(),
            ]);

            $this->syncParticipants($meetingMinute, $validated['participants'] ?? []);
            $this->syncAgendas($meetingMinute, $validated['agendas'] ?? []);
            $this->syncActionItems($meetingMinute, $validated['action_items'] ?? []);

            DB::commit();

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'MOM berhasil diperbarui.',
                    'data' => [
                        'id' => $meetingMinute->id,
                        'meeting_no' => $meetingMinute->meeting_no,
                        'redirect_url' => route('operation.meeting-minutes.show', $meetingMinute),
                    ],
                ]);
            }

            return redirect()
                ->route('operation.meeting-minutes.show', $meetingMinute)
                ->with('success', 'MOM berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui MOM.',
                    'error' => app()->environment('local') ? $e->getMessage() : null,
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui MOM.');
        }
    }

    public function destroy(Request $request, MeetingMinute $meetingMinute): JsonResponse|RedirectResponse
    {
        try {
            $meetingMinute->delete();

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'MOM berhasil dihapus.',
                ]);
            }

            return redirect()
                ->route('operation.meeting-minutes.index')
                ->with('success', 'MOM berhasil dihapus.');
        } catch (\Throwable $e) {
            report($e);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus MOM.',
                    'error' => app()->environment('local') ? $e->getMessage() : null,
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus MOM.');
        }
    }

    public function updateActionItemStatus(Request $request, MeetingMinuteActionItem $actionItem): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'in_progress',
                    'done',
                    'blocked',
                    'cancelled',
                ]),
            ],
            'notes' => ['nullable', 'string'],
        ]);

        $actionItem->update([
            'status' => $validated['status'],
            'notes' => array_key_exists('notes', $validated)
                ? $validated['notes']
                : $actionItem->notes,
            'completed_at' => $validated['status'] === 'done'
                ? now()
                : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status action item berhasil diperbarui.',
            'data' => [
                'id' => $actionItem->id,
                'status' => $actionItem->status,
                'completed_at' => optional($actionItem->completed_at)->format('d M Y H:i'),
            ],
        ]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],

            'meeting_type' => [
                'nullable',
                Rule::in([
                    'internal',
                    'client',
                    'vendor',
                    'academic',
                    'marketing',
                    'finance',
                    'operation',
                    'other',
                ]),
            ],

            'meeting_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],

            'location' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:255'],

            'department' => [
                'nullable',
                Rule::in([
                    'operation',
                    'academic',
                    'sales',
                    'marketing',
                    'finance',
                    'management',
                    'general_affair',
                    'other',
                ]),
            ],

            'related_project' => ['nullable', 'string', 'max:255'],
            'organizer_id' => ['nullable', 'exists:users,id'],
            'summary' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'published',
                    'completed',
                    'cancelled',
                ]),
            ],

            'is_active' => ['nullable'],

            'participants' => ['nullable', 'array'],
            'participants.*.id' => ['nullable', 'integer'],
            'participants.*.user_id' => ['nullable', 'exists:users,id'],
            'participants.*.name' => ['nullable', 'string', 'max:255'],
            'participants.*.email' => ['nullable', 'email', 'max:255'],
            'participants.*.role' => [
                'nullable',
                Rule::in([
                    'organizer',
                    'notulen',
                    'participant',
                    'guest',
                ]),
            ],
            'participants.*.attendance_status' => [
                'nullable',
                Rule::in([
                    'present',
                    'absent',
                    'late',
                    'excused',
                ]),
            ],
            'participants.*.notes' => ['nullable', 'string'],

            'agendas' => ['nullable', 'array'],
            'agendas.*.id' => ['nullable', 'integer'],
            'agendas.*.topic' => ['nullable', 'string', 'max:255'],
            'agendas.*.description' => ['nullable', 'string'],
            'agendas.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'action_items' => ['nullable', 'array'],
            'action_items.*.id' => ['nullable', 'integer'],
            'action_items.*.title' => ['nullable', 'string', 'max:255'],
            'action_items.*.description' => ['nullable', 'string'],
            'action_items.*.pic_user_id' => ['nullable', 'exists:users,id'],
            'action_items.*.pic_name' => ['nullable', 'string', 'max:255'],
            'action_items.*.priority' => [
                'nullable',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                    'urgent',
                ]),
            ],
            'action_items.*.due_date' => ['nullable', 'date'],
            'action_items.*.status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'in_progress',
                    'done',
                    'blocked',
                    'cancelled',
                ]),
            ],
            'action_items.*.completed_at' => ['nullable', 'date'],
            'action_items.*.notes' => ['nullable', 'string'],
        ]);
    }

    private function syncParticipants(MeetingMinute $meetingMinute, array $participants): void
    {
        $meetingMinute->participants()->delete();

        foreach ($participants as $participant) {
            $userId = $participant['user_id'] ?? null;
            $name = $participant['name'] ?? null;
            $email = $participant['email'] ?? null;

            if (empty($userId) && empty($name) && empty($email)) {
                continue;
            }

            MeetingMinuteParticipant::create([
                'meeting_minute_id' => $meetingMinute->id,
                'user_id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => $participant['role'] ?? 'participant',
                'attendance_status' => $participant['attendance_status'] ?? 'present',
                'notes' => $participant['notes'] ?? null,
            ]);
        }
    }

    private function syncAgendas(MeetingMinute $meetingMinute, array $agendas): void
    {
        $meetingMinute->agendas()->delete();

        foreach ($agendas as $index => $agenda) {
            if (empty($agenda['topic'])) {
                continue;
            }

            MeetingMinuteAgenda::create([
                'meeting_minute_id' => $meetingMinute->id,
                'topic' => $agenda['topic'],
                'description' => $agenda['description'] ?? null,
                'sort_order' => $agenda['sort_order'] ?? ($index + 1),
            ]);
        }
    }

    private function syncActionItems(MeetingMinute $meetingMinute, array $actionItems): void
    {
        $meetingMinute->actionItems()->delete();

        foreach ($actionItems as $actionItem) {
            if (empty($actionItem['title'])) {
                continue;
            }

            $status = $actionItem['status'] ?? 'pending';

            MeetingMinuteActionItem::create([
                'meeting_minute_id' => $meetingMinute->id,
                'title' => $actionItem['title'],
                'description' => $actionItem['description'] ?? null,
                'pic_user_id' => $actionItem['pic_user_id'] ?? null,
                'pic_name' => $actionItem['pic_name'] ?? null,
                'priority' => $actionItem['priority'] ?? 'medium',
                'due_date' => $actionItem['due_date'] ?? null,
                'status' => $status,
                'completed_at' => $status === 'done'
                    ? ($actionItem['completed_at'] ?? now())
                    : null,
                'notes' => $actionItem['notes'] ?? null,
            ]);
        }
    }

    private function generateMeetingNo(string $meetingDate): string
    {
        $date = date('Ymd', strtotime($meetingDate));
        $prefix = 'MOM-' . $date . '-';

        $lastMeeting = MeetingMinute::query()
            ->where('meeting_no', 'like', $prefix . '%')
            ->orderByDesc('meeting_no')
            ->first();

        if (! $lastMeeting) {
            return $prefix . '001';
        }

        $lastNumber = (int) Str::afterLast($lastMeeting->meeting_no, '-');

        return $prefix . str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
    }

    private function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}