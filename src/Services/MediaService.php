<?php

namespace App\Services;

use App\Entities\Comment;
use App\Entities\MediaElement;
use App\Entities\User;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\Database\DatabaseManager;
use Framework\Service\Service;

class MediaService extends Service
{
	public const MEDIATYPE_VIDEO = 0;
	public const MEDIATYPE_PHOTO = 1;
	public const MEDIATYPE_FILE = 2;

	/**
	 * @return MediaElement[]
	 */
	public function getSharedMedia(): array
	{
		$db = $this->getApp()->getDBManager();

		return $db->getAll(MediaElement::class);
	}

	/**
	 * @param DatabaseManager $db
	 * @param string $mediaIdentifier
	 * @param int $mediaType
	 * @return MediaElement|null
	 * @throws NonUniqueResultException
	 */
	private function getMediaElementByIdentifierAndMediaType(DatabaseManager $db,
															 string $mediaIdentifier,
															 int $mediaType): ?MediaElement
	{
		return $db->query('m', MediaElement::class)
			->andWhere('m.id = :mediaIdentifier')
			->andWhere('m.mediaType = :mediaType')
			->setParameters([
				'mediaIdentifier' => $mediaIdentifier,
				'mediaType' => $mediaType
			])
			->getQuery()
			->getOneOrNullResult();
	}

	/**
	 * @param string $mediaIdentifier
	 * @param int $validateMediaType
	 * @return MediaElement|null
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function getMediaElement(string $mediaIdentifier, int $validateMediaType = -1): ?MediaElement
	{
		$db = $this->getApp()->getDBManager();

		/** @var ?MediaElement $found */
		$found = $validateMediaType === -1 ?
			$db->find(MediaElement::class, $mediaIdentifier)
			:
			$this->getMediaElementByIdentifierAndMediaType($db, $mediaIdentifier, $validateMediaType);

		return $found;
	}

	/**
	 * @param string $mediaIdentifier
	 * @param User $as
	 * @return bool
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function like(string $mediaIdentifier, User $as): bool
	{
		$db = $this->getApp()->getDBManager();
		$mediaElement = $this->getMediaElement($mediaIdentifier);

		if (!($mediaElement instanceof MediaElement)) {
			return false;
		}

		if (!$mediaElement->likedBy->contains($as)) {
			$mediaElement->likedBy->add($as);
		}

		$db->flush();

		return true;
	}

	/**
	 * @param string $mediaIdentifier
	 * @param User $as
	 * @return bool
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function dislike(string $mediaIdentifier, User $as): bool
	{
		$db = $this->getApp()->getDBManager();
		$mediaElement = $this->getMediaElement($mediaIdentifier);

		if (!($mediaElement instanceof MediaElement)) {
			return false;
		}

		if ($mediaElement->likedBy->contains($as)) {
			$mediaElement->likedBy->removeElement($as);
		}

		$db->flush();

		return true;
	}

	/**
	 * @param MediaElement $mediaElement
	 * @param User $as
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function checkAndCountView(MediaElement $mediaElement, User $as)
	{
		$db = $this->getApp()->getDBManager();

		if (!$mediaElement->viewedBy->contains($as)) {
			$mediaElement->viewedBy->add($as);
		}

		$db->flush();
	}

	/**
	 * Utility function which returns a file size string in a human-readable format from the number of bytes
	 * @param int $bytes
	 * @return string
	 */
	public function getHumanReadableSize(int $bytes): string
	{
		$i = floor(log($bytes) / log(1024));
		$sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

		return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
	}

	/**
	 * @param string $content
	 * @param User $author
	 * @param MediaElement $mediaElement
	 * @return Comment
	 */
	private function createComment(string $content, User $author, MediaElement $mediaElement): Comment
	{
		$comment = new Comment();
		$comment->content = $content;
		$comment->author = $author;
		$comment->mediaElement = $mediaElement;

		return $comment;
	}

	/**
	 * @param MediaElement $mediaElement
	 * @param string $content
	 * @param User $as
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function postComment(MediaElement $mediaElement, string $content, User $as)
	{
		$db = $this->getApp()->getDBManager();
		$comment = $this->createComment($content, $as, $mediaElement);

		$db->persistAndFlush($comment);
	}

	/**
	 * @param string $mediaIdentifier
	 * @return Comment[]
	 */
	public function getComments(string $mediaIdentifier): array
	{
		$db = $this->getApp()->getDBManager();
		$comments = $db->query('c', Comment::class)
			->andWhere('c.mediaElement = :mediaIdentifier')
			->setParameter('mediaIdentifier', $mediaIdentifier)
			->getQuery()
			->getResult();
		$comments = array_reverse($comments);

		return $comments;
	}
}