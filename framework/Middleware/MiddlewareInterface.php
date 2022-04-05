<?php

namespace Framework\Middleware;

use App\App;
use Framework\Http\Response;

interface MiddlewareInterface
{
	public function run(App $app): ?Response;
}