<?php

use App\Models\PlaceCoordinate;
use App\Models\Port;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Base dédiée uniquement aux tests navigateur. Aucune donnée du site n'est lue.
require __DIR__.'/../../vendor/autoload.php';
$database = realpath(__DIR__.'/../../storage').DIRECTORY_SEPARATOR.'browser-tests.sqlite';
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '', 'CACHE_STORE' => 'array'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
if (! is_file($database)) {
    touch($database);
}
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.connections.sqlite.database') !== $database || ! $app->environment('testing')) {
    throw new RuntimeException('La base de tests doit être isolée.');
}
Artisan::call('migrate:fresh', ['--force' => true]);
User::factory()->create(['name' => 'Capitaine Test', 'email' => 'browser@example.test', 'password' => 'Browser-test-123!']);
PlaceCoordinate::create(['name' => 'Pointe à Pitre', 'normalized_name' => 'pointe-a-pitre', 'latitude' => 44.07984, 'longitude' => 1.98937, 'feature_class' => 'P', 'feature_code' => 'PPL', 'country_code' => 'FR']);
foreach ([['test-depart', 'Port Navigo', 'FR', 43.3, 5.3], ['test-arrivee', 'Port Horizon', 'FR', 43.8, 5.8], ['test-homonyme', 'Port Navigo', 'CA', 46.8, -71.2]] as [$slug, $name, $country, $lat, $lon]) {
    Port::create(['slug' => $slug, 'name' => $name, 'country_code' => $country, 'latitude' => $lat, 'longitude' => $lon, 'min_depth_meters' => 8, 'has_water' => true]);
}
foreach (range(1, 26) as $index) {
    Port::create(['slug' => 'test-pagination-'.$index, 'name' => sprintf('Z Escale %02d', $index), 'country_code' => 'FR', 'latitude' => 43.5, 'longitude' => 5.5, 'min_depth_meters' => 8, 'has_water' => true]);
}
echo "Base navigateur isolée prête.\n";
