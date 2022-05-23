<?php

namespace App\Services;

use App\Entities\FileUploadToken;
use App\Entities\MediaElement;
use App\Entities\SharedMedia;
use App\Entities\User;
use App\Entities\VideoComment;
use App\Entities\VideoUploadToken;
use App\Exceptions\CannotWriteMediaToDiskException;
use App\Exceptions\MediaUploadCancellationFailureException;
use App\Exceptions\MediaTooLargeException;
use App\Interfaces\PostUploadMediaElementConfigurator;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Exception;
use Framework\Database\DatabaseManager;
use Framework\Http\UploadedFile;
use Framework\Service\Service;
use Framework\TempFileUtil\Exception\TempFileDeleteException;
use Framework\TempFileUtil\TempFile;
use Framework\Utils\Utils;

class MediaService extends Service
{
	private const MEDIA_SOURCES_DIR = PUBLIC_DIR . '/media_sources/';
	private const MEDIA_THUMBNAILS_DIR = PUBLIC_DIR . '/thumbnails/';

	public const MEDIATYPE_VIDEO = 0;
	public const MEDIATYPE_PHOTO = 1;
	public const MEDIATYPE_FILE = 2;

	public const VIDEO_VISIBILITY_PUBLIC = 0;
	public const VIDEO_VISIBILITY_UNLISTED = 1;
	public const VIDEO_VISIBILITY_PRIVATE = 2;

	public MediaSharingService $mediaSharingService;

	/**
	 * @return MediaElement[] returns all media held in the database. may contain private media which needs to be filtered before displaying to the user.
	 */
	public function getAllMedia(): array
	{
		$db = $this->getApp()->getDBManager();

		return $db->getAll(MediaElement::class, 0, null, [
			'order_by' => 'uploadedAt',
			'direction' => 'DESC'
		]);
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

	private function createMediaElement(User $uploadedBy, int $mediaType): MediaElement
	{
		$mediaElement = new MediaElement();
		$mediaElement->id = uniqid('media');
		$mediaElement->uploadedBy = $uploadedBy;
		$mediaElement->mediaType = $mediaType;
		$mediaElement->uploadedAt = new DateTime();

		return $mediaElement;
	}

	/**
	 * automatically sets the {@link MediaElement::$sizeText} property to the size string of a file the provided
	 * {@link MediaElement} points to
	 * @param MediaElement $mediaElement
	 * @return void
	 */
	private function applySizeTextAutomatically(MediaElement $mediaElement): void
    {
		$mediaElement->sizeText = Utils::getHumanReadableSize(filesize($this->getFilePath($mediaElement)));
	}

	/**
	 * @param MediaElement $mediaElement
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function deleteMediaElement(MediaElement $mediaElement): void
    {
		$db = $this->getApp()->getDBManager();

		unlink($this->getFilePath($mediaElement));

		$db->removeAndFlush($mediaElement);
	}

	/**
	 * @param MediaElement $mediaElement
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function deleteVideo(MediaElement $mediaElement): void
    {
		unlink(self::MEDIA_THUMBNAILS_DIR . $mediaElement->id);
		$this->deleteMediaElement($mediaElement);
	}

	public function getFilePath(MediaElement $mediaElement): string
	{
        if ($mediaElement->mediaType === self::MEDIATYPE_VIDEO) {
            return self::MEDIA_SOURCES_DIR . $mediaElement->id . '.' . Utils::mimeToExtension($mediaElement->mimeType);
        }

		return self::MEDIA_SOURCES_DIR . $mediaElement->id;
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
	 * @param string $content
	 * @param User $author
	 * @param MediaElement $mediaElement
	 * @return VideoComment
	 */
	private function createComment(string $content, User $author, MediaElement $mediaElement): VideoComment
	{
		$comment = new VideoComment();
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
	public function postComment(MediaElement $mediaElement, string $content, User $as): void
    {
		$db = $this->getApp()->getDBManager();
		$comment = $this->createComment($content, $as, $mediaElement);

		$db->persistAndFlush($comment);
	}

	/**
	 * @param string $mediaIdentifier
	 * @return VideoComment[]
	 */
	public function getComments(string $mediaIdentifier): array
	{
		$db = $this->getApp()->getDBManager();
		$comments = $db->query('c', VideoComment::class)
			->andWhere('c.mediaElement = :mediaIdentifier')
			->setParameter('mediaIdentifier', $mediaIdentifier)
			->getQuery()
			->getResult();
		$comments = array_reverse($comments);

		return $comments;
	}

	/**
	 * @param int $commentId
	 * @return VideoComment|null
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function getVideoComment(int $commentId): ?VideoComment
	{
		/** @var ?VideoComment $comment */
		$comment = $this->getApp()->getDBManager()->find(VideoComment::class, $commentId);

		return $comment;
	}

	/**
	 * @param User $as
	 * @param UploadedFile $file
	 * @param string $album
	 * @return void
	 * @throws CannotWriteMediaToDiskException
	 * @throws MediaTooLargeException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function postPhoto(User $as, UploadedFile $file, string $album): void
    {
		switch ($file->getError()) {
			case UPLOAD_ERR_INI_SIZE:
				throw new MediaTooLargeException();
			case UPLOAD_ERR_CANT_WRITE:
				throw new CannotWriteMediaToDiskException();
		}

		$db = $this->getApp()->getDBManager();

		$mediaElement = $this->createMediaElement($as, self::MEDIATYPE_PHOTO);
		$mediaElement->album = $album;

		$this->applySizeTextAutomatically($mediaElement);
		$db->persistAndFlush($mediaElement);
		$file->move($this->getFilePath($mediaElement));
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private function generateMediaUploadToken(): string
	{
		return bin2hex(random_bytes(16));
	}

	/**
	 * @param User $for
	 * @param string $fileName
	 * @return void
	 * @throws Exception
	 */
	public function startFileUpload(User $for, string $fileName): void
    {
		$db = $this->getApp()->getDBManager();

		$token = new FileUploadToken();
		$token->token = $this->generateMediaUploadToken();
		$token->for = $for;
		$token->fileName = $fileName;

		if ($for->fileUploadToken instanceof FileUploadToken) {
			$this->cancelMediaUpload($for->fileUploadToken);
		}

		$for->fileUploadToken = $token;

		$db->persist($for);
		$db->persistAndFlush($token);
	}

	/**
	 * @param object|FileUploadToken|VideoUploadToken $uploadToken
	 * @return TempFile
	 */
	private function getTempFileForMediaUpload(object $uploadToken): TempFile
	{
		$tempFileUtil = $this->getApp()->getTempFileUtil();
		$tempFileName = $uploadToken->token;
		$tempFile = $tempFileUtil->getTempFile($tempFileName);

		if (!($tempFile instanceof TempFile)) {
			$tempFile = $tempFileUtil->create($tempFileName);
		}

		return $tempFile;
	}

	/**
	 * @param object $uploadToken
	 * @param UploadedFile $file
	 * @param bool $finalPart
	 * @param int $mediaType
	 * @param PostUploadMediaElementConfigurator $configurator
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TempFileDeleteException
	 */
	public function handleMediaPartUpload(object $uploadToken,
										  UploadedFile $file,
										  bool $finalPart,
										  int $mediaType,
										  PostUploadMediaElementConfigurator $configurator): void
    {
		$tempFile = $this->getTempFileForMediaUpload($uploadToken);

		$tempFile->writeUploadedFile($file, true);

		if ($finalPart) {
			$db = $this->getApp()->getDBManager();

			$mediaElement = $this->createMediaElement($uploadToken->for, $mediaType);

			$configurator->configure($uploadToken, $mediaElement);
			$tempFile->move($this->getFilePath($mediaElement));
			$this->applySizeTextAutomatically($mediaElement);
			$db->remove($uploadToken);
			$db->persistAndFlush($mediaElement);
		}
	}

	/**
	 * @param object|FileUploadToken|VideoUploadToken $fileUploadToken
	 * @return void
	 * @throws MediaUploadCancellationFailureException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function cancelMediaUpload(object $fileUploadToken): void
    {
		$db = $this->getApp()->getDBManager();

		$tempFile = $this->getTempFileForMediaUpload($fileUploadToken);

		try {
			$tempFile->delete();
		} catch (TempFileDeleteException) {
			throw new MediaUploadCancellationFailureException();
		}

		$db->removeAndFlush($fileUploadToken);
	}

	/**
	 * @param MediaElement $mediaElement
	 * @return string a share token assigned to this specific {@link MediaElement} referenced by a {@link SharedMedia} object
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function shareFile(MediaElement $mediaElement): string
	{
		$sharedMedia = $this->mediaSharingService->isShared($mediaElement) ?
			$this->mediaSharingService->getSharedInfo($mediaElement)
			:
			$this->mediaSharingService->createSharedMedia($mediaElement);

		return $sharedMedia->id->toString();
	}

	/**
	 * @param string $shareToken
	 * @return MediaElement|null
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function getFileByShareToken(string $shareToken): ?MediaElement
	{
		$sharedMedia = $this->mediaSharingService->getSharedInfoByShareToken($shareToken);

		return $sharedMedia instanceof SharedMedia ?
			$sharedMedia->mediaElement
			:
			null;
	}

	private function getThumbnailTempFileName(VideoUploadToken $videoUploadToken): string
	{
		return "thumb$videoUploadToken->token";
	}

	/**
	 * @param VideoUploadToken $videoUploadToken
	 * @param MediaElement $mediaElement
	 * @return string relative path to the thumbnail file accesible by the browser
	 * @throws TempFileDeleteException
	 */
	public function publishThumbnail(VideoUploadToken $videoUploadToken, MediaElement $mediaElement): string
	{
		$this->getApp()->getTempFileUtil()
			->getTempFile($this->getThumbnailTempFileName($videoUploadToken))
			->move(self::MEDIA_THUMBNAILS_DIR . $mediaElement->id);

		return "/thumbnails/$mediaElement->id";
	}

    /**
     * @param User $for
     * @param string $name
     * @param string $description
     * @param string $mimeType
     * @param int $visibility
     * @param UploadedFile $thumbnailFile
     * @return void
     * @throws MediaUploadCancellationFailureException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
	public function startVideoUpload(User $for,
									 string $name,
									 string $description,
                                     string $mimeType,
									 int $visibility,
									 UploadedFile $thumbnailFile): void
    {
		$db = $this->getApp()->getDBManager();
		$tempFileUtil = $this->getApp()->getTempFileUtil();

		$token = new VideoUploadToken();
		$token->token = $this->generateMediaUploadToken();
		$token->for = $for;
		$token->name = $name;
		$token->description = $description;
        $token->mimeType = $mimeType;
		$token->visibility = $visibility;

		$tempThumbnailFileName = $this->getThumbnailTempFileName($token);
		$tempFileUtil->create($tempThumbnailFileName)->writeUploadedFile($thumbnailFile);
		$token->thumbnailTempFileName = $tempThumbnailFileName;

		if ($for->fileUploadToken instanceof FileUploadToken) {
			$this->cancelMediaUpload($for->fileUploadToken);
		}

		$for->videoUploadToken = $token;

		$db->persist($for);
		$db->persistAndFlush($token);
	}
}