<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class FileUploadToken
{
	/**
	 * @ORM\Id()
	 * @ORM\Column(length=32)
	 */
	public string $token;

	/**
	 * @ORM\OneToOne(targetEntity="User", inversedBy="fileUploadToken")
	 * @ORM\JoinColumn(name="user_username", referencedColumnName="username", nullable=false)
	 */
	public User $for;

	/**
	 * @ORM\Column(type="text")
	 */
	public string $fileName;
}