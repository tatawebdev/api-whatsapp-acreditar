<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::get('/webhook/mockado', [WebhookController::class, 'processWebhookMockado']);


Route::post('/webhook', [WebhookController::class, 'processWebhook']);
Route::get('/webhook', [WebhookController::class, 'handle']);





Route::get('/user', function (Request $request) {
    var_dump($request->user());
    return $request->user();
});
