# laravel-guard

Laravel middleware adapter for [guard-core-php](https://github.com/rennf93/guard-core-php): maps `Illuminate\Http\Request` objects to the guard-core engine and translates block verdicts back to Laravel-native responses. Works with Laravel 10, 11, and 12.

Docs: <https://rennf93.github.io/laravel-guard/>

## Install

```bash
composer require rennf93/laravel-guard
```

## Usage

Bind the middleware in the container and register it globally:

```php
use Illuminate\Http\Request;
use RenzoFranceschini\GuardCore\Config\SecurityConfig;
use RenzoFranceschini\GuardCore\Engine\GuardEngine;
use RenzoFranceschini\GuardCoreLaravel\GuardMiddleware;

// In a service provider (or any container binding)
$this->app->bind(GuardMiddleware::class, function () {
    $config = new SecurityConfig(
        enableRedis: false,
        blacklist: ['192.0.2.0/24'],
        rateLimit: 100,
        rateLimitWindow: 60,
        enableRateLimiting: true,
    );

    return new GuardMiddleware(new GuardEngine($config));
});
```

```php
// Laravel 11/12, bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->prepend(GuardMiddleware::class);
});
```

```php
// Laravel 10, app/Http/Kernel.php
protected $middleware = [
    // ...
    \RenzoFranceschini\GuardCoreLaravel\GuardMiddleware::class,
];
```

Blocked requests get the engine's block verdict translated exactly (status, body, headers) as an `Illuminate\Http\Response`: `403 Forbidden` for a blacklisted IP, `429 Too many requests` with `Retry-After` for a rate limit hit. Passing requests continue down the stack untouched.

## Lifecycle

PHP shared-nothing applies: construct `GuardEngine` (and therefore `GuardMiddleware`) per request in classic FPM, or per worker under Octane/FrankenPHP. The middleware holds no mutable state of its own. In-memory fallbacks are per-request safety nets; distributed rate limits, IP bans, and cloud-range caches require Redis (set `enableRedis: true` and point `REDIS_HOST`/`REDIS_PORT` at your instance).

## Behavior notes

- Fail-closed: if the engine throws, the middleware returns the engine's fail-closed response (`500 Security check failed`, honorably overridden by `customErrorResponses`) instead of letting the request through.
- Bounded body read: the request body is scanned as a prefix of at most 256 KiB (`LaravelGuardRequest::MAX_BODY_BYTES`, matching the engine's full-scan window). The framework materializes the full body in memory; the engine only ever sees the capped prefix. Payloads beyond the prefix, or signatures split across its boundary, are not detected.
- Client address: the adapter maps the engine's `clientHost()` onto `$request->ip()`. Without Laravel's TrustProxies middleware that is the connecting `REMOTE_ADDR`, and the engine's own `trusted_proxies` / `X-Forwarded-For` resolution applies. With Laravel's TrustProxies configured, Laravel resolves the forwarded chain first and the engine sees the resolved client. Pick one side to do the resolving; configuring both can double-hop.
- With `redisFailOpen: true` the middleware constructs and serves requests even when Redis is unreachable; with `redisFailOpen: false` construction fails closed.
- No security headers or CORS are added by this adapter. (Laravel/Symfony response mechanics put a `Date` and a private `Cache-Control` on every response object; the engine's own headers are copied exactly.)

## Testing

```bash
composer lint
composer test
```

`composer test` runs the plain-PHP suite in `bin/test_laravel.php` (unit coverage always; set `REDIS_HOST` to a reachable Redis to include the shared-state integration cases).

## Status

Released: v1.0.0 on Packagist. The engine, `rennf93/guard-core-php`, is at v4.0.4.

## License

MIT
