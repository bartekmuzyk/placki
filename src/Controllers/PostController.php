<?php
/** @noinspection PhpUnused */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Controllers;

use App\Entities\Post;
use App\Entities\PostComment;
use App\Services\AccountService;
use App\Services\PostService;
use Framework\Controller\Controller;
use Framework\Http\Response;

class PostController extends Controller
{
	public function configureRoutes()
	{
		$this->post('/polub', 'like');
		$this->post('/odlub', 'dislike');

		$this->get('/komentarze', 'comments');
		$this->post('/komentarze', 'postComment');
		$this->delete('/komentarze', 'deleteComment');
	}

	public function like(PostService $postService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$authorized = $postService->like($req->query['id'], $accountService->currentLoggedInUser);

		return Response::code($authorized ? 200 : 403);
	}

	public function dislike(PostService $postService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$authorized = $postService->dislike($req->query['id'], $accountService->currentLoggedInUser);

		return Response::code($authorized ? 200 : 403);
	}

	public function comments(PostService $postService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$post = $postService->getPost((int)$req->query['id']);

		if (!($post instanceof Post)) {
			return Response::code(404);
		} else if (!$postService->authorizeToPost($post, $accountService->currentLoggedInUser)) {
			return Response::code(403);
		}

		return $this->template('komentarze.twig', [
			'show_scrollbar' => true,
			'self' => $accountService->currentLoggedInUser,
			'comments' => $post->comments
		]);
	}

	public function postComment(PostService $postService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id') || !$req->hasPayload('content')) {
			return Response::code(400);
		}

		$post = $postService->getPost((int)$req->query['id']);

		if (!$postService->authorizeToPost($post, $accountService->currentLoggedInUser)) {
			return Response::code(403);
		}

		$postService->postComment($post, $req->payload['content'], $accountService->currentLoggedInUser);

		return new Response();
	}

	public function deleteComment(PostService $postService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$comment = $postService->getPostComment((int)$req->query['id']);

		if (!($comment instanceof PostComment)) {
			return Response::code(404);
		} else if ($comment->author !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$this->getDBManager()->removeAndFlush($comment);

		return new Response();
	}
}