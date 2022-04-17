<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Attachment
{
	/**
	 * @ORM\Id()
	 * @ORM\Column(length=16)
	 */
	public string $id;

	/**
	 * @ORM\Column(type="text")
	 */
	public string $originalFilename;

	/**
	 * @ORM\ManyToOne(targetEntity="Post", inversedBy="attachments")
	 * @ORM\JoinColumn(name="post_id", onDelete="CASCADE")
	 */
	public Post $post;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	public string $extension;
}