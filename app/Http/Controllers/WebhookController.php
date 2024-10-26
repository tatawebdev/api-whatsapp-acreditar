<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;



class WebhookController extends Controller
{
    /**
     * @OA\Post(
     *     path="/webhook",
     *     summary="Receber e processar dados do webhook",
     *     description="Este endpoint recebe dados do webhook e os salva em um arquivo JSON.",
     *     tags={"Webhook"},
     *     @OA\Parameter(
     *         name="hub_challenge",
     *         in="query",
     *         description="Código de verificação do webhook",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Dados do Webhook",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "event": "new_message",
     *                 "data": {
     *                     "id": "12345",
     *                     "content": "Exemplo de conteúdo"
     *                 }
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Retorna o hub_challenge se presente, ou mensagem de sucesso.",
     *         @OA\JsonContent(
     *             type="object",
     *             example={"status": "success", "message": "Webhook data saved successfully!"}
     *         )
     *     )
     * )
     */
    public function handle(Request $request)
    {
        if (isset($_REQUEST['hub_challenge'])) {
            return response($_REQUEST['hub_challenge'], 200)
                  ->header('Content-Type', 'text/plain');
        }

        $webhookData = file_get_contents("php://input");

        $filePath = storage_path('webhooks/webhook_' . now()->timestamp . '.json');
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        file_put_contents($filePath, $webhookData);

        return response()->json(['status' => 'success', 'message' => 'Webhook data saved successfully!']);
    }
}
