<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class SyncAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'команда для синхронизации аккаунтов с FINDCREEK ID';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Начинается процесс синхронизации");
        $this->info("Отправка запроса на получение списка аккаунтов...");
        if (empty(env("SERVICE_TOKEN"))) {
            $this->info("Ошибка. Отсутствует service_token в .env");
            return;
        }

        $url = env("ID_SERVICE_URL") . "super.getAllUsers/";
        $response = Http::get($url, [
            "service_token" => env("SERVICE_TOKEN"),
            "fields" => "id"
        ]);

        if ($response->successful()) {
            $this->info("Обработка полученных данных");
            $json = $response->json();

            if (empty($json["response"])) {
                $this->info("Ошибка. Ответ сервера: " . $json["error"]["error_msg"] . ". Error code: " .  $json["error"]["error_code"] . ". Error subcode: " .  $json["error"]["error_subcode"]);
                return;
            }

            $this->info("Получено " . count($json["response"]) . " аккаунтов");
            $this->info("Начало синхронизации ...");
            $synced = 0;

            foreach ($json["response"] as $key => $value) {
                if (!User::find($value["id"])) {
                    $synced++;
                    User::create([
                        "id" => $value["id"]
                    ]);
                }
            }

            $this->info("Синхронизация завершена. Добавлено " . $synced . " аккаунтов");
        } else {
            $this->info("Ошибка. Запрос вернул ошибку");
        }



        $this->info("Завершено.");
    }
}
