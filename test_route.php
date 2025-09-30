<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

// Create a kernel instance
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request
$request = Illuminate\Http\Request::create('/api/rewards/v2/users/me/referrals', 'GET');

// Handle the request
$response = $kernel->handle($request);

echo 'Status Code: ' . $response->getStatusCode() . '\n';
echo 'Content: ' . $response->getContent() . '\n';

$kernel->terminate($request, $response);

