<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationSession;
use App\Models\FileModel;
use App\Models\Message;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
            ->leftJoin(
                DB::raw('(SELECT conversation_id, status FROM messages WHERE id IN (SELECT MAX(id) FROM messages GROUP BY conversation_id)) AS latest_message'),
                'conversations.id',
                '=',
                'latest_message.conversation_id'
            )
            ->select(
                'conversations.id',
                'conversations.contact_name',
                'conversations.from',
                'conversations.updated_at',
                'conversation_sessions.session_end',
                'latest_message.status as last_message_status'
            )
            ->groupBy(
                'conversations.id',
                'conversations.contact_name',
                'conversations.from',
                'conversations.updated_at',
                'conversation_sessions.session_end',
                'latest_message.status'
            )
            ->orderBy('conversations.updated_at', 'desc')
            ->get();

        return response()->json($conversations);
    }




    // public function getMessages($id = null)
    // {
    //     if (!$id)
    //         return response()->json(['error' => 'ID da conversa é necessário.'], 400);

    //     $messages = Message::with('fileby_content')
    //         ->where('conversation_id', $id)
    //         ->paginate(20000);

    //     return response()->json($messages);
    // }
    public function getMessages($id = null)
    {
        if (!$id) {
            return response()->json(['error' => 'ID da conversa é necessário.'], 400);
        }

        $messages = Message::with('fileby_content')
            ->where('conversation_id', $id)
            // ->orderBy('timestamp', 'ansc')
            ->paginate(1000000);


        // Retornar as mensagens paginadas
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
    private function sendAudioWhatsApp($phone, $url)
    {
        $objMensagem = \WhatsApp\Message::getInstance();
        $objMensagem->setRecipientNumber($phone);
        $returnMeta = $objMensagem->sendLinkAudioMessage($url);

        return $returnMeta;
    }


    public function downloadResourcesMedia(Request $request, $file_id = null)
    {
        // Verifica se o file_id foi fornecido
        if (!$file_id) {
            return response()->json(['error' => 'O parâmetro "file_id" precisa ser fornecido.'], 400);
        }


        // Tenta buscar o arquivo pelo file_id
        $file = FileModel::where('file_id', $file_id)->first();



        // Se o arquivo for encontrado, retorna o arquivo
        if ($file) {
            return response()->json($file, 200);
        }


        $media = new Media();
        $mediaInfo = $media->getMediaInfo($file_id);

        // Verifica se a mídia foi encontrada e se os dados necessários estão completos
        if (!$mediaInfo || !isset($mediaInfo['url'], $mediaInfo['sha256'])) {
            return response()->json([
                'error' => 'Mídia não encontrada ou dados incompletos.'
            ], 404);
        }

        // Verifica se o arquivo já existe
        $file = FileModel::where('file_url', $mediaInfo['url'])
            ->orWhere('file_sha256', $mediaInfo['sha256'])
            ->first();

        // Se o arquivo não existir, faz o download e cria um novo registro
        if (!$file) {
            $directoryPath = 'resources-whatsapp';

            // Cria o diretório caso não exista
            if (!Storage::disk('public')->exists($directoryPath)) {
                Storage::disk('public')->makeDirectory($directoryPath);
            }

            // Faz o download da mídia
            $src = $media->downloadMedia($mediaInfo, "$directoryPath/{$mediaInfo['id']}");

            // Cria o novo arquivo no banco
            $file = FileModel::create([
                'file_sha256' => $mediaInfo['sha256'],
                'file_url' => Storage::url($src),
                'file_id' => $mediaInfo['id'],
                'file_size' => $mediaInfo['file_size'],
                'file_mime_type' => $mediaInfo['mime_type'],
                'file_src' => Storage::disk('public')->path($src),
            ]);
        }
        // Retorna o arquivo para o download
        return response()->json($file, 200);
    }
    public function sendAudio(Request $request)
    {
        // Validação do arquivo
        $validated = $request->validate([
            'from' => 'required',
            'contact_name' => 'required',
            'audio' => 'required|file', // Garante que o campo é um arquivo de áudio válido
        ]);

        // Verifica se há uma sessão ativa
        $session = ConversationSession::activeSession($validated['from']);
        if (!$session) {
            return response()->json(['error' => 'Sessão expirada.'], 403);
        }

        if ($request->hasFile('audio') && $request->file('audio')->isValid()) {
            $file = $request->file('audio');
            $fileMimeType = $file->getMimeType();

            // Gera nome para o arquivo mp3
            $mp3FileName = 'audio_' . time() . '.mp3';

            // Armazenar o arquivo de áudio original
            $audioPath = $file->storeAs('audios', 'original_' . $file->getClientOriginalName(), 'public');
            $audioUrl = Storage::url($audioPath);

            // Convertendo o arquivo para MP3 usando FFMpeg
            $ffmpeg = FFMpeg::create();
            $audio = $ffmpeg->open($file->getRealPath());
            $convertedPath = storage_path('app/public/audios/' . $mp3FileName);

            // Salva o arquivo convertido para MP3
            $audio->save(new Mp3(), $convertedPath);

            // Gera a URL do arquivo convertido
            $convertedUrl = Storage::url('audios/' . $mp3FileName);

            // Gerar o hash SHA-256 do arquivo original
            $fileSha256 = hash_file('sha256', $file->getRealPath());

            // Gera meta para envio de áudio (considerando a função sendAudioWhatsApp como exemplo)
            $returnMeta = $this->sendAudioWhatsApp($validated['from'], $convertedUrl);

            // Gerar um novo ID para o arquivo
            $fileId = 'send_' . uniqid();

            // Criação ou atualização da conversa
            $conversation = Conversation::firstOrCreate(
                ['from' => $validated['from']],
                ['contact_name' => $validated['contact_name']]
            );

            $conversation->updated_at = now();
            $conversation->save();

            // Criação da mensagem
            $message = $conversation->messages()->create([
                'content' => $fileId,
                'from' => $validated['from'],
                'message_id' => $returnMeta['messages'][0]['id'],
                'timestamp' => strtotime(now()),
                'type' => "audio", // Alterado de "image" para "audio"
                'sent_by_user' => (int) ($validated['sent_by_user'] ?? false),
                'unique_identifier' => ($validated['unique_identifier'] ?? null),
            ]);

            // Armazenando os dados do arquivo
            $message->fileby_content = FileModel::create([
                'file_sha256' => $fileSha256, // Armazenar o hash SHA-256
                'file_url' => $convertedUrl,
                'file_id' => $fileId,
                'file_size' => $file->getSize(),
                'file_mime_type' => 'audio/mp3', // Garantir o tipo MIME como audio/mp3 após conversão
                'file_src' => $file->getClientOriginalName(),
            ]);

            return response()->json([
                'audioUrl' => $convertedUrl,
                'message' => $message,
                'fileMimeType' => 'audio/mp3', // Tipo MIME do arquivo convertido
            ]);
        }

        return response()->json(['error' => 'Arquivo inválido ou ausente.'], 400);
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
            $file = $request->file('file');

            $fileSha256 = hash_file('sha256', $file->getRealPath());

            $existingFile = FileModel::where('file_sha256', $fileSha256)->first();

            //caso precisar loga de um file melhora

            // if ($existingFile) {
            //     return response()->json([
            //         'error' => 'Este arquivo já foi enviado anteriormente.',
            //         'existingFile' => $existingFile,
            //     ], 400);
            // }

            // Salvar a imagem no diretório 'public/images'
            $imagePath = $file->store('images', 'public');
            $imageUrl = Storage::url($imagePath);

            // Gera meta para envio de imagem
            $returnMeta = $this->sendImageWhatsApp($validated['from'], $imageUrl);

            // Gerar um novo ID para o arquivo
            $fileId = 'send_' . uniqid();

            $conversation = Conversation::firstOrCreate(
                ['from' => $validated['from']],
                ['contact_name' => $validated['contact_name']]
            );

            $conversation->updated_at = now();
            $conversation->save();

            $message = $conversation->messages()->create([
                'content' => $fileId,
                'from' => $validated['from'],
                'message_id' => $returnMeta['messages'][0]['id'],
                'timestamp' => strtotime(now()),
                'type' => "image",
                'sent_by_user' => (int) ($validated['sent_by_user'] ?? false),
                'unique_identifier' => ($validated['unique_identifier'] ?? null),
            ]);

            $message->fileby_content = FileModel::create([
                'file_sha256' => $fileSha256, // Armazenar o hash SHA-256
                'file_url' => $imageUrl,
                'file_id' => $fileId,
                'file_size' => $file->getSize(),
                'file_mime_type' => $file->getMimeType(),
                'file_src' => $file->getClientOriginalName(),
            ]);


            return response()->json([
                'imageUrl' => $imageUrl,
                'message' => $message,
            ]);
        }

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
            'type' => "message_text",
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
