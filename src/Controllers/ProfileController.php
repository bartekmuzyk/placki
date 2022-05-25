<?php
/** @noinspection PhpUnused */

namespace App\Controllers;

use App\Entities\MediaElement;
use App\Services\AccountService;
use App\Services\MediaService;
use Framework\Controller\Controller;
use Framework\Http\Response;

class ProfileController extends Controller
{
    public function configureRoutes()
    {
        $this->get('/', 'index');
        $this->get('/moje_media', 'myMedia');
    }

    public function index(AccountService $accountService): Response
    {
        return $this->template('ja.twig', [
            'self' => $accountService->currentLoggedInUser
        ]);
    }

    public function myMedia(AccountService $accountService, MediaService $mediaService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasQuery('type')) {
            return Response::code(400);
        }

        return $this->json(
            array_map(
                fn(MediaElement $mediaElement) => $mediaElement->id,
                $mediaService->getMediaByAuthorAndMediaType($accountService->currentLoggedInUser, (int)$req->query['type'])
            )
        );
    }
}