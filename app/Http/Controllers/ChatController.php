<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // public function getConversations()
    // {
    //     return Conversation::with('messages')->get();
    // }
    public function getConversations()
    {
        return Conversation::get();
    }
    public function getMessages($id = null)
    {
        if (!$id)
            return response()->json(['error' => 'ID da conversa é necessário.'], 400);
    
        $messages = Message::where('conversation_id', $id)->paginate(20);
    
        return response()->json($messages);
    }
    
    

    public function sendMessage(Request $request)
    {
        $conversation = Conversation::firstOrCreate(
            ['from' => $request->from],
            ['contact_name' => $request->contact_name]
        );

        $message = $conversation->messages()->create([
            'content' => $request->content,
            'sent_by_user' => $request->sent_by_user,
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
