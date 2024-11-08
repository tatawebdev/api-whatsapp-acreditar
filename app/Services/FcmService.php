<?php

// app/Services/FcmService.php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class FcmService
{
    protected $clientEmail;
    protected $privateKey;
    protected $projectId;
    protected $accessToken;

    public function __construct()
    {
        // Carrega as configurações do Firebase a partir do config
        $this->clientEmail = config('firebase.client_email');
        $this->privateKey = config('firebase.private_key');
        $this->projectId = config('firebase.project_id');

        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Envia uma notificação FCM para um ou mais dispositivos
     * 
     * @param array $deviceTokens
     * @param string $title
     * @param string $body
     * @param array $data
     * 
     * @return void
     */
    public function sendNotification(array $deviceTokens, string $title, string $body, array $data = [])
    {
        if (count($deviceTokens) == 0) {
            return;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        foreach ($deviceTokens as $deviceToken) {
            $fields = [
                'message' => [
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ]
                ]
            ];

            if (!empty($data)) {
                $fields['message']['data'] = $data;
            }

            $fields['message']['token'] = $deviceToken;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $fields);

            if ($response->failed()) {
                throw new Exception('Failed to send notification: ' . $response->body());
            }
        }
    }

    /**
     * Obtém o token de acesso para autenticação na API do FCM
     * 
     * @return string|null
     */
    protected function getAccessToken()
    {
        $scope = "https://www.googleapis.com/auth/firebase.messaging";
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600;

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $claims = json_encode([
            'iss' => $this->clientEmail,
            'scope' => $scope,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $issuedAt,
            'exp' => $expirationTime,
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlClaims = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claims));

        // Criar a assinatura
        $signatureInput = "$base64UrlHeader.$base64UrlClaims";
        openssl_sign($signatureInput, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = "$base64UrlHeader.$base64UrlClaims.$base64UrlSignature";

        // Solicitar o token
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            throw new Exception('Failed to obtain access token: ' . $response->body());
        }

        $data = $response->json();

        return $data['access_token'] ?? null;
    }
}
