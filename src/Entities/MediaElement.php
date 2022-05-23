<?php

namespace App\Entities;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class MediaElement
{
	/**
	 * @ORM\Id()
	 * @ORM\Column(length=18)
	 */
	public string $id;

	/**
	 * @ORM\Column(type="smallint")
	 */
	public int $mediaType;

    /**
     * @ORM\Column(type="text")
     */
    public string $mimeType;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	public string $name;

	/**
	 * @ORM\Column(length=10)
	 */
	public string $sizeText;

	/**
	 * @ORM\ManyToOne(targetEntity="User", inversedBy="uploadedMedia")
	 * @ORM\JoinColumn(name="uploadedBy", referencedColumnName="username", onDelete="CASCADE")
	 */
	public User $uploadedBy;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	public string $description;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	public string $album;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	public string $thumbnail;

	/**
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	public int $visibility;

	/**
	 * @ORM\Column(type="datetime")
	 */
	public DateTimeInterface $uploadedAt;

	/**
	 * @ORM\ManyToMany(targetEntity="User", inversedBy="viewedMedia")
	 * @ORM\JoinTable(name="user_viewed_media",
	 *     joinColumns={@ORM\JoinColumn(name="media_id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@ORM\JoinColumn(name="user_username", referencedColumnName="username", onDelete="CASCADE")}
	 * )
	 */
	public $viewedBy;

	/**
	 * @ORM\ManyToMany(targetEntity="User", inversedBy="likedMedia")
	 * @ORM\JoinTable(name="user_liked_media",
	 *     joinColumns={@ORM\JoinColumn(name="media_id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@ORM\JoinColumn(name="user_username", referencedColumnName="username", onDelete="CASCADE")}
	 * )
	 */
	public $likedBy;

	/**
	 * @ORM\OneToMany(targetEntity="VideoComment", mappedBy="mediaElement")
	 */
	public $comments;

	/**
	 * @ORM\OneToOne(targetEntity="SharedMedia", inversedBy="mediaElement")
	 * @ORM\JoinColumn(name="shared", onDelete="SET NULL")
	 */
	public ?SharedMedia $shared;

	public function __construct()
	{
		$this->viewedBy = new ArrayCollection();
		$this->likedBy = new ArrayCollection();
		$this->comments = new ArrayCollection();
	}
}