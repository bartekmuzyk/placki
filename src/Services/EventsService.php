<?php

namespace App\Services;

use App\Controllers\EventController;
use App\Entities\Event;
use App\Entities\User;
use App\Exceptions\EventIconDeletionFailureException;
use DateTimeInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\Http\UploadedFile;
use Framework\Service\Service;

class EventsService extends Service
{
    private const EVENT_ICONS_DIR = PUBLIC_DIR . '/event_icons/';

    /** date format used when returning the list of events as a JSON response (example in {@link EventController::jsonEvents()}) */
    public const JSON_API_KEY_DATE_FORMAT = 'Ymd';
    /** date format which must be used when expecting the value to be parsed by <code>moment.js</code> on the frontend */
    public const FRONTEND_DATE_FORMAT = DateTimeInterface::ATOM;

    /**
     * @return Event[]
     */
    public function getAllEvents(): array
    {
        $db = $this->getApp()->getDBManager();

        return $db->getAll(Event::class, 0, null, ['order_by' => 'at']);
    }

    /**
     * @param int $id
     * @return Event|null
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function getEvent(int $id): ?Event
    {
        $db = $this->getApp()->getDBManager();
        /** @var ?Event $event */
        $event = $db->find(Event::class, $id);

        return $event;
    }

    /**
     * @param User $organiser
     * @param string $title
     * @param string $description
     * @param DateTimeInterface $at
     * @param UploadedFile $iconFile
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createEvent(User $organiser, string $title, string $description, DateTimeInterface $at, UploadedFile $iconFile): void
    {
        $db = $this->getApp()->getDBManager();

        $event = new Event();
        $event->organiser = $organiser;
        $event->partakingUsers->add($organiser);  // the organiser always takes part in an event they created
        $event->title = $title;
        $event->description = $description;
        $event->at = $at;

        $db->persistAndFlush($event);

        $iconFile->move(self::EVENT_ICONS_DIR . $event->id);
    }

    /**
     * @param Event $event
     * @return void
     * @throws EventIconDeletionFailureException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteEvent(Event $event): void
    {
        $db = $this->getApp()->getDBManager();

        $eventIconDir = self::EVENT_ICONS_DIR . $event->id;
        $iconDeleted = !is_file($eventIconDir) || unlink($eventIconDir);

        if (!$iconDeleted) {
            throw new EventIconDeletionFailureException();
        }

        $db->removeAndFlush($event);
    }

    /**
     * @param Event $event
     * @param User $user
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function partake(Event $event, User $user): void
    {
        $db = $this->getApp()->getDBManager();

        $event->partakingUsers->add($user);
        $db->persistAndFlush($event);
    }

    /**
     * @param Event $event
     * @param User $user
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function dontPartake(Event $event, User $user): void
    {
        $db = $this->getApp()->getDBManager();

        $event->partakingUsers->removeElement($user);
        $db->persistAndFlush($event);
    }
}