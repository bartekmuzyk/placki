<?php

namespace App\Exceptions;

use Exception;
use Framework\Http\UploadedFile;

class AttachmentException extends Exception
{
	public UploadedFile $defectiveFile;

	public function __construct(UploadedFile $file)
	{
		parent::__construct();
		$this->defectiveFile = $file;
	}
}