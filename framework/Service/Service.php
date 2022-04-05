<?php

namespace Framework\Service;

use App\App;

abstract class Service
{
	private App $app;

	public function __construct(App $app)
	{
		$this->app = $app;
	}

	protected function getApp(): App
	{
		return $this->app;
	}
}