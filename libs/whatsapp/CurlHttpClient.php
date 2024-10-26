<?php

namespace WhatsApp;

include_once __DIR__ . "/Config.php";

class CurlHttpClient extends Config
{
    public function sendRequest($url, $method, $data = [], $headers = [])
    {
        if (empty($headers)) {
            $headers[] = "Authorization: Bearer " . config('whatsapp.token');
            $headers[] = "Content-Type: application/json";
        }

        // Converte dados para JSON se for um array
        if (is_array($data)) {
            $data = json_encode($data);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            // Captura erro do cURL e envia para o método logError
            $this->logError(curl_error($ch));
        }

        curl_close($ch);
        return $result;
    }

    public function logError($error)
    {
        // Aqui você pode implementar a lógica para logar no banco de dados
        // Exemplo básico usando uma tabela de logs (Laravel)

        throw new \Exception($error);
    }
}
