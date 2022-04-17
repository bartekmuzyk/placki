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

	/**
	 * @ORM\ManyToMany(targetEntity="MediaElement", mappedBy="viewedBy")
	 */
	public $viewedMedia;

	/**
	 * @ORM\OneToMany(targetEntity="Post", mappedBy="author")
	 */
	public $posts;

	/**
	 * @ORM\ManyToMany(targetEntity="Post", mappedBy="likedBy")
	 */
	public $likedPosts;

	/**
	 * @ORM\ManyToMany(targetEntity="Group", mappedBy="members")
	 */
	public $joinedGroups;

	/**
	 * @ORM\OneToMany(targetEntity="Group", mappedBy="owner")
	 */
	public $ownedGroups;

	/**
	 * @ORM\ManyToMany(targetEntity="Group", mappedBy="bans")
	 */
	public $bannedInGroups;

	/**
	 * @ORM\ManyToMany(targetEntity="User", mappedBy="joinRequests")
	 */
	public $joinRequests;

	public function __construct()
	{
		$this->uploadedMedia = new ArrayCollection();
		$this->likedMedia = new ArrayCollection();
		$this->viewedMedia = new ArrayCollection();
		$this->posts = new ArrayCollection();
		$this->likedPosts = new ArrayCollection();
		$this->joinedGroups = new ArrayCollection();
		$this->ownedGroups = new ArrayCollection();
		$this->bannedInGroups = new ArrayCollection();
		$this->joinRequests = new ArrayCollection();
	}
}