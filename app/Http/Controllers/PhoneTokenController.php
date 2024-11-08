<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Phone;
use App\Models\FcmToken;

class PhoneTokenController extends Controller
{
    public function store(Request $request)
    {
        // Validação dos dados recebidos
        $validated = $request->validate([
            // 'phone_number' => 'nullable|string|unique:phones,phone_number', // número de telefone é opcional e único
            'fcm_token' => 'required', // token FCM é obrigatório e único
        ]);

        // Cria o token FCM
        $token = FcmToken::firstOrCreate(
            ['fcm_token' => $validated['fcm_token']]
        );

        // Retorna uma resposta de sucesso
        return response()->json([
            'message' => 'Número de telefone associado com sucesso ao token FCM.',
            'phone_number' => $request->phone_number,
            'fcm_token' => $request->fcm_token,
        ], 201);
    }
}
