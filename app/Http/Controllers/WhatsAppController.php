<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use WhatsApp\Media;

class WhatsAppController extends Controller
{

    protected $whatsappApiUrl = 'https://graph.facebook.com/v20.0/414634731742393/messages'; // URL base da API de Nuvem
    protected $token; // Seu token de acesso
    protected $client; // Cliente Guzzle

    public function teste()
    {

        $media = new Media();
        $mediaId = '931171155733815'; // Substitua pelo seu ID da mídia
        $savePath =  "ok"; // Caminho onde deseja salvar a mídia

        if ($media->downloadMedia($mediaId, $savePath)) {
            echo "Mídia baixada com sucesso!";
        } else {
            echo "Erro ao baixar a mídia.";
        }
    }
    public function __construct()
    {
        // $this->token = env('WHATSAPP_API_TOKEN'); // Carrega o token do arquivo .env
        $this->token = 'EAAMQuliRZBUUBO2k8k3b0e4zqnONq8RZBtNMJwTjEO3I0Y08QFvlAPrl3VttjEMQQF5QwywpLyZBJLsbpLdwbbeqK21bbOmx3ztGUzPZAh8zieyjQz2aEMpIWQD9PXO8j0cY8KGL6B95dAuZCdWu6Tw3Jl6F5aHicuFIqcCdTZCrse9VDSJoNJph01MNm0PZCECCscsKIZAszH45TKS4wUlUxLHXtd2HR7ZBqXqi4'; // Carrega o token do arquivo .env
        $this->client = new Client(); // Inicializa o cliente Guzzle
    }

    public function sendMessage(Request $request)
    {
        // Validação dos dados de entrada
        $request->validate([
            'to' => 'required|string',  // Número de telefone do destinatário
            'message' => 'required|string', // Mensagem a ser enviada
        ]);

        // Dados da mensagem
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $request->input('to'), // Número de telefone
            'type' => 'text',
            'text' => [
                'body' => $request->input('message'),
            ],
        ];

        try {
            // Enviando a requisição para a API
            $response = $this->client->post($this->whatsappApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);
            echo '<pre>';

            var_dump(json_decode($response->getBody(), true));
            exit;
            // Retorna a resposta da API
            return response()->json([
                'status' => 'success',
                'data' => json_decode($response->getBody(), true),
            ], 200);
        } catch (RequestException $e) {
            // Caso ocorra um erro, retorna a mensagem de erro
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ], $e->getCode() ?: 500);
        }
    }
}
