<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiWorkflow;
use App\Models\User;
use App\Services\AiOffice\AcademicWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiOfficeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Academic AI Office
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view('academic.ai-office.index');
    }


    /*
    |--------------------------------------------------------------------------
    | Office State
    |--------------------------------------------------------------------------
    */

    public function state(): JsonResponse
    {
        $agents = AiAgent::query()
            ->where('division', 'academic')
            ->where('status', 'active')
            ->orderByRaw("
                CASE code
                    WHEN 'luna' THEN 1
                    WHEN 'raka' THEN 2
                    WHEN 'bagas' THEN 3
                    WHEN 'nova' THEN 4
                    ELSE 99
                END
            ")
            ->get([
                'id',
                'code',
                'name',
                'role',
                'description',
                'agent_type',
                'runtime_state',
                'avatar',
                'can_delegate',
            ]);


        $primaryAgent =
            $agents->firstWhere(
                'code',
                'luna'
            );


        return response()->json([
            'success' =>
                true,

            'office' => [
                'name' =>
                    'Academic AI Office',

                'division' =>
                    'academic',

                'status' =>
                    'active',
            ],

            'primary_agent' =>
                $primaryAgent
                    ? $this->primaryAgentPayload(
                        $primaryAgent
                    )
                    : null,

            'agents' =>
                $agents
                    ->map(
                        fn (AiAgent $agent) =>
                            $this->agentPayload(
                                $agent
                            )
                    )
                    ->values(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Store Message
    |--------------------------------------------------------------------------
    */

    public function message(
        Request $request,
        AcademicWorkflowService $academicWorkflowService
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([
                'message' => [
                    'required',
                    'string',
                    'max:5000',
                ],

                'conversation_id' => [
                    'nullable',
                    'integer',
                    'exists:ai_conversations,id',
                ],
            ]);


        $user =
            $request->user();


        /*
        |--------------------------------------------------------------------------
        | Luna
        |--------------------------------------------------------------------------
        */

        $luna = AiAgent::query()
            ->where(
                'division',
                'academic'
            )
            ->where(
                'code',
                'luna'
            )
            ->where(
                'status',
                'active'
            )
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        $result =
            DB::transaction(
                function () use (
                    $validated,
                    $user,
                    $luna,
                    $academicWorkflowService
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Conversation
                    |--------------------------------------------------------------------------
                    */

                    $conversation =
                        null;


                    if (
                        !empty(
                            $validated['conversation_id']
                        )
                    ) {

                        $conversation =
                            AiConversation::query()
                                ->where(
                                    'id',
                                    $validated['conversation_id']
                                )
                                ->where(
                                    'user_id',
                                    $user->id
                                )
                                ->where(
                                    'division',
                                    'academic'
                                )
                                ->where(
                                    'status',
                                    'active'
                                )
                                ->first();
                    }


                    if (!$conversation) {

                        $conversation =
                            AiConversation::create([
                                'user_id' =>
                                    $user->id,

                                'primary_agent_id' =>
                                    $luna->id,

                                'division' =>
                                    'academic',

                                'title' =>
                                    null,

                                'status' =>
                                    'active',

                                'last_message_at' =>
                                    now(),
                            ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | User Message
                    |--------------------------------------------------------------------------
                    */

                    $message =
                        AiMessage::create([
                            'conversation_id' =>
                                $conversation->id,

                            'agent_id' =>
                                null,

                            'user_id' =>
                                $user->id,

                            'sender_type' =>
                                'user',

                            'message_type' =>
                                'text',

                            'content' =>
                                $validated['message'],

                            'metadata' => [
                                'source' =>
                                    'academic_ai_office',
                            ],
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Create + Execute Workflow
                    |--------------------------------------------------------------------------
                    */

                    $workflow =
                        $academicWorkflowService
                            ->createForMessage(
                                $conversation,
                                $user,
                                $luna,
                                $validated['message']
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | Persist Luna Assistant Message
                    |--------------------------------------------------------------------------
                    |
                    | Untuk sekarang assistant_response tersedia untuk:
                    |
                    | - respond_directly
                    | - unsupported
                    |
                    | get_active_classes belum menghasilkan assistant_response
                    | dari AI karena Luna final response dari specialist_result
                    | adalah step roadmap berikutnya.
                    |--------------------------------------------------------------------------
                    */

                    $assistantMessage =
                        $this->persistWorkflowAssistantMessage(
                            workflow:
                                $workflow,

                            conversation:
                                $conversation,

                            user:
                                $user,

                            luna:
                                $luna
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Update Conversation
                    |--------------------------------------------------------------------------
                    */

                    $conversation->update([
                        'last_message_at' =>
                            $assistantMessage?->created_at
                            ?? $message->created_at
                            ?? now(),
                    ]);


                    return [
                        'conversation' =>
                            $conversation,

                        'message' =>
                            $message,

                        'assistant_message' =>
                            $assistantMessage,

                        'workflow' =>
                            $workflow,
                    ];
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' =>
                true,

            'conversation' => [
                'id' =>
                    $result['conversation']->id,

                'status' =>
                    $result['conversation']->status,
            ],

            'message' =>
                $this->messagePayload(
                    $result['message']
                ),

            /*
            |--------------------------------------------------------------------------
            | Luna Response
            |--------------------------------------------------------------------------
            |
            | Frontend step berikutnya cukup membaca:
            |
            | data.assistant_message.content
            |--------------------------------------------------------------------------
            */

            'assistant_message' =>
                $result['assistant_message']
                    ? $this->assistantMessagePayload(
                        $result['assistant_message']
                    )
                    : null,

            'workflow' =>
                $this->workflowPayload(
                    $result['workflow']
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Persist Workflow Assistant Message
    |--------------------------------------------------------------------------
    */

    private function persistWorkflowAssistantMessage(
        AiWorkflow $workflow,
        AiConversation $conversation,
        User $user,
        AiAgent $luna
    ): ?AiMessage {
        $context =
            $this->workflowContext(
                $workflow
            );


        /*
        |--------------------------------------------------------------------------
        | Assistant Response
        |--------------------------------------------------------------------------
        */

        $assistantResponse =
            trim(
                (string) (
                    $context['assistant_response']
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | No Assistant Response Yet
        |--------------------------------------------------------------------------
        |
        | Contoh saat ini:
        |
        | get_active_classes
        |
        | Final AI response dari specialist_result belum masuk roadmap step ini.
        |--------------------------------------------------------------------------
        */

        if (
            $assistantResponse === ''
        ) {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Resolved Action
        |--------------------------------------------------------------------------
        */

        $resolvedAction =
            (string) (
                $context['resolved_action']
                ?? 'respond_directly'
            );


        /*
        |--------------------------------------------------------------------------
        | Planner Source
        |--------------------------------------------------------------------------
        */

        $planner =
            $this->normalizeArrayValue(
                $context['planner']
                ?? []
            );


        /*
        |--------------------------------------------------------------------------
        | Save Luna Message
        |--------------------------------------------------------------------------
        */

        return AiMessage::create([
            'conversation_id' =>
                $conversation->id,

            'agent_id' =>
                $luna->id,

            /*
            |--------------------------------------------------------------------------
            | Important
            |--------------------------------------------------------------------------
            |
            | ai_messages.user_id saat ini NOT NULL.
            |
            | Jadi assistant message tetap di-link ke owner conversation.
            |--------------------------------------------------------------------------
            */

            'user_id' =>
                $user->id,

            'sender_type' =>
                'assistant',

            'message_type' =>
                'text',

            'content' =>
                $assistantResponse,

            'metadata' => [
                'source' =>
                    'luna_ai',

                'workflow_id' =>
                    $workflow->id,

                'action' =>
                    $resolvedAction,

                'planner_source' =>
                    $planner['source']
                    ?? null,

                'task_summary' =>
                    $context['task_summary']
                    ?? null,
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | User Message Payload
    |--------------------------------------------------------------------------
    */

    private function messagePayload(
        AiMessage $message
    ): array {
        return [
            'id' =>
                $message->id,

            'sender_type' =>
                $message->sender_type,

            'message_type' =>
                $message->message_type,

            'content' =>
                $message->content,

            'created_at' =>
                $message->created_at,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Assistant Message Payload
    |--------------------------------------------------------------------------
    */

    private function assistantMessagePayload(
        AiMessage $message
    ): array {
        $message->loadMissing(
            'agent'
        );


        return [
            'id' =>
                $message->id,

            'sender_type' =>
                $message->sender_type,

            'message_type' =>
                $message->message_type,

            'content' =>
                $message->content,

            'agent' =>
                $message->agent
                    ? [
                        'id' =>
                            $message->agent->id,

                        'code' =>
                            $message->agent->code,

                        'name' =>
                            $message->agent->name,

                        'role' =>
                            $message->agent->role,
                    ]
                    : null,

            'metadata' =>
                $message->metadata,

            'created_at' =>
                $message->created_at,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Workflow Payload
    |--------------------------------------------------------------------------
    */

    private function workflowPayload(
        AiWorkflow $workflow
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Ensure Relations
        |--------------------------------------------------------------------------
        */

        $workflow->loadMissing([
            'steps.agent',
        ]);


        return [
            'id' =>
                $workflow->id,

            'title' =>
                $workflow->title,

            'objective' =>
                $workflow->objective,

            'status' =>
                $workflow->status,


            /*
            |--------------------------------------------------------------------------
            | Current Workflow Owner
            |--------------------------------------------------------------------------
            */

            'active_agent' =>
                $this->resolveWorkflowActiveAgent(
                    $workflow
                ),

            'active_state' =>
                $this->resolveWorkflowActiveState(
                    $workflow
                ),


            /*
            |--------------------------------------------------------------------------
            | Specialist Execution
            |--------------------------------------------------------------------------
            */

            'execution_agent' =>
                $this->resolveExecutionAgent(
                    $workflow
                ),


            /*
            |--------------------------------------------------------------------------
            | Handoff
            |--------------------------------------------------------------------------
            */

            'handoff' =>
                $this->resolveHandoff(
                    $workflow
                ),


            /*
            |--------------------------------------------------------------------------
            | Specialist Result
            |--------------------------------------------------------------------------
            */

            'specialist_result' =>
                $this->resolveSpecialistResult(
                    $workflow
                ),


            /*
            |--------------------------------------------------------------------------
            | Workflow State
            |--------------------------------------------------------------------------
            */

            'ready_for_user' =>
                $this->resolveReadyForUser(
                    $workflow
                ),


            /*
            |--------------------------------------------------------------------------
            | Resolved Action
            |--------------------------------------------------------------------------
            */

            'resolved_action' =>
                $this->workflowContext(
                    $workflow
                )['resolved_action']
                ?? null,


            /*
            |--------------------------------------------------------------------------
            | Steps
            |--------------------------------------------------------------------------
            */

            'steps' =>
                $workflow
                    ->steps
                    ->sortBy(
                        'step_order'
                    )
                    ->values()
                    ->map(
                        function ($step) {

                            return [
                                'id' =>
                                    $step->id,

                                'step_order' =>
                                    $step->step_order,

                                'name' =>
                                    $step->name,

                                'description' =>
                                    $step->description,

                                'action' =>
                                    $step->action,

                                'status' =>
                                    $step->status,

                                'agent' =>
                                    $step->agent
                                        ? [
                                            'id' =>
                                                $step->agent->id,

                                            'code' =>
                                                $step->agent->code,

                                            'name' =>
                                                $step->agent->name,

                                            'role' =>
                                                $step->agent->role,
                                        ]
                                        : null,
                            ];
                        }
                    ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Active Agent
    |--------------------------------------------------------------------------
    */

    private function resolveWorkflowActiveAgent(
        AiWorkflow $workflow
    ): ?array {
        $context =
            $this->workflowContext(
                $workflow
            );


        /*
        |--------------------------------------------------------------------------
        | Completed Workflow
        |--------------------------------------------------------------------------
        */

        if (
            $workflow->status
            === 'completed'
        ) {

            $finalAgent =
                $this->resolveAgentFromContext(
                    $context['final_agent']
                    ?? null
                );


            if ($finalAgent) {

                return $this->workflowAgentPayload(
                    $finalAgent,
                    'completed'
                );
            }


            return $this->resolveOrchestratorPayload(
                $workflow,
                'completed'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Active Workflow Step
        |--------------------------------------------------------------------------
        */

        $activeStep =
            $workflow->steps
                ->first(
                    function ($step) {

                        return in_array(
                            $step->status,
                            [
                                'running',
                                'waiting',
                                'waiting_approval',
                            ],
                            true
                        );
                    }
                );


        if (
            $activeStep
            &&
            $activeStep->agent
        ) {

            return $this->workflowAgentPayload(
                $activeStep->agent,
                $this->resolveStepVisualState(
                    $activeStep->status
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Failed Workflow
        |--------------------------------------------------------------------------
        */

        if (
            $workflow->status
            === 'failed'
        ) {

            $failedStep =
                $workflow->steps
                    ->where(
                        'status',
                        'failed'
                    )
                    ->sortByDesc(
                        'step_order'
                    )
                    ->first();


            if (
                $failedStep
                &&
                $failedStep->agent
            ) {

                return $this->workflowAgentPayload(
                    $failedStep->agent,
                    'error'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Fallback Orchestrator
        |--------------------------------------------------------------------------
        */

        return $this->resolveOrchestratorPayload(
            $workflow,
            $this->resolveWorkflowActiveState(
                $workflow
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Active State
    |--------------------------------------------------------------------------
    */

    private function resolveWorkflowActiveState(
        AiWorkflow $workflow
    ): string {
        return match (
            $workflow->status
        ) {
            'running' =>
                'working',

            'waiting' =>
                'waiting',

            'waiting_approval' =>
                'waiting_approval',

            'completed' =>
                'completed',

            'failed' =>
                'error',

            default =>
                'thinking',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Step Visual State
    |--------------------------------------------------------------------------
    */

    private function resolveStepVisualState(
        string $status
    ): string {
        return match (
            $status
        ) {
            'running' =>
                'working',

            'waiting' =>
                'waiting',

            'waiting_approval' =>
                'waiting_approval',

            'completed' =>
                'completed',

            'failed' =>
                'error',

            default =>
                'thinking',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Execution Agent
    |--------------------------------------------------------------------------
    */

    private function resolveExecutionAgent(
        AiWorkflow $workflow
    ): ?array {
        $context =
            $this->workflowContext(
                $workflow
            );


        /*
        |--------------------------------------------------------------------------
        | Context Agent
        |--------------------------------------------------------------------------
        */

        $agent =
            $this->resolveAgentFromContext(
                $context['execution_agent']
                ?? null
            );


        $executionStep =
            null;


        /*
        |--------------------------------------------------------------------------
        | Fallback Specialist Step
        |--------------------------------------------------------------------------
        */

        if (!$agent) {

            $executionStep =
                $workflow->steps
                    ->first(
                        function ($step) use ($workflow) {

                            return
                                $step->agent_id
                                    !== null

                                &&
                                $step->agent_id
                                    !== $workflow->orchestrator_agent_id

                                &&
                                !in_array(
                                    $step->action,
                                    [
                                        'return_result_to_orchestrator',
                                        'not_required',
                                        'not_available',
                                    ],
                                    true
                                );
                        }
                    );


            $agent =
                $executionStep?->agent;
        }


        if (!$agent) {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Resolve Execution Step
        |--------------------------------------------------------------------------
        */

        if (!$executionStep) {

            $executionStep =
                $workflow->steps
                    ->first(
                        function ($step) use ($agent) {

                            return
                                $step->agent_id
                                    === $agent->id

                                &&
                                !in_array(
                                    $step->action,
                                    [
                                        'return_result_to_orchestrator',
                                        'not_required',
                                        'not_available',
                                    ],
                                    true
                                );
                        }
                    );
        }


        $state =
            $executionStep
                ? $this->resolveStepVisualState(
                    $executionStep->status
                )
                : 'idle';


        $payload =
            $this->workflowAgentPayload(
                $agent,
                $state
            );


        $payload['action'] =
            $executionStep?->action;


        $payload['step_id'] =
            $executionStep?->id;


        $payload['step_status'] =
            $executionStep?->status;


        return $payload;
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Handoff
    |--------------------------------------------------------------------------
    */

    private function resolveHandoff(
        AiWorkflow $workflow
    ): ?array {
        $context =
            $this->workflowContext(
                $workflow
            );


        $handoff =
            $context['handoff']
            ?? null;


        if (
            is_array(
                $handoff
            )
        ) {

            return [
                'from' =>
                    $handoff['from']
                    ?? null,

                'to' =>
                    $handoff['to']
                    ?? null,

                'action' =>
                    $handoff['action']
                    ?? null,

                'message' =>
                    $handoff['message']
                    ?? null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Delegate Step Fallback
        |--------------------------------------------------------------------------
        */

        $delegateStep =
            $workflow->steps
                ->firstWhere(
                    'action',
                    'delegate_to_agent'
                );


        $output =
            $this->normalizeArrayValue(
                $delegateStep?->output
            );


        if (!$output) {

            return null;
        }


        return [
            'from' =>
                $output['from']
                ?? null,

            'to' =>
                $output['to']
                ?? null,

            'action' =>
                $output['action']
                ?? null,

            'message' =>
                $output['message']
                ?? null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Specialist Result
    |--------------------------------------------------------------------------
    */

    private function resolveSpecialistResult(
        AiWorkflow $workflow
    ): ?array {
        $executionStep =
            $workflow->steps
                ->first(
                    function ($step) use ($workflow) {

                        return
                            $step->agent_id
                                !== null

                            &&
                            $step->agent_id
                                !== $workflow->orchestrator_agent_id

                            &&
                            !in_array(
                                $step->action,
                                [
                                    'return_result_to_orchestrator',
                                    'not_required',
                                    'not_available',
                                ],
                                true
                            );
                    }
                );


        if (!$executionStep) {

            return null;
        }


        $output =
            $this->normalizeArrayValue(
                $executionStep->output
            );


        return [
            'step_id' =>
                $executionStep->id,

            'agent' =>
                $executionStep->agent
                    ? [
                        'id' =>
                            $executionStep->agent->id,

                        'code' =>
                            $executionStep->agent->code,

                        'name' =>
                            $executionStep->agent->name,

                        'role' =>
                            $executionStep->agent->role,
                    ]
                    : null,

            'action' =>
                $executionStep->action,

            'status' =>
                $executionStep->status,

            'result' =>
                $output,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Ready For User
    |--------------------------------------------------------------------------
    */

    private function resolveReadyForUser(
        AiWorkflow $workflow
    ): bool {
        $context =
            $this->workflowContext(
                $workflow
            );


        if (
            array_key_exists(
                'ready_for_user',
                $context
            )
        ) {

            return (bool)
                $context['ready_for_user'];
        }


        return $workflow->status
            === 'completed';
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Orchestrator
    |--------------------------------------------------------------------------
    */

    private function resolveOrchestratorPayload(
        AiWorkflow $workflow,
        string $state
    ): ?array {
        $agent =
            AiAgent::query()
                ->where(
                    'id',
                    $workflow->orchestrator_agent_id
                )
                ->where(
                    'status',
                    'active'
                )
                ->first();


        if (!$agent) {

            return null;
        }


        return $this->workflowAgentPayload(
            $agent,
            $state
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Agent From Context
    |--------------------------------------------------------------------------
    */

    private function resolveAgentFromContext(
        mixed $agentContext
    ): ?AiAgent {
        if (
            !is_array(
                $agentContext
            )
        ) {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | ID
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $agentContext['id']
            )
        ) {

            $agent =
                AiAgent::query()
                    ->where(
                        'id',
                        $agentContext['id']
                    )
                    ->first();


            if ($agent) {

                return $agent;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Code
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $agentContext['code']
            )
        ) {

            return AiAgent::query()
                ->where(
                    'code',
                    $agentContext['code']
                )
                ->first();
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Workflow Agent Payload
    |--------------------------------------------------------------------------
    */

    private function workflowAgentPayload(
        AiAgent $agent,
        string $state
    ): array {
        return [
            'id' =>
                $agent->id,

            'code' =>
                $agent->code,

            'name' =>
                $agent->name,

            'role' =>
                $agent->role,

            'runtime_state' =>
                $agent->runtime_state,

            'state' =>
                $state,

            'avatar_url' =>
                $agent->avatarForState(
                    $state
                ),

            'avatars' =>
                $this->agentAvatarStates(
                    $agent
                ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Primary Agent Payload
    |--------------------------------------------------------------------------
    */

    private function primaryAgentPayload(
        AiAgent $agent
    ): array {
        return [
            'id' =>
                $agent->id,

            'code' =>
                $agent->code,

            'name' =>
                $agent->name,

            'role' =>
                $agent->role,

            'runtime_state' =>
                $agent->runtime_state,

            'avatar_url' =>
                $agent->avatarForState(),

            'avatars' =>
                $this->agentAvatarStates(
                    $agent
                ),

            'can_delegate' =>
                $agent->can_delegate,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Agent Payload
    |--------------------------------------------------------------------------
    */

    private function agentPayload(
        AiAgent $agent
    ): array {
        return [
            'id' =>
                $agent->id,

            'code' =>
                $agent->code,

            'name' =>
                $agent->name,

            'role' =>
                $agent->role,

            'description' =>
                $agent->description,

            'agent_type' =>
                $agent->agent_type,

            'runtime_state' =>
                $agent->runtime_state,

            'avatar_url' =>
                $agent->avatarForState(),

            'can_delegate' =>
                $agent->can_delegate,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Agent Avatar States
    |--------------------------------------------------------------------------
    */

    private function agentAvatarStates(
        AiAgent $agent
    ): array {
        return [
            'idle' =>
                $agent->avatarForState(
                    'idle'
                ),

            'greeting' =>
                $agent->avatarForState(
                    'greeting'
                ),

            'listening' =>
                $agent->avatarForState(
                    'listening'
                ),

            'thinking' =>
                $agent->avatarForState(
                    'thinking'
                ),

            'working' =>
                $agent->avatarForState(
                    'working'
                ),

            'waiting' =>
                $agent->avatarForState(
                    'waiting'
                ),

            'waiting_approval' =>
                $agent->avatarForState(
                    'waiting_approval'
                ),

            'completed' =>
                $agent->avatarForState(
                    'completed'
                ),

            'questioning' =>
                $agent->avatarForState(
                    'questioning'
                ),

            'explaining' =>
                $agent->avatarForState(
                    'explaining'
                ),

            'error' =>
                $agent->avatarForState(
                    'error'
                ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Workflow Context
    |--------------------------------------------------------------------------
    */

    private function workflowContext(
        AiWorkflow $workflow
    ): array {
        return $this->normalizeArrayValue(
            $workflow->context
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Array Value
    |--------------------------------------------------------------------------
    */

    private function normalizeArrayValue(
        mixed $value
    ): array {
        if (
            is_array(
                $value
            )
        ) {

            return $value;
        }


        if (
            is_object(
                $value
            )
        ) {

            return (array)
                $value;
        }


        if (
            is_string(
                $value
            )
            &&
            $value !== ''
        ) {

            $decoded =
                json_decode(
                    $value,
                    true
                );


            return is_array(
                $decoded
            )
                ? $decoded
                : [];
        }


        return [];
    }
}