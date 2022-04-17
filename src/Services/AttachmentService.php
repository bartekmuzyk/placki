<?php

namespace App\Services;

use App\Entities\Attachment;
use App\Exceptions\AttachmentTooLargeException;
use App\Exceptions\CannotWriteAttachmentToDiskException;
use Framework\Http\UploadedFile;
use Framework\Service\Service;

class AttachmentService extends Service
{
	public const ATTACHMENTS_DIR = PUBLIC_DIR . '/attachments/';

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

	public function getAttachmentFilePath(Attachment $attachment, bool $full = false): string
	{
		return $full ?
			self::ATTACHMENTS_DIR . $attachment->id
			:
			'/attachments/' . $attachment->id;
	}

	public function deleteAttachmentFileFromDisk(Attachment $attachment)
	{
		unlink($this->getAttachmentFilePath($attachment, true));
	}

	public function saveAttachmentFileOnDisk(Attachment $attachment, UploadedFile $file)
	{
		$file->move($this->getAttachmentFilePath($attachment, true));
	}
}