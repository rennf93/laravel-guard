<?php

declare(strict_types=1);

namespace RenzoFranceschini\GuardCoreLaravel;

use Closure;
use Illuminate\Http\Request;
use RenzoFranceschini\GuardCore\Engine\GuardEngine;
use RenzoFranceschini\GuardCore\Redis\GuardRedisException;
use Symfony\Component\HttpFoundation\Response;

final class GuardMiddleware
{
    private readonly ResponseTranslator $translator;

    public function __construct(
        private readonly GuardEngine $engine
    ) {
        $this->translator = new ResponseTranslator();
        try {
            $engine->initialize();
        } catch (GuardRedisException $e) {
            if (!$engine->config()->redisFailOpen) {
                throw $e;
            }
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $blocked = $this->engine->execute(new LaravelGuardRequest($request));
        } catch (\Throwable) {
            return $this->translator->translate($this->engine->failClosedResponse());
        }

        if ($blocked !== null) {
            return $this->translator->translate($blocked);
        }

        return $next($request);
    }
}
