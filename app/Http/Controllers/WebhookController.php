<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsApp\WebhookProcessor;
use Illuminate\Support\Facades\Storage;

class WebhookController extends Controller
{
    public function processWebhook(Request $request)
    {
        $data = $request->getContent();
        $result = WebhookProcessor::tratarWebhookWhatsApp($data);
        return response()->json($result);
    }

    public function processWebhookMockado(Request $request)
    {

        WebhookProcessor::debugOn();

        $data = $request->getContent();
        $result = WebhookProcessor::tratarWebhookWhatsApp($data);

        dd($result);
        // return response()->json($result);
    }
}
