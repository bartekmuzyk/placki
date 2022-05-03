<?php

namespace Framework\TempFileUtil;

use Framework\Http\UploadedFile;
use Framework\TempFileUtil\Exception\TempFileDeleteException;
use Framework\TempFileUtil\Exception\TempFileReadException;

class TempFile
{
	private string $filePath;

	public function __construct(string $filePath)
	{
		$this->filePath = $filePath;
	}

	public function write(string $contents, bool $append = false): void
    {
		file_put_contents($this->filePath, $contents, $append ? FILE_APPEND : 0);
	}

	public function writeUploadedFile(UploadedFile $file, bool $append = false): void
    {
		$this->write($file->read(), $append);
	}

	/**
	 * @return string
	 * @throws TempFileReadException
	 */
	public function read(): string
	{
		$result = file_get_contents($this->filePath);

		if ($result === false) {
			throw new TempFileReadException();
		}

		return $result;
	}

	/**
	 * copies the file to the provided path and deletes the original
	 * @param string $path
	 * @return void
	 * @throws TempFileDeleteException when deletion fails (file is already copied at that point)
	 */
	public function move(string $path): void
    {
		$this->copy($path);
		$this->delete();
	}

	public function copy(string $path): void
    {
		copy($this->filePath, $path);
	}

	/**
	 * @return void
	 * @throws TempFileDeleteException
	 */
	public function delete(): void
    {
		$result = unlink($this->filePath);

		if (!$result) {
			throw new TempFileDeleteException();
		}
	}
}