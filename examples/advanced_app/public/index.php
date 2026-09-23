<?php

declare(strict_types=1);

use App\Config;
use App\Routes;
use Illuminate\Http\Request;
use RenzoFranceschini\GuardCore\Engine\GuardEngine;
use RenzoFranceschini\GuardCoreLaravel\GuardMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$engine = new GuardEngine(Config::securityConfig());
$guard = new GuardMiddleware($engine);

// The guard runs first; admin routes behind the engine gate drive the ban
// manager (see src/Routes.php).
$guard->handle(Request::capture(), fn (Request $request) => (new Routes($engine))->handle($request))->send();
