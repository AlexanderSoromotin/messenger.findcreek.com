<?php

use App\Events\WebSocketMessageSent;
use App\Helpers\Websocket;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserChatController;
use App\Http\Controllers\UserController;
use App\Listeners\WebSocketMessageSentListener;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get("/hello", [MessageController::class, 'edit'])->middleware('token.auth');

// Chats
Route::post("/chats", [ChatController::class, 'store'])->middleware('token.auth');                          // Создать чат
Route::get("/chats", [ChatController::class, 'index'])->middleware('token.auth');                           // Получить список чатов
Route::get("/chats/{id}", [ChatController::class, 'show'])->middleware('token.auth');                       // Получить данные о чате
Route::patch("/chats/{id}", [ChatController::class, 'update'])->middleware('token.auth');                   // Обновить чат
Route::delete("/chats/{id}", [ChatController::class, 'destroy'])->middleware('token.auth');                 // Удалить чат

// Messages
Route::delete("/messages/{message}", [MessageController::class, 'destroy'])->middleware('token.auth');                  // Удалить сообщение
Route::post("/chats/{chat}/messages", [MessageController::class, 'store'])->middleware('token.auth');                   // Создать сообщение в чате
Route::get("/chats/{id}/messages", [MessageController::class, 'index'])->middleware('token.auth');                      // Получить список сообщений из чата
Route::patch('/messages/{message}', [MessageController::class, 'update'])->middleware('token.auth');    // Обновить сообщение


// Chat user
Route::delete('/chats/{id}/leave', [UserChatController::class, 'leaveChat'])->middleware('token.auth');                 // Покинуть чат
Route::post("/chats/{chatId}/members/{userId}", [UserChatController::class, 'store'])->middleware('token.auth');        // Добавить пользователя в чат
Route::delete("/chats/{chatId}/members/{userId}", [UserChatController::class, 'destroy'])->middleware('token.auth');    // Удалить пользователя из чата
Route::get('/chats/{id}/members', [ChatController::class, 'getChatMembers'])->middleware('token.auth');                 // Получить данные пользователей в чате
// Route::post('/chats/{id}/join', [UserChatController::class, 'joinChat'])->middleware('token.auth');                                // Присоединиться к чату

Route::get('/users/{id}/register', [UserController::class, 'registerUser']);           // Синхронизация аккаунта из идентификационного сервиса с бд мессенджера


$websocket = new Websocket();
Route::get('/websocket', function () use ($websocket) {
    echo "new request";

    $server = IoServer::factory(
        new HttpServer(
            new WsServer($websocket)
        ),
        (int) env("WS_PORT", 8080)
    );

    $server->run();
});
