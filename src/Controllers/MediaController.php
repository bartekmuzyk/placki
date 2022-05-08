<?php
/** @noinspection PhpUnused */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Controllers;

use App\Entities\FileUploadToken;
use App\Entities\MediaElement;
use App\Entities\VideoComment;
use App\Entities\VideoUploadToken;
use App\Exceptions\CannotWriteMediaToDiskException;
use App\Exceptions\MediaTooLargeException;
use App\Interfaces\PostUploadMediaElementConfigurator;
use App\Services\AccountService;
use App\Services\MediaService;
use Framework\Controller\Controller;
use Framework\Http\Response;
use Framework\Http\UploadedFile;

class MediaController extends Controller
{
	public function configureRoutes()
	{
		$this->get('/', 'index');

		$this->get('/film', 'video');
		$this->delete('/film', 'deleteVideo');

		$this->post('/film/wrzuc/start', 'startVideoUpload');
		$this->post('/film/wrzuc', 'uploadVideoPart');
		$this->post('/film/wrzuc/anuluj', 'cancelVideoUpload');

		$this->post('/film/polub', 'like');
		$this->post('/film/odlub', 'dislike');

		$this->get('/film/komentarze', 'comments');
		$this->post('/film/komentarze', 'postComment');
		$this->delete('/film/komentarze', 'deleteComment');

		$this->post('/zdjecie', 'postPhoto');
		$this->delete('/zdjecie', 'deletePhoto');

		$this->post('/plik/wrzuc/start', 'startFileUpload');
		$this->post('/plik/wrzuc', 'uploadFilePart');
		$this->post('/plik/wrzuc/anuluj', 'cancelFileUpload');

		$this->delete('/plik', 'deleteFile');

		$this->get('/plik/udostepnij', 'shareFile');
		$this->get('/plik/udostepnione', 'downloadSharedFile');
	}

	public function index(MediaService $mediaService, AccountService $accountService): Response
	{
		$mediaElements = $mediaService->getAllMedia();

		$videos = array_filter(
			$mediaElements,
			fn(MediaElement $mediaElement) =>
				$mediaElement->mediaType === MediaService::MEDIATYPE_VIDEO
				&& (
					$mediaElement->visibility === MediaService::VIDEO_VISIBILITY_PUBLIC || (
						(
							$mediaElement->visibility === MediaService::VIDEO_VISIBILITY_PRIVATE ||
							$mediaElement->visibility === MediaService::VIDEO_VISIBILITY_UNLISTED
						) &&
						$mediaElement->uploadedBy === $accountService->currentLoggedInUser
					)
				)
		);

		$photos = array_filter(
			$mediaElements,
			fn(MediaElement $mediaElement) => $mediaElement->mediaType === MediaService::MEDIATYPE_PHOTO
		);

		$albums = array_unique(array_map(
			fn(MediaElement $mediaElement) => $mediaElement->album,
			$photos
		));

		$files = array_filter(
			$mediaElements,
			fn(MediaElement $mediaElement) => $mediaElement->mediaType === MediaService::MEDIATYPE_FILE
		);

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

	public function video(AccountService $accountService, MediaService $mediaService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return $this->redirect('/media');
		}

		$mediaIdentifier = $req->query['id'];
		$mediaElement = $mediaService->getMediaElement($mediaIdentifier, MediaService::MEDIATYPE_VIDEO);

		if (
			!($mediaElement instanceof MediaElement) ||
			(
				$mediaElement->visibility === MediaService::VIDEO_VISIBILITY_PRIVATE &&
				$mediaElement->uploadedBy !== $accountService->currentLoggedInUser
			)
		) {
			return $this->redirect('/media');
		}

		$mediaService->checkAndCountView($mediaElement, $accountService->currentLoggedInUser);

		$mediaSourceUrl = '/media_sources/' . $mediaElement->id;

		return $this->template('video.twig', [
			'self' => $accountService->currentLoggedInUser,
			'video' => $mediaElement,
			'src' => $mediaSourceUrl,
			'additional' => [
				'filename' => pathinfo(PUBLIC_DIR . $mediaSourceUrl, PATHINFO_BASENAME),
				'resolution' => 'nieznana',
				'bitrate' => 'nieznany',
				'size' => $mediaElement->sizeText
			],
			'like_info' => [
				'count' => $mediaElement->likedBy->count(),
				'liked_by_me' => $mediaElement->likedBy->contains($accountService->currentLoggedInUser)
			],
			'views' => $mediaElement->viewedBy->count()
		]);
	}

	public function deleteVideo(AccountService $accountService, MediaService $mediaService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$mediaElement = $mediaService->getMediaElement($req->query['id'], MediaService::MEDIATYPE_VIDEO);

		if (!($mediaElement instanceof MediaElement)) {
			return Response::code(404);
		} else if ($mediaElement->uploadedBy !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$mediaService->deleteVideo($mediaElement);

		return new Response();
	}

	public function like(AccountService $accountService, MediaService $mediaService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$mediaExists = $mediaService->like($req->query['id'], $accountService->currentLoggedInUser);

		return Response::code($mediaExists ? 200 : 404);
	}

	public function dislike(AccountService $accountService, MediaService $mediaService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$mediaExists = $mediaService->dislike($req->query['id'], $accountService->currentLoggedInUser);

		return Response::code($mediaExists ? 200 : 404);
	}

	public function comments(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return new Response('nie można załadować komentarzy dla tego filmu', 400);
		}

		return $this->template('komentarze.twig', [
			'self' => $accountService->currentLoggedInUser,
			'comments' => $mediaService->getComments($req->query['id'])
		]);
	}

	public function postComment(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id') || !$req->hasPayload('content')) {
			return Response::code(400);
		}

		$videoMediaElement = $mediaService->getMediaElement($req->query['id'], MediaService::MEDIATYPE_VIDEO);
		$mediaService->postComment($videoMediaElement, $req->payload['content'], $accountService->currentLoggedInUser);

		return new Response();
	}

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

	public function postPhoto(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasPayload('album')) {
			return Response::code(400);
		}

		$photoFile = $req->getFile('photo');

		if (!($photoFile instanceof UploadedFile)) {
			return Response::code(400);
		}

		try {
			$mediaService->postPhoto(
				$accountService->currentLoggedInUser,
				$photoFile,
				$req->payload['album']
			);
		} catch (CannotWriteMediaToDiskException) {
			return $this->json(['error' => 'cannot write to disk']);
		} catch (MediaTooLargeException) {
			return $this->json(['error' => 'too large']);
		}

		return new Response();
	}

	public function deletePhoto(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$mediaElement = $mediaService->getMediaElement($req->query['id'], MediaService::MEDIATYPE_PHOTO);

		if (!($mediaElement instanceof MediaElement)) {
			return Response::code(404);
		} else if ($mediaElement->uploadedBy !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$mediaService->deleteMediaElement($mediaElement);

		return new Response();
	}

	public function startFileUpload(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasPayload('filename')) {
			return Response::code(400);
		}

		$mediaService->startFileUpload($accountService->currentLoggedInUser, $req->payload['filename']);

		return new Response();
	}

	public function uploadFilePart(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		$file = $req->getFile('part');

		if (!($file instanceof UploadedFile)) {
			return new Response('no file', 400);
		}

		$fileUploadToken = $accountService->currentLoggedInUser->fileUploadToken;

		if (!($fileUploadToken instanceof FileUploadToken)) {
			return Response::code(404);
		}

		$mediaService->handleMediaPartUpload(
			$fileUploadToken,
			$file,
			$req->hasPayload('final'),
			MediaService::MEDIATYPE_FILE,
			new class implements PostUploadMediaElementConfigurator {
				/**
				 * @param object|FileUploadToken $uploadToken
				 * @param MediaElement $mediaElement
				 * @return void
				 */
				function configure(object $uploadToken, MediaElement $mediaElement): void
				{
					$mediaElement->name = $uploadToken->fileName;
				}
			}
		);

		return new Response();
	}

	public function cancelFileUpload(MediaService $mediaService, AccountService $accountService): Response
	{
		$fileUploadToken = $accountService->currentLoggedInUser->fileUploadToken;

		$mediaService->cancelMediaUpload($fileUploadToken);

		return new Response();
	}

	public function deleteFile(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$mediaElement = $mediaService->getMediaElement($req->query['id'], MediaService::MEDIATYPE_FILE);

		if (!($mediaElement instanceof MediaElement)) {
			return Response::code(404);
		} else if ($mediaElement->uploadedBy !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$mediaService->deleteMediaElement($mediaElement);

		return new Response();
	}

	public function shareFile(MediaService $mediaService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$mediaElement = $mediaService->getMediaElement($req->query['id'], MediaService::MEDIATYPE_FILE);

		if (!($mediaElement instanceof MediaElement)) {
			return Response::code(404);
		}

		$shareToken = $mediaService->shareFile($mediaElement);

		return new Response($shareToken);
	}

	public function downloadSharedFile(MediaService $mediaService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('token')) {
			return $this->redirect('/');
		}

		$file = $mediaService->getFileByShareToken($req->query['token']);

		if (!($file instanceof MediaElement)) {
			return $this->redirect('/', [
				'error' => 'nie znaleziono pliku'
			]);
		}

		return $this->file($mediaService->getFilePath($file), $file->name);
	}

	public function startVideoUpload(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasPayload('name') || !$req->hasPayload('description') || !$req->hasPayload('visibility')) {
			return Response::code(400);
		}

		$thumbnailFile = $req->getFile('thumbnail');

		if (!($thumbnailFile instanceof UploadedFile)) {
			return Response::code(400);
		}

		$mediaService->startVideoUpload(
			$accountService->currentLoggedInUser,
			$req->payload['name'],
			$req->payload['description'],
			(int)$req->payload['visibility'],
			$thumbnailFile
		);

		return new Response();
	}

	public function uploadVideoPart(MediaService $mediaService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		$file = $req->getFile('part');

		if (!($file instanceof UploadedFile)) {
			return new Response('no file', 400);
		}

		$videoUploadToken = $accountService->currentLoggedInUser->videoUploadToken;

		if (!($videoUploadToken instanceof VideoUploadToken)) {
			return Response::code(404);
		}

		$mediaService->handleMediaPartUpload(
			$videoUploadToken,
			$file,
			$req->hasPayload('final'),
			MediaService::MEDIATYPE_VIDEO,
			new class($mediaService) implements PostUploadMediaElementConfigurator {
				private MediaService $mediaService;

				public function __construct(MediaService $mediaService)
				{
					$this->mediaService = $mediaService;
				}

				/**
				 * @param object|VideoUploadToken $uploadToken
				 * @param MediaElement $mediaElement
				 * @return void
				 */
				function configure(object $uploadToken, MediaElement $mediaElement): void
				{
					$mediaElement->name = $uploadToken->name;
					$mediaElement->description = $uploadToken->description;
					$mediaElement->visibility = $uploadToken->visibility;

					$thumbnailUri = $this->mediaService->publishThumbnail($uploadToken, $mediaElement);
					$mediaElement->thumbnail = $thumbnailUri;
				}
			}
		);

		return new Response();
	}

	public function cancelVideoUpload(MediaService $mediaService, AccountService $accountService): Response
	{
		$videoUploadToken = $accountService->currentLoggedInUser->videoUploadToken;

		$mediaService->cancelMediaUpload($videoUploadToken);

		return new Response();
	}
}