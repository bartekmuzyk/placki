<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

namespace App\Controllers;

use App\Entities\MediaElement;
use App\Entities\Post;
use App\Entities\SharedMedia;
use App\Services\AccountService;
use App\Services\DateFormatterService;
use App\Services\MediaService;
use Framework\Controller\Controller;
use Framework\Http\Response;
use Framework\Http\UploadedFile;

class ProfileController extends Controller
{
    public function configureRoutes()
    {
        $this->get('/', 'index');

        $this->post('/profilowe', 'setProfilePicture');

        $this->get('/moje_media/wideo', 'myVideos');
        $this->get('/moje_media/zdjecia', 'myPhotos');
        $this->get('/moje_media/pliki', 'myFiles');

        $this->get('/polubione/posty', 'likedPosts');
        $this->get('/polubione/filmy', 'likedVideos');
    }

    public function index(): Response
    {
        return $this->template('ja.twig');
    }

    public function setProfilePicture(AccountService $accountService): Response
    {
        $req = $this->getRequest();
        $pictureFile = $req->getFile('pic');

        if (!($pictureFile instanceof UploadedFile)) {
            return Response::code(400);
        }

        $accountService->setCustomProfilePicture($accountService->currentLoggedInUser, $pictureFile);

        return new Response();
    }

    public function myVideos(AccountService $accountService, MediaService $mediaService, DateFormatterService $dateFormatterService): Response
    {
        $req = $this->getRequest();

        return $this->json(
            array_map(
                fn(MediaElement $mediaElement) => [
                    'id' => $mediaElement->id,
                    'name' => $mediaElement->name,
                    'thumbnail' => $mediaElement->thumbnail,
                    'uploadedAt' => $dateFormatterService->format($mediaElement->uploadedAt, DateFormatterService::FORMAT_DATE_AND_TIME),
                    'isPrivate' => $mediaElement->visibility === MediaService::VIDEO_VISIBILITY_PRIVATE
                ],
                $mediaService->getMediaByAuthorAndMediaType(
                    $req->hasQuery('uzytkownik') ? $req->query['uzytkownik'] : $accountService->currentLoggedInUser,
                    MediaService::MEDIATYPE_VIDEO
                )
            )
        );
    }

    public function myPhotos(AccountService $accountService, MediaService $mediaService): Response
    {
        $req = $this->getRequest();

        return $this->json(
            array_map(
                fn(MediaElement $mediaElement) => [
                    'id' => $mediaElement->id,
                    'album' => $mediaElement->album
                ],
                $mediaService->getMediaByAuthorAndMediaType(
                    $req->hasQuery('uzytkownik') ? $req->query['uzytkownik'] : $accountService->currentLoggedInUser,
                    MediaService::MEDIATYPE_PHOTO
                )
            )
        );
    }

    public function myFiles(AccountService $accountService, MediaService $mediaService, DateFormatterService $dateFormatterService): Response
    {
        $req = $this->getRequest();

        return $this->json(
            array_map(
                fn(MediaElement $mediaElement) => [
                    'id' => $mediaElement->id,
                    'name' => $mediaElement->name,
                    'size' => $mediaElement->sizeText,
                    'uploadedAt' => $dateFormatterService->format($mediaElement->uploadedAt, DateFormatterService::FORMAT_DATE_AND_TIME),
                    'isShared' => $mediaElement->shared instanceof SharedMedia
                ],
                $mediaService->getMediaByAuthorAndMediaType(
                    $req->hasQuery('uzytkownik') ? $req->query['uzytkownik'] : $accountService->currentLoggedInUser,
                    MediaService::MEDIATYPE_FILE
                )
            )
        );
    }

    public function likedPosts(AccountService $accountService, DateFormatterService $dateFormatterService): Response
    {
        $req = $this->getRequest();

        $targetUser = $req->hasQuery('uzytkownik') ?
            $accountService->getUser($req->query['uzytkownik'])
            :
            $accountService->currentLoggedInUser;

        return $this->json(
            array_map(
                fn(Post $post) => [
                    'id' => $post->id,
                    'author' => $post->author->username,
                    'content' => $post->content,
                    'at' => $dateFormatterService->format($post->at, DateFormatterService::FORMAT_HUMAN)
                ],
                $targetUser->likedPosts->toArray()
            )
        );
    }

    public function likedVideos(AccountService $accountService, DateFormatterService $dateFormatterService): Response
    {
        $req = $this->getRequest();

        $targetUser = $req->hasQuery('uzytkownik') ?
            $accountService->getUser($req->query['uzytkownik'])
            :
            $accountService->currentLoggedInUser;

        return $this->json(
            array_map(
                fn(MediaElement $mediaElement) => [
                    'id' => $mediaElement->id,
                    'thumbnail' => $mediaElement->thumbnail,
                    'name' => $mediaElement->name,
                    'isPrivate' => $mediaElement->visibility === MediaService::VIDEO_VISIBILITY_PRIVATE
                ],
                $targetUser->likedMedia->filter(
                    fn(MediaElement $mediaElement) => $mediaElement->mediaType === MediaService::MEDIATYPE_VIDEO
                )->toArray()
            )
        );
    }
}