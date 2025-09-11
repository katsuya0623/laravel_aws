<?php
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

/** 公開ディレクトリを /web に固定 */
$app->usePublicPath(__DIR__);

/** Laravel 11 以降 */
if (method_exists($app, 'handleRequest')) {
    $app->handleRequest(Request::capture());
    return;
}

/** Laravel 10 以前 */
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Request::capture()
);
$response->send();
$kernel->terminate($request, $response);
