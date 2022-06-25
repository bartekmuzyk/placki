<?php

namespace App\Exceptions;

class CDNFileDeletionFailureException extends CDNException
{
    public function __construct(string $path)
    {
        parent::__construct("File from CDN directory at path $path could not be deleted.");
    }
}