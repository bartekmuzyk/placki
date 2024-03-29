<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class PostComment
{
	/**
	 * @ORM\Id()
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public int $id;

	/**
	 * @ORM\Column(length=1000, options={"charset":"utf8mb4", "collation":"utf8mb4_unicode_ci"})
	 */
	public string $content;

	/**
	 * @ORM\ManyToOne(targetEntity="User")
	 * @ORM\JoinColumn(name="author_username", referencedColumnName="username", onDelete="CASCADE")
	 */
	public User $author;

//	XDDD
//	can we just appreciate the effort he put into the writing

	/**
	 * @ORM\ManyToOne(targetEntity="Post", inversedBy="comments")
	 * @ORM\JoinColumn(name="post_id", onDelete="CASCADE")
	 */
	public Post $post;
}