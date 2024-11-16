;5];  \Z
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . config('whatsapp.token'),
            "User-Agent: Laravel"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

        // Executa a chamada cURL
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Se o código de resposta HTTP for 200 (OK)
        if ($httpCode === 200) {
            // Determina a extensão do arquivo
            $extension = explode('/', curl_getinfo($ch, CURLINFO_CONTENT_TYPE))[1];
            $filePath = "{$savePath}.{$extension}";

            // Salva o arquivo no diretório público
            Storage::disk('public')->put($filePath, $data);

            // return Storage::url($filePath);  
            return $filePath;
        }

        // Retorna um erro se o download falhar
        return $this->logError("Erro ao baixar mídia: Código HTTP $httpCode");
    }

    public function getMediaInfo($mediaId)
    {
        $url = "https://graph.facebook.com/v20.0/{$mediaId}/";
        $headers = ["Authorization: Bearer " . config('whatsapp.token')];

        $result = $this->sendRequest($url, 'GET', $headers);
        $decodedResult = json_decode($result, true);

        return $decodedResult['error'] ?? $decodedResult;
    }
}
