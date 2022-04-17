<?php /** @noinspection RedundantSuppression */

namespace Framework\Service;

use App\App;
use Framework\Exception\NoSuchServiceException;

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

	/**
	 * @param class-string<Service> $service
	 * @return Service
	 * @throws NoSuchServiceException
	 */
	protected function getService(string $service): Service
	{
		return $this->app->getService($service);
	}
}