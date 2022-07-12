<?php

namespace Framework\Twig;

use App\App;
use Framework\Middleware\TwigMiddlewareInterface;
use HaydenPierce\ClassFinder\ClassFinder;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class TwigRenderer {
	private Environment $environment;

    private App $app;

	public function __construct(App $app)
	{
        $this->app = $app;
		$loader = new FilesystemLoader(PROJECT_ROOT . '/templates');
		$this->environment = new Environment($loader, [
			'cache' => PROJECT_ROOT . '/cache/twig'
		]);
	}

	/**
	 * @param string $templateFile
	 * @param array $variables
	 * @return string
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public function render(string $templateFile, array $variables): string
	{
        foreach ($this->app->getRuntimeConfig()['twig']['middleware'] as $middlewareClass) {
            /** @var TwigMiddlewareInterface $middlewareInstance */
            $middlewareInstance = new $middlewareClass();
            $middlewareInstance->run($this->app, $this->environment);
        }

        $this->environment->addGlobal('_ENV', $_ENV);

		return $this->environment->render($templateFile, $variables);
	}
}