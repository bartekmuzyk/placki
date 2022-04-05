<?php

namespace Framework\Http;

class Response {
	public string $content;

	public int $code;

	public function __construct(string $content = '', int $code = 200)
	{
		$this->content = $content;
		$this->code = $code;
	}

	public static function code(int $code): self
	{
		return new self('', $code);
	}
}