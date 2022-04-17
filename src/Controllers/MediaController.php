<?php

namespace App\Controllers;

use App\Entities\MediaElement;
use App\Entities\VideoComment;
use App\Services\AccountService;
use App\Services\MediaService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\Controller\Controller;
use Framework\Http\Response;

class MediaController extends Controller
{
	public function configureRoutes()
	{
		$this->get('/', 'index');

		$this->get('/film', 'video');

		$this->post('/film/polub', 'like');
		$this->post('/film/odlub', 'dislike');

		$this->get('/film/komentarze', 'comments');
		$this->post('/film/komentarze', 'postComment');
		$this->delete('/film/komentarze', 'deleteComment');
	}

	public function index(MediaService $mediaService, AccountService $accountService): Response
	{
		$mediaElements = $mediaService->getSharedMedia();

		$videos = array_filter($mediaElements, function (MediaElement $mediaElement) {
			return $mediaElement->mediaType === MediaService::MEDIATYPE_VIDEO;
		});

		$photos = array_filter($mediaElements, function (MediaElement $mediaElement) {
			return $mediaElement->mediaType === MediaService::MEDIATYPE_PHOTO;
		});

		$albums = array_unique(array_map(function (MediaElement $mediaElement) {
			return $mediaElement->album;
		}, $photos));

		$files = array_filter($mediaElements, function (MediaElement $mediaElement) {
			return $mediaElement->mediaType === MediaService::MEDIATYPE_FILE;
		});

		return $this->template('media.twig', [
			'self' => $accountService->currentLoggedInUser,
			'shared_videos' => $videos,
			'shared_photos' => [
				'albums' => $albums,
				'photo_list' => $photos
			],
			'shared_files' => $files
		]);
	}

	/**
	 * @param AccountService $accountService
	 * @param MediaService $mediaService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function video(AccountService $accountService, MediaService $mediaService): Response
	{
		$request = $this->getRequest();

		if (!array_key_exists('id', $request->query)) {
			return $this->redirect('/media');
		}

		$mediaIdentifier = $request->query['id'];
		$mediaElement = $mediaService->getMediaElement($mediaIdentifier, MediaService::MEDIATYPE_VIDEO);

		if (!($mediaElement instanceof MediaElement)) {
			return $this->redirect('/media');
		}

		$mediaService->checkAndCountView($mediaElement, $accountService->currentLoggedInUser);

		$mediaSourceUrl = '/media_sources/' . $mediaElement->sourceUri;
		$localMediaUri = PROJECT_ROOT . '/public' . $mediaSourceUrl;

		return $this->template('video.twig', [
			'self' => $accountService->currentLoggedInUser,
			'video' => $mediaElement,
			'src' => $mediaSourceUrl,
			'additional' => [
				'filename' => pathinfo($localMediaUri, PATHINFO_BASENAME),
				'resolution' => 'nieznana',
				'bitrate' => 'nieznany',
				'size' => $mediaService->getHumanReadableSize(filesize($localMediaUri))
			],
			'like_info' => [
				'count' => $mediaElement->likedBy->count(),
				'liked_by_me' => $mediaElement->likedBy->contains($accountService->currentLoggedInUser)
			],
			'views' => $mediaElement->viewedBy->count()
		]);
	}

	/**
	 * @param AccountService $accountService
	 * @param MediaService $mediaService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function like(AccountService $accountService, MediaService $mediaService): Response
	{
		$req = $this->getRequest();

		if (!array_key_exists('id', $req->query)) {
			return Response::code(400);
		}

		$mediaExists = $mediaService->like($req->query['id'], $accountService->currentLoggedInUser);

		return Response::code($mediaExists ? 200 : 404);
	}

	/**
	 * @param AccountService $accountService
	 * @param MediaService $mediaService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function dislike(AccountService $accountService, MediaService $mediaService): Response
	{
		$req = $this->getRequest();

		if (!array_key_exists('id', $req->query)) {
			return Response::code(400);
		}

		$mediaExists = $mediaService->dislike($req->query['id'], $accountService->currentLoggedInUser);

		return Response::code($mediaExists ? 200 : 404);
	}

	public function comments(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!array_key_exists('id', $req->query)) {
			return new Response('nie można załadować komentarzy dla tego filmu', 400);
		}

		return $this->template('komentarze.twig', [
			'self' => $accountService->currentLoggedInUser,
			'comments' => $mediaService->getComments($req->query['id'])
		]);
	}

	/**
	 * @param MediaService $mediaService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function postComment(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!array_key_exists('id', $req->query) || !array_key_exists('content', $req->payload)) {
			return Response::code(400);
		}

		$videoMediaElement = $mediaService->getMediaElement($req->query['id'], MediaService::MEDIATYPE_VIDEO);
		$mediaService->postComment($videoMediaElement, $req->payload['content'], $accountService->currentLoggedInUser);

		return new Response();
	}

	/**
	 * @param MediaService $mediaService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function deleteComment(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$comment = $mediaService->getVideoComment((int)$req->query['id']);

		if (!($comment instanceof VideoComment)) {
			return Response::code(404);
		} else if ($comment->author !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$this->getDBManager()->removeAndFlush($comment);

		return new Response();
	}
}