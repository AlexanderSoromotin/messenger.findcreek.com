<?php

namespace App\Http\Controllers;

use App\Events\WebSocketMessageSent;
use App\Helpers\Websocket;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageContent;
use App\Models\UserChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $chat = Chat::find($id);

        if (!Auth::user()->chats->find($id)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $messages = $chat->messages()
            ->with('content')
            ->latest()
            ->paginate(20);

        $messages->getCollection()->transform(function ($message) {
            $message->makeHidden(['created_at', 'updated_at', 'deleted_at']);
            return $message;
        });

        return response()->json($messages);
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
    public function store(Request $request, $chat)
    {
        $chatId = (int) $chat;
        $replyToMessageId = $request->input('reply_to_message_id');
        $replyText = "";
        $senderId = Auth::user()["id"];
        $text = $request->input('text');
        $attachments = $request->input('attachments_ids');

        $chat = Chat::find($chatId);

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        $sender = $chat->activeMembers()->find($senderId);

        if (empty($text) and empty($attachments)) {
            return response()->json(['error' => 'The message is empty'], 400);
        }

        if (!$sender) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        if (empty($attachments)) {
            $attachments = "[]";
        }

        $newMessage = [
            'chat_id' => $chatId,
            'sender_id' => $senderId,
        ];

        if (!empty($replyToMessageId)) {
            $replyToMessage = Message::find($replyToMessageId);
            if (!$replyToMessage) {
                return response()->json(['error' => 'Replying message not found'], 404);
            } else {
                $replyText = mb_substr($replyToMessage->text, 0, 490) . "...";
                $newMessage["reply_to_message_id"] = $replyToMessageId;
            }
        }

        // Создать новое сообщение
        $message = Message::create($newMessage);

        // Создать запись контента сообщения
        $messageContent = MessageContent::create([
            'message_id' => $message->id,
            'text' => mb_substr($text, 0, 14900),
            "reply_text" => $replyText,
            'attachments_ids' => $attachments,
        ]);

        // Обновить дату последнего сообщения в чате
        $chat->last_message_date = $message->created_at;
        $chat->save();

        $message->load('content');

        return response()->json([
            "message" => "Message created",
            "data" => $message
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Message $message)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Message $message)
    {
        return "edit method :^)";
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($message, Request $request)
    {
        $message = Message::find($message);

        if (!$message) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        // Проверяем, является ли текущий пользователь владельцем чата
        if ($message->sender_id != Auth::user()->id) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Обновляем содержимое сообщения
        $message->content->text = $request->input('text');
        $attachments_ids = [];
        if (!empty($request->input('attachments_ids'))) {


            $attachments_ids = array_map('intval', explode(',', $request->input('attachments_ids')));
            $attachments_ids = array_filter($attachments_ids);
        }
        $message->content->attachments_ids = array_values($attachments_ids);
        $message->content->save();

        $message->makeHidden(['created_at', 'updated_at', 'deleted_at']);

        return response()->json([
            "message" => "Message edited",
            "data" => $message
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($message)
    {
        $message = Message::find($message);

        if (!$message) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        if ($message->sender_id != Auth::user()["id"]) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $message->delete();

        return response()->json([
            "message" => "Message deleted",
            "data" => $message
        ]);
    }

    public static function createTechicalMessage(int $chatId, $type, $data = [], $isSilent = 1)
    {
        $senderId = Auth::user()["id"];
        $chat = Chat::find($chatId);

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

//        if ($type == "user_invitation") {
//            $newMessage = [
//                'chat_id' => $chatId,
//                'sender_id' => $senderId,
//                'is_technical' => true,
//                'technical_data' => [
//                    "event" => $type,
//                    "data" => $data
//                ]
//            ];
//        }
//        if ($type == "chat_created") {
//            $newMessage = [
//                'chat_id' => $chatId,
//                'sender_id' => $senderId,
//                'is_technical' => true,
//                'technical_data' => [
//                    "event" => $type,
//                    "data" => $data
//                ]
//            ];
//        }
//        if ($type == "chat_edited") {
//            $newMessage = [
//                'chat_id' => $chatId,
//                'sender_id' => $senderId,
//                'is_technical' => true,
//                'technical_data' => [
//                    "event" => $type,
//                    "data" => $data
//                ]
//            ];
//        }
//
//        if ($type == "user_added") {
//            $newMessage = [
//                'chat_id' => $chatId,
//                'sender_id' => $senderId,
//                'is_technical' => true,
//                'technical_data' => [
//                    "event" => $type,
//                    "data" => $data
//                ]
//            ];
//        }

//        if ($type == "user_kicked") {
            $newMessage = [
                'chat_id' => $chatId,
                'sender_id' => $senderId,
                'is_technical' => true,
                'technical_data' => [
                    "event" => $type,
                    "data" => $data
                ]
            ];
//        }

        $newMessage["is_silent"] = (boolean) $isSilent;

        // Создать новое сообщение
        $message = Message::create($newMessage);

        // Создать запись контента сообщения
        $messageContent = MessageContent::create([
            'message_id' => $message->id,
            'text' => "",
            'reply_text' => "",
            'attachments_ids' => "[]",
        ]);

        // Обновить дату последнего сообщения в чате
        $chat->last_message_date = $message->created_at;
        $chat->save();

        $message->load('content');

        return $message;
    }
}
