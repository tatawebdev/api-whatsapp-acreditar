<?php

use App\Http\Controllers\ChatController;
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
Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.sendMessage');

Route::post('/chat/conversations', [ChatController::class, 'getConversations'])->name('chat.getConversations');
Route::post('/chat/conversations/{id?}', [ChatController::class, 'getMessages'])->name('chat.getMessages');

 

// Route::get('/', function () {
//     return view('welcome');
// });
