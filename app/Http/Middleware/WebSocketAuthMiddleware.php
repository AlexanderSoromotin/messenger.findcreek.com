<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Http;

class WebSocketAuthMiddleware
{
    protected $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle($request, Closure $next)
    {
        // Проверяем наличие токена в запросе
        if (! $request->hasHeader('Authorization')) {
            return $this->unauthorizedResponse();
        }

        // Извлекаем токен из заголовка авторизации
        $token = $request->header('Authorization');

        // Выполняем проверку токена и аутентификацию пользователя
        if (! $this->auth->onceUsingId($this->decodeToken($token))) {
//        if (!$this->decodeToken($token)) {
            return $this->unauthorizedResponse();
        }

        return $next($request);
    }

    protected function decodeToken($token)
    {
        // Расшифруйте токен и извлеките идентификатор пользователя или выполните другую логику
        // для получения идентификатора пользователя из токена
        $url = env("ID_SERVICE_URL") . "account.getInfo/";
        $response = Http::get($url, [
            "token" => $token,
            "fields" => "id"
        ]);

        if ($response->successful()) {
            $json = $response->json();

            if (empty($json["error"])) {
                // Проверяем валидность токена и получаем пользователя
                User::firstOrCreate(
                    ["id" => $json["response"]["id"]],
                    ["id" => $json["response"]["id"]]
                );

                $user = User::find($json["response"]["id"]);

                // Проверяем, что пользователь найден
                if ($user) {
                    // Авторизуем пользователя
                    return true;
                }
            }
        }
        return false;
    }

    protected function unauthorizedResponse()
    {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
