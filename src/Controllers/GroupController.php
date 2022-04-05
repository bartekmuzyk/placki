<?php

namespace App\Controllers;

use App\Services\AccountService;
use App\Services\GroupsService;
use Framework\Controller\Controller;
use Framework\Http\Response;

class GroupController extends Controller
{
	public function configureRoutes()
	{
		$this->get('/', 'index');
	}

	public function index(AccountService $accountService, GroupsService $groupsService): Response
	{
		$me = $accountService->currentLoggedInUser;
		$groups = $groupsService->getGroups();

		return $this->template('grupy.twig', [
			'self' => $me,
			'groups' => []
		]);
	}
}