<?php

require __DIR__.'/vendor/autoload.php';

use LineMob\Bot\Translation\Setup;
use React\EventLoop\Factory;
use React\Http\Request;
use React\Http\Response;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;

$port = '8888';
$config = [
    'google_project_id' => 'XXXX',
    'google_api_key' => 'XXXX',
    'google_default_locale' => 'th',
    'google_fallback_locale' => 'en',
    'line_channel_token' => '7wCIxlYGX/gK9Fk+5TeriJd5ZIGunpOoCXYqSaqgyDSSOhvU7nRO/lIc61dW0WqqMI0++zl/wR3WNIHLC9XZVT6Jtf7TL6ej6IQCLrEmHHC30Lq/QQdt/DdwjjAWd3hlD7wV1QKcYkS4WmZeLVGuFwdB04t89/1O/w1cDnyilFU=',
    'line_channel_secret' => '459a5fbe2607dbd36eee21d67f376093',
    'isDebug' => true
];

$app = function (Request $request, Response $response) use ($config) {
    $request->on('data', function ($data) use ($request, $config) {
        $receiver = Setup::demo($config);
        $signature = $request->getHeaderLine('X-Line-Signature');

        var_dump($data);

        if ($receiver->validate($data, $signature)) {
            var_dump($receiver->handle($data));
        } else {
            throw new \RuntimeException("Invalid signature: " . $signature);
        }
    });

    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    $response->end("Hello World hot\n");
};

$loop = Factory::create();
$socket = new SocketServer($loop);
$http = new HttpServer($socket, $loop);

$http->on('request', $app);

echo("Server running at http://127.0.0.1:" . $port);

$socket->listen($port);
$loop->run();
