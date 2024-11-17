<?php

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PhoneTokenController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::match(['get', 'post'], '/webhook', [WebhookController::class, 'processWebhook']);
Route::get('/webhook/mockado', [WebhookController::class, 'processWebhookMockado']);


// curl -X GET "http://127.0.0.1:8000/api/send-message?to=5511964870744&message=Olá,%20esta%20é%20uma%20mensagem%20de%20teste!"

Route::get('/send-message', [WhatsAppController::class, 'sendMessage']);
Route::get('/teste', [WhatsAppController::class, 'teste']);


Route::post('/chat/conversations', [ChatController::class, 'getConversations'])->name('chat.getConversations');
Route::post('/chat/conversations/{id?}', [ChatController::class, 'getMessages'])->name('chat.getMessages');
Route::get('/chat/conversations/{id?}', [ChatController::class, 'getMessages'])->name('chat.getMessages');

Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.sendMessage');
Route::post('/chat/send/image', [ChatController::class, 'sendImage'])->name('chat.sendImage');
Route::post('/chat/send/audio', [ChatController::class, 'sendAudio'])->name('chat.sendAudio');

Route::get('/resources/{id_file?}', [ChatController::class, 'downloadResourcesMedia'])->name('chat.downloadResourcesMedia');



Route::get('/audio', function (){


    
$url =    'https://api-whatsapp-acreditar.tataweb.com.br/audio.mp3';


    $objMensagem = \WhatsApp\Message::getInstance();
    $objMensagem->setRecipientNumber('5511951936777');
    $returnMeta = $objMensagem->sendLinkAudioMessage($url);
dd($returnMeta);

});
Route::post('/phone/token', [PhoneTokenController::class, 'store']);



// Route::get('conversations', [ChatController::class, 'getConversations']);
// Route::post('send-message', [ChatController::class, 'sendMessage']);
// Route::post('receive-message', [ChatController::class, 'receiveMessage']);



// Route::get('/user', function (Request $request) {
//     var_dump($request->user());
//     return $request->user();
// });
