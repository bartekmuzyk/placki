<?php

namespace Framework\Middleware;

use App\App;
use Framework\Http\Response;

interface AppMiddlewareInterface
{
	public function run(App $app): ?Response;
}