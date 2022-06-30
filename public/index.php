<?php
/** @noinspection PhpNoReturnAttributeCanBeAddedInspection */
/** @noinspection PhpUndefinedVariableInspection */

const PUBLIC_DIR = __DIR__;
define('PROJECT_ROOT', dirname(PUBLIC_DIR));

require_once PROJECT_ROOT . '/vendor/autoload.php';

use App\App;
use Doctrine\ORM\Query\QueryException;
use Framework\BaseApp;
use Framework\Exception\InvalidInjectionParameter;
use Framework\Exception\NoSuchServiceException;
use Framework\Http\Request;
use Framework\Http\Response;
use JetBrains\PhpStorm\NoReturn;
use Twig\Error\SyntaxError;

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
 * @param object $exceptionOrError
 * @return void
 * @internal
 */
function handleExceptionOrError(object $exceptionOrError): void
{
    global $app;

    $errorInfo = get_class($exceptionOrError) . ": {$exceptionOrError->getMessage()}\n\n{$exceptionOrError->getTraceAsString()}";
    file_put_contents(PROJECT_ROOT . '/last_error.txt', $errorInfo);

    $showErrors = !$app instanceof BaseApp || $app->getRuntimeConfig()['show_errors'];

    if ($showErrors) {
        $className = get_class($exceptionOrError);
        $trace = getTraceAsHTML($exceptionOrError);
        $additionalInfoHtml = $exceptionOrError instanceof SyntaxError ?
            "
            <div style='border: 5px solid white;'>
                <b>Template file: {$exceptionOrError->getSourceContext()->getName()}</b>
                <label>Line number: {$exceptionOrError->getTemplateLine()}</label>
            </div>
            "
            :
            '';

        $processOwnerUsername = function_exists('posix_getpwuid') ?
            posix_getpwuid(posix_geteuid())['name']
            :
            getenv('username');

        sendResponse("
<body style='background-color: #EF5350; color: white; font-family: sans-serif;'>
	<h1>Unhandled $className</h1>
	<p>{$exceptionOrError->getMessage()}</p>
	$additionalInfoHtml
	<hr style='border-color: white;'/>
	<div style='width: 100%; padding: 5px; background-color: #212121; color: #FAFAFA; box-sizing: border-box; border-radius: 10px; border: 3px dotted white;'>
		<code style='font-size: 18px; line-height: 22px;'>$trace</code>
	</div>
	<span><b>Process owner:</b> $processOwnerUsername</span>
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
	handleExceptionOrError($exception);
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

if (!in_array($requestMethod, ['GET', 'POST', 'PUT', 'DELETE'])) {
	sendResponse('Request method not supported', 405);
}

$routes = $app->routes[$requestMethod];
$wildcardRoutes = $app->wildcardRoutes[$requestMethod];
$route = $_SERVER['REQUEST_URI'];

if (strpos($route, '?')) {  // Remove query parameters from url for resolving route
	$route = explode('?', $route, 2)[0];
}

$request->route = $route;

/** @var ?array $routeData */
$routeData = null;

if (array_key_exists($route, $routes)) {
    $routeData = $routes[$route];
} else {
    foreach ($wildcardRoutes as $wildcardRoute => $wildcardRouteData) {
        if (str_starts_with($route, $wildcardRoute)) {
            $routeData = $wildcardRouteData;
        }
    }
}

if ($routeData == null) {
    sendResponse('Route not found', 404);
}

[$controllerInstance, $controllerMethodName] = $routeData;
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
    session_save_path(PROJECT_ROOT . '/sessions');
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
    handleExceptionOrError($exception);
}