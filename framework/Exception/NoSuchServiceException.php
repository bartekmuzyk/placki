<?php

namespace Framework\Exception;

class NoSuchServiceException extends FrameworkException
{
	public function __construct(string $serviceName)
	{
		parent::__construct("No such service: $serviceName");
	}
}