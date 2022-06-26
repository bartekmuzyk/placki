<?php

namespace App\Services;

use App\Exceptions\CDNFileCreationFailureException;
use App\Exceptions\CDNFileDeletionFailureException;
use App\Exceptions\CDNFileNotFoundException;
use Framework\Http\UploadedFile;
use Framework\Service\Service;
use Framework\TempFileUtil\Exception\TempFileReadException;
use Framework\TempFileUtil\TempFile;

class CDNService extends Service
{
    public const CDN_DIR = PROJECT_ROOT . '/cdn';

    public function getFilePath(string $path): string
    {
        return self::CDN_DIR . '/' . $path;
    }

    public function fileExists(string $path): bool
    {
        return is_file($this->getFilePath($path));
    }

    /**
     * @param string $path
     * @return string
     * @throws CDNFileNotFoundException
     */
    public function readFile(string $path): string
    {
        $content = file_get_contents($this->getFilePath($path));

        if ($content === false) {
            throw new CDNFileNotFoundException($path);
        }

        return $content;
    }

    /**
     * @param string $path
     * @param string $contents
     * @return void
     * @throws CDNFileCreationFailureException
     */
    public function writeFile(string $path, string $contents): void
    {
        $result = file_put_contents($this->getFilePath($path), $contents);

        if ($result === false) {
            throw new CDNFileCreationFailureException();
        }
    }

    /**
     * @param string $path
     * @return void
     * @throws CDNFileDeletionFailureException
     */
    public function deleteFile(string $path): void
    {
        $result = unlink($this->getFilePath($path));

        if (!$result) {
            throw new CDNFileDeletionFailureException($path);
        }
    }

    /**
     * @param string $path
     * @param UploadedFile|TempFile $file
     * @return void
     * @throws CDNFileCreationFailureException
     * @throws TempFileReadException
     */
    public function writeFileFrom(string $path, UploadedFile|TempFile $file): void
    {
        $this->writeFile($path, $file->read());
    }
}