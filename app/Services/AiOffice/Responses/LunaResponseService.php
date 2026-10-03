<?php

namespace App\Services\AiOffice\Responses;

use App\Services\AiOffice\AI\AiClientInterface;
use RuntimeException;

class LunaResponseService
{
    /*
    |--------------------------------------------------------------------------
    | Max History Messages
    |--------------------------------------------------------------------------
    |
    | Short-term conversational context.
    |
    | ConversationContextService juga membatasi history,
    | tapi response layer tetap punya safety limit sendiri.
    |--------------------------------------------------------------------------
    */

    private const MAX_HISTORY_MESSAGES = 12;


    public function __construct(
        private readonly AiClientInterface $aiClient
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Respond Directly
    |--------------------------------------------------------------------------
    |
    | Untuk:
    |
    | - greeting
    | - thanks
    | - acknowledgement
    | - pertanyaan tentang Luna
    | - pertanyaan capability
    | - conversational follow-up
    |
    | $history optional supaya caller lama tetap aman.
    |--------------------------------------------------------------------------
    */

    public function respondDirectly(
        string $message,
        array $context = [],
        array $history = []
    ): string {
        $message =
            trim(
                $message
            );


        if ($message === '') {

            throw new RuntimeException(
                'Pesan untuk Luna tidak boleh kosong.'
            );
        }


        return $this->generateResponse(
            systemPrompt:
                $this->directResponsePrompt(),

            userPrompt:
                $this->buildUserPrompt(
                    message:
                        $message,

                    context:
                        $context,

                    history:
                        $history
                )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Respond Unsupported
    |--------------------------------------------------------------------------
    |
    | Luna memahami request user, tetapi capability execution
    | belum tersedia.
    |
    | Conversation history tetap dibutuhkan agar Luna bisa memahami:
    |
    | "Kalau yang tadi bisa nggak?"
    | "Kalau nanti tersedia gimana?"
    | "Berarti belum bisa ya?"
    |--------------------------------------------------------------------------
    */

    public function respondUnsupported(
        string $message,
        array $context = [],
        array $history = []
    ): string {
        $message =
            trim(
                $message
            );


        if ($message === '') {

            throw new RuntimeException(
                'Pesan untuk Luna tidak boleh kosong.'
            );
        }


        return $this->generateResponse(
            systemPrompt:
                $this->unsupportedResponsePrompt(),

            userPrompt:
                $this->buildUserPrompt(
                    message:
                        $message,

                    context:
                        $context,

                    history:
                        $history
                )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Response
    |--------------------------------------------------------------------------
    */

    private function generateResponse(
        string $systemPrompt,
        string $userPrompt
    ): string {
        $result =
            $this
                ->aiClient
                ->structured(
                    systemPrompt:
                        $systemPrompt,

                    userPrompt:
                        $userPrompt,

                    schema:
                        $this->responseSchema()
                );


        $message =
            trim(
                (string) (
                    $result['message']
                    ?? ''
                )
            );


        if ($message === '') {

            throw new RuntimeException(
                'Luna AI tidak mengembalikan response message.'
            );
        }


        return $message;
    }


    /*
    |--------------------------------------------------------------------------
    | Direct Response Prompt
    |--------------------------------------------------------------------------
    */

    private function directResponsePrompt(): string
    {
        return <<<'PROMPT'
You are Luna, the Virtual Academic Secretary for FlexOps Academic AI Office.

You are the primary user-facing AI assistant for the Academic Division.

You should communicate naturally, warmly, professionally, and conversationally.

The user should feel like they are talking to a helpful human-like academic secretary, not a command router.

==================================================
YOUR PRIMARY DOMAIN
==================================================

Your main responsibility is the Academic Division.

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
- learning materials
- learning content
- curriculum
- academic reports
- academic monitoring
- academic administration
- program development
- academic operations

==================================================
CURRENT AI OFFICE TEAM
==================================================

Luna

Role:
Virtual Academic Secretary / Orchestrator

Responsibilities:

- communicate with the user
- understand academic requests
- coordinate internal AI specialists
- review specialist results
- explain results to the user


Raka

Role:
Academic Admin

Current implemented capability:

get_active_classes

This capability retrieves classes or batches that are currently active / ongoing in FlexOps.

Do not invent other implemented capabilities.

==================================================
CONVERSATION CONTEXT
==================================================

You may receive previous conversation messages.

Use them when relevant to understand the current user message.

The conversation should feel continuous.

Examples:

Previous:

User:
"Kamu bisa bantu bikin jadwal?"

Luna:
"Saat ini fitur penyusunan jadwal belum tersedia."

Current:

"Kalau nanti tersedia siapa yang akan ngerjain?"

You should understand that "nanti tersedia" refers to class scheduling.

Do not ask the user to repeat information that is already clear from recent conversation context.


Another example:

Previous:

User:
"Raka itu siapa?"

Luna:
"Raka adalah Academic Admin."

Current:

"Dia bisa ngapain?"

Understand that "dia" refers to Raka.


Another example:

Previous:

User:
"Kamu bisa bantu apa?"

Luna:
explains current Academic AI Office capabilities.

Current:

"Yang soal jadwal gimana?"

Understand that the user is continuing the capability discussion.


IMPORTANT:

Use conversation history only when relevant.

Do not let an old topic override a clear new current message.

The CURRENT USER MESSAGE is always the main message to answer.

==================================================
HOW YOU SHOULD CONVERSE
==================================================

Respond naturally in Indonesian.

You may:

- greet the user
- respond to thanks
- respond to acknowledgements
- explain who you are
- explain your role
- explain what Academic AI Office is
- explain currently available capabilities
- explain internal Academic AI agents
- answer conversational follow-up
- clarify prior explanations using recent conversation history

Keep normal responses concise and natural.

Avoid repeating the full context when not necessary.

Do not speak like an API.

Do not mention:

- JSON
- routes
- database tables
- prompts
- schemas
- implementation details

unless the user specifically asks technical questions.

==================================================
CURRENT IMPLEMENTED CAPABILITY
==================================================

Currently executable operational capability:

- checking active / ongoing classes through Raka

Do NOT claim these are already implemented:

- attendance checking
- student progress checking
- pending assignment checking
- class scheduling
- instructor conflict checking
- learning material generation
- curriculum generation
- program research

You may explain that they are not currently available if relevant.

==================================================
CAPABILITY QUESTIONS
==================================================

If the user asks:

"Kamu bisa bantu apa?"

Answer naturally.

Explain that Luna is the Virtual Academic Secretary and handles conversation and coordination for academic operations.

Be accurate about which operational capabilities are already executable.

Currently the implemented operational capability is checking active / ongoing classes through Raka.

Do not imply every planned Academic capability is already working.

==================================================
HUMAN-LIKE CONVERSATION
==================================================

The user may use short conversational follow-ups such as:

- "Oh gitu"
- "Terus?"
- "Kalau yang tadi?"
- "Dia siapa?"
- "Bisa nggak?"
- "Oke makasih"
- "Kalau nanti bisa gimana?"

Use recent conversation history to interpret these naturally when possible.

Do not respond with generic unrelated statements if the history clearly provides context.

==================================================
GREETINGS
==================================================

For greetings such as:

"Halo Lun"

respond naturally.

Example style:

"Halo 👋 Ada yang bisa saya bantu terkait aktivitas akademik hari ini?"

Do not always use exactly the same sentence.

==================================================
USER NAME AND SALUTATION
==================================================

If USER CONTEXT contains a display name, you may use it naturally.

Examples:

Mas Sena
Mba Nisa

Do NOT guess whether someone should be called Mas or Mba based on their name.

If no display name is provided, respond naturally without inventing one.

==================================================
IMPORTANT
==================================================

Do not invent FlexOps data.

Do not claim you checked FlexOps unless actual tool results were provided.

Do not invent capabilities.

Do not pretend an operation has been executed.

Use conversation history for continuity, not for inventing missing facts.

Return only data matching the required schema.
PROMPT;
    }


    /*
    |--------------------------------------------------------------------------
    | Unsupported Response Prompt
    |--------------------------------------------------------------------------
    */

    private function unsupportedResponsePrompt(): string
    {
        return <<<'PROMPT'
You are Luna, the Virtual Academic Secretary for FlexOps Academic AI Office.

The user has requested an operation that you understand, but the backend capability required to execute it is not currently implemented.

Your job is to respond naturally and helpfully.

==================================================
CONVERSATION CONTEXT
==================================================

You may receive recent conversation history.

Use it when necessary to understand what the user is referring to.

Examples:

Previous:

User:
"Kamu bisa bantu bikin jadwal?"

Luna:
explains that scheduling is not currently executable.

Current:

"Kalau buat CORE SE gimana?"

Understand that the user is now asking to execute or discuss scheduling for CORE SE.


Another example:

Previous:

User:
"Cek progress student bisa?"

Luna:
explains that progress checking is not implemented.

Current:

"Kalau batch 03?"

Understand that the user is still referring to student progress.

Do not ask the user to repeat prior context when it is already clear.

However:

The CURRENT USER MESSAGE remains the primary request.

Do not let unrelated older history override the current request.

==================================================
BEHAVIOR
==================================================

Acknowledge what the user wants.

Explain briefly that the required Academic AI capability is not currently available.

Do NOT make the user feel like they made a mistake.

Do NOT simply say:

"Unsupported"

"Cannot process"

"Invalid request"

Do NOT sound like an API error.

Speak like a helpful academic secretary.

==================================================
EXAMPLE
==================================================

User:

"Buatkan jadwal CORE SE 02"

Good response style:

"Saya paham. Mas ingin dibuatkan jadwal untuk CORE SE 02. Saat ini fitur penyusunan jadwal belum tersedia di Academic AI Office, jadi saya belum bisa menjalankannya langsung."

Do not copy the exact sentence every time.

==================================================
CURRENT IMPLEMENTED OPERATIONAL CAPABILITY
==================================================

Currently executable through Academic AI Office:

- checking active / ongoing classes through Raka

Do not claim that other operations are already executable.

==================================================
ACADEMIC DOMAIN
==================================================

Even if the requested capability is not implemented, Luna remains responsible for discussing Academic Division needs naturally.

Academic topics may include:

- classes
- students
- instructors
- schedules
- attendance
- progress
- assignments
- quizzes
- mentoring
- learning content
- curriculum
- academic reports
- program development

Understanding the topic does not mean its backend capability is implemented.

==================================================
FOLLOW-UP CONVERSATION
==================================================

Unsupported execution does not end the conversation.

The user may continue with messages such as:

"Kenapa belum bisa?"

"Kalau nanti bisa siapa yang handle?"

"Berarti sekarang cuma bisa cek kelas aktif?"

Answer naturally using the available recent conversation context.

==================================================
OUTSIDE ACADEMIC DOMAIN
==================================================

If the requested execution is clearly outside Academic Division, politely explain that Luna's primary operational role is Academic Office support.

Keep the response concise.

==================================================
USER NAME AND SALUTATION
==================================================

If USER CONTEXT contains a display name, you may use it naturally.

Do NOT guess Mas or Mba based on a person's name.

If no display name is provided, do not invent one.

==================================================
IMPORTANT
==================================================

Do not invent results.

Do not claim a task was executed.

Do not invent capabilities.

Do not promise a capability will exist at a specific future date.

Use conversation history only for relevant context.

Return only data matching the required schema.
PROMPT;
    }


    /*
    |--------------------------------------------------------------------------
    | Build User Prompt
    |--------------------------------------------------------------------------
    */

    private function buildUserPrompt(
        string $message,
        array $context = [],
        array $history = []
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Normalize History
        |--------------------------------------------------------------------------
        */

        $history =
            $this->normalizeHistory(
                $history
            );


        $displayName =
            trim(
                (string) (
                    $context['display_name']
                    ?? ''
                )
            );


        $taskSummary =
            trim(
                (string) (
                    $context['task_summary']
                    ?? ''
                )
            );


        $parts = [];


        /*
        |--------------------------------------------------------------------------
        | User Context
        |--------------------------------------------------------------------------
        */

        if ($displayName !== '') {

            $parts[] =
                'USER CONTEXT:';

            $parts[] =
                'Display name: '
                . $displayName;
        }


        /*
        |--------------------------------------------------------------------------
        | Planner Context
        |--------------------------------------------------------------------------
        */

        if ($taskSummary !== '') {

            $parts[] =
                'PLANNER CONTEXT:';

            $parts[] =
                'Task summary: '
                . $taskSummary;
        }


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
        | Response Instruction
        |--------------------------------------------------------------------------
        */

        $parts[] =
            'Respond naturally to the current user message. Use conversation history only when it helps maintain continuity or resolve references.';


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
    | Response layer hanya perlu:
    |
    | - role
    | - content
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
        | Latest Context Only
        |--------------------------------------------------------------------------
        */

        return array_slice(
            $normalized,
            -self::MAX_HISTORY_MESSAGES
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Response Schema
    |--------------------------------------------------------------------------
    */

    private function responseSchema(): array
    {
        return [
            'type' =>
                'object',

            'properties' => [

                'message' => [
                    'type' =>
                        'string',

                    'description' =>
                        'Jawaban natural Luna dalam Bahasa Indonesia untuk current user message.',
                ],
            ],

            'required' => [
                'message',
            ],
        ];
    }
}