<?php

namespace App\Services;

use App\Entities\Attachment;
use App\Exceptions\AttachmentTooLargeException;
use App\Exceptions\CannotWriteAttachmentToDiskException;
use App\Exceptions\CDNFileCreationFailureException;
use App\Exceptions\CDNFileDeletionFailureException;
use Framework\Http\UploadedFile;
use Framework\Service\Service;
use Framework\TempFileUtil\Exception\TempFileReadException;

class AttachmentService extends Service
{
    public CDNService $CDNService;

	/**
	 * @param UploadedFile $file
	 * @return Attachment
	 * @throws AttachmentTooLargeException
	 * @throws CannotWriteAttachmentToDiskException
	 */
	public function createAttachment(UploadedFile $file): Attachment
	{
		switch ($file->getError()) {
			case UPLOAD_ERR_INI_SIZE:
				throw new AttachmentTooLargeException($file);
			case UPLOAD_ERR_CANT_WRITE:
				throw new CannotWriteAttachmentToDiskException($file);
		}

		$attachmentId = uniqid('att');

		$attachment = new Attachment();
		$attachment->id = $attachmentId;
		$attachment->originalFilename = $file->getBasename();
		$attachment->extension = $file->getExtension();

		return $attachment;
	}

	public function getAttachmentFilePath(Attachment $attachment): string
	{
		return '/cdn/attachments/' . $attachment->id;
	}

    /**
     * @param Attachment $attachment
     * @return void
     * @throws CDNFileDeletionFailureException
     */
	public function deleteAttachmentFileFromDisk(Attachment $attachment): void
    {
        $this->CDNService->deleteFile("attachments/$attachment->id");
	}

    /**
     * @param Attachment $attachment
     * @param UploadedFile $file
     * @return void
     * @throws CDNFileCreationFailureException
     * @throws TempFileReadException
     */
	public function saveAttachmentFileOnDisk(Attachment $attachment, UploadedFile $file): void
    {
        $this->CDNService->writeFileFrom("attachments/$attachment->id", $file);
	}
}