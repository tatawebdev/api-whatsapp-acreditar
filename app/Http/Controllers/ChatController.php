<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationSession;
use App\Models\FileModel;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use WhatsApp\Media;

class ChatController extends Controller
{
    // public function getConversations()
    // {
    //     return Conversation::with('messages')->get();
    // }
    public function getConversations()
    {
        $conversations = Conversation::leftJoin('messages', 'conversations.id', '=', 'messages.conversation_id')
            ->leftJoin(
                'conversation_sessions',
                function ($join) {
                    $join->on('conversations.id', '=', 'conversation_sessions.conversation_id')
                        ->whereRaw('conversation_sessions.session_end = (select max(session_end) from conversation_sessions where conversation_sessions.conversation_id = conversations.id)');
                }
            )
            ->select(
                'conversations.id',
                'conversations.contact_name',
                'conversations.from',
                'conversations.updated_at',
                'conversation_sessions.session_end'
            )
            ->groupBy(
                'conversations.id',
                'conversations.contact_name',
                'conversations.from',
                'conversations.updated_at',
                'conversation_sessions.session_end'
            )
            ->orderBy('conversations.updated_at', 'desc')
            ->get();

        return response()->json($conversations);
    }



    public function getMessages($id = null)
    {
        if (!$id)
            return response()->json(['error' => 'ID da conversa é necessário.'], 400);

        $messages = Message::with('fileby_content')
            ->where('conversation_id', $id)
            ->paginate(20000);

        return response()->json($messages);
    }

    public function teste()
    {
        $returnMeta = $this->sendMessageWhatsApp('5511951936777', 'ola');

        dd((isset($returnMeta['messages'][0]['id'])));
    }
    private function sendMessageWhatsApp($phone, $message)
    {
        $objMensagem = \WhatsApp\Message::getInstance();
        $objMensagem->setRecipientNumber($phone);
        $returnMeta = $objMensagem->sendMessageText($message);

        return $returnMeta;
    }
    private function sendImageWhatsApp($phone, $url)
    {
        $objMensagem = \WhatsApp\Message::getInstance();
        $objMensagem->setRecipientNumber($phone);
        $returnMeta = $objMensagem->sendLinkImageMessage($url);

        return $returnMeta;
    }


    public function getImage(Request $request)
    {
        $validated = $request->validate([
            'file_id' => 'required',
        ]);

        $media = new Media();
        $mediaInfo = $media->getMediaInfo($validated['file_id']);

        $file = FileModel::where('file_url', $mediaInfo['url'])
            ->orWhere('file_sha256', $mediaInfo['sha256'])
            ->first();

        $directoryPath = 'docs-whatsapp';

        // Verifica se a pasta existe, caso contrário, cria
        if (!Storage::disk('public')->exists($directoryPath)) {
            Storage::disk('public')->makeDirectory($directoryPath);
        }

        $src = $media->downloadMedia($mediaInfo, "$directoryPath/{$mediaInfo['id']}");

        if (!$file) {
            // Se não existir o arquivo no banco de dados, cria um novo registro
            $file = FileModel::create([
                'file_sha256' => $mediaInfo['sha256'],
                'file_url' => $mediaInfo['url'],
                'file_id' => $mediaInfo['id'],
                'file_size' => $mediaInfo['file_size'],
                'file_mime_type' => $mediaInfo['mime_type'],
                'file_src' => $src,
            ]);
        }

        return response()->json([
            'file_url' => $src,
        ]);
    }
    public function sendImage(Request $request)
    {
        // Validação do arquivo
        $validated = $request->validate([
            'from' => 'required',
            'contact_name' => 'required',
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        $session = ConversationSession::activeSession($validated['from']);

        if (!$session) {
            return response()->json([
                'error' => 'Não é possível enviar a mensagem. A sessão de 24 horas para esta conversa expirou.',
            ], 403);
        }

        // Verificar se há um arquivo no request
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            // Salvar a imagem no diretório 'public/images'
            $imagePath = $request->file('file')->store('images', 'public');
            $imageUrl = Storage::url($imagePath);



            $returnMeta = $this->sendImageWhatsApp($validated['from'], $imageUrl);




            // Retornar a URL da imagem para ser usada no frontend
            return response()->json([
                'imageUrl' => Storage::url($imagePath),
            ]);
        }

        // Se falhar, retornar um erro
        return response()->json([
            'error' => 'Falha no upload da imagem.',
        ], 400);
    }


    public function sendMessage(Request $request)
    {

        $validated = $request->validate([
            'from' => 'required',
            'contact_name' => 'required',
            'content' => 'required',
            'sent_by_user' => 'nullable|boolean',
            'unique_identifier' => 'nullable|string',
        ]);

        $session = ConversationSession::activeSession($validated['from']);

        if (!$session) {
            return response()->json([
                'error' => 'Não é possível enviar a mensagem. A sessão de 24 horas para esta conversa expirou.',
            ], 403);
        }

        $returnMeta = $this->sendMessageWhatsApp($validated['from'], $validated['content']);
        // Verifica se o envio pelo WhatsApp foi bem-sucedido
        if (empty($returnMeta['messages'][0]['id'])) {
            return response()->json([
                'error' => 'Falha ao enviar mensagem pelo WhatsApp.',
                'details' => $returnMeta,
            ], 500);
        }
        // Verifica se a conversa já existe ou cria uma nova
        $conversation = Conversation::firstOrCreate(
            ['from' => $validated['from']],
            ['contact_name' => $validated['contact_name']]
        );

        $conversation->updated_at = now();

        $conversation->save();



        // Criação de uma nova mensagem associada à conversa
        $message = $conversation->messages()->create([
            'content' => $validated['content'],
            'from' => $validated['from'],
            'message_id' => $returnMeta['messages'][0]['id'],
            'timestamp' => strtotime(now()), // Preenchido com a data e hora atual
            'type' => "",
            'sent_by_user' => (int) ($validated['sent_by_user'] ?? false),
            'unique_identifier' => ($validated['unique_identifier'] ?? null),
        ]);

        return response()->json($message, 201);
    }


    public function receiveMessage(Request $request)
    {
        $conversation = Conversation::where('from', $request->from)->first();

        if ($conversation) {
            $message = $conversation->messages()->create([
                'content' => $request->content,
                'sent_by_user' => false,
            ]);

            return response()->json($message, 201);
        }

        return response()->json(['error' => 'Conversation not found'], 404);
    }
}
