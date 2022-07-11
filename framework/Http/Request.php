<?php

namespace Framework\Http;

class Request {
	public array $query;

	public array $payload;

	public array $files;

    public array $headers;

	public string $route;

	public static function createFromGlobals(): self
	{
		$instance = new self();

		$instance->query = $_GET;
		$instance->payload = $_POST;
		$instance->files = $_FILES;
        $instance->headers = array_change_key_case(getallheaders(), CASE_LOWER);

		return $instance;
	}

    public function getAuthorization(): ?string
    {
        return array_key_exists('authorization', $this->headers) ? $this->headers['authorization'] : null;
    }

	public function getFile(string $key): ?UploadedFile
	{
		return array_key_exists($key, $this->files) ? new UploadedFile($this->files[$key]) : null;
	}

	public function getFileFromArray(string $key, int $index): ?UploadedFile
	{
		return array_key_exists($key, $this->files) ?
			new UploadedFile([
				'name' => $this->files[$key]['name'][$index],
				'size' => $this->files[$key]['size'][$index],
				'tmp_name' => $this->files[$key]['tmp_name'][$index],
				'type' => $this->files[$key]['type'][$index],
				'error' => $this->files[$key]['error'][$index]
			])
			:
			null;
	}

	/**
	 * @param string $key
	 * @return UploadedFile[]
	 */
	public function getFilesArray(string $key): array
	{
		if (!array_key_exists($key, $this->files)) {
			return [];
		}

		$files = [];

		foreach ($this->files[$key]['tmp_name'] as $index => $tmp_name) {
			$files[] = $this->getFileFromArray($key, $index);
		}

		return $files;
	}

	public function hasQuery(string $parameterName): bool
	{
		return array_key_exists($parameterName, $this->query);
	}

	public function hasPayload(string $parameterName): bool
	{
		return array_key_exists($parameterName, $this->payload);
	}
}