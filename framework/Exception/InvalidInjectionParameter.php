<?php

namespace Framework\Exception;

class InvalidInjectionParameter extends FrameworkException
{
	public function __construct(string $typeName, string $parameterName, string $controllerMethodName)
	{
		parent::__construct("Invalid type annotation '$typeName' for dependency injection for parameter '$parameterName' in controller method: $controllerMethodName");
	}
}