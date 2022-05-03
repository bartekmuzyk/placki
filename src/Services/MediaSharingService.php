<?php

namespace App\Services;

use App\Entities\MediaElement;
use App\Entities\SharedMedia;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\Service\Service;

class MediaSharingService extends Service
{
	/**
	 * @param MediaElement $mediaElement
	 * @return SharedMedia
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function createSharedMedia(MediaElement $mediaElement): SharedMedia
	{
		$db = $this->getApp()->getDBManager();

		$sharedMedia = new SharedMedia();
		$sharedMedia->mediaElement = $mediaElement;

		$mediaElement->shared = $sharedMedia;

		$db->persist($sharedMedia);
		$db->persistAndFlush($mediaElement);

		return $sharedMedia;
	}

	public function isShared(MediaElement $mediaElement): bool
	{
		return $mediaElement->shared instanceof SharedMedia;
	}

	public function getSharedInfo(MediaElement $mediaElement): ?SharedMedia
	{
		return $mediaElement->shared;
	}

	/**
	 * @param string $shareToken
	 * @return SharedMedia|null
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function getSharedInfoByShareToken(string $shareToken): ?SharedMedia
	{
		$db = $this->getApp()->getDBManager();
		/** @var ?SharedMedia $sharedMedia */
		$sharedMedia = $db->find(SharedMedia::class, $shareToken);

		return $sharedMedia;
	}
}