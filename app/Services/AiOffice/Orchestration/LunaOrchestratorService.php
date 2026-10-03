<?php

namespace App\Services\AiOffice\Orchestration;

use App\Services\AiOffice\AI\AiClientInterface;
use RuntimeException;

class LunaOrchestratorService
{
    /*
    |--------------------------------------------------------------------------
    | Max Context Messages
    |--------------------------------------------------------------------------
    |
    | Defense tambahan.
    |
    | ConversationContextService sudah membatasi history,
    | tapi Luna Planner juga jangan menerima history tanpa batas.
    |--------------------------------------------------------------------------
    */

    private const MAX_HISTORY_MESSAGES = 12;


    public function __construct(
        private readonly AiClientInterface $aiClient
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Plan User Request
    |--------------------------------------------------------------------------
    |
    | $history optional supaya caller lama tetap aman:
    |
    | $luna->plan('Halo Lun');
    |
    | tetap bisa digunakan.
    |
    | Untuk contextual planning:
    |
    | $luna->plan(
    |     'Yang Software Engineering aja',
    |     $history
    | );
    |--------------------------------------------------------------------------
    */

    public function plan(
        string $message,
        array $history = []
    ): array {
        $message =
            trim($message);


        if ($message === '') {

            throw new RuntimeException(
                'Pesan untuk Luna tidak boleh kosong.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Normalize Conversation History
        |--------------------------------------------------------------------------
        */

        $history =
            $this->normalizeHistory(
                $history
            );


        /*
        |--------------------------------------------------------------------------
        | Gemini Planning
        |--------------------------------------------------------------------------
        */

        $plan =
            $this->aiClient->structured(
                systemPrompt:
                    $this->systemPrompt(),

                userPrompt:
                    $this->buildUserPrompt(
                        message:
                            $message,

                        history:
                            $history
                    ),

                schema:
                    $this->schema()
            );


        /*
        |--------------------------------------------------------------------------
        | Validate AI Plan
        |--------------------------------------------------------------------------
        */

        return $this->validatePlan(
            $plan
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Build User Prompt
    |--------------------------------------------------------------------------
    |
    | History dan current message dipisahkan secara eksplisit.
    |
    | Current message tetap menjadi instruction utama.
    |--------------------------------------------------------------------------
    */

    private function buildUserPrompt(
        string $message,
        array $history = []
    ): string {
        $parts = [];


        /*
        |--------------------------------------------------------------------------
        | Conversation History
        |--------------------------------------------------------------------------
        */

        if (!empty($history)) {

            $parts[] =
                'CONVERSATION HISTORY:';


            foreach (
                $history
                as $historyMessage
            ) {

                $role =
                    $historyMessage['role']
                    ?? 'user';


                $content =
                    trim(
                        (string) (
                            $historyMessage['content']
                            ?? ''
                        )
                    );


                if ($content === '') {
                    continue;
                }


                $speaker =
                    $role === 'assistant'
                        ? 'Luna'
                        : 'User';


                $parts[] =
                    $speaker
                    . ': '
                    . $content;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Current Message
        |--------------------------------------------------------------------------
        */

        $parts[] =
            'CURRENT USER MESSAGE:';

        $parts[] =
            $message;


        /*
        |--------------------------------------------------------------------------
        | Planning Instruction
        |--------------------------------------------------------------------------
        */

        $parts[] =
            'Use the conversation history only when it is relevant to understanding the current user message. The current user message is the request you must classify now.';


        return implode(
            "\n\n",
            $parts
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize History
    |--------------------------------------------------------------------------
    |
    | Luna Planner hanya butuh:
    |
    | - role
    | - content
    |
    | ID, timestamp, agent metadata tidak perlu dikirim ke Gemini planner.
    |--------------------------------------------------------------------------
    */

    private function normalizeHistory(
        array $history
    ): array {
        $normalized = [];


        foreach (
            $history
            as $item
        ) {

            if (!is_array($item)) {
                continue;
            }


            $role =
                strtolower(
                    trim(
                        (string) (
                            $item['role']
                            ?? ''
                        )
                    )
                );


            $content =
                trim(
                    (string) (
                        $item['content']
                        ?? ''
                    )
                );


            if (
                !in_array(
                    $role,
                    [
                        'user',
                        'assistant',
                    ],
                    true
                )
            ) {
                continue;
            }


            if ($content === '') {
                continue;
            }


            $normalized[] = [
                'role' =>
                    $role,

                'content' =>
                    $content,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Keep Only Latest Messages
        |--------------------------------------------------------------------------
        */

        return array_slice(
            $normalized,
            -self::MAX_HISTORY_MESSAGES
        );
    }


    /*
    |--------------------------------------------------------------------------
    | System Prompt
    |--------------------------------------------------------------------------
    */

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are Luna, the Virtual Academic Secretary and primary orchestrator for FlexOps Academic AI Office.

==================================================
IDENTITY
==================================================

You are a conversational Virtual Academic Secretary.

You are NOT merely a command router.

The user should feel like they are talking naturally with a helpful academic secretary.

Your primary responsibility is everything related to the Academic Division.

You can:

- have natural conversation with the user
- answer greetings
- respond to acknowledgements and thanks
- explain who you are
- explain your role
- explain what you can currently help with
- explain available agents and capabilities
- understand academic operational requests
- understand conversational follow-up using prior conversation context
- decide when an internal specialist is required
- decide whether a currently implemented capability can execute the request

At this stage, your job is ONLY to create an execution plan.

You do NOT execute database tools yourself during planning.

You do NOT invent academic data.

You do NOT claim that a database task has already been completed.

You do NOT invent agents.

You do NOT invent capabilities.


==================================================
CONVERSATION CONTEXT
==================================================

You may receive previous conversation messages before the CURRENT USER MESSAGE.

Use previous messages only when they help understand the current message.

Examples:

Previous conversation:

User:
"Kamu bisa bantu bikin jadwal?"

Luna:
"Saat ini fitur penyusunan jadwal belum tersedia."

Current message:

"Kalau nanti tersedia siapa yang akan ngerjain?"

This is conversational follow-up.

Use:

agent = "luna"
action = "respond_directly"


Another example:

Previous conversation:

User:
"Cek kelas yang masih jalan"

Luna:
"Raka sedang mengecek kelas aktif."

Current message:

"Yang Software Engineering aja"

This is a contextual follow-up referring to previous academic data.

Do NOT invent the filtered result.

If no implemented capability can execute the contextual follow-up, use unsupported.

Conversation history helps understand references such as:

- "yang tadi"
- "yang itu"
- "yang Software Engineering aja"
- "kalau yang lainnya?"
- "terus gimana?"
- "kalau nanti tersedia?"
- "dia bisa ngapain lagi?"
- "yang batch kedua aja"

Do not treat previous user requests as the current request.

Always classify the CURRENT USER MESSAGE.


==================================================
AVAILABLE ACTIONS
==================================================

There are currently exactly three valid actions:

1. respond_directly
2. get_active_classes
3. unsupported


==================================================
ACTION 1 — RESPOND DIRECTLY
==================================================

Use:

agent = "luna"
action = "respond_directly"
requires_approval = false
handoff_message = ""

when Luna can respond conversationally without executing a FlexOps data tool.

This includes:

- greetings
- introductions
- thanks
- acknowledgements
- casual conversation
- questions about Luna
- questions about Academic AI Office
- questions about available agents
- questions about available capabilities
- questions about what Luna can help with
- questions asking whether Luna can perform something
- conversational follow-up that can be answered from the conversation itself
- clarification about something Luna previously explained
- normal conversation that does not require retrieving new FlexOps operational data


Examples that MUST use respond_directly:

"Halo Lun"

"Hai Luna"

"Selamat pagi Lun"

"Makasih Lun"

"Terima kasih ya"

"Oke Lun"

"Siap"

"Good job Lun"

"Kamu siapa?"

"Luna itu siapa?"

"Tugas kamu apa?"

"Kamu bisa bantu apa?"

"Kamu bisa bantu apa aja Lun?"

"Apa aja yang bisa kamu kerjakan?"

"Kamu punya kemampuan apa?"

"Fitur kamu sekarang apa aja?"

"Apa yang bisa dibantu Academic Office?"

"Siapa aja agent di Academic Office?"

"Raka itu siapa?"

"Raka bisa bantu apa?"

"Kalau urusan akademik kamu bisa bantu apa?"

"Kamu bisa bantu bikin jadwal kelas?"

"Kamu bisa cek progress student?"

"Kamu bisa bantu bikin materi?"


==================================================
ASKING ABOUT CAPABILITY VS REQUESTING EXECUTION
==================================================

You MUST distinguish between:

A. asking whether something can be done

and

B. requesting the task to actually be executed.


Example:

"Kamu bisa bantu bikin jadwal kelas?"

→ respond_directly


"Buatkan jadwal CORE SE 02"

→ unsupported


Example:

"Kamu bisa cek progress student?"

→ respond_directly


"Cek progress student CORE SE 02"

→ unsupported


Do NOT use unsupported merely because the user mentions an unavailable capability.

Use unsupported only when the user is actually requesting execution of an unavailable operation.


==================================================
ACTION 2 — GET ACTIVE CLASSES
==================================================

Raka is an internal Academic AI specialist.

Role:

Academic Admin


CURRENT IMPLEMENTED RAKA CAPABILITY:

get_active_classes


Purpose:

Retrieve classes or batches that are currently active / ongoing in FlexOps.


Use:

agent = "raka"
action = "get_active_classes"
requires_approval = false

when the user is actually requesting active class or active batch data.


Examples:

"Cek kelas aktif"

"Cek batch aktif"

"Kelas apa aja yang masih jalan?"

"Batch apa aja yang masih berjalan?"

"Batch mana yang sekarang sedang berlangsung?"

"Lun, coba lihat kelas yang masih aktif sekarang."

"Ada kelas ongoing apa saja?"

"Program kelas mana yang sekarang masih berjalan?"

"Sekarang kelas apa saja yang masih berlangsung?"

"Saya mau tahu batch yang masih running."

"Tolong cek kelas yang saat ini sedang berjalan."

"Lun, sekarang batch apa aja yang masih jalan?"


The wording does NOT need to exactly match these examples.

Understand semantic intent.


==================================================
HANDOFF MESSAGE
==================================================

When action = get_active_classes:

handoff_message must be natural Indonesian.

The message should explain that Luna will ask Raka to perform the check.

Examples:

"Sebentar, saya akan meminta Raka untuk mengecek kelas yang sedang aktif."

"Baik, saya akan meminta Raka untuk melihat batch yang saat ini masih berjalan."

"Sebentar, saya akan menghubungi Raka untuk mengecek kelas yang masih berlangsung."


The message MUST NOT:

- claim Raka has already finished
- invent database results
- mention class names that have not been retrieved
- invent student information


==================================================
ACTION 3 — UNSUPPORTED
==================================================

Use:

agent = "luna"
action = "unsupported"
requires_approval = false
handoff_message = ""

only when:

1. the user is actually asking the system to EXECUTE a task

AND

2. there is currently no implemented capability that can execute it.


Examples currently unsupported:

"Buatkan jadwal CORE SE 02"

"Ubah jadwal kelas AIO 02"

"Cek konflik jadwal Coach Sena"

"Cek attendance CORE SE 02"

"Cek progress student INTRO SE 03"

"Siapa yang belum submit tugas?"

"Cek quiz AIO 02"

"Buatkan materi sesi 5"

"Buat slide sesi berikutnya"

"Buat worksheet student"

"Buat curriculum program baru"

"Riset program AI baru"

"Ubah nilai student"

"Pindahkan student ke batch lain"


==================================================
UNSUPPORTED DOES NOT MEAN SILENCE
==================================================

Unsupported means the backend capability required to execute the request is not currently available.

Luna can still understand and discuss the request naturally.


==================================================
ACADEMIC DOMAIN
==================================================

Luna's primary operational domain is Academic Division.

Academic topics include:

- classes
- batches
- programs
- students
- instructors
- schedules
- attendance
- student progress
- assignments
- quizzes
- mentoring
- learning content
- learning materials
- curriculum
- academic reports
- academic monitoring
- academic administration
- academic operations
- program development

Understanding an academic request does NOT automatically mean a capability exists.


==================================================
OUTSIDE ACADEMIC DOMAIN
==================================================

Normal social conversation is allowed.

If the user requests execution of work clearly outside Academic Division, use unsupported.


==================================================
APPROVAL
==================================================

Current rules:

respond_directly:
requires_approval = false

get_active_classes:
requires_approval = false

unsupported:
requires_approval = false


Never invent approval requirements.


==================================================
TASK SUMMARY
==================================================

task_summary must:

- be concise
- be written in Indonesian
- represent the current user's actual intention
- use conversation history only to resolve references when needed
- not claim the task has already been completed


Examples:

User:
"Halo Lun"

task_summary:
"Menyapa Luna."


User:
"Kamu bisa bantu apa aja Lun?"

task_summary:
"Menanyakan kemampuan dan layanan Luna."


User:
"Cek kelas aktif"

task_summary:
"Mengecek kelas yang sedang aktif."


==================================================
FINAL DECISION RULE
==================================================

First understand the CURRENT USER MESSAGE.

Use conversation history only to resolve context or references.

Then decide:

Is the user merely talking to Luna or asking ABOUT a capability?

YES
→ respond_directly


Is the user actually requesting active / ongoing class data?

YES
→ get_active_classes


Is the user actually requesting execution of another operation with no implemented capability?

YES
→ unsupported


==================================================
IMPORTANT RULES
==================================================

Never invent FlexOps data.

Never invent tools.

Never invent capabilities.

Never delegate to Raka unless the implemented Raka capability matches the requested execution.

Never classify normal conversation as unsupported.

Never classify a question ABOUT capabilities as unsupported.

Never confuse an old conversation request with the current message.

Return only data matching the required schema.

Do not add explanations outside the structured response.
PROMPT;
    }


    /*
    |--------------------------------------------------------------------------
    | Structured Output Schema
    |--------------------------------------------------------------------------
    */

    private function schema(): array
    {
        return [
            'type' =>
                'object',

            'properties' => [

                'task_summary' => [
                    'type' =>
                        'string',

                    'description' =>
                        'Ringkasan singkat dalam Bahasa Indonesia mengenai maksud current user message.',
                ],


                'agent' => [
                    'type' =>
                        'string',

                    'enum' => [
                        'luna',
                        'raka',
                    ],

                    'description' =>
                        'Agent yang menangani execution plan.',
                ],


                'action' => [
                    'type' =>
                        'string',

                    'enum' => [
                        'respond_directly',
                        'get_active_classes',
                        'unsupported',
                    ],

                    'description' =>
                        'Jenis action yang harus dilakukan oleh Academic AI Office.',
                ],


                'requires_approval' => [
                    'type' =>
                        'boolean',

                    'description' =>
                        'Menentukan apakah action membutuhkan approval user.',
                ],


                'handoff_message' => [
                    'type' =>
                        'string',

                    'description' =>
                        'Pesan delegasi Luna. Harus kosong apabila tidak ada specialist handoff.',
                ],
            ],

            'required' => [
                'task_summary',
                'agent',
                'action',
                'requires_approval',
                'handoff_message',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Validate AI Plan
    |--------------------------------------------------------------------------
    */

    private function validatePlan(
        array $plan
    ): array {
        $taskSummary =
            trim(
                (string) (
                    $plan['task_summary']
                    ?? ''
                )
            );


        $agent =
            strtolower(
                trim(
                    (string) (
                        $plan['agent']
                        ?? ''
                    )
                )
            );


        $action =
            strtolower(
                trim(
                    (string) (
                        $plan['action']
                        ?? ''
                    )
                )
            );


        $requiresApproval =
            (bool) (
                $plan['requires_approval']
                ?? false
            );


        $handoffMessage =
            trim(
                (string) (
                    $plan['handoff_message']
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Task Summary
        |--------------------------------------------------------------------------
        */

        if ($taskSummary === '') {

            throw new RuntimeException(
                'Luna AI Planner tidak mengembalikan task_summary.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Agent
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $agent,
                [
                    'luna',
                    'raka',
                ],
                true
            )
        ) {

            throw new RuntimeException(
                sprintf(
                    'Luna AI Planner mengembalikan agent yang tidak dikenal: "%s".',
                    $agent
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Action
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $action,
                [
                    'respond_directly',
                    'get_active_classes',
                    'unsupported',
                ],
                true
            )
        ) {

            throw new RuntimeException(
                sprintf(
                    'Luna AI Planner mengembalikan action yang tidak dikenal: "%s".',
                    $action
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Respond Directly
        |--------------------------------------------------------------------------
        */

        if (
            $action
            === 'respond_directly'
        ) {

            $agent =
                'luna';


            $requiresApproval =
                false;


            $handoffMessage =
                '';
        }


        /*
        |--------------------------------------------------------------------------
        | Get Active Classes
        |--------------------------------------------------------------------------
        */

        if (
            $action
            === 'get_active_classes'
        ) {

            if (
                $agent
                !== 'raka'
            ) {

                throw new RuntimeException(
                    'Action get_active_classes harus ditangani oleh Raka.'
                );
            }


            $requiresApproval =
                false;


            if (
                $handoffMessage === ''
            ) {

                $handoffMessage =
                    'Sebentar, saya akan meminta Raka untuk mengecek kelas yang sedang aktif.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Unsupported
        |--------------------------------------------------------------------------
        */

        if (
            $action
            === 'unsupported'
        ) {

            $agent =
                'luna';


            $requiresApproval =
                false;


            $handoffMessage =
                '';
        }


        /*
        |--------------------------------------------------------------------------
        | Normalized Plan
        |--------------------------------------------------------------------------
        */

        return [
            'task_summary' =>
                $taskSummary,

            'agent' =>
                $agent,

            'action' =>
                $action,

            'requires_approval' =>
                $requiresApproval,

            'handoff_message' =>
                $handoffMessage,
        ];
    }
}