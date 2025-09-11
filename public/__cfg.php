<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
header('Content-Type: text/plain');
echo "driver: ".config('session.driver').PHP_EOL;
echo "files : ".config('session.files').PHP_EOL;
