<?php

namespace App\Http\Controllers;

use App\Models\User; // Certifique-se de ter o modelo User importado
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebhookController2 extends Controller
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
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao processar os dados do webhook",
     *         @OA\JsonContent(
     *             type="object",
     *             example={"status": "error", "message": "Invalid data"}
     *         )
     *     )
     * )
     */

    public function handle(Request $request)
    {
        if ($request->has('hub_challenge')) {
            return response($request->input('hub_challenge'), 200);
        }

        // Aqui você deve capturar os dados do webhook
        $webhookData = json_encode($request->all());

        // Define o caminho para o arquivo
        $filePath = 'webhooks/webhook_' . now()->timestamp . '.json';

        // Salva os dados no disco
        Storage::disk('webhooks')->put($filePath, $webhookData);

        return response()->json(['status' => 'success', 'message' => 'Webhook data saved successfully!']);
    }


    /**
     * @OA\Get(
     *     path="/users/index",
     *     operationId="getUsers",
     *     tags={"Users"},
     *     summary="Get list of users",
     *     @OA\Response(
     *         response=200,
     *         description="A list of users",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No users found",
     *         @OA\JsonContent(
     *             type="object",
     *             example={"status": "error", "message": "No users found"}
     *         )
     *     )
     * )
     */
    public function index()
    {
        // Retorna todos os usuários ou uma mensagem de erro caso não haja usuários
        $users = User::all();

        if ($users->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No users found'], 404);
        }

        return response()->json($users);
    }
}
