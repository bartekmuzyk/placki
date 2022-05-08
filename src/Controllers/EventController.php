<?php
/** @noinspection PhpUnused */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Controllers;

use App\Entities\Event;
use App\Entities\User;
use App\Exceptions\EventIconDeletionFailureException;
use App\Services\AccountService;
use App\Services\EventsService;
use DateTime;
use Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand;
use Framework\Controller\Controller;
use Framework\Http\Response;
use Framework\Http\UploadedFile;

class EventController extends Controller
{
    public function configureRoutes()
    {
        $this->get('/', 'index');
        $this->post('/', 'createEvent');
        $this->delete('/', 'deleteEvent');

        $this->get('/json', 'jsonEvents');

        $this->post('/udzial', 'setPartakeState');
    }

    public function index(AccountService $accountService): Response
    {
        return $this->template('wydarzenia.twig', [
            'self' => $accountService->currentLoggedInUser
        ]);
    }

    public function jsonEvents(EventsService $eventsService, AccountService $accountService): Response
    {
        $events = $eventsService->getAllEvents();
        $eventsArray = [];

        foreach ($events as $event) {
            $key = $event->at->format(EventsService::JSON_API_KEY_DATE_FORMAT);

            if (!array_key_exists($key, $eventsArray)) {
                $eventsArray[$key] = [];
            }

            $eventsArray[$key][] = [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'at' => $event->at->format(EventsService::FRONTEND_DATE_FORMAT),
                'icon' => $eventsService->getIconPublicPath($event),
                'organiser' => $event->organiser->username,
                'partaking' => array_map(
                    fn (User $user) => ['username' => $user->username, 'pic' => $user->profilePic],
                    $event->partakingUsers->toArray()
                ),
                'selfIsPartaking' => $event->partakingUsers->contains($accountService->currentLoggedInUser),
                'selfIsOrganiser' => $event->organiser === $accountService->currentLoggedInUser
            ];
        }

        return $this->json($eventsArray);
    }

    public function createEvent(AccountService $accountService, EventsService $eventsService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasPayload('title') || !$req->hasPayload('at')) {
            return Response::code(400);
        }

        $iconFile = $req->getFile('icon');

        if (!($iconFile instanceof UploadedFile)) {
            return Response::code(400);
        }

        $eventsService->createEvent(
            $accountService->currentLoggedInUser,
            $req->payload['title'],
            $req->payload['description'] ?? '',
            new DateTime($req->payload['at']),
            $iconFile
        );

        return new Response();
    }

    public function deleteEvent(AccountService $accountService, EventsService $eventsService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasQuery('id')) {
            return Response::code(400);
        }

        $event = $eventsService->getEvent((int)$req->query['id']);

        if (!($event instanceof Event)) {
            return Response::code(404);
        } else if ($event->organiser !== $accountService->currentLoggedInUser) {
            return Response::code(403);
        }

        try {
            $eventsService->deleteEvent($event);
        } catch (EventIconDeletionFailureException) {
            return Response::code(500);
        }

        return new Response();
    }

    public function setPartakeState(AccountService $accountService, EventsService $eventsService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasQuery('id') || !$req->hasPayload('partake')) {
            return Response::code(400);
        }

        $event = $eventsService->getEvent((int)$req->query['id']);

        if (!($event instanceof Event)) {
            return Response::code(404);
        } else if ($event->organiser === $accountService->currentLoggedInUser) {
            return new Response('is organiser', 400);
        }

        switch ($req->payload['partake']) {
            case 'yes':
                $eventsService->partake($event, $accountService->currentLoggedInUser);
                break;
            case 'no':
                $eventsService->dontPartake($event, $accountService->currentLoggedInUser);
                break;
            default:
                return Response::code(400);
        }

        return new Response();
    }
}