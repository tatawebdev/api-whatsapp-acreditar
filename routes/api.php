<?php

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::match(['get', 'post'], '/webhook', [WebhookController::class, 'processWebhook']);
Route::get('/webhook/mockado', [WebhookController::class, 'processWebhookMockado']);





// Route::get('conversations', [ChatController::class, 'getConversations']);
// Route::post('send-message', [ChatController::class, 'sendMessage']);
// Route::post('receive-message', [ChatController::class, 'receiveMessage']);



// Route::get('/user', function (Request $request) {
//     var_dump($request->user());
//     return $request->user();
// });
