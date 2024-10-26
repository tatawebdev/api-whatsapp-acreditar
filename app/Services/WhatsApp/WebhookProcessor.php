<?php

namespace App\Services\WhatsApp;

use App\Models\WebhookData;
use Illuminate\Support\Facades\Storage;

class WebhookProcessor
{
    public static $debug = false;
    private static $filename = 'webhooks_whatsapp/webhook_data.json';

    public static function debugOn()
    {
        self::$debug = true;
    }

    public static function debugOff()
    {
        self::$debug = false;
    }

    public static function sanitizeString($string)
    {
        $what = ['ä', 'ã', 'à', 'á', 'â', 'ê', 'ë', 'è', 'é', 'ï', 'ì', 'í', 'ö', 'õ', 'ò', 'ó', 'ô', 'ü', 'ù', 'ú', 'û', 'À', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ', 'ç', 'Ç'];
        $by = ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'a', 'a', 'e', 'i', 'o', 'u', 'n', 'n', 'c', 'c'];
        return str_replace($what, $by, $string);
    }

    public static function setData($data)
    {
        $conteudoOriginalArray = self::getData() ?: [];

        if (is_array($data)) {
            $jsonData = $data;
        } else {
            $jsonData = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Invalid JSON data provided: ' . json_last_error_msg());
                $jsonData = [];
            }
        }

        $conteudoOriginalArray[] = $jsonData;

        $novoConteudoJSON = json_encode($conteudoOriginalArray, JSON_PRETTY_PRINT);

        Storage::put(self::$filename, $novoConteudoJSON);
    }

    public static function getData()
    {
        if (Storage::exists(self::$filename)) {
            $conteudo = Storage::get(self::$filename);
            return json_decode($conteudo, true) ?: [];
        }

        return self::$debug ? self::getMockado() : [];
    }


    public static function getMethod()
    {
        return request()->method();
    }

    public static function isPOST()
    {
        return request()->isMethod('post');
    }

    public static function getMockado()
    {
        $json = '{
    "object": "whatsapp_business_account",
    "entry": [
        {
            "id": "463862936810412",
            "changes": [
                {
                    "value": {
                        "messaging_product": "whatsapp",
                        "metadata": {
                            "display_phone_number": "15551918890",
                            "phone_number_id": "414634731742393"
                        },
                        "contacts": [
                            {
                                "profile": {
                                    "name": "Tata Web"
                                },
                                "wa_id": "5511964870744"
                            }
                        ],
                        "messages": [
                            {
                                "from": "5511964870744",
                                "id": "wamid.HBgNNTUxMTk2NDg3MDc0NBUCABIYFjNFQjA3RkM1QzVFRTI2OTVDNkJGQ0QA",
                                "timestamp": "1729953067",
                                "text": {
                                    "body": "ola"
                                },
                                "type": "text"
                            }
                        ]
                    },
                    "field": "messages"
                }
            ]
        }
    ]
}';

        return [json_decode($json, true)];
    }

    public static function tratarWebhookWhatsApp($webhookData = null)
    {
        if (!$webhookData) {
            if (self::$debug) {
                $data = self::getData();
                $webhookData = json_encode(end($data));
            } else {
                $webhookData = file_get_contents("php://input");
            }
        }


        if (self::$debug) {
            self::setData($webhookData);
        }
        // Inicializa o array de resultados
        $result = [
            'event_type' => null,
            'celular' => null,
        ];

        // Verifica se há dados no webhook
        if (empty($webhookData)) {
            return $result;
        }

        // Decodifica o JSON do webhook
        $event = json_decode($webhookData, true);
        $entry = $event['entry'][0] ?? null;
        $changes = $entry['changes'][0] ?? null;
        $changesValue = $changes['value'] ?? null;
        $contacts = $changesValue['contacts'][0] ?? null;

        // Verifica e atribui o ID, se presente
        if (isset($entry['id'])) {
            $result['conta_id'] = $entry['id'];
        }
        if (isset($contacts['profile']['name'])) {
            $result['name'] = $contacts['profile']['name'];
        }
        if (isset($changesValue['metadata']['phone_number_id'])) {
            $result['api_phone_id'] = $changesValue['metadata']['phone_number_id'];
            $result['api_phone_number'] = $changesValue['metadata']['display_phone_number'];
        }

        // define('API_PHONE_ID', $result['api_phone_id'] ?? env('API_PHONE_PRODUCAO'));

        // Define o tipo de evento com base nos dados do webhook
        if (isset($changesValue['statuses'])) {
            $result['event_type'] = 'status';
            $result['celular'] = $changesValue['statuses'][0]['recipient_id'];
            $result['status'] = $changesValue['statuses'][0]['status'];
            $result['status_id'] = $changesValue['statuses'][0]['id'];
            $result['conversation'] = $changesValue['statuses'][0]['conversation'] ?? null;
        } elseif (isset($changesValue['messages'])) {
            $message = $changesValue['messages'][0];
            $result['celular'] = $message['from'];
            $result['event_type'] = $message['type'];
            $result['timestamp'] = $message['timestamp'];
            $result['message_id'] = $message['id'] ?? null;

            // Ação com base no tipo de mensagem
            switch ($message['type']) {
                case 'text':
                    if (isset($message['text']['body'])) {
                        $result['event_type'] = 'message_text';
                        $result['message'] = $message['text']['body'];
                    }
                    break;
                case 'button':
                    if (isset($message['button']['payload'])) {
                        $result['event_type'] = 'message_button';
                        $result['message'] = $message['button']['payload'];
                    }
                    break;
                case 'interactive':
                    if (isset($message['interactive']['button_reply']['title'])) {
                        $result['event_type'] = 'message_button';
                        $result['message'] = $message['interactive']['button_reply']['title'];
                        $result['interactive_id'] = $message['interactive']['button_reply']['id'];
                    } elseif (isset($message['interactive']['list_reply']['id'])) {
                        $result['event_type'] = 'interactive';
                        $result['interactive'] = $message['interactive']['list_reply'];
                        $result['message'] = $message['interactive']['list_reply']['title'];
                        $result['interactive_id'] = $message['interactive']['list_reply']['id'];
                    }
                    break;
            }
        }

        $testNumbersString = env('TEST_NUMBERS', '');
        $numerosTeste = array_map('trim', explode(',', $testNumbersString));
        // define('TESTE', in_array($result['api_phone_number'], $numerosTeste));

        // Armazena o JSON original do webhook
        $result['json'] = $event;

        WebhookData::create([
            'event_type' => $result['event_type'] ?? null,
            'celular' => $result['celular'] ?? null,
            'conta_id' => $result['conta_id'] ?? null,
            'api_phone_id' => $result['api_phone_id'] ?? null,
            'api_phone_number' => $result['api_phone_number'] ?? null,
            'status' => $result['status'] ?? null,
            'status_id' => $result['status_id'] ?? null,
            'conversation' => $result['conversation'] ?? null,
            'json' => $result['json'] ?? null,
        ]);


        return $result;
    }
}
