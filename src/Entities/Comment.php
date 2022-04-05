<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Comment
{
	/**
	 * @ORM\Id()
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public int $id;

	/**
	 * @ORM\Column(length=1000)
	 */
	public string $content;

	/**
	 * @ORM\ManyToOne(targetEntity="User")
	 * @ORM\JoinColumn(name="author_username", referencedColumnName="username")
	 */
	public User $author;

//	XDDD
//	can we just appreciate the effort he put into the writing

	/**
	 * @ORM\ManyToOne(targetEntity="MediaElement", inversedBy="comments")
	 * @ORM\JoinColumn(name="media_id")
	 */
	public MediaElement $mediaElement;
}