<?php

namespace Framework\Http;

class Request {
	public array $query;

	public array $payload;

	public array $files;

	public string $route;

	public static function createFromGlobals(): self
	{
		$instance = new self();

		$instance->query = $_GET;
		$instance->payload = $_POST;
		$instance->files = $_FILES;

		return $instance;
	}
}