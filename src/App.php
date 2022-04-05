<?php

namespace App;

use App\Controllers\GroupController;
use App\Controllers\MediaController;
use App\Controllers\PostController;
use App\Middleware\CheckAuth;
use App\Services\AccountService;
use App\Services\PostService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Framework\BaseApp;
use Framework\Http\Response;

class App extends BaseApp
{
	public function setup()
	{
		$this->addMiddleware(new CheckAuth());

		$this->get('/', 'index');
		$this->post('/login', 'postLogin');
		$this->get('/rejestracja', 'register');
		$this->post('/rejestracja', 'postRegister');
		$this->get('/wyloguj', 'logout');
		$this->get('/glowna', 'homepage');
		$this->get('/posty', 'posts');
		$this->useController('/post', PostController::class);
		$this->useController('/media', MediaController::class);
		$this->useController('/grupy', GroupController::class);
	}

	public function index(): Response
	{
		$session = $this->getSessionManager();
		$req = $this->getRequest();

		return $session->has('user') ?
			$this->redirect('/glowna')
			:
			$this->template('index.twig', [
				'error' => $req->query['error'] ?? ''
			]);
	}

	public function postLogin(AccountService $accountService): Response
	{
		$req = $this->getRequest();
		$session = $this->getSessionManager();
		$userSessionData = $accountService->login($req->payload['username'], $req->payload['password']);

		if (is_array($userSessionData)) {
			$session->set('user', $userSessionData);

			return $this->redirect('/glowna');
		} else {
			return $this->redirect('/', [
				'error' => 'zła nazwa użytkownika/hasło'
			]);
		}
	}

	public function register(): Response
	{
		$req = $this->getRequest();

		return $this->template('rejestracja.twig', [
			'error' => $req->query['error'] ?? null
		]);
	}

	/**
	 * @throws OptimisticLockException
	 * @throws ORMException
	 */
	public function postRegister(AccountService $accountService): Response
	{
		$req = $this->getRequest();

		$username = $req->payload['username'];
		$password = $req->payload['password'];

		if (!$username || !$password) {
			return Response::code(400);
		}

		$alreadyExists = $accountService->register($username, $password);

		return $alreadyExists ?
			$this->redirect('/rejestracja', [
				'error' => 'konto o takiej nazwie użytkownika już istnieje'
			])
			:
			$this->redirect('/');
	}

	public function logout(): Response
	{
		$session = $this->getSessionManager();

		$session->remove('user');

		return $this->redirect('/');
	}

	public function homepage(AccountService $accountService): Response
	{
		return $this->template('glowna.twig', [
			'self' => $accountService->currentLoggedInUser
		]);
	}

	public function posts(PostService $postService): Response
	{
		$req = $this->getRequest();

		$limit = array_key_exists('limit', $req->query) ? (int)$req->query['limit'] : 100;
		$posts = $postService->getPosts($limit);

		return $this->template('posty.twig', [
			'posts' => $posts
		]);
	}
}