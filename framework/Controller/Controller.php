<?php

namespace Framework\Controller;

use App\App;
use Framework\Database\DatabaseManager;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Session\SessionManager;

/**
 * @method Response template(string $template, array $variables = [], int $code = 200)
 * @method Response json(array $data, int $code = 200)
 * @method Response redirect(string $path, array $queryParams = [])
 * @method Request getRequest()
 * @method SessionManager getSessionManager()
 * @method DatabaseManager getDBManager()
 */
abstract class Controller
{
	private App $app;

	private string $routePrefix;

	public function __construct(App $app, string $routePrefix)
	{
		$this->app = $app;
		$this->routePrefix = $routePrefix;
	}

	public abstract function configureRoutes();

	private function addRoute(string $method, string $route, string $controllerMethod): self
	{
		if ($route === '/') {
			$route = '';
		}

		$this->app->addRoute($method, $this->routePrefix . $route, $this, $controllerMethod);

		return $this;
	}

	public function get(string $route, string $controllerMethod): self
	{
		return $this->addRoute('GET', $route, $controllerMethod);
	}

	public function post(string $route, string $controllerMethod): self
	{
		return $this->addRoute('POST', $route, $controllerMethod);
	}

	public function put(string $route, string $controllerMethod): self
	{
		return $this->addRoute('PUT', $route, $controllerMethod);
	}

	public function delete(string $route, string $controllerMethod): self
	{
		return $this->addRoute('DELETE', $route, $controllerMethod);
	}

	public function __call($name, $arguments)
	{
		return $this->app->$name(...$arguments);
	}
}