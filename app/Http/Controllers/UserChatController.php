<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use App\Models\UserChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserChatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $chatId, $userId)
    {
        $chat = Chat::find($chatId);

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }
        if ($chat->user_id != Auth::user()["id"]) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $user = UserChat::firstOrcreate(
            ["chat_id" => $chatId, "user_id" => $userId],
            ["chat_id" => $chatId, "user_id" => $userId]
        );

        if($user->is_active == "false") {
            $user->is_active = "true";
            $user->save();
        }

        return response()->json(['message' => "The user has been successfully added to the chat"]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserChat $userChat)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserChat $userChat)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserChat $userChat)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserChat $userChat, $chatId, $userId)
    {
        $chat = Chat::find($chatId);

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }
        if ($chat->user_id != Auth::user()["id"]) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        UserChat::query()->where("chat_id", $chatId)->where("user_id", $userId)->update(["is_active" => "false"]);

        return response()->json(['message' => "The user was successfully removed from the chat"]);
    }

    public function leaveChat(Request $request, int $id)
    {
        // Получите данные из запроса
        $userId = Auth::user()["id"];

        // Найти чат по идентификатору
        $chat = Chat::find($id);

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        if ($chat->user_id == $userId) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Удалить связь пользователя с чатом
        UserChat::query()->where("chat_id", $id)->where("user_id", $userId)->update(["is_active" => "false"]);

        return response()->json([
            'message' => "Successfully left the chat",
            "data" => [
                "user_id" => $userId,
                "chat_id" => $id
            ]
        ]);
    }
}
