<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\TrelloIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

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

        Log::info('Trello webhook received', [
            'board_id' => $boardId,
            'integration_id' => $integration?->id,
            'source_key' => $integration?->source_key,
            'action_id' => data_get($payload, 'action.id'),
            'action_type' => data_get($payload, 'action.type'),
            'card_id' => data_get($payload, 'action.data.card.id'),
            'card_name' => data_get($payload, 'action.data.card.name'),
            'list_id' => data_get($payload, 'action.data.list.id'),
            'list_name' => data_get($payload, 'action.data.list.name'),
            'payload' => $payload,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trello webhook received',
            'source_key' => $integration?->source_key,
        ]);
    }
}