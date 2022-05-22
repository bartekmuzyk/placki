<?php
/** @noinspection PhpNoReturnAttributeCanBeAddedInspection */
/** @noinspection PhpUndefinedVariableInspection */

const PUBLIC_DIR = __DIR__;
define('PROJECT_ROOT', dirname(PUBLIC_DIR));

require_once PROJECT_ROOT . '/vendor/autoload.php';

use App\App;
use Framework\Exception\InvalidInjectionParameter;
use Framework\Exception\NoSuchServiceException;
use Framework\Http\Request;
use Framework\Http\Response;
use JetBrains\PhpStorm\NoReturn;

/**
 * @internal
 */
function sendResponse(string $content, int $code): void
{
	http_response_code($code);
	echo $content;
	exit;
}

/**
 * @internal
 * @param object|Exception|Error $exceptionOrError
 * @return string
 */
function getTraceAsHTML(object $exceptionOrError): string
{
	return str_replace("\n", "<div style='height: 8px;'></div>", $exceptionOrError->getTraceAsString());
}

/**
 * @internal
 * @param object|Exception|Error $exceptionOrError
 * @return void
 */
function renderExceptionOrError(object $exceptionOrError): void
{
    global $app;

    $showErrors = (bool)$app->getRuntimeConfig()['show_errors'];

    if ($showErrors) {
        $className = get_class($exceptionOrError);
        $trace = getTraceAsHTML($exceptionOrError);
        sendResponse("
<body style='background-color: #EF5350; color: white; font-family: sans-serif;'>
	<h1>Unhandled $className</h1>
	<p>{$exceptionOrError->getMessage()}</p>
	<hr style='border-color: white;'/>
	<div style='width: 100%; padding: 5px; background-color: #212121; color: #FAFAFA; box-sizing: border-box; border-radius: 10px; border: 3px dotted white;'>
		<code style='font-size: 18px; line-height: 22px;'>$trace</code>
	</div>
</body>
", 500);
    } else {
        sendResponse('<h1>Internal server error</h1>', 500);
    }
}

/**
 * @internal
 * @param ReflectionMethod $controllerMethod
 * @return object[]
 * @throws InvalidInjectionParameter|NoSuchServiceException
 */
function getArgumentsForDependencyInjection(ReflectionMethod $controllerMethod): array
{
	global $app;

	$args = [];

	foreach ($controllerMethod->getParameters() as $parameter) {
		$type = $parameter->getType();

		if ($type === null) {
			throw new InvalidInjectionParameter('No type annotation provided', $parameter->getName(), $controllerMethod->getName());
		} else if ($type->isBuiltin() || $type->allowsNull()) {
			throw new InvalidInjectionParameter($type->getName(), $parameter->getName(), $controllerMethod->getName());
		}

		$args[] = $app->getService($type->getName());
	}

	return $args;
}

$request = Request::createFromGlobals();

try {
	$app = new App($request);
} catch (Exception $exception) {
	renderExceptionOrError($exception);
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

if (!in_array($requestMethod, ['GET', 'POST', 'PUT', 'DELETE'])) {
	sendResponse('Request method not supported', 405);
}

$routes = $app->routes[$requestMethod];
$route = $_SERVER['REQUEST_URI'];

if (strpos($route, '?')) {  // Remove query parameters from url for resolving route
	$route = explode('?', $route, 2)[0];
}

$request->route = $route;

if (!array_key_exists($route, $routes)) {
	sendResponse('Route not found', 404);
}

[$controllerInstance, $controllerMethodName] = $routes[$route];
$reflectedController = new ReflectionObject($controllerInstance);

if (!$reflectedController->hasMethod($controllerMethodName)) {
	sendResponse("No controller method with name \"$controllerMethodName\" for route: $route", 404);
} else {
	$controllerMethod = $reflectedController->getMethod($controllerMethodName);

	if (!$controllerMethod->hasReturnType() || $controllerMethod->getReturnType()->getName() !== Response::class) {
		sendResponse(
			"The controller method \"$controllerMethodName\" has no return type or doesn't return a Response object",
			500
		);
	}
}

try {
	session_start();

	/** @var Response $response */
	$response = null;

	foreach ($app->middleware as $middleware) {
		$middlewareResponse = $middleware->run($app);

		if ($middlewareResponse instanceof Response) {
			$response = $middlewareResponse;
			break;
		}
	}

	if ($response === null) {
		$args = getArgumentsForDependencyInjection($controllerMethod);
		$response = $controllerMethod->invoke($controllerInstance, ...$args);
	}

	foreach ($response->headers as $name => $value) {
		header("$name: $value");
	}

	sendResponse($response->content, $response->code);
} catch (Exception|Error $exception) {
	renderExceptionOrError($exception);
}