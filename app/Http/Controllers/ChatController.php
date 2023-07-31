<?php

namespace App\Http\Controllers;

use App\Events\ChatCreated;
use App\Models\Chat;
use App\Models\ChatGroup;
use App\Models\User;
use App\Models\UserChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Получение списка чатов
        $user = Auth::user();
        $chats = $user->chats()->paginate(20);

        $chats->getCollection()->transform(function ($chat) {
            $chat->makeHidden(['pivot']);
            return $chat;
        });

        $chats->makeHidden(["pivot"]);
        return $chats;
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
    public function store(Request $request)
    {
        // Создание чата
        $user = Auth::user();
        $name = $request->input('name');
        $chatGroupId = (int) $request->input('chat_group_id');
        $avatar = $request->input('avatar');
        $usersIds = $request->input('users_ids');
        $userId = $user["id"];

        if (ChatGroup::where('id', $chatGroupId)->where('user_id', $userId)->first()) {
            $chat["chat_group_id"] = $chatGroupId;
        }

        $chat = [];

        $chat["user_id"] = $userId;
        $chat["avatar"] = $avatar;
        if (!empty($name)) {
            $chat["name"] = $name;
        }

        if (empty($usersIds)) {
            $usersIds = collect($userId);
        } else {
            $usersIds = explode(",", $usersIds);
            $usersIds[] = $userId;

            // Создание коллекции из массива чисел и удаление дубликатов
            $usersIds = collect($usersIds)->map(function ($number) {
                if (!User::find($number)) {
                    return false;
                }
                return (int) $number;
            })->unique();
        }

        if ($usersIds->count() < 2) {
            return response()->json(['error' => 'Invalid users_ids'], 400);
        }
        if ($usersIds->count() > 2) {
            $chat["type"] = "chat";
        }

        $newChat = Chat::create($chat);

        if ($newChat) {
            // Вывод коллекции без дубликатов
            $usersIds->each(function ($number) use ($newChat) {

                // Создание записей в транзитивной таблице
                UserChat::firstOrCreate(
                    ["user_id" => $number, "chat_id" => $newChat->id],
                    ["user_id" => $number, "chat_id" => $newChat->id]
                );
            });
            return response()->json([
                "message" => "Chat created",
                "data" => $newChat
            ]);
        }

        return response()->json(['error' => 'Internal error'], 500);
    }

    /**
     * Display the specified resource.
     */
    public function show(Chat $chat, $id)
    {
        // Получение чата
        $user = Auth::user();
        $chat = $user->chats()->find($id);

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        $chat->makeHidden(['pivot', 'updated_at', 'deleted_at']);
        $users = $chat->activeMembers;
        $users->makeHidden(['pivot', 'created_at', 'updated_at']);

        return ['chat' => $chat];

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Chat $chat)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Получите данные из запроса
        $data = $request->only(['chat_group_id', 'name', 'avatar']);

        if ($data["chat_group_id"] == 0) {
            $data["chat_group_id"] = null;
        }

        // Найти чат по идентификатору
        $chat = Chat::find($id);

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        if ($chat->user_id != Auth::user()["id"]) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Обновить данные чата
        $chat->update($data);

        // Ответить с обновленными данными чата
        return response()->json([
            "message" => "Chat edited",
            "data" => $chat
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        // Удаление чата
        $chat = Chat::query()->where(['id' => $id])->first();

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }
        if ($chat->user_id != Auth::user()["id"]) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $chat->delete();
        UserChat::query()->where('chat_id', $id)->delete();

        return response()->json([
            'message' => 'Chat successfully deleted',
            "data" => $chat
        ]);
    }

    public function getChatMembers($chat)
    {
        $chat = Chat::find($chat);
        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }
        if ($chat->user_id != Auth::user()["id"]) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $members = $chat->activeMembers;

        $members->makeHidden(['created_at', 'updated_at', 'pivot']);

        return response()->json(['members' => $members]);
    }
}
