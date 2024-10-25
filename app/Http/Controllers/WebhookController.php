<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        if (isset($_REQUEST['hub_challenge'])) {
            return response($_REQUEST['hub_challenge'], 200)
                  ->header('Content-Type', 'text/plain');
        }

        $webhookData = file_get_contents("php://input");

        $filePath = storage_path('webhooks/webhook_' . now()->timestamp . '.json');
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        file_put_contents($filePath, $webhookData);

        return response()->json(['status' => 'success', 'message' => 'Webhook data saved successfully!']);
    }
}
