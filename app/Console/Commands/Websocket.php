<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class Websocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'команда для запуска сервера (вебсокет)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Запуск вебсокета ...");
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new \App\Helpers\Websocket()
                )
            ),
            (int) env("WS_PORT", 8080)
        );

        $server->run();
        $this->info("Вебсокеты запущены");

    }
}
