<?php
// app/Http/Controllers/ChatController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message; // Um modelo Message, se estiver armazenando no banco de dados

class ChatController extends Controller
{
    public function index()
    {
        // Exibe a view do chat
        return view('chat');
    }

    public function sendMessage(Request $request)
    {
        // Salva a mensagem no banco de dados
        $message = new Message();
        $message->content = $request->input('message');
        $message->user_id = auth()->id(); // ID do usuário autenticado
        $message->save();

        return response()->json(['message' => $message]);
    }
}
