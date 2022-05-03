<?php

namespace Framework\Session;

class SessionManager {
	public function has(string $key): bool
	{
		return isset($_SESSION[$key]);
	}

	public function get(string $key)
	{
		return $_SESSION[$key];
	}

	public function set(string $key, $value): void
    {
		$_SESSION[$key] = $value;
	}

	public function remove(string $key): void
    {
		$this->set($key, null);
	}
}