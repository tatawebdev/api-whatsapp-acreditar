<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsApp\WebhookProcessor;
use Illuminate\Support\Facades\Storage;

class WebhookController extends Controller
{
    public function processWebhook(Request $request)
    {
        if ($this->isChallengeRequest($request)) {
            return $this->handleChallenge($request);
        }

        $data = $request->getContent();
        $result = WebhookProcessor::tratarWebhookWhatsApp($data);

        $this->saveWebhookData($request->all());

        return response()->json($result);
    }

    public function processWebhookMockado(Request $request)
    {
        if ($this->isChallengeRequest($request)) {
            return $this->handleChallenge($request);
        }

        WebhookProcessor::debugOn();

        $data = $request->getContent();
        $result = WebhookProcessor::tratarWebhookWhatsApp($data);

        // Para depuração, exibindo o resultado
        dd($result);
        // return response()->json($result);
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
