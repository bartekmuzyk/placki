<?php

namespace App\Services;

use App\Entities\Attachment;
use App\Entities\Group;
use App\Entities\Post;
use App\Entities\PostComment;
use App\Entities\User;
use App\Exceptions\AttachmentTooLargeException;
use App\Exceptions\CannotWriteAttachmentToDiskException;
use ArrayIterator;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Exception;
use Framework\Http\UploadedFile;
use Framework\Service\Service;
use MultipleIterator;

class PostService extends Service
{
	public AttachmentService $attachmentService;

	/**
	 * @param int $limit
	 * @param User $as
	 * @return Post[]
	 */
	public function getPosts(int $limit, User $as): array
	{
		$db = $this->getApp()->getDBManager();
		$qb = $db->query('p', Post::class)->from(User::class, 'u');

		/** @var Post[] $posts */
		$posts = $qb
			->andWhere('u.username = :username')
			->andWhere(
				$qb->expr()->orX(
					'p.group IS NULL',
					$qb->expr()->isMemberOf('p.group', 'u.joinedGroups')
				)
			)
			->orderBy('p.at', 'DESC')
			->setParameter('username', $as->username)
			->getQuery()
			->setMaxResults($limit)
			->getResult();

		return $posts;
	}

	/**
	 * @param int $groupId
	 * @param int $limit
	 * @return Post[]
	 */
	public function getPostsFromGroup(int $groupId, int $limit): array
	{
		$db = $this->getApp()->getDBManager();

		/** @var Post[] $posts */
		$posts = $db->query('p', Post::class)
			->andWhere('p.group = :groupId')
			->orderBy('p.at', 'DESC')
			->setParameter('groupId', $groupId)
			->getQuery()
			->setMaxResults($limit)
			->getResult();

		return $posts;
	}

	/**
	 * @param int $postId
	 * @return Post|null
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function getPost(int $postId): ?Post
	{
		/** @var ?Post $post */
		$post = $this->getApp()->getDBManager()->find(Post::class, $postId);

		return $post;
	}

	/**
	 * @param string $content
	 * @param Group|null $group
	 * @param User $author
	 * @param UploadedFile[] $attachmentsFiles
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws AttachmentTooLargeException
	 * @throws CannotWriteAttachmentToDiskException
	 * @throws Exception
	 */
	public function createPost(string $content, ?Group $group, User $author, array $attachmentsFiles)
	{
		$db = $this->getApp()->getDBManager();

		$post = new Post();
		$post->content = $content;
		$post->group = $group;
		$post->author = $author;
		$post->at = new DateTime();

		foreach ($attachmentsFiles as $attachmentFile) {
			$attachment = $this->attachmentService->createAttachment($attachmentFile);

			$attachment->post = $post;
			$post->attachments->add($attachment);

			$db->persist($attachment);
		}

		$db->persistAndFlush($post);

		$attachmentsIterator = new MultipleIterator();
		$attachmentsIterator->attachIterator(new ArrayIterator($attachmentsFiles));
		$attachmentsIterator->attachIterator($post->attachments->getIterator());

		foreach ($attachmentsIterator as [$attachmentFile, $attachmentEntity]) {
			$this->attachmentService->saveAttachmentFileOnDisk($attachmentEntity, $attachmentFile);
		}
	}

	/**
	 * @param Post $post
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function deletePost(Post $post)
	{
		$db = $this->getApp()->getDBManager();

		foreach ($post->attachments as $attachment) {
			$this->attachmentService->deleteAttachmentFileFromDisk($attachment);
		}

		$db->removeAndFlush($post);
	}

	public function authorizeToPost(Post $post, User $as): bool
	{
		return !($post->group instanceof Group) || $as->joinedGroups->contains($post->group);
	}

	/**
	 * @param int $postId
	 * @param User $as
	 * @return bool
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function like(int $postId, User $as): bool
	{
		$post = $this->getPost($postId);
		$authorized = $this->authorizeToPost($post, $as);

		if ($authorized) {
			$post->likedBy->add($as);

			$this->getApp()->getDBManager()->flush();
		}

		return $authorized;
	}

	/**
	 * @param int $postId
	 * @param User $as
	 * @return bool
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function dislike(int $postId, User $as): bool
	{
		$post = $this->getPost($postId);
		$authorized = $this->authorizeToPost($post, $as);

		if ($authorized) {
			$post->likedBy->removeElement($as);

			$this->getApp()->getDBManager()->flush();
		}

		return $authorized;
	}

	/**
	 * @param string $content
	 * @param User $author
	 * @param Post $post
	 * @return PostComment
	 */
	private function createComment(string $content, User $author, Post $post): PostComment
	{
		$comment = new PostComment();
		$comment->content = $content;
		$comment->author = $author;
		$comment->post = $post;

		return $comment;
	}

	/**
	 * @param Post $post
	 * @param string $content
	 * @param User $as
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function postComment(Post $post, string $content, User $as)
	{
		$db = $this->getApp()->getDBManager();
		$comment = $this->createComment($content, $as, $post);

		$db->persistAndFlush($comment);
	}

	/**
	 * @param int $postId
	 * @return PostComment[]
	 */
	public function getComments(int $postId): array
	{
		$db = $this->getApp()->getDBManager();
		$comments = $db->query('c', PostComment::class)
			->andWhere('c.post = :postId')
			->setParameter('postId', $postId)
			->getQuery()
			->getResult();
		$comments = array_reverse($comments);

		return $comments;
	}

	/**
	 * @param int $commentId
	 * @return PostComment|null
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function getPostComment(int $commentId): ?PostComment
	{
		/** @var ?PostComment $comment */
		$comment = $this->getApp()->getDBManager()->find(PostComment::class, $commentId);

		return $comment;
	}
}