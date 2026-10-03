<?php

namespace App\Services\AiOffice;

use App\Models\AiAgent;
use App\Models\AiConversation;
use App\Models\AiWorkflow;
use App\Models\AiWorkflowStep;
use App\Models\User;
use App\Services\AiOffice\Agents\RakaAgentService;
use App\Services\AiOffice\Conversation\ConversationContextService;
use App\Services\AiOffice\Orchestration\LunaOrchestratorService;
use App\Services\AiOffice\Responses\LunaResponseService;
use Throwable;

class AcademicWorkflowService
{
    /*
    |--------------------------------------------------------------------------
    | Conversation History Limit
    |--------------------------------------------------------------------------
    */

    private const CONVERSATION_HISTORY_LIMIT = 12;


    public function __construct(
        private readonly RakaAgentService $rakaAgentService,
        private readonly LunaOrchestratorService $lunaOrchestratorService,
        private readonly LunaResponseService $lunaResponseService,
        private readonly ConversationContextService $conversationContextService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Create Workflow
    |--------------------------------------------------------------------------
    */

    public function createForMessage(
        AiConversation $conversation,
        User $user,
        AiAgent $orchestrator,
        string $message
    ): AiWorkflow {
        /*
        |--------------------------------------------------------------------------
        | Conversation History
        |--------------------------------------------------------------------------
        |
        | Current user message sudah disimpan controller sebelum workflow dibuat.
        |
        | History yang dikirim ke AI harus berisi percakapan SEBELUM
        | current message agar current message tidak masuk dua kali.
        |--------------------------------------------------------------------------
        */

        $history =
            $this->resolveConversationHistory(
                $conversation,
                $message
            );


        /*
        |--------------------------------------------------------------------------
        | Luna AI Planning
        |--------------------------------------------------------------------------
        */

        $planning =
            $this->resolvePlan(
                message:
                    $message,

                history:
                    $history
            );


        $plan =
            $planning['plan'];


        /*
        |--------------------------------------------------------------------------
        | Create Workflow
        |--------------------------------------------------------------------------
        */

        $workflow =
            AiWorkflow::create([
                'conversation_id' =>
                    $conversation->id,

                'requested_by_user_id' =>
                    $user->id,

                'orchestrator_agent_id' =>
                    $orchestrator->id,

                'division' =>
                    'academic',

                'title' =>
                    $this->buildWorkflowTitle(
                        $message
                    ),

                'objective' =>
                    $message,

                'status' =>
                    'pending',

                'context' => [
                    'source' =>
                        'user_message',

                    'original_message' =>
                        $message,

                    'orchestrator' => [
                        'id' =>
                            $orchestrator->id,

                        'code' =>
                            $orchestrator->code,

                        'name' =>
                            $orchestrator->name,
                    ],

                    /*
                    |--------------------------------------------------------------------------
                    | Luna AI Plan
                    |--------------------------------------------------------------------------
                    */

                    'plan' =>
                        $plan,

                    'planner' => [
                        'source' =>
                            $planning['source'],

                        'error' =>
                            $planning['error'],

                        'history_count' =>
                            count(
                                $history
                            ),
                    ],
                ],

                'started_at' =>
                    null,

                'completed_at' =>
                    null,
            ]);


        /*
        |--------------------------------------------------------------------------
        | Default Steps
        |--------------------------------------------------------------------------
        */

        $this->createDefaultSteps(
            $workflow,
            $orchestrator
        );


        /*
        |--------------------------------------------------------------------------
        | Execute Plan
        |--------------------------------------------------------------------------
        */

        match (
            $plan['action']
        ) {

            'respond_directly' =>
                $this->executeDirectConversationWorkflow(
                    workflow:
                        $workflow,

                    user:
                        $user,

                    orchestrator:
                        $orchestrator,

                    plan:
                        $plan,

                    history:
                        $history
                ),

            'get_active_classes' =>
                $this->executeGetActiveClassesWorkflow(
                    $workflow,
                    $orchestrator,
                    $plan
                ),

            'unsupported' =>
                $this->executeUnsupportedWorkflow(
                    workflow:
                        $workflow,

                    user:
                        $user,

                    orchestrator:
                        $orchestrator,

                    plan:
                        $plan,

                    history:
                        $history
                ),

            default =>
                $this->executeUnsupportedWorkflow(
                    workflow:
                        $workflow,

                    user:
                        $user,

                    orchestrator:
                        $orchestrator,

                    plan:
                        $plan,

                    history:
                        $history
                ),
        };


        /*
        |--------------------------------------------------------------------------
        | Reload Workflow
        |--------------------------------------------------------------------------
        */

        return $workflow->fresh([
            'steps.agent',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Conversation History
    |--------------------------------------------------------------------------
    |
    | Controller sudah menyimpan current user message.
    |
    | Strategy:
    |
    | 1. Ambil recent messages.
    | 2. Kalau message terakhir adalah current message yang sama,
    |    keluarkan.
    | 3. Sisakan maksimal 12 message sebelumnya.
    |--------------------------------------------------------------------------
    */

    private function resolveConversationHistory(
        AiConversation $conversation,
        string $currentMessage
    ): array {
        $history =
            $this
                ->conversationContextService
                ->recentMessages(
                    $conversation,
                    self::CONVERSATION_HISTORY_LIMIT
                    + 1
                );


        if (
            empty(
                $history
            )
        ) {

            return [];
        }


        /*
        |--------------------------------------------------------------------------
        | Remove Current User Message
        |--------------------------------------------------------------------------
        */

        $lastMessage =
            end(
                $history
            );


        if (
            is_array(
                $lastMessage
            )
            &&
            ($lastMessage['role'] ?? null)
                === 'user'
            &&
            trim(
                (string) (
                    $lastMessage['content']
                    ?? ''
                )
            )
                === trim(
                    $currentMessage
                )
        ) {

            array_pop(
                $history
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Final Limit
        |--------------------------------------------------------------------------
        */

        return array_slice(
            $history,
            -self::CONVERSATION_HISTORY_LIMIT
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Plan
    |--------------------------------------------------------------------------
    */

    private function resolvePlan(
        string $message,
        array $history = []
    ): array {
        try {

            $plan =
                $this
                    ->lunaOrchestratorService
                    ->plan(
                        $message,
                        $history
                    );


            return [
                'source' =>
                    'ai',

                'plan' =>
                    $plan,

                'error' =>
                    null,
            ];

        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Safe Fallback
            |--------------------------------------------------------------------------
            */

            return [
                'source' =>
                    'fallback',

                'plan' =>
                    $this->fallbackPlan(
                        $message
                    ),

                'error' =>
                    $exception->getMessage(),
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Fallback Plan
    |--------------------------------------------------------------------------
    */

    private function fallbackPlan(
        string $message
    ): array {
        $normalizedMessage =
            mb_strtolower(
                trim(
                    $message
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Direct Conversation
        |--------------------------------------------------------------------------
        */

        $conversationKeywords = [
            'halo',
            'hai',
            'hello',
            'makasih',
            'terima kasih',
            'thank you',
            'thanks',
            'kamu siapa',
            'bisa bantu apa',
            'bisa bantu apa aja',
            'tugas kamu apa',
        ];


        foreach (
            $conversationKeywords
            as $keyword
        ) {

            if (
                str_contains(
                    $normalizedMessage,
                    $keyword
                )
            ) {

                return [
                    'task_summary' =>
                        'Berinteraksi langsung dengan Luna.',

                    'agent' =>
                        'luna',

                    'action' =>
                        'respond_directly',

                    'requires_approval' =>
                        false,

                    'handoff_message' =>
                        '',
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Active Classes
        |--------------------------------------------------------------------------
        */

        $activeClassKeywords = [
            'kelas aktif',
            'kelas yang aktif',
            'kelas masih jalan',
            'kelas yang masih jalan',
            'kelas sedang berjalan',

            'batch aktif',
            'batch yang aktif',
            'batch masih jalan',
            'batch yang masih jalan',
            'batch sedang berjalan',

            'active class',
            'active classes',
            'active batch',
            'ongoing class',
            'ongoing classes',
        ];


        foreach (
            $activeClassKeywords
            as $keyword
        ) {

            if (
                str_contains(
                    $normalizedMessage,
                    $keyword
                )
            ) {

                return [
                    'task_summary' =>
                        'Mengecek kelas yang sedang aktif.',

                    'agent' =>
                        'raka',

                    'action' =>
                        'get_active_classes',

                    'requires_approval' =>
                        false,

                    'handoff_message' =>
                        'Sebentar, saya akan meminta Raka untuk mengecek kelas yang sedang aktif.',
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Unsupported
        |--------------------------------------------------------------------------
        */

        return [
            'task_summary' =>
                $this->buildWorkflowTitle(
                    $message
                ),

            'agent' =>
                'luna',

            'action' =>
                'unsupported',

            'requires_approval' =>
                false,

            'handoff_message' =>
                '',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Execute Direct Conversation
    |--------------------------------------------------------------------------
    */

    private function executeDirectConversationWorkflow(
        AiWorkflow $workflow,
        User $user,
        AiAgent $orchestrator,
        array $plan,
        array $history = []
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Start Workflow
        |--------------------------------------------------------------------------
        */

        $workflow->update([
            'status' =>
                'running',

            'started_at' =>
                now(),

            'context' =>
                array_merge(
                    $workflow->context
                    ?? [],

                    [
                        'resolved_action' =>
                            'respond_directly',

                        'task_summary' =>
                            $plan['task_summary'],

                        'requires_approval' =>
                            false,

                        'response_history_count' =>
                            count(
                                $history
                            ),
                    ]
                ),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Step 2 - Understand Request
        |--------------------------------------------------------------------------
        */

        $understandStep =
            $this->getStep(
                $workflow,
                2
            );


        if (
            $understandStep
        ) {

            $understandStep->update([
                'status' =>
                    'completed',

                'input' => [
                    'objective' =>
                        $workflow->objective,

                    'history_count' =>
                        count(
                            $history
                        ),
                ],

                'output' => [
                    'task_summary' =>
                        $plan['task_summary'],

                    'resolved_action' =>
                        'respond_directly',

                    'requires_specialist' =>
                        false,

                    'requires_approval' =>
                        false,
                ],

                'started_at' =>
                    now(),

                'completed_at' =>
                    now(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Step 3 - Direct Response
        |--------------------------------------------------------------------------
        */

        $responseStep =
            $this->getStep(
                $workflow,
                3
            );


        if (
            $responseStep
        ) {

            $responseStep->update([
                'agent_id' =>
                    $orchestrator->id,

                'name' =>
                    'Respond Directly',

                'description' =>
                    'Luna responds directly to the user without delegating the request.',

                'action' =>
                    'respond_directly',

                'status' =>
                    'running',

                'input' => [
                    'message' =>
                        $workflow->objective,

                    'task_summary' =>
                        $plan['task_summary'],

                    'history_count' =>
                        count(
                            $history
                        ),
                ],

                'started_at' =>
                    now(),

                'completed_at' =>
                    null,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate Contextual Luna Response
        |--------------------------------------------------------------------------
        */

        try {

            $assistantResponse =
                $this
                    ->lunaResponseService
                    ->respondDirectly(
                        $workflow->objective,
                        [
                            'display_name' =>
                                $this->resolveUserDisplayName(
                                    $user
                                ),

                            'task_summary' =>
                                $plan['task_summary'],
                        ],
                        $history
                    );


            /*
            |--------------------------------------------------------------------------
            | Complete Response Step
            |--------------------------------------------------------------------------
            */

            if (
                $responseStep
            ) {

                $responseStep->update([
                    'status' =>
                        'completed',

                    'output' => [
                        'assistant_response' =>
                            $assistantResponse,

                        'history_count' =>
                            count(
                                $history
                            ),
                    ],

                    'completed_at' =>
                        now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Steps 4 & 5 - Specialist Not Required
            |--------------------------------------------------------------------------
            */

            foreach (
                [
                    4,
                    5,
                ]
                as $stepOrder
            ) {

                $step =
                    $this->getStep(
                        $workflow,
                        $stepOrder
                    );


                if (
                    $step
                ) {

                    $step->update([
                        'agent_id' =>
                            null,

                        'name' =>
                            $stepOrder === 4
                                ? 'Specialist Not Required'
                                : 'No Specialist Result',

                        'description' =>
                            'This conversation does not require specialist execution.',

                        'action' =>
                            'not_required',

                        'status' =>
                            'completed',

                        'output' => [
                            'skipped' =>
                                true,

                            'reason' =>
                                'direct_conversation',
                        ],

                        'started_at' =>
                            now(),

                        'completed_at' =>
                            now(),
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Step 6 - Prepare Response
            |--------------------------------------------------------------------------
            */

            $reviewStep =
                $this->getStep(
                    $workflow,
                    6
                );


            if (
                $reviewStep
            ) {

                $reviewStep->update([
                    'agent_id' =>
                        $orchestrator->id,

                    'name' =>
                        'Prepare Response',

                    'description' =>
                        'Luna prepares the conversational response for the user.',

                    'action' =>
                        'prepare_direct_response',

                    'status' =>
                        'completed',

                    'input' => [
                        'source_step_id' =>
                            $responseStep?->id,

                        'history_count' =>
                            count(
                                $history
                            ),
                    ],

                    'output' => [
                        'assistant_response' =>
                            $assistantResponse,

                        'ready_for_user' =>
                            true,
                    ],

                    'started_at' =>
                        now(),

                    'completed_at' =>
                        now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Step 7 - Completed
            |--------------------------------------------------------------------------
            */

            $completedStep =
                $this->getStep(
                    $workflow,
                    7
                );


            if (
                $completedStep
            ) {

                $completedStep->update([
                    'agent_id' =>
                        $orchestrator->id,

                    'name' =>
                        'Completed',

                    'description' =>
                        'Luna response is ready to be presented to the user.',

                    'action' =>
                        'complete_direct_response',

                    'status' =>
                        'completed',

                    'output' => [
                        'ready_for_user' =>
                            true,
                    ],

                    'started_at' =>
                        now(),

                    'completed_at' =>
                        now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Workflow Completed
            |--------------------------------------------------------------------------
            */

            $workflow->update([
                'status' =>
                    'completed',

                'completed_at' =>
                    now(),

                'context' =>
                    array_merge(
                        $workflow->context
                        ?? [],

                        [
                            'resolved_action' =>
                                'respond_directly',

                            'task_summary' =>
                                $plan['task_summary'],

                            'assistant_response' =>
                                $assistantResponse,

                            'response_history_count' =>
                                count(
                                    $history
                                ),

                            'final_agent' => [
                                'id' =>
                                    $orchestrator->id,

                                'code' =>
                                    $orchestrator->code,

                                'name' =>
                                    $orchestrator->name,

                                'role' =>
                                    $orchestrator->role,
                            ],

                            'ready_for_user' =>
                                true,
                        ]
                    ),
            ]);

        } catch (
            Throwable $exception
        ) {

            if (
                $responseStep
            ) {

                $responseStep->update([
                    'status' =>
                        'failed',

                    'error_message' =>
                        $exception->getMessage(),

                    'completed_at' =>
                        now(),
                ]);
            }


            $this->failWorkflow(
                $workflow,
                $exception->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Execute Get Active Classes Workflow
    |--------------------------------------------------------------------------
    */

    private function executeGetActiveClassesWorkflow(
        AiWorkflow $workflow,
        AiAgent $orchestrator,
        array $plan
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Raka
        |--------------------------------------------------------------------------
        */

        $raka =
            AiAgent::query()
                ->where(
                    'division',
                    'academic'
                )
                ->where(
                    'code',
                    'raka'
                )
                ->where(
                    'status',
                    'active'
                )
                ->first();


        if (
            !$raka
        ) {

            $this->failWorkflow(
                $workflow,
                'Raka agent is not available.',
                3
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Plan
        |--------------------------------------------------------------------------
        */

        if (
            ($plan['agent'] ?? null)
            !== 'raka'
        ) {

            $this->failWorkflow(
                $workflow,
                'Luna AI Planner menghasilkan agent yang tidak sesuai untuk get_active_classes.',
                2
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Start Workflow
        |--------------------------------------------------------------------------
        */

        $workflow->update([
            'status' =>
                'running',

            'started_at' =>
                $workflow->started_at
                ?? now(),

            'context' =>
                array_merge(
                    $workflow->context
                    ?? [],

                    [
                        'resolved_action' =>
                            'get_active_classes',

                        'task_summary' =>
                            $plan['task_summary'],

                        'requires_approval' =>
                            $plan['requires_approval'],

                        'execution_agent' => [
                            'id' =>
                                $raka->id,

                            'code' =>
                                $raka->code,

                            'name' =>
                                $raka->name,

                            'role' =>
                                $raka->role,
                        ],
                    ]
                ),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Step 2 - Understand
        |--------------------------------------------------------------------------
        */

        $understandStep =
            $this->getStep(
                $workflow,
                2
            );


        if (
            $understandStep
        ) {

            $understandStep->update([
                'status' =>
                    'completed',

                'input' => [
                    'objective' =>
                        $workflow->objective,
                ],

                'output' => [
                    'task_summary' =>
                        $plan['task_summary'],

                    'resolved_action' =>
                        $plan['action'],

                    'requires_specialist' =>
                        true,

                    'requires_approval' =>
                        $plan['requires_approval'],

                    'specialist' => [
                        'id' =>
                            $raka->id,

                        'code' =>
                            $raka->code,

                        'name' =>
                            $raka->name,

                        'role' =>
                            $raka->role,
                    ],
                ],

                'started_at' =>
                    now(),

                'completed_at' =>
                    now(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Step 3 - Delegate
        |--------------------------------------------------------------------------
        */

        $delegateStep =
            $this->getStep(
                $workflow,
                3
            );


        if (
            !$delegateStep
        ) {

            $this->failWorkflow(
                $workflow,
                'Delegate workflow step was not found.'
            );

            return;
        }


        $handoffMessage =
            trim(
                (string) (
                    $plan['handoff_message']
                    ?? ''
                )
            );


        if (
            $handoffMessage === ''
        ) {

            $handoffMessage =
                'Sebentar, saya akan meminta Raka untuk mengecek kelas yang sedang aktif.';
        }


        $handoff = [
            'from' => [
                'id' =>
                    $orchestrator->id,

                'code' =>
                    $orchestrator->code,

                'name' =>
                    $orchestrator->name,

                'role' =>
                    $orchestrator->role,
            ],

            'to' => [
                'id' =>
                    $raka->id,

                'code' =>
                    $raka->code,

                'name' =>
                    $raka->name,

                'role' =>
                    $raka->role,
            ],

            'action' =>
                $plan['action'],

            'message' =>
                $handoffMessage,
        ];


        $delegateStep->update([
            'agent_id' =>
                $orchestrator->id,

            'name' =>
                'Delegate to Raka',

            'description' =>
                'Luna delegates the academic operation to Raka.',

            'action' =>
                'delegate_to_agent',

            'status' =>
                'completed',

            'input' => [
                'action' =>
                    $plan['action'],

                'target_agent' =>
                    $plan['agent'],

                'task_summary' =>
                    $plan['task_summary'],
            ],

            'output' =>
                $handoff,

            'started_at' =>
                now(),

            'completed_at' =>
                now(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Save Handoff
        |--------------------------------------------------------------------------
        */

        $workflow->update([
            'context' =>
                array_merge(
                    $workflow->context
                    ?? [],

                    [
                        'handoff' =>
                            $handoff,
                    ]
                ),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Step 4 - Raka Executes
        |--------------------------------------------------------------------------
        */

        $executionStep =
            $this->getStep(
                $workflow,
                4
            );


        if (
            !$executionStep
        ) {

            $this->failWorkflow(
                $workflow,
                'Raka execution workflow step was not found.'
            );

            return;
        }


        $executionStep->update([
            'agent_id' =>
                $raka->id,

            'name' =>
                'Check Active Classes',

            'description' =>
                'Raka checks active academic classes in FlexOps.',

            'action' =>
                'get_active_classes',

            'status' =>
                'running',

            'input' => [
                'status' =>
                    'ongoing',
            ],

            'started_at' =>
                now(),

            'completed_at' =>
                null,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Execute Raka
        |--------------------------------------------------------------------------
        */

        try {

            $result =
                $this
                    ->rakaAgentService
                    ->handleTask(
                        'get_active_classes',
                        [
                            'status' =>
                                'ongoing',
                        ]
                    );


            if (
                !(
                    $result['success']
                    ?? false
                )
            ) {

                $executionStep->update([
                    'status' =>
                        'failed',

                    'output' =>
                        $result,

                    'error_message' =>
                        'Raka failed to retrieve active classes.',

                    'completed_at' =>
                        now(),
                ]);


                $this->failWorkflow(
                    $workflow,
                    'Raka failed to retrieve active classes.'
                );


                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Step 4 Completed
            |--------------------------------------------------------------------------
            */

            $executionStep->update([
                'status' =>
                    'completed',

                'output' =>
                    $result,

                'completed_at' =>
                    now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Step 5 - Return Result
            |--------------------------------------------------------------------------
            */

            $returnStep =
                $this->getStep(
                    $workflow,
                    5
                );


            if (
                $returnStep
            ) {

                $returnStep->update([
                    'agent_id' =>
                        $raka->id,

                    'name' =>
                        'Return Result to Luna',

                    'description' =>
                        'Raka returns the active class data to Luna for review.',

                    'action' =>
                        'return_result_to_orchestrator',

                    'status' =>
                        'completed',

                    'input' => [
                        'source_step_id' =>
                            $executionStep->id,

                        'action' =>
                            'get_active_classes',
                    ],

                    'output' => [
                        'returned_to' => [
                            'id' =>
                                $orchestrator->id,

                            'code' =>
                                $orchestrator->code,

                            'name' =>
                                $orchestrator->name,
                        ],

                        'result' =>
                            $result,

                        'message' =>
                            'Pengecekan kelas aktif telah selesai dan hasilnya dikirim kembali ke Luna.',
                    ],

                    'started_at' =>
                        now(),

                    'completed_at' =>
                        now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Step 6 - Luna Reviews
            |--------------------------------------------------------------------------
            |
            | Masih deterministic untuk sekarang.
            |--------------------------------------------------------------------------
            */

            $reviewStep =
                $this->getStep(
                    $workflow,
                    6
                );


            if (
                $reviewStep
            ) {

                $reviewStep->update([
                    'agent_id' =>
                        $orchestrator->id,

                    'name' =>
                        'Review Raka Result',

                    'description' =>
                        'Luna reviews the result returned by Raka and prepares it for the user.',

                    'action' =>
                        'review_specialist_result',

                    'status' =>
                        'completed',

                    'input' => [
                        'specialist' =>
                            'raka',

                        'action' =>
                            'get_active_classes',

                        'source_step_id' =>
                            $executionStep->id,
                    ],

                    'output' => [
                        'result_ready' =>
                            true,

                        'summary' => [
                            'active_classes_count' =>
                                $result['meta']['count']
                                ?? 0,
                        ],

                        'next_action' =>
                            'explain_to_user',
                    ],

                    'started_at' =>
                        now(),

                    'completed_at' =>
                        now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Step 7 - Completed
            |--------------------------------------------------------------------------
            */

            $completedStep =
                $this->getStep(
                    $workflow,
                    7
                );


            if (
                $completedStep
            ) {

                $completedStep->update([
                    'agent_id' =>
                        $orchestrator->id,

                    'name' =>
                        'Completed',

                    'description' =>
                        'Luna has reviewed the specialist result and the workflow is ready to be presented to the user.',

                    'action' =>
                        'complete_workflow',

                    'status' =>
                        'completed',

                    'input' => [
                        'review_step_id' =>
                            $reviewStep?->id,
                    ],

                    'output' => [
                        'active_classes_count' =>
                            $result['meta']['count']
                            ?? 0,

                        'final_agent' => [
                            'id' =>
                                $orchestrator->id,

                            'code' =>
                                $orchestrator->code,

                            'name' =>
                                $orchestrator->name,
                        ],

                        'ready_for_user' =>
                            true,
                    ],

                    'started_at' =>
                        now(),

                    'completed_at' =>
                        now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Workflow Completed
            |--------------------------------------------------------------------------
            */

            $workflow->update([
                'status' =>
                    'completed',

                'completed_at' =>
                    now(),

                'context' =>
                    array_merge(
                        $workflow->context
                        ?? [],

                        [
                            'resolved_action' =>
                                $plan['action'],

                            'task_summary' =>
                                $plan['task_summary'],

                            'requires_approval' =>
                                $plan['requires_approval'],

                            'execution_agent' => [
                                'id' =>
                                    $raka->id,

                                'code' =>
                                    $raka->code,

                                'name' =>
                                    $raka->name,

                                'role' =>
                                    $raka->role,
                            ],

                            'handoff' =>
                                $handoff,

                            'result_summary' => [
                                'active_classes_count' =>
                                    $result['meta']['count']
                                    ?? 0,
                            ],

                            'final_agent' => [
                                'id' =>
                                    $orchestrator->id,

                                'code' =>
                                    $orchestrator->code,

                                'name' =>
                                    $orchestrator->name,

                                'role' =>
                                    $orchestrator->role,
                            ],

                            'ready_for_user' =>
                                true,
                        ]
                    ),
            ]);

        } catch (
            Throwable $exception
        ) {

            $executionStep->update([
                'status' =>
                    'failed',

                'error_message' =>
                    $exception->getMessage(),

                'completed_at' =>
                    now(),
            ]);


            $this->failWorkflow(
                $workflow,
                $exception->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Execute Unsupported Workflow
    |--------------------------------------------------------------------------
    */

    private function executeUnsupportedWorkflow(
        AiWorkflow $workflow,
        User $user,
        AiAgent $orchestrator,
        array $plan,
        array $history = []
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Start Workflow
        |--------------------------------------------------------------------------
        */

        $workflow->update([
            'status' =>
                'running',

            'started_at' =>
                now(),

            'context' =>
                array_merge(
                    $workflow->context
                    ?? [],

                    [
                        'resolved_action' =>
                            'unsupported',

                        'task_summary' =>
                            $plan['task_summary'],

                        'unsupported' =>
                            true,

                        'response_history_count' =>
                            count(
                                $history
                            ),

                        'ready_for_user' =>
                            false,
                    ]
                ),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Step 2 - Understand Request
        |--------------------------------------------------------------------------
        */

        $understandStep =
            $this->getStep(
                $workflow,
                2
            );


        if (
            $understandStep
        ) {

            $understandStep->update([
                'status' =>
                    'completed',

                'input' => [
                    'objective' =>
                        $workflow->objective,

                    'history_count' =>
                        count(
                            $history
                        ),
                ],

                'output' => [
                    'task_summary' =>
                        $plan['task_summary'],

                    'resolved_action' =>
                        'unsupported',

                    'requires_specialist' =>
                        false,

                    'requires_approval' =>
                        false,
                ],

                'started_at' =>
                    now(),

                'completed_at' =>
                    now(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Step 3 - Capability Not Available
        |--------------------------------------------------------------------------
        */

        $responseStep =
            $this->getStep(
                $workflow,
                3
            );


        if (
            $responseStep
        ) {

            $responseStep->update([
                'agent_id' =>
                    $orchestrator->id,

                'name' =>
                    'Capability Not Available',

                'description' =>
                    'Luna understands the request but the required capability is not currently available.',

                'action' =>
                    'unsupported_request',

                'status' =>
                    'running',

                'input' => [
                    'task_summary' =>
                        $plan['task_summary'],

                    'message' =>
                        $workflow->objective,

                    'history_count' =>
                        count(
                            $history
                        ),
                ],

                'started_at' =>
                    now(),

                'completed_at' =>
                    null,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate Contextual Unsupported Response
        |--------------------------------------------------------------------------
        */

        try {

            $assistantResponse =
                $this
                    ->lunaResponseService
                    ->respondUnsupported(
                        $workflow->objective,
                        [
                            'display_name' =>
                                $this->resolveUserDisplayName(
                                    $user
                                ),

                            'task_summary' =>
                                $plan['task_summary'],
                        ],
                        $history
                    );


            /*
            |--------------------------------------------------------------------------
            | Complete Response Step
            |--------------------------------------------------------------------------
            */

            if (
                $responseStep
            ) {

                $responseStep->update([
                    'status' =>
                        'completed',

                    'output' => [
                        'supported' =>
                            false,

                        'assistant_response' =>
                            $assistantResponse,

                        'history_count' =>
                            count(
                                $history
                            ),
                    ],

                    'completed_at' =>
                        now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Steps 4 & 5 - No Specialist
            |--------------------------------------------------------------------------
            */

            foreach (
                [
                    4,
                    5,
                ]
                as $stepOrder
            ) {

                $step =
                    $this->getStep(
                        $workflow,
                        $stepOrder
                    );


                if (
                    $step
                ) {

                    $step->update([
                        'agent_id' =>
                            null,

                        'name' =>
                            $stepOrder === 4
                                ? 'Specialist Not Available'
                                : 'No Specialist Result',

                        'description' =>
                            'No specialist execution is currently available for this request.',

                        'action' =>
                            'not_available',

                        'status' =>
                            'completed',

                        'output' => [
                            'skipped' =>
                                true,

                            'reason' =>
                                'unsupported_capability',
                        ],

                        'started_at' =>
                            now(),

                        'completed_at' =>
                            now(),
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Step 6 - Prepare Response
            |--------------------------------------------------------------------------
            */

            $reviewStep =
                $this->getStep(
                    $workflow,
                    6
                );


            if (
                $reviewStep
            ) {

                $reviewStep->update([
                    'agent_id' =>
                        $orchestrator->id,

                    'name' =>
                        'Prepare Response',

                    'description' =>
                        'Luna prepares a natural explanation for the unavailable capability.',

                    'action' =>
                        'prepare_unsupported_response',

                    'status' =>
                        'completed',

                    'input' => [
                        'task_summary' =>
                            $plan['task_summary'],

                        'history_count' =>
                            count(
                                $history
                            ),
                    ],

                    'output' => [
                        'supported' =>
                            false,

                        'assistant_response' =>
                            $assistantResponse,

                        'ready_for_user' =>
                            true,
                    ],

                    'started_at' =>
                        now(),

                    'completed_at' =>
                        now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Step 7 - Completed
            |--------------------------------------------------------------------------
            */

            $completedStep =
                $this->getStep(
                    $workflow,
                    7
                );


            if (
                $completedStep
            ) {

                $completedStep->update([
                    'agent_id' =>
                        $orchestrator->id,

                    'name' =>
                        'Request Reviewed',

                    'description' =>
                        'Luna has reviewed the request and prepared an explanation for the user.',

                    'action' =>
                        'complete_unsupported_workflow',

                    'status' =>
                        'completed',

                    'output' => [
                        'supported' =>
                            false,

                        'ready_for_user' =>
                            true,
                    ],

                    'started_at' =>
                        now(),

                    'completed_at' =>
                        now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Complete Workflow
            |--------------------------------------------------------------------------
            */

            $workflow->update([
                'status' =>
                    'completed',

                'completed_at' =>
                    now(),

                'context' =>
                    array_merge(
                        $workflow->context
                        ?? [],

                        [
                            'resolved_action' =>
                                'unsupported',

                            'task_summary' =>
                                $plan['task_summary'],

                            'unsupported' =>
                                true,

                            'assistant_response' =>
                                $assistantResponse,

                            'response_history_count' =>
                                count(
                                    $history
                                ),

                            'final_agent' => [
                                'id' =>
                                    $orchestrator->id,

                                'code' =>
                                    $orchestrator->code,

                                'name' =>
                                    $orchestrator->name,

                                'role' =>
                                    $orchestrator->role,
                            ],

                            'ready_for_user' =>
                                true,
                        ]
                    ),
            ]);

        } catch (
            Throwable $exception
        ) {

            if (
                $responseStep
            ) {

                $responseStep->update([
                    'status' =>
                        'failed',

                    'error_message' =>
                        $exception->getMessage(),

                    'completed_at' =>
                        now(),
                ]);
            }


            $this->failWorkflow(
                $workflow,
                $exception->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve User Display Name
    |--------------------------------------------------------------------------
    */

    private function resolveUserDisplayName(
        User $user
    ): string {
        $name =
            trim(
                (string) (
                    $user->name
                    ?? ''
                )
            );


        if (
            $name === ''
        ) {

            return '';
        }


        $firstName =
            collect(
                preg_split(
                    '/\s+/',
                    $name
                )
                ?: []
            )
                ->first();


        if (
            !$firstName
        ) {

            return '';
        }


        /*
        |--------------------------------------------------------------------------
        | Salutation
        |--------------------------------------------------------------------------
        */

        $salutation =
            strtolower(
                trim(
                    (string) (
                        $user->salutation
                        ?? ''
                    )
                )
            );


        if (
            $salutation === 'mas'
        ) {

            return 'Mas '
                . $firstName;
        }


        if (
            in_array(
                $salutation,
                [
                    'mba',
                    'mbak',
                ],
                true
            )
        ) {

            return 'Mba '
                . $firstName;
        }


        /*
        |--------------------------------------------------------------------------
        | Gender Fallback
        |--------------------------------------------------------------------------
        */

        $gender =
            strtolower(
                trim(
                    (string) (
                        $user->gender
                        ?? ''
                    )
                )
            );


        if (
            in_array(
                $gender,
                [
                    'male',
                    'pria',
                    'l',
                    'm',
                ],
                true
            )
        ) {

            return 'Mas '
                . $firstName;
        }


        if (
            in_array(
                $gender,
                [
                    'female',
                    'wanita',
                    'perempuan',
                    'p',
                    'f',
                ],
                true
            )
        ) {

            return 'Mba '
                . $firstName;
        }


        /*
        |--------------------------------------------------------------------------
        | Don't Guess
        |--------------------------------------------------------------------------
        */

        return '';
    }


    /*
    |--------------------------------------------------------------------------
    | Get Workflow Step
    |--------------------------------------------------------------------------
    */

    private function getStep(
        AiWorkflow $workflow,
        int $stepOrder
    ): ?AiWorkflowStep {
        return $workflow
            ->steps()
            ->where(
                'step_order',
                $stepOrder
            )
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | Fail Workflow
    |--------------------------------------------------------------------------
    */

    private function failWorkflow(
        AiWorkflow $workflow,
        string $message,
        ?int $stepOrder = null
    ): void {
        if (
            $stepOrder
        ) {

            $step =
                $this->getStep(
                    $workflow,
                    $stepOrder
                );


            if (
                $step
            ) {

                $step->update([
                    'status' =>
                        'failed',

                    'error_message' =>
                        $message,

                    'started_at' =>
                        $step->started_at
                        ?? now(),

                    'completed_at' =>
                        now(),
                ]);
            }
        }


        $workflow->update([
            'status' =>
                'failed',

            'completed_at' =>
                now(),

            'context' =>
                array_merge(
                    $workflow->context
                    ?? [],

                    [
                        'error' =>
                            $message,

                        'ready_for_user' =>
                            false,
                    ]
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Default Steps
    |--------------------------------------------------------------------------
    */

    private function createDefaultSteps(
        AiWorkflow $workflow,
        AiAgent $orchestrator
    ): void {
        $steps = [
            [
                'step_order' =>
                    1,

                'agent_id' =>
                    $orchestrator->id,

                'name' =>
                    'Request Received',

                'description' =>
                    'User request has been received by Luna.',

                'action' =>
                    'receive_request',

                'status' =>
                    'completed',

                'input' =>
                    null,

                'output' => [
                    'received_by' => [
                        'id' =>
                            $orchestrator->id,

                        'code' =>
                            $orchestrator->code,

                        'name' =>
                            $orchestrator->name,
                    ],
                ],

                'started_at' =>
                    now(),

                'completed_at' =>
                    now(),
            ],

            [
                'step_order' =>
                    2,

                'agent_id' =>
                    $orchestrator->id,

                'name' =>
                    'Understand Request',

                'description' =>
                    'Luna understands the request and determines the required workflow.',

                'action' =>
                    'understand_request',

                'status' =>
                    'pending',

                'input' =>
                    null,

                'output' =>
                    null,

                'started_at' =>
                    null,

                'completed_at' =>
                    null,
            ],

            [
                'step_order' =>
                    3,

                'agent_id' =>
                    $orchestrator->id,

                'name' =>
                    'Delegate Task',

                'description' =>
                    'Luna determines whether the request should be handled directly or delegated.',

                'action' =>
                    'route_request',

                'status' =>
                    'pending',

                'input' =>
                    null,

                'output' =>
                    null,

                'started_at' =>
                    null,

                'completed_at' =>
                    null,
            ],

            [
                'step_order' =>
                    4,

                'agent_id' =>
                    null,

                'name' =>
                    'Specialist Execution',

                'description' =>
                    'The assigned academic specialist performs the required work when needed.',

                'action' =>
                    'agent_execution',

                'status' =>
                    'pending',

                'input' =>
                    null,

                'output' =>
                    null,

                'started_at' =>
                    null,

                'completed_at' =>
                    null,
            ],

            [
                'step_order' =>
                    5,

                'agent_id' =>
                    null,

                'name' =>
                    'Return Result',

                'description' =>
                    'The specialist returns the work result to Luna when specialist execution is required.',

                'action' =>
                    'return_result_to_orchestrator',

                'status' =>
                    'pending',

                'input' =>
                    null,

                'output' =>
                    null,

                'started_at' =>
                    null,

                'completed_at' =>
                    null,
            ],

            [
                'step_order' =>
                    6,

                'agent_id' =>
                    $orchestrator->id,

                'name' =>
                    'Review Result',

                'description' =>
                    'Luna prepares the response for the user.',

                'action' =>
                    'prepare_response',

                'status' =>
                    'pending',

                'input' =>
                    null,

                'output' =>
                    null,

                'started_at' =>
                    null,

                'completed_at' =>
                    null,
            ],

            [
                'step_order' =>
                    7,

                'agent_id' =>
                    $orchestrator->id,

                'name' =>
                    'Completed',

                'description' =>
                    'Workflow result is ready to be presented to the user.',

                'action' =>
                    'complete_workflow',

                'status' =>
                    'pending',

                'input' =>
                    null,

                'output' =>
                    null,

                'started_at' =>
                    null,

                'completed_at' =>
                    null,
            ],
        ];


        foreach (
            $steps
            as $step
        ) {

            AiWorkflowStep::create([
                'workflow_id' =>
                    $workflow->id,

                ...$step,
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Workflow Title
    |--------------------------------------------------------------------------
    */

    private function buildWorkflowTitle(
        string $message
    ): string {
        $message =
            trim(
                $message
            );


        if (
            $message === ''
        ) {

            return 'Academic Request';
        }


        return mb_strlen(
            $message
        ) > 80

            ? mb_substr(
                $message,
                0,
                77
            )
                . '...'

            : $message;
    }
}