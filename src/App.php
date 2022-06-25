<?php
/** @noinspection PhpUnused */

namespace App;

use App\Controllers\APIController;
use App\Controllers\CDNController;
use App\Controllers\EventController;
use App\Controllers\GroupController;
use App\Controllers\MediaController;
use App\Controllers\PostController;
use App\Controllers\ProfileController;
use App\Entities\Post;
use App\Exceptions\AttachmentTooLargeException;
use App\Exceptions\CannotWriteAttachmentToDiskException;
use App\Middleware\App\CheckAuth;
use App\Services\AccountService;
use App\Services\AttachmentService;
use App\Services\EventsService;
use App\Services\PostService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\BaseApp;
use Framework\Http\Response;

class App extends BaseApp
{
	public function setup()
	{
        $this->addMiddleware(new CheckAuth());
        $this->useController('/api', APIController::class);
        $this->useController('/cdn', CDNController::class);

		$this->get('/', 'index');

		$this->post('/login', 'postLogin');

		$this->get('/rejestracja', 'register');
		$this->post('/rejestracja', 'postRegister');

		$this->get('/wyloguj', 'logout');

		$this->get('/glowna', 'homepage');

		$this->get('/posty', 'posts');
		$this->post('/posty', 'postPost');
		$this->delete('/posty', 'deletePost');

		$this->useController('/post', PostController::class);
		$this->useController('/media', MediaController::class);
		$this->useController('/grupy', GroupController::class);
        $this->useController('/wydarzenia', EventController::class);

        $this->get('/ludzie', 'people');

        $this->useController('/ja', ProfileController::class);
	}

	public function index(AccountService $accountService): Response
	{
		$req = $this->getRequest();

		return $accountService->isLoggedIn() ?
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

		if (!$req->hasPayload('username') || !$req->hasPayload('password')) {
			return Response::code(400);
		}

		$alreadyExists = $accountService->register($req->payload['username'], $req->payload['password']);

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

	public function homepage(EventsService $eventsService): Response
	{
		return $this->template('glowna.twig', [
            'nearest_events' => $eventsService->getNearestEvents()
        ]);
	}

	public function posts(PostService $postService, AccountService $accountService, AttachmentService $attachmentService): Response
	{
		$req = $this->getRequest();

		$limit = $req->hasQuery('limit') ? (int)$req->query['limit'] : 100;
		$posts = $postService->getPosts($limit, $accountService->currentLoggedInUser);
		$attachmentSources = [];

		foreach ($posts as $post) {
			foreach ($post->attachments as $attachment) {
				$attachmentSources[$attachment->id] = $attachmentService->getAttachmentFilePath($attachment);
			}
		}

		return $this->template('posty.twig', [
			'posts' => $posts,
			'attachmentSources' => $attachmentSources
		]);
	}

	/**
	 * @param PostService $postService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function postPost(PostService $postService, AccountService $accountService): Response
	{
		$req = $this->getRequest();
		$attachments = $req->getFilesArray('attachments');

		if (!$req->hasPayload('content') && count($attachments) === 0) {
			return Response::code(400);
		}

		try {
			$postService->createPost(
				$req->payload['content'] ?? '',
				null,
				$accountService->currentLoggedInUser,
				$attachments
			);
		} catch (CannotWriteAttachmentToDiskException $e) {
			return $this->json([
				'filename' => $e->defectiveFile->getBasename(),
				'error' => 'cannot write to disk'
			], 400);
		} catch (AttachmentTooLargeException $e) {
			return $this->json([
				'filename' => $e->defectiveFile->getBasename(),
				'error' => 'too large'
			], 400);
		}

		return new Response();
	}

	/**
	 * @param PostService $postService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function deletePost(PostService $postService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$post = $postService->getPost((int)$req->query['id']);

		if (!($post instanceof Post)) {
			return Response::code(404);
		} else if ($post->author !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$postService->deletePost($post);

		return new Response();
	}

    public function people(AccountService $accountService): Response
    {
        return $this->template('ludzie.twig', [
            'people' => $accountService->getAllUsers()
        ]);
    }
}