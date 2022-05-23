<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class VideoUploadToken
{
	/**
	 * @ORM\Id()
	 * @ORM\Column(length=32)
	 */
	public string $token;

	/**
	 * @ORM\OneToOne(targetEntity="User", inversedBy="videoUploadToken")
	 * @ORM\JoinColumn(name="user_username", referencedColumnName="username", nullable=false)
	 */
	public User $for;

	/**
	 * @ORM\Column(type="text")
	 */
	public string $name;

	/**
	 * @ORM\Column(type="text")
	 */
	public string $description;

    /**
     * @ORM\Column(type="text")
     */
    public string $mimeType;

	/**
	 * @ORM\Column(type="smallint")
	 */
	public int $visibility;

	/**
	 * @ORM\Column()
	 */
	public string $thumbnailTempFileName;
}