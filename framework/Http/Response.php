<?php

namespace Framework\Http;

class Response {
	public string $content;

	public int $code;

	public array $headers;

	public function __construct(string $content = '', int $code = 200, array $headers = [])
	{
		$this->content = $content;
		$this->code = $code;
		$this->headers = $headers;
	}

	public static function code(int $code): self
	{
		return new self('', $code);
	}
}