<?php

namespace Framework\TempFileUtil;

class TempFileUtil
{
	private string $dirPath;

	public function __construct(string $tempFilesDirectoryPath)
	{
		$this->dirPath = $tempFilesDirectoryPath;
	}

	private function getFilePathForName(string $name): string
	{
		return $this->dirPath . $name;
	}

	public function getTempFile(string $name): ?TempFile
	{
		return $this->exists($name) ? new TempFile($this->getFilePathForName($name)) : null;
	}

	public function exists(string $name): bool
	{
		return is_file($this->getFilePathForName($name));
	}

	public function create(string $name): TempFile
	{
		if (!$this->exists($name)) {
			touch($this->getFilePathForName($name));
		}

		return $this->getTempFile($name);
	}
}