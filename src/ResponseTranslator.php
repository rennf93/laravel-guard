<?php

declare(strict_types=1);

namespace RenzoFranceschini\GuardCoreLaravel;

use Illuminate\Http\Response;
use RenzoFranceschini\GuardCore\Request\GuardResponse;

final class ResponseTranslator
{
    public function translate(GuardResponse $response): Response
    {
        $laravel = new Response($response->body() ?? '', $response->statusCode());
        foreach ($response->headers()->all() as $name => $value) {
            $laravel->headers->set($name, $value);
        }

        return $laravel;
    }
}
