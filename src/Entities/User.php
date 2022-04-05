<?php

namespace App\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User
{
	/**
	 * @ORM\Id()
	 * @ORM\Column(length=40)
	 */
	public string $username;

	/**
	 * @ORM\Column(length=64)
	 */
	public string $password;

	/**
	 * @ORM\Column(type="text")
	 */
	public string $profilePic;

	/**
	 * @ORM\OneToMany(targetEntity="MediaElement", mappedBy="uploadedBy")
	 */
	public $uploadedMedia;

	/**
	 * @ORM\ManyToMany(targetEntity="MediaElement", mappedBy="likedBy")
	 */
	public $likedMedia;

	public function __construct()
	{
		$this->uploadedMedia = new ArrayCollection();
		$this->likedMedia = new ArrayCollection();
	}
}