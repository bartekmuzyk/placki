<?php

namespace App\Controllers;

use Framework\Controller\Controller;
use Framework\Http\Response;

class PostController extends Controller
{
	public function configureRoutes()
	{
		$this->post('/polub', 'like');
		$this->post('/odlub', 'dislike');
	}

	public function like(): Response
	{
		// MOCK

		return new Response();
	}

	public function dislike(): Response
	{
		// MOCK

		return new Response();
	}
}