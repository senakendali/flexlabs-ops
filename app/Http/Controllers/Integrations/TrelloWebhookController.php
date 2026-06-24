<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\TrelloIntegration;
use App\Models\TrelloWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class TrelloWebhookController extends Controller
{
    public function handle(Request $request): Response|JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Trello Callback Verification
        |--------------------------------------------------------------------------
        |
        | Saat webhook dibuat, Trello akan mengecek callbackURL dan wajib dapat
        | response 200. Kita izinkan GET dan HEAD untuk validasi.
        |
        */
        if ($request->isMethod('HEAD') || $request->isMethod('GET')) {
            return response('', 200);
        }

        $payload = $request->all();

        /*
        |--------------------------------------------------------------------------
        | Resolve Board ID
        |--------------------------------------------------------------------------
        |
        | Payload Trello bisa beda-beda tergantung action. Jadi kita ambil dari
        | beberapa kemungkinan path.
        |
        */
        $boardId = data_get($payload, 'model.id')
            ?: data_get($payload, 'action.data.board.id')
            ?: data_get($payload, 'action.data.card.idBoard');

        $actionId = data_get($payload, 'action.id');
        $actionType = data_get($payload, 'action.type');

        $cardId = data_get($payload, 'action.data.card.id');
        $cardName = data_get($payload, 'action.data.card.name');

        $listId = data_get($payload, 'action.data.list.id')
            ?: data_get($payload, 'action.data.listAfter.id')
            ?: data_get($payload, 'action.data.listBefore.id');

        $listName = data_get($payload, 'action.data.list.name')
            ?: data_get($payload, 'action.data.listAfter.name')
            ?: data_get($payload, 'action.data.listBefore.name');

        $memberCreatorId = data_get($payload, 'action.idMemberCreator')
            ?: data_get($payload, 'action.memberCreator.id');

        $memberCreatorName = data_get($payload, 'action.memberCreator.fullName');
        $memberCreatorUsername = data_get($payload, 'action.memberCreator.username');

        $happenedAt = data_get($payload, 'action.date');

        $integration = null;

        if ($boardId) {
            $integration = TrelloIntegration::query()
                ->where('trello_board_id', $boardId)
                ->where('is_active', true)
                ->first();

            if ($integration) {
                $integration->forceFill([
                    'last_webhook_at' => now(),
                    'raw_payload' => $payload,
                    'last_error' => null,
                ])->save();
            }
        }

        try {
            $eventData = [
                'trello_integration_id' => $integration?->id,
                'source_key' => $integration?->source_key,

                'trello_board_id' => $boardId,
                'trello_action_id' => $actionId,
                'trello_action_type' => $actionType,

                'trello_card_id' => $cardId,
                'trello_card_name' => $cardName,

                'trello_list_id' => $listId,
                'trello_list_name' => $listName,

                'trello_member_creator_id' => $memberCreatorId,
                'trello_member_creator_name' => $memberCreatorName,
                'trello_member_creator_username' => $memberCreatorUsername,

                'happened_at' => $happenedAt,
                'received_at' => now(),

                'processing_status' => 'pending',
                'processing_error' => null,

                'headers_json' => $this->safeHeaders($request),
                'payload_json' => $payload,
            ];

            if ($actionId) {
                $event = TrelloWebhookEvent::updateOrCreate(
                    ['trello_action_id' => $actionId],
                    $eventData
                );
            } else {
                $event = TrelloWebhookEvent::create($eventData);
            }

            Log::info('Trello webhook event stored', [
                'event_id' => $event->id,
                'board_id' => $boardId,
                'integration_id' => $integration?->id,
                'source_key' => $integration?->source_key,
                'action_id' => $actionId,
                'action_type' => $actionType,
                'card_id' => $cardId,
                'card_name' => $cardName,
                'list_id' => $listId,
                'list_name' => $listName,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to store Trello webhook event', [
                'message' => $exception->getMessage(),
                'board_id' => $boardId,
                'action_id' => $actionId,
                'action_type' => $actionType,
                'payload' => $payload,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Always Return 200
        |--------------------------------------------------------------------------
        |
        | Untuk webhook, jangan return error ke Trello hanya karena proses internal
        | gagal. Nanti Trello bisa retry berkali-kali. Error internal cukup kita log.
        |
        */
        return response()->json([
            'success' => true,
            'message' => 'Trello webhook received',
            'source_key' => $integration?->source_key,
            'action_type' => $actionType,
        ]);
    }

    private function safeHeaders(Request $request): array
    {
        return collect($request->headers->all())
            ->except([
                'authorization',
                'cookie',
                'x-xsrf-token',
                'x-csrf-token',
            ])
            ->toArray();
    }
}