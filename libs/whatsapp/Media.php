<?php

namespace WhatsApp;

use Illuminate\Support\Facades\Storage;

class Media extends CurlHttpClient
{
    public function uploadMedia($mediaFilePath, $mediaType = 'image/jpeg')
    {
        $url = 'https://graph.facebook.com/v18.0/<MEDIA_ID>/media';
        $headers = ["Authorization: Bearer " . config('whatsapp.token')];

        $postData = [
            'file' => new \CURLFile($mediaFilePath, $mediaType),
            'type' => $mediaType,
            'messaging_product' => 'whatsapp'
        ];

        $result = $this->sendRequest($url, 'POST', $headers, $postData);
        $decodedResult = json_decode($result, true);

        return $decodedResult['id'] ?? $this->logError("Erro ao enviar mídia: $result");
    }

    public function downloadMedia($mediaId, $savePath)
    {
        $mediaInfo = $this->getMediaInfo($mediaId);
        if (!$mediaInfo || empty($mediaInfo['url'])) return $this->logError("URL da mídia não encontrada.");

        $ch = curl_init($mediaInfo['url']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . config('whatsapp.token'),
            "User-Agent: Laravel"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

        // Executa a chamada
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $extension = explode('/', curl_getinfo($ch, CURLINFO_CONTENT_TYPE))[1];
            $filePath = "{$savePath}.{$extension}";
            Storage::disk('public')->put($filePath, $data);
            return Storage::path($filePath);
        }

        return $this->logError("Erro ao baixar mídia: Código HTTP $httpCode");
    }

    public function getMediaInfo($mediaId)
    {
        $url = "https://graph.facebook.com/v18.0/{$mediaId}/";
        $headers = ["Authorization: Bearer " . config('whatsapp.token')];

        $result = $this->sendRequest($url, 'GET', $headers);
        $decodedResult = json_decode($result, true);

        return $decodedResult['error'] ?? $decodedResult;
    }
}
