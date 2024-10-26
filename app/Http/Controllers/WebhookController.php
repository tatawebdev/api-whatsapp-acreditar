<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Services\WhatsApp\WebhookProcessor;
use Illuminate\Support\Facades\Storage;
use WhatsApp\Media;

class WebhookController extends Controller
{
    public function processWebhook(Request $request)
    {
        if ($this->isChallengeRequest($request)) {
            return $this->handleChallenge($request);
        }

        $data = $request->getContent();
        $webhookInfo = WebhookProcessor::tratarWebhookWhatsApp($data);


        $this->saveWebhookData($request->all());


        $methodName = 'process_' . $webhookInfo['event_type'];

        if (method_exists($this, $methodName)) {
            $this->$methodName($webhookInfo);
        }

        return response()->json($webhookInfo);
    }

    public function processWebhookMockado(Request $request)
    {
        WebhookProcessor::debugOn();
        $data = $request->getContent();
        $webhookInfo = WebhookProcessor::tratarWebhookWhatsApp($data);

        // Construa o nome do método a ser chamado
        $methodName = 'process_' . $webhookInfo['event_type'];

dd($methodName);


        if (method_exists($this, $methodName)) {
            return $this->$methodName($webhookInfo);
        }
    }

    private function process_message_text($webhookInfo)
    {

        $conversation = Conversation::firstOrCreate(
            ['from' => $webhookInfo['celular']],
            ['contact_name' => $webhookInfo['name']]
        );

        $message = $conversation->messages()->create([
            'from' => $webhookInfo['celular'],
            'message_id' => $webhookInfo['message_id'],
            'content' => $webhookInfo['message'],
            'timestamp' => $webhookInfo['timestamp'],
            'type' => $webhookInfo['event_type'],
            'sent_by_user' => 1,
        ]);
    }
    private function process_document($webhookInfo)
    {
        // Verifica se o ID do documento está presente
        if (!isset($webhookInfo['document']['id'])) {
            return; // Ou lance uma exceção se preferir
        }

        $directoryPath = 'docs-whatsapp';

        if (!Storage::disk('public')->exists($directoryPath)) {
            Storage::disk('public')->makeDirectory($directoryPath);
        }

        $id = $webhookInfo['document']['id'];

        // Tenta baixar a mídia e verifica o resultado
        $media = new Media();
        if ($media->downloadMedia($id, "$directoryPath/$id")) {

            $conversation = Conversation::firstOrCreate(
                ['from' => $webhookInfo['celular']],
                ['contact_name' => $webhookInfo['name']]
            );

            $message = $conversation->messages()->create([
                'from' => $webhookInfo['celular'],
                'message_id' => $webhookInfo['message_id'],
                'content' => $id,
                'timestamp' => $webhookInfo['timestamp'],
                'type' => $webhookInfo['event_type'],
                'sent_by_user' => 1,
            ]);

            // Você pode adicionar algum log ou ação adicional aqui, se necessário
        } else {
            // Trate o caso em que o download falhou
            // Você pode adicionar um log ou um aviso aqui
            return;
        }
    }

    private function process_message_image($webhookInfo)
    {
        // Lógica para processar imagens
    }

    // Verifica se é uma solicitação de desafio do webhook
    private function isChallengeRequest(Request $request): bool
    {
        return $request->has('hub_challenge');
    }

    // Lida com a solicitação de desafio
    private function handleChallenge(Request $request)
    {
        return response($request->input('hub_challenge'), 200);
    }

    // Salva os dados do webhook em um arquivo JSON
    private function saveWebhookData(array $data): void
    {
        $webhookData = json_encode($data);
        $filePath = 'webhooks/webhook_' . now()->timestamp . '.json';

        Storage::disk('webhooks')->put($filePath, $webhookData);
    }
}
