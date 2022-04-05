<?php

namespace Framework\Twig;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

class TwigRenderer {
	private Environment $environment;

	public function __construct()
	{
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
		return $this->environment->render($templateFile, $variables);
	}
}