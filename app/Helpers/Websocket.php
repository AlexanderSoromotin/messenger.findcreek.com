<?php

namespace App\Helpers;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserChatController;

class Websocket implements MessageComponentInterface
{
    public $clients;
    public $authorizedClients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->authorizedClients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Декодируем сообщение, чтобы получить данные запроса
        $data = json_decode($msg, true);

        // Определяем тип запроса
        $type = $data['type'] ?? null;

        // Проверяем тип запроса и вызываем соответствующий метод контроллера
        if ($type === 'api') {
            $response = $this->handleApiRequest($from, $data);
        } elseif ($type === 'message') {
            $response = $this->handleMessageRequest($from, $data);
        } else {
            $response = $this->createErrorResponse('Invalid request type');
        }

        // Отправляем ответ обратно клиенту
        $from->send(json_encode($response));
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $this->clients->detach($conn);
        $conn->close();
    }

    protected function handleApiRequest(ConnectionInterface $from, $data)
    {
        $url = $data['url'];
        $method = $data['method'];
        $params = $data['params'];
        if (empty($params)) {
            $params = [];
        }
        if (empty($method)) {
            $method = "get";
        }
        if (empty($url)) {
            return [
                "type" => "api",
                "message" => "error",
                "data" => [
                    "url" => $url,
                    "method" => $method,
                    "params" => $params
                ]
            ];
        }

        $user = null;
        $userToken = null;
        foreach ($this->authorizedClients as $key => $value) {
            if ($value->resourceId == $from->resourceId) {
                $user = $value->user;
                $userToken = $value->token;
            }
        }

        if (!$user) {
            return $this->createErrorResponse("Invalid authentication");
        }

        // Создание запроса
        $request = Request::create("/api" . $url, $method, $params);

        // Аутентификация пользователя
        Auth::login($user);

        $request->headers->add(['Authorization' => 'Bearer ' . $userToken]);

        // Получение результата маршрута, соответствующего запросу
        $route = Route::getRoutes()->match($request);
        $controller = explode("\\", $route->action["uses"]);
        $controller = $controller[count($controller) - 1];
        $outputMessage = "Request completed";

        // Вызов контроллера и передача запроса
        $response = app()->handle($request->setRouteResolver(function () use ($route) {
            return $route;
        }));

        // Создание объекта Response
        $responseData = $response->getContent();
        $responseStatus = $response->getStatusCode();
        $headers = $response->headers->all();

        $websocketResponse = [
            'response' => json_decode($responseData, 1),
            'status' => $responseStatus,
            'headers' => $headers
        ];

        $controllerResponse = $websocketResponse["response"];

        if ($websocketResponse["status"] != 200 or !empty($websocketResponse["response"]["error"])) {
            return [
                "type" => "api",
                "message" => "error",
                "data" => $websocketResponse
            ];
        }

        if ($controller == "MessageController@store") {
            // Отправка сообщения участникам чата о том, что появилось новое сообщение
            $chatId = $controllerResponse["data"]["chat_id"];

            $outputMessage = "Message created";
            $this->sendMessageToChatsUsers($from, $chatId, $controllerResponse["data"], $outputMessage);
        }
        if ($controller == "MessageController@update") {
            // Отправка сообщения участникам чата о том, что сообщение было отредактировано
            $chatId = $controllerResponse["data"]["chat_id"];

            $outputMessage = "Message edited";
            $this->sendMessageToChatsUsers($from, $chatId, $controllerResponse["data"], $outputMessage);
        }
        if ($controller == "MessageController@destroy") {
            // Отправка сообщения участникам чата о том, что сообщение было удалено
            $chatId = $controllerResponse["data"]["chat_id"];

            $outputMessage = "Message deleted";
            $this->sendMessageToChatsUsers($from, $chatId, $controllerResponse["data"], $outputMessage);
        }

        if ($controller == "ChatController@store") {
            // Отправка сообщения участникам чата о том, что создан чат
            $chatId = $controllerResponse["data"]["id"];

            $outputMessage = "Chat created";
            $technicalMessage = MessageController::createTechicalMessage($chatId, 'chat_created', [], 0);
            $this->sendMessageToChatsUsers($from, $chatId, $controllerResponse["data"], $outputMessage);
            $this->sendMessageToChatsUsers($from, $chatId, $technicalMessage, "Message created");
        }
        if ($controller == "ChatController@update") {
            // Отправка сообщения участникам чата о том, что чат обновлён
            $chatId = $controllerResponse["data"]["id"];

            $outputMessage = "Chat edited";
            $technicalMessage = MessageController::createTechicalMessage($chatId, 'chat_edited', [
                "user_id" => $controllerResponse["data"]["user_id"]
            ]);
//            $this->sendMessageToChatsUsers($from, $chatId, $controllerResponse["data"], $outputMessage);
            $this->sendMessageToChatsUsers($from, $chatId, $technicalMessage, "Message created");
        }

        if ($controller == "UserChatController@leaveChat") {
            // Отправка сообщения участникам чата о том, что один из пользователей покинул чат
            $chatId = $controllerResponse["data"]["chat_id"];

            $outputMessage = "User left the chat";
            $technicalMessage = MessageController::createTechicalMessage($chatId, 'user_left', [
                "user_id" => $controllerResponse["data"]["user_id"]
            ]);
//            $this->sendMessageToChatsUsers($from, $chatId, $controllerResponse, $outputMessage);
            $this->sendMessageToChatsUsers($from, $chatId, $technicalMessage, "Message created");
        }

        if ($controller == "UserChatController@store") {
            // Отправка сообщения участникам чата о том, что пользователя добавили в чат
            $chatId = $controllerResponse["data"]["chat"]["id"];

            $outputMessage = "User was added to the chat";
            $technicalMessage = MessageController::createTechicalMessage($chatId, 'user_added', [
                "user_id" => $controllerResponse["data"]["user"]["id"]
            ]);
//            $this->sendMessageToChatsUsers($from, $chatId, $controllerResponse, $outputMessage);
            $this->sendMessageToChatsUsers($from, $chatId, $technicalMessage, "Message created");
        }

        if ($controller == "UserChatController@destroy") {
            // Отправка сообщения участникам чата о том, что пользователя кикнули из чата
            $chatId = $controllerResponse["data"]["chat"]["id"];

            $outputMessage = "User was kicked out of the chat";
            $technicalMessage = MessageController::createTechicalMessage($chatId, 'user_kicked', [
                "user_id" => $controllerResponse["data"]["user"]["id"]
            ]);
//            $this->sendMessageToChatsUsers($from, $chatId, $controllerResponse, $outputMessage);
            $this->sendMessageToChatsUsers($from, $chatId, $technicalMessage, "Message created");
        }

        return [
            "type" => "api",
            "message" => $outputMessage,
            "data" => $websocketResponse
        ];
    }

    protected function handleMessageRequest(ConnectionInterface $from, $data)
    {
        if (empty($data["message"])) {
            return null;
        }

        if ($data["message"] == "authorization") {
            $token = str_replace("Bearer ", "", $data["data"]["token"]);

            $userData = $this->getUserIdByToken($token);
            if ($userData) {
                $from->user = $userData;
                $from->token = $token;
                $this->authorizedClients->attach($from);

                return [
                    'type' => 'message',
                    'data' => [
                        "message" => 'The user is successfully authorized'
                    ],
                ];
            }
            return [
                'type' => 'message',
                'data' => [
                    "message" => 'Authorization failed'
                ],
            ];
        }

        // Возвращаем ответ клиенту
        return [
            'type' => 'message',
            'data' => [
                "message" => 'Invalid message'
            ],
        ];
    }

    protected function getUserIdByToken($token)
    {
        // Отправка токена на сторонний микросервис авторизации
        // и получение данных пользователя

        // Проверяем, что токен присутствует
        if ($token) {
            $url = env("ID_SERVICE_URL") . "account.getInfo/";
            $response = Http::get($url, [
                "token" => $token,
                "fields" => "id"
            ]);

            if ($response->successful()) {
                $json = $response->json();

                if (empty($json["error"])) {
                    // Проверяем валидность токена и получаем пользователя
                    $user = User::firstOrCreate([
                        "id" => $json["response"]["id"]
                    ],
                        [
                            "id" => $json["response"]["id"]
                        ]);

                    $user = User::find($json["response"]["id"]);

                    // Проверяем, что пользователь найден
                    if ($user) {
                        return $user;
                    }
                }
            }
        }
        return null;
    }

    protected function createErrorResponse($message)
    {
        return [
            'type' => 'error',
            'message' => $message,
        ];
    }

    public function getClients()
    {
        return $this->clients;
    }

    public function getClientByResourceId($resourceId, $authorized = 1)
    {
        if ($authorized) {
            foreach ($this->authorizedClients as $client) {
                if ($client->resourceId === $resourceId) {
                    return $client;
                }
            }
            return null;
        }

        foreach ($this->clients as $client) {
            if ($client->resourceId === $resourceId) {
                return $client;
            }
        }

        return null;
    }

    public function sendMessageToChatsUsers ($from, $chatsIds, $data, $wsMessageType)
    {
        if (gettype($chatsIds) == "integer") {
            $chatsIds = [$chatsIds];
        }

        // Получить чаты с указанными идентификаторами
        $chats = Chat::whereIn('id', $chatsIds)->get();

        // Создать пустой массив для пользователей
        $users = [];

        // Для каждого чата получить пользователей и добавить их в массив
        foreach ($chats as $chat) {
            $users = array_merge($users, $chat->users->toArray());
        }

        // Преобразовать массив в коллекцию для уникальности пользователей
        $uniqueUsers = collect($users)->unique('id')->values()->all();

        $fromUser = 0;
        if ($from) {
            $fromUser = $this->getClientByResourceId($from->resourceId);
        }

        foreach ($uniqueUsers as $user) {
            foreach ($this->authorizedClients as $client) {
                // in_array($wsMessageType, ["New message", "New chat"])
                if ($fromUser->user->id == $client->user->id) {
                    continue;
                }

                if ($user["id"] == $client->user->id) {
                    // Отсылаем сообщение
                    $output = [
                        "type" => "message",
                        "message" => $wsMessageType,
                        "data" => $data
                    ];
                    $client->send(json_encode($output, JSON_UNESCAPED_UNICODE));
                }
            }
        }

    }
}
