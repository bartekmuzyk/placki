<?php

namespace Framework\Http;

class UploadedFile
{
	private array $data;

	public function __construct(array $data)
	{
		$this->data = $data;
	}

	public function move(string $path): bool
	{
		return move_uploaded_file($this->data['tmp_name'], $path);
	}

	public function getBasename(): string
	{
		return basename($this->data['name']);
	}

	public function getExtension(): ?string
	{
		$ext = strtolower(pathinfo($this->getBasename(), PATHINFO_EXTENSION));

		return strlen($ext) > 0 ? $ext : null;
	}

    public function getMimeType(): string
    {
        return mime_content_type($this->data['tmp_name']);
    }

	public function getSize(): int
	{
		return $this->data['size'];
	}

	public function getError(): int
	{
		return $this->data['error'];
	}

	public function read(): string
	{
		return file_get_contents($this->data['tmp_name']);
	}
}