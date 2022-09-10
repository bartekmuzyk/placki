<?php

namespace App\Entities;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Post
{
	/**
	 * @ORM\Id()
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public int $id;

	/**
	 * @ORM\Column(length=100, options={"charset":"utf8mb4", "collation":"utf8mb4_unicode_ci"})
	 */
	public string $content;

	/**
	 * @ORM\ManyToOne(targetEntity="User", inversedBy="posts")
	 * @ORM\JoinColumn(name="author_username", referencedColumnName="username", onDelete="CASCADE")
	 */
	public User $author;

	/**
	 * @ORM\ManyToMany(targetEntity="User", inversedBy="likedPosts")
	 * @ORM\JoinTable(name="user_liked_posts",
	 *     joinColumns={@ORM\JoinColumn(name="post_id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@ORM\JoinColumn(name="user_username", referencedColumnName="username", onDelete="CASCADE")}
	 * )
	 */
	public $likedBy;

	/**
	 * @ORM\OneToMany(targetEntity="PostComment", mappedBy="post")
	 */
	public $comments;

	/**
	 * @ORM\ManyToOne(targetEntity="Group", inversedBy="posts")
	 * @ORM\JoinColumn(name="group_id", onDelete="CASCADE")
	 */
	public ?Group $group;

	/**
	 * @ORM\Column(type="datetime")
	 */
	public DateTimeInterface $at;

	/**
	 * @var ArrayCollection<Attachment>
	 * @ORM\OneToMany(targetEntity="Attachment", mappedBy="post")
	 */
	public $attachments;

	public function __construct()
	{
		$this->likedBy = new ArrayCollection();
		$this->comments = new ArrayCollection();
		$this->attachments = new ArrayCollection();
	}
}