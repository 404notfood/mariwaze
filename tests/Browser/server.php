<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Tests\Browser\BrowserServiceProvider;

// Routeur CLI de test, jamais chargé par public/index.php.
require __DIR__.'/../../vendor/autoload.php';
if (getenv('APP_ENV') !== 'testing') {
    throw new RuntimeException('Serveur réservé aux tests.');
}
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$app->register(BrowserServiceProvider::class);
$app->handleRequest(Request::capture());
