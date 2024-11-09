<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\FcmToken;
use App\Models\FileModel;
use App\Models\Message;
use App\Services\FcmService;
use Illuminate\Http\Request;
use App\Services\WhatsApp\WebhookProcessor;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\Storage;
use WhatsApp\Media;

class WebhookController extends Controller
{
    private $message = null;
    public function processWebhook(Request $request)
    {
        if ($this->isChallengeRequest($request)) {
            return $this->handleChallenge($request);
        }

        WebhookProcessor::debugOn();
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


    private function createMessage($webhookInfo, $content)
    {
        // Criar ou atualizar a conversa
        $conversation = Conversation::firstOrCreate(
            ['from' => $webhookInfo['celular']],
            ['contact_name' => $webhookInfo['name']]
        );

        $conversation->updated_at = now();
        $conversation->save();

        // Criar a mensagem
        $data = $conversation->messages()->create([
            'from' => $webhookInfo['celular'],
            'message_id' => $webhookInfo['message_id'],
            'content' => is_array($content) ? json_encode($content) : $content,
            'timestamp' => $webhookInfo['timestamp'],
            'type' => $webhookInfo['event_type'],
            'sent_by_user' => "1",
        ]);

        $this->message = $data;

        // WebhookProcessor::setData($data);

        // Obter tokens FCM
        $fcmTokens = FcmToken::all()->pluck('fcm_token')->toArray();
        // Verificar se há tokens FCM
        if (count($fcmTokens) > 0) {
            // Instanciar o serviço FCM
            $fcmService = new FcmService();

            // Preparar dados para a notificação
            $notificationData = [
                'name' => "{$webhookInfo['name']}",
                'phone' => "{$webhookInfo['celular']}",
                'message_id' => "{$webhookInfo['message_id']}",
                'content' => is_array($content) ? json_encode($content) : $content,
                'timestamp' => "{$webhookInfo['timestamp']}",
                'type' => $webhookInfo['event_type'],
                'sent_by_user' => "1",
                'chat_conversation_id' => "$conversation->id",
                'chat_messege_id' => "$data->id",
            ];

            // Enviar a notificação
            $fcmService->sendNotification($fcmTokens, $webhookInfo['name'], $content,  $notificationData);
        }
    }
    private function process_unsupported($webhookInfo) {}
    private function process_status($webhookInfo)
    {
        $message = Message::where('message_id', $webhookInfo['message_id'])->first();

        if (!$message) {
            \App\Models\ErrorLog::create([
                'message' => 'Mensagem não encontrada para o message_id: ' . $webhookInfo['message_id'],
                'stack_trace' => json_encode(debug_backtrace()),
                'file' => __FILE__,
                'line' => __LINE__,
            ]);
            return;
        }

        if ($message->conversation_id) {
            $conversation = Conversation::find($message->conversation_id);

            if ($conversation) {

                $conversation->updated_at = now();
                $conversation->save();

                // Sequência correta dos status
                $validStatusOrder = ['sent', 'delivered', 'read'];
                $currentStatus = $message->status;

                // Verificando a sequência do novo status com o status atual
                $currentIndex = array_search($currentStatus, $validStatusOrder);
                $newIndex = array_search($webhookInfo['status'], $validStatusOrder);

                // Se o novo status for inválido em relação ao atual, não atualize
                if ($newIndex === false || ($currentIndex !== false && $newIndex <= $currentIndex)) {
                    // Se a transição for inválida, use o status atual
                    $currentStatus = $message->status;
                } else {
                    // Se a transição for válida, atualize com o novo status
                    $currentStatus = $webhookInfo['status'];
                }

                $message->status = $currentStatus;

                $message->error_data = json_encode($webhookInfo['errors'] ?? []);

                $message->save();



                // Obter tokens FCM
                $fcmTokens = FcmToken::all()->pluck('fcm_token')->toArray();
                // Verificar se há tokens FCM
                if (count($fcmTokens) > 0) {
                    // Instanciar o serviço FCM
                    $fcmService = new FcmService();

                    // Preparar dados para a notificação
                    $notificationData = [
                        'phone' => "{$webhookInfo['celular']}",
                        'message_id' => "{$webhookInfo['message_id']}",
                        'type' => $webhookInfo['event_type'],
                        'status' => $webhookInfo['status'],
                        'sent_by_user' => "1",
                        'chat_conversation_id' => "$conversation->id",
                        'chat_messege_id' => "$message->id",
                    ];

                    // Enviar a notificação
                    $fcmService->sendNotification($fcmTokens, '', '',  $notificationData);
                }
            } else {

                \App\Models\ErrorLog::create([
                    'message' => 'Conversa não encontrada para conversation_id: ' . $message->conversation_id,
                    'stack_trace' => json_encode(debug_backtrace()),
                    'file' => __FILE__,
                    'line' => __LINE__,
                ]);
            }
        }
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

    private function document($webhookInfo, $mediaId)
    {
        $media = new Media();

        $mediaInfo = $media->getMediaInfo($mediaId);
        // if (!$mediaInfo || empty($mediaInfo['url'])) return $this->logError("URL da mídia não encontrada.");


        // Criar ou atualizar a conversa
        $conversation = Conversation::firstOrCreate(
            ['from' => $webhookInfo['celular']],
            ['contact_name' => $webhookInfo['name']]
        );

        $conversation->updated_at = now();
        $conversation->save();



        // Criar a mensagem
        $message = $conversation->messages()->create([
            'from' => $webhookInfo['celular'],
            'message_id' => $webhookInfo['message_id'],
            'content' => is_array($mediaId) ? json_encode($mediaId) : $mediaId,
            'timestamp' => $webhookInfo['timestamp'],
            'type' => $webhookInfo['event_type'],
            'sent_by_user' => "1",
        ]);

        // Verificando se o arquivo já existe com o sha256 ou file_url
        $file = FileModel::where('file_url', $mediaInfo['url'])
            ->orWhere('file_sha256', $mediaInfo['sha256'])
            ->first();

        $fcmService = new FcmService();

        // Preparar dados para a notificação
        $notificationData = [
            'name' => "{$webhookInfo['name']}",
            'phone' => "{$webhookInfo['celular']}",
            'message_id' => "{$webhookInfo['message_id']}",
            'content' => is_array($mediaId) ? json_encode($mediaId) : $mediaId,
            'timestamp' => "{$webhookInfo['timestamp']}",
            'type' => $webhookInfo['event_type'],
            'type_status' => 'load',
            'sent_by_user' => "1",
            'chat_conversation_id' => "$conversation->id",
            'chat_messege_id' => "$message->id",
        ];


        $fcmTokens = FcmToken::all()->pluck('fcm_token')->toArray();

        // Enviar a notificação
        if (count($fcmTokens) > 0)
            $fcmService->sendNotification($fcmTokens, $webhookInfo['name'], 'Novo documento',  $notificationData, true);


        $directoryPath = 'docs-whatsapp';

        // Verifica se a pasta existe, caso contrário, cria
        if (!Storage::disk('public')->exists($directoryPath)) {
            Storage::disk('public')->makeDirectory($directoryPath);
        }

        $src =   $media->downloadMedia($mediaInfo, "$directoryPath/{$mediaInfo['id']}");
        if (!$file) {
            $file = FileModel::create([
                'file_sha256' => "{$mediaInfo['sha256']}",
                'file_url' => "{$mediaInfo['url']}",
                'file_id' => "{$mediaInfo['id']}",
                'file_size' => "{$mediaInfo['file_size']}",
                'file_mime_type' => "{$mediaInfo['mime_type']}",
                'file_mime_type' => "{$mediaInfo['mime_type']}",
                'file_src' => "{$src}",
            ]);
        }

        $notificationData['type_status'] = 'download';
        $file = [
            'file_sha256' => "{$mediaInfo['sha256']}",
            'file_url' => "{$mediaInfo['url']}",
            'file_id' => "{$mediaInfo['id']}",
            'file_size' => "{$mediaInfo['file_size']}",
            'file_mime_type' => "{$mediaInfo['mime_type']}",
            'file_mime_type' => "{$mediaInfo['mime_type']}",
            'file_src' => "{$src}",
        ];
        $notificationData = array_merge($notificationData, $file);

        if (count($fcmTokens) > 0)
            $fcmService->sendNotification($fcmTokens, $webhookInfo['name'], 'Novo documento',  $notificationData, true);
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
