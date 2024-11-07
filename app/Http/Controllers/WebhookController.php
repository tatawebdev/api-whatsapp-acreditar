<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Services\WhatsApp\WebhookProcessor;
use Hamcrest\Arrays\IsArray;
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


        // if ($webhookInfo['event_type'] == 'unsupported')
        //     return;


        // Construa o nome do método a ser chamado
        $methodName = 'process_' . $webhookInfo['event_type'];

        if (method_exists($this, $methodName)) {
            return $this->$methodName($webhookInfo);
        }
    }


    private function createMessage($webhookInfo,  $content)
    {
        $conversation = Conversation::firstOrCreate(
            ['from' => $webhookInfo['celular']],
            ['contact_name' => $webhookInfo['name']]
        );

        $conversation->updated_at = now();

        $conversation->save();


        $conversation->messages()->create([
            'from' => $webhookInfo['celular'],
            'message_id' => $webhookInfo['message_id'],
            'content' => is_array($content) ? json_encode($content) : $content,
            'timestamp' => $webhookInfo['timestamp'],
            'type' => $webhookInfo['event_type'],
            'sent_by_user' => 1,
        ]);
    }

    private function process_message_text($webhookInfo)
    {
        $this->createMessage($webhookInfo, $webhookInfo['message']);
    }

    private function process_contacts($webhookInfo)
    {
        $this->createMessage($webhookInfo, $webhookInfo['contacts']);
    }


    private function process_image($webhookInfo)
    {
        $this->process_file($webhookInfo);
    }
    private function process_sticker($webhookInfo)
    {
        $this->process_file($webhookInfo);
    }
    private function process_document($webhookInfo)
    {
        $this->process_file($webhookInfo);
    }

    private function process_audio($webhookInfo)
    {
        $this->process_file($webhookInfo);
    }

    private function process_video($webhookInfo)
    {
        $this->process_file($webhookInfo);
    }

    private function process_file($webhookInfo)
    {

        $fileType = $webhookInfo['event_type'];
        // Verifica se o ID do arquivo está presente
        if (!isset($webhookInfo[$fileType]['id'])) {
            throw new \Exception('ID do ' . $fileType . ' não encontrado.' . json_encode($webhookInfo));
        }

        $id = $webhookInfo[$fileType]['id'];
        $this->document($webhookInfo, $id);
    }

    private function document($webhookInfo, $id)
    {

        $directoryPath = 'docs-whatsapp';

        // Verifica se a pasta existe, caso contrário, cria
        if (!Storage::disk('public')->exists($directoryPath)) {
            Storage::disk('public')->makeDirectory($directoryPath);
        }


        // Tenta baixar a mídia e verifica o resultado
        $media = new Media();
        if ($media->downloadMedia($id, "$directoryPath/$id")) {
            // Cria ou recupera a conversa
            $this->createMessage($webhookInfo, $id);
        } else {
            throw new \Exception("Falha ao baixar o documento: ID $id");
        }
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
