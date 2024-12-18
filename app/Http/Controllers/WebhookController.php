<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationSession;
use App\Models\FcmToken;
use App\Models\FileModel;
use App\Models\Message;
use App\Services\FcmService;
use Illuminate\Http\Request;
use App\Services\WhatsApp\WebhookProcessor;
use Carbon\Carbon;
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
        $data = json_encode(WebhookProcessor::getMockado()[0]);
        $webhookInfo = WebhookProcessor::tratarWebhookWhatsApp($data);


        // if ($webhookInfo['event_type'] == 'unsupported')
        //     return;


        // Construa o nome do método a ser chamado
        $methodName = 'process_' . $webhookInfo['event_type'];


        if (method_exists($this, $methodName)) {
            return $this->$methodName($webhookInfo);
        }
    }
    function sendNotificationToAllUsers($data, $title = '', $body = '')
    {
        $fcmTokens = FcmToken::all()->pluck('fcm_token')->toArray();

        if (count($fcmTokens) > 0) {
            $fcmService = new FcmService();

            $onlyData = count($data) > 0 && !$title && !$body;

            $fcmService->sendNotification($fcmTokens, $title, $body, $data, $onlyData);
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

        $session = ConversationSession::activeSession($webhookInfo['celular']);

        if (!$session) {
            $session = ConversationSession::createNewSession($conversation->id, $webhookInfo['celular']);
        }

        // Criar a mensagem
        $data = $conversation->messages()->create([
            'from' => $webhookInfo['celular'],
            'message_id' => $webhookInfo['message_id'],
            'content' => is_array($content) ? json_encode($content) : $content,
            'timestamp' => $webhookInfo['timestamp'],
            'type' => $webhookInfo['event_type'],
            'sent_by_user' => "1",
            'conversation_session_id' => $session->id,
        ]);

        $this->message = $data;

        // WebhookProcessor::setData($data);

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
            'session_end' => "$session->session_end",
        ];

        $this->sendNotificationToAllUsers($notificationData, $webhookInfo['name'], $content);

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


                if (!$message->conversation_session_id) {
                    $session = ConversationSession::activeSession($webhookInfo['celular']);

                    if ($session) {
                        $message->conversation_session_id = $session->id;
                    }
                }
                if ($message->conversation_section) {
                    // Dados que podem ser atualizados na seção da conversa
                    $fields = [
                        'whatsapp_conversation_id' => $webhookInfo['conversation']['id'] ?? null,
                        'whatsapp_origin_type' => $webhookInfo['conversation']['origin']['type'] ?? null,
                        'whatsapp_expiration_timestamp' => $webhookInfo['conversation']['expiration_timestamp'] ?? null,
                        'whatsapp_billable' => $webhookInfo['pricing']['billable'] ?? null,
                        'whatsapp_pricing_model' => $webhookInfo['pricing']['pricing_model'] ?? null,
                        'whatsapp_pricing_category' => $webhookInfo['pricing']['category'] ?? null,
                    ];

                    // Verificar se a data de expiração precisa ser atualizada
                    if ($message->conversation_section->whatsapp_expiration_timestamp)
                        $message->conversation_section->session_end = Carbon::createFromTimestamp($message->conversation_section->whatsapp_expiration_timestamp);

                    // Atualizar os campos caso o valor não seja nulo e não esteja definido
                    foreach ($fields as $field => $value) {
                        if ($value !== null && !isset($message->conversation_section->$field)) {
                            $message->conversation_section->$field = $value;
                        }
                    }

                    // Salvar se algum campo foi atualizado
                    if ($message->conversation_section->isDirty()) {
                        $message->conversation_section->save();
                    }
                }

                // $webhookInfo['message_id']}

                $message->save();


                $notificationData = [
                    'phone' => "{$webhookInfo['celular']}",
                    'message_id' => "{$webhookInfo['message_id']}",
                    'type' => $webhookInfo['event_type'],
                    'status' => $webhookInfo['status'],
                    'sent_by_user' => "0",
                    'chat_conversation_id' => "$conversation->id",
                    'chat_messege_id' => "$message->id",
                    'session_end' => "{$message->conversation_section->session_end}",
                    'unique_identifier' => "$message->unique_identifier" ?? '',
                ];

                // Enviar a notificação
                $this->sendNotificationToAllUsers($notificationData);
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
        // $media = new Media();

        // $mediaInfo = $media->getMediaInfo($mediaId);
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
        // $file = FileModel::where('file_url', $mediaInfo['url'])
        //     ->orWhere('file_sha256', $mediaInfo['sha256'])
        //     ->first();

        // Preparar dados para a notificação
        $notificationData = [
            'name' => "{$webhookInfo['name']}",
            'phone' => "{$webhookInfo['celular']}",
            'message_id' => "{$webhookInfo['message_id']}",
            'content' => is_array($mediaId) ? json_encode($mediaId) : $mediaId,
            'timestamp' => "{$webhookInfo['timestamp']}",
            'type' => $webhookInfo['event_type'],
            'type_status' => 'new',
            'sent_by_user' => "1",
            'chat_conversation_id' => "$conversation->id",
            'chat_messege_id' => "$message->id",
        ];

        // Enviar a notificação
        $this->sendNotificationToAllUsers($notificationData, $webhookInfo['name'], 'Novo documento');


        // $directoryPath = 'docs-whatsapp';

        // // Verifica se a pasta existe, caso contrário, cria
        // if (!Storage::disk('public')->exists($directoryPath)) {
        //     Storage::disk('public')->makeDirectory($directoryPath);
        // }
        // return;

        // $src =   $media->downloadMedia($mediaInfo, "$directoryPath/{$mediaInfo['id']}");
        // if (!$file) {
        //     $file = FileModel::create([
        //         'file_sha256' => "{$mediaInfo['sha256']}",
        //         'file_url' => "{$mediaInfo['url']}",
        //         'file_id' => "{$mediaInfo['id']}",
        //         'file_size' => "{$mediaInfo['file_size']}",
        //         'file_mime_type' => "{$mediaInfo['mime_type']}",
        //         'file_src' => "{$src}",
        //     ]);
        // }

        // $notificationData['type_status'] = 'src';
        // $file = [
        //     'file_sha256' => "{$mediaInfo['sha256']}",
        //     'file_id' => "{$mediaInfo['id']}",
        //     'file_size' => "{$mediaInfo['file_size']}",
        //     'file_mime_type' => "{$mediaInfo['mime_type']}",
        //     'file_src' => "{$src}",
        // ];
        // $notificationData = array_merge($notificationData, $file);

        // $this->sendNotificationToAllUsers($notificationData, $webhookInfo['name'], 'Novo documento');

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
