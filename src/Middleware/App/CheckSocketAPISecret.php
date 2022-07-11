<?php

namespace App\Middleware\App;

use App\App;
use Framework\Http\Response;
use Framework\Middleware\AppMiddlewareInterface;

class CheckSocketAPISecret implements AppMiddlewareInterface
{
    public function run(App $app): ?Response
    {
        $req = $app->getRequest();

        if (str_starts_with($req->route, '/socket_api')) {
            $secret = $req->headers['x-placki-api-secret'] ?? '';

            if ($secret !== $_ENV['PLACKI_API_SECRET']) {
                return Response::code(401);
            }
        }

        return null;
    }
}