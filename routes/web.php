<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\PhoneTokenController;
use App\Models\FcmToken;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// https://mdbootstrap.com/docs/standard/extended/chat/
Route::get('/', function () {
    return view('chat');
});


Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::get('/teste', [ChatController::class, 'teste']);

Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.sendMessage');

Route::get('/teste', function () {

    dd(config("filesystems.disks.public"));

});



Route::get('/chat/send', function () {
    // Cria uma nova instância de Request com os dados desejados
    $myRequest = new Request([
        'from' => '5511951936777',
        'contact_name' => 'Jerê',
        'content' => 'Olá',
        'sent_by_user' => true,
    ]);
    // Instancia o controlador e chama o método sendMessage, passando $myRequest
    $chatController = new ChatController();
    return $chatController->sendMessage($myRequest);
})->name('chat.sendMessage');

// Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.sendMessage');

// 5511951936777 Jerê ola

Route::post('/chat/conversations', [ChatController::class, 'getConversations'])->name('chat.getConversations');
Route::post('/chat/conversations/{id?}', [ChatController::class, 'getMessages'])->name('chat.getMessages');

Route::post('/phone/token', [PhoneTokenController::class, 'store']);

 

// Route::get('/', function () {
//     return view('welcome');
// });
