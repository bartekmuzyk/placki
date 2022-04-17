<?php

namespace Framework\Twig;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class TwigRenderer {
	private Environment $environment;

	public function __construct()
	{
		$loader = new FilesystemLoader(PROJECT_ROOT . '/templates');
		$this->environment = new Environment($loader, [
			'cache' => PROJECT_ROOT . '/cache/twig'
		]);
		$this->environment->addFilter(new TwigFilter(
			'breakify',
			fn(string $source) => str_replace("\n", '<br/>', $source),
			[
				'pre_escape' => 'html',
				'is_safe' => ['html']
			]
		));
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