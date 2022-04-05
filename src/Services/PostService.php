<?php

namespace App\Services;

use Framework\Service\Service;

class PostService extends Service
{
	public function getPosts(int $limit): array
	{
		// MOCK NA JUTRO

		return [];
	}

	public function like(int $postId)
	{
		// TODO
	}

	public function dislike(int $postId)
	{
		// TODO
	}
}