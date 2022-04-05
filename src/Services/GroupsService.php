<?php

namespace App\Services;

use Framework\Service\Service;

class GroupsService extends Service
{
	public function getGroups(): array
	{
		$db = $this->getApp()->getDBManager();

		return [];
	}
}