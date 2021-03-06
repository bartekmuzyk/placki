<?php

namespace Framework;

use Exception;
use Framework\Controller\Controller;
use Framework\Database\DatabaseManager;
use Framework\Exception\NoSuchServiceException;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Middleware\AppMiddlewareInterface;
use Framework\Serializer\Exception\SerializerException;
use Framework\Serializer\Serializer;
use Framework\Serializer\SerializerManager;
use Framework\Service\Service;
use Framework\Session\SessionManager;
use Framework\TempFileUtil\TempFileUtil;
use Framework\Utils\Utils;
use HaydenPierce\ClassFinder\ClassFinder;
use JetBrains\PhpStorm\ArrayShape;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionProperty;
use Framework\Twig\TwigRenderer;

abstract class BaseApp {
	private const CONFIG_FILE = PROJECT_ROOT . '/config/config.yaml';

	public array $routes;

    public array $wildcardRoutes;

	private Request $request;

	private TwigRenderer $twigRenderer;

	private SessionManager $sessionManager;

	private ?DatabaseManager $databaseManager = null;

	private TempFileUtil $tempFileUtil;

    private SerializerManager $serializerManager;

    #[ArrayShape(['database' => 'array', 'run' => 'array', 'serializer' => 'array'])]
	private array $config;

	/** @var AppMiddlewareInterface[] */
	public array $middleware;

	private array $serviceMap = [];

	/**
	 * @param Request|null $request
	 * @throws Exception
	 */
	public function __construct(?Request $request)
	{
        if (!is_file(self::CONFIG_FILE)) {
            throw new Exception(sprintf('Expected configuration file at: %s', self::CONFIG_FILE));
        }

        $this->config = yaml_parse_file(self::CONFIG_FILE);

        $this->autoRegisterServices();

		if ($request instanceof Request) {
			$this->routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => []];
			$this->wildcardRoutes = ['GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => []];
			$this->request = $request;
			$this->twigRenderer = new TwigRenderer($this);
			$this->sessionManager = new SessionManager();
			$this->tempFileUtil = new TempFileUtil(PROJECT_ROOT . '/cache/temp_files/');

			$this->setup();
		}

        $this->serializerManager = new SerializerManager($this, $this->config['serializer']['converters']);
        $this->autoRegisterSerializers();
		$this->databaseManager = DatabaseManager::fromConfig($this->config['database']);
	}

    /**
     * @internal
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function injectServicesIntoInstance(object $instance): void
    {
        $reflectedServiceClass = new ReflectionObject($instance);

        foreach ($reflectedServiceClass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyType = $property->getType();

            if ($propertyType instanceof ReflectionNamedType && !$propertyType->allowsNull() && !$propertyType->isBuiltin()) {
                $propertyName = $property->getName();
                $instance->$propertyName = $this->getService($propertyType->getName());
            }
        }
    }

	/** @noinspection PhpUnhandledExceptionInspection */
	private function autoRegisterServices(): void
    {
		$serviceClasses = Utils::getAllDerivingClassesInNamespace('App\\Services', Service::class);

		foreach ($serviceClasses as $serviceClass) {
			$this->serviceMap[$serviceClass] = new $serviceClass($this);
		}

		foreach ($this->serviceMap as $parentServiceClass => $parentServiceInstance) {
			$this->injectServicesIntoInstance($parentServiceInstance);
		}
	}

    /** @noinspection PhpUnhandledExceptionInspection */
    private function autoRegisterSerializers(): void
    {
        $serializerClasses = Utils::getAllDerivingClassesInNamespace('App\\Serializers', Serializer::class);

        foreach ($serializerClasses as $serializerClass) {
            $this->serializerManager->registerSerializer($serializerClass);
        }
    }

    public function getRuntimeConfig(): array
    {
        return $this->config['run'];
    }

	/**
	 * @param class-string<Service> $service
	 * @return Service
	 * @throws NoSuchServiceException
	 */
	public function getService(string $service): Service
	{
		if (isset($this->serviceMap[$service])) {
			return $this->serviceMap[$service];
		}

		throw new NoSuchServiceException($service);
	}

	protected abstract function setup();

	/**
	 * @param string $method
	 * @param string $route
	 * @param object|BaseApp|Controller $controllerInstance
	 * @param string $controllerMethod
	 * @return void
	 */
	public function addRoute(string $method, string $route, object $controllerInstance, string $controllerMethod): self
	{
		$routeData = [$controllerInstance, $controllerMethod];

        if (str_ends_with($route, '*')) {
            $this->wildcardRoutes[$method][str_replace('*', '' , $route)] = $routeData;
        } else {
            $this->routes[$method][$route] = $routeData;
            $this->routes[$method]["$route/"] = $routeData;
        }

		return $this;
	}

	protected function get(string $route, string $controllerMethod): self
	{
		return $this->addRoute('GET', $route, $this, $controllerMethod);
	}

	protected function post(string $route, string $controllerMethod): self
	{
		return $this->addRoute('POST', $route, $this, $controllerMethod);
	}

	protected function put(string $route, string $controllerMethod): self
	{
		return $this->addRoute('PUT', $route, $this, $controllerMethod);
	}

	protected function delete(string $route, string $controllerMethod): self
	{
		return $this->addRoute('DELETE', $route, $this, $controllerMethod);
	}

	/**
	 * @param string $routePrefix
	 * @param class-string<Controller> $controllerClass
	 * @return void
	 */
	protected function useController(string $routePrefix, string $controllerClass): void
    {
		$controllerInstance = new $controllerClass($this, $routePrefix);
		$controllerInstance->configureRoutes();
	}

	protected function addMiddleware(AppMiddlewareInterface $middleware): void
    {
		$this->middleware[] = $middleware;
	}

	public function template(string $template, array $variables = [], int $code = 200): Response
	{
		/** @noinspection PhpUnhandledExceptionInspection */
		$rendered = $this->twigRenderer->render($template, $variables);

		return new Response($rendered, $code);
	}

	public function json(array $data, int $code = 200): Response
	{
		return new Response(json_encode($data), $code);
	}

    /**
     * @param array|object|null $data
     * @param string|null $serializerVariant
     * @param bool $toPrimitive
     * @param int $code
     * @return Response
     * @throws SerializerException
     */
    public function serialize(
        array|object|null $data,
        ?string $serializerVariant = null,
        bool $toPrimitive = false,
        int $code = 200
    ): Response
    {
        return new Response(
            $this->serializerManager->encode($data, $serializerVariant, $toPrimitive, $this->config['serializer']['encoder']),
            $code
        );
    }

	public function redirect(string $path, array $queryParams = []): Response
	{
		if (count($queryParams) > 0) {
			$path .= '?' . http_build_query($queryParams);
		}

		return new Response('', 302, [
			'Location' => $path
		]);
	}

	public function file(string $path, ?string $customFilename = null): Response
	{
		if (file_exists($path)) {
			$filename = is_string($customFilename) ? $customFilename : basename($path);

			return new Response(file_get_contents($path), 200, [
				'Content-Type' => 'application/octet-stream',
				'Content-Transfer-Encoding' => 'Binary',
				'Content-Length' => (string)filesize($path),
				'Content-Disposition' => "attachment; filename=\"$filename\""
			]);
		} else {
			return Response::code(404);
		}
	}

	public function getRequest(): Request
	{
		return $this->request;
	}

	public function getSessionManager(): SessionManager
	{
		return $this->sessionManager;
	}

	public function getDBManager(): DatabaseManager
	{
		return $this->databaseManager;
	}

	public function getTempFileUtil(): TempFileUtil
	{
		return $this->tempFileUtil;
	}
}