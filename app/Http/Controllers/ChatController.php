<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    // public function getConversations()
    // {
    //     return Conversation::with('messages')->get();
    // }
    public function getConversations()
    {
        $conversations = Conversation::leftJoin('messages', 'conversations.id', '=', 'messages.conversation_id')
            ->select(
                'conversations.id',
                'conversations.contact_name',
                'conversations.from',
                'conversations.updated_at'
            )
            ->groupBy('conversations.id', 'conversations.contact_name', 'conversations.from', 'conversations.updated_at')
            ->orderBy('conversations.updated_at', 'desc')
            ->get();

        return response()->json($conversations);
    }


    public function getMessages($id = null)
    {
        if (!$id)
            return response()->json(['error' => 'ID da conversa é necessário.'], 400);

        $messages = Message::where('conversation_id', $id)->paginate(20);

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
    public function sendMessage(Request $request)
    {

        $validated = $request->validate([
            'from' => 'required',
            'contact_name' => 'required',
            'content' => 'required',
            'sent_by_user' => 'nullable|boolean',
        ]);


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

        // Atualiza o campo updated_at da conversa
        $conversation->updated_at = now();

        // Salva a instância da conversa
        $conversation->save();

        // Criação de uma nova mensagem associada à conversa
        $message = $conversation->messages()->create([
            'content' => $validated['content'],
            'from' => $validated['from'],
            'message_id' => "",
            'timestamp' => strtotime(now()), // Preenchido com a data e hora atual
            'type' => "",
            'sent_by_user' => (int) ($validated['sent_by_user'] ?? false),
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
