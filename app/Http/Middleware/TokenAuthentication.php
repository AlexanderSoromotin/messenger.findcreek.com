<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use function PHPUnit\Framework\isNull;

class TokenAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Получаем токен из заголовков
        $token = $request->header('Authorization');
        $token = str_replace("Bearer ", "", $token);

//        if (strripos($token, "=") !== false) {
//            $token = urlencode($token);
//        }

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
                        // Авторизуем пользователя
                        Auth::login($user);

                        // Продолжаем выполнение запроса
                        return $next($request);
                    }
                }
            }
        }

        // Если токен невалидный или отсутствует, возвращаем ошибку авторизации
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
