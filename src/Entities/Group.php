<?php

namespace App\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="placki_Group")
 */
class Group
{
	/**
	 * @ORM\Id()
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public int $id;

	/**
	 * @ORM\Column(length=40)
	 */
	public string $name;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	public string $description;

	/**
	 * @ORM\Column(type="text")
	 */
	public string $picFilename;

	/**
	 * @ORM\ManyToMany(targetEntity="User", inversedBy="joinedGroups")
	 * @ORM\JoinTable(name="user_joined_groups",
	 *     joinColumns={@ORM\JoinColumn(name="group_id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@ORM\JoinColumn(name="user_username", referencedColumnName="username", onDelete="CASCADE")}
	 * )
	 */
	public $members;

	/**
	 * @ORM\ManyToOne(targetEntity="User", inversedBy="ownedGroups")
	 * @ORM\JoinColumn(name="owner_username", referencedColumnName="username", onDelete="CASCADE")
	 */
	public User $owner;

	/**
	 * @ORM\OneToMany(targetEntity="Post", mappedBy="group")
	 */
	public $posts;

	/**
	 * @ORM\ManyToMany(targetEntity="User", inversedBy="bannedInGroups")
	 * @ORM\JoinTable(name="user_banned_in_groups",
	 *     joinColumns={@ORM\JoinColumn(name="group_id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@ORM\JoinColumn(name="user_username", referencedColumnName="username", onDelete="CASCADE")}
	 * )
	 */
	public $bans;

	/**
	 * @ORM\Column(type="smallint")
	 */
	public int $accessLevel;
	/**
	 * @ORM\ManyToMany(targetEntity="User", inversedBy="joinRequests")
	 * @ORM\JoinTable(name="group_join_requests",
	 *     joinColumns={@ORM\JoinColumn(name="group_id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@ORM\JoinColumn(name="user_username", referencedColumnName="username", onDelete="CASCADE")}
	 * )
	 */
	public $joinRequests;

	public function __construct()
	{
		$this->members = new ArrayCollection();
		$this->posts = new ArrayCollection();
		$this->bans = new ArrayCollection();
		$this->joinRequests = new ArrayCollection();
	}
}