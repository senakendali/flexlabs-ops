<?php

namespace App\Services\AiOffice\Conversation;

use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Support\Collection;

class ConversationContextService
{
    /*
    |--------------------------------------------------------------------------
    | Default History Limit
    |--------------------------------------------------------------------------
    |
    | 12 messages kira-kira = 6 turn user ↔ Luna.
    |
    | Untuk MVP cukup untuk short-term conversational context
    | tanpa membuat prompt Gemini terlalu besar.
    |--------------------------------------------------------------------------
    */

    private const DEFAULT_MESSAGE_LIMIT = 12;


    /*
    |--------------------------------------------------------------------------
    | Recent Messages
    |--------------------------------------------------------------------------
    |
    | Mengambil recent conversation history dalam urutan:
    |
    | oldest
    | ↓
    | newest
    |
    | Hanya user + assistant text message yang masuk ke AI context.
    |--------------------------------------------------------------------------
    */

    public function recentMessages(
        AiConversation $conversation,
        int $limit = self::DEFAULT_MESSAGE_LIMIT
    ): array {
        $limit =
            $this->normalizeLimit(
                $limit
            );


        $messages =
            AiMessage::query()
                ->where(
                    'conversation_id',
                    $conversation->id
                )
                ->where(
                    'message_type',
                    'text'
                )
                ->whereIn(
                    'sender_type',
                    [
                        'user',
                        'assistant',
                    ]
                )
                ->with([
                    'agent:id,code,name,role',
                ])
                ->latest('id')
                ->limit(
                    $limit
                )
                ->get()
                ->reverse()
                ->values();


        return $this->mapMessages(
            $messages
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Recent Messages Before Message
    |--------------------------------------------------------------------------
    |
    | Method ini penting nanti saat current user message sudah tersimpan
    | di ai_messages.
    |
    | Misalnya:
    |
    | #61 user      Halo Lun
    | #62 assistant Halo...
    | #63 user      Kalau kelas aktif gimana?
    |
    | Ketika memproses #63 kita bisa meminta history:
    |
    | recentMessagesBefore($conversation, 63)
    |
    | sehingga #63 tidak dikirim dua kali:
    |
    | history + current message.
    |--------------------------------------------------------------------------
    */

    public function recentMessagesBefore(
        AiConversation $conversation,
        int $beforeMessageId,
        int $limit = self::DEFAULT_MESSAGE_LIMIT
    ): array {
        $limit =
            $this->normalizeLimit(
                $limit
            );


        $messages =
            AiMessage::query()
                ->where(
                    'conversation_id',
                    $conversation->id
                )
                ->where(
                    'id',
                    '<',
                    $beforeMessageId
                )
                ->where(
                    'message_type',
                    'text'
                )
                ->whereIn(
                    'sender_type',
                    [
                        'user',
                        'assistant',
                    ]
                )
                ->with([
                    'agent:id,code,name,role',
                ])
                ->latest('id')
                ->limit(
                    $limit
                )
                ->get()
                ->reverse()
                ->values();


        return $this->mapMessages(
            $messages
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Has History
    |--------------------------------------------------------------------------
    */

    public function hasHistory(
        AiConversation $conversation
    ): bool {
        return AiMessage::query()
            ->where(
                'conversation_id',
                $conversation->id
            )
            ->where(
                'message_type',
                'text'
            )
            ->whereIn(
                'sender_type',
                [
                    'user',
                    'assistant',
                ]
            )
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Message Count
    |--------------------------------------------------------------------------
    */

    public function messageCount(
        AiConversation $conversation
    ): int {
        return AiMessage::query()
            ->where(
                'conversation_id',
                $conversation->id
            )
            ->where(
                'message_type',
                'text'
            )
            ->whereIn(
                'sender_type',
                [
                    'user',
                    'assistant',
                ]
            )
            ->count();
    }


    /*
    |--------------------------------------------------------------------------
    | Map Messages
    |--------------------------------------------------------------------------
    |
    | Service ini sengaja tidak mengembalikan Eloquent model ke AI layer.
    |
    | AI layer hanya menerima data yang memang dibutuhkan untuk context.
    |--------------------------------------------------------------------------
    */

    private function mapMessages(
        Collection $messages
    ): array {
        return $messages
            ->map(
                function (AiMessage $message) {

                    return [
                        'id' =>
                            $message->id,

                        'role' =>
                            $this->resolveRole(
                                $message->sender_type
                            ),

                        'content' =>
                            trim(
                                (string) $message->content
                            ),

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

                        'created_at' =>
                            $message->created_at
                                ?->toISOString(),
                    ];
                }
            )
            ->filter(
                fn (array $message) =>
                    $message['content'] !== ''
            )
            ->values()
            ->all();
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve AI Role
    |--------------------------------------------------------------------------
    */

    private function resolveRole(
        string $senderType
    ): string {
        return match (
            $senderType
        ) {
            'assistant' =>
                'assistant',

            default =>
                'user',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Limit
    |--------------------------------------------------------------------------
    |
    | Jangan biarkan caller tanpa sengaja mengirim ratusan message
    | ke Gemini.
    |--------------------------------------------------------------------------
    */

    private function normalizeLimit(
        int $limit
    ): int {
        if ($limit < 1) {
            return self::DEFAULT_MESSAGE_LIMIT;
        }


        return min(
            $limit,
            30
        );
    }
}