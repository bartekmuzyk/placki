<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * @ORM\Entity()
 */
class SharedMedia
{
	/**
	 * @ORM\Id()
	 * @ORM\Column(type="uuid")
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class=UuidGenerator::class)
	 */
	public UuidInterface $id;

	/**
	 * @ORM\OneToOne(targetEntity="MediaElement", mappedBy="shared")
	 * @ORM\JoinColumn(name="media_id", nullable=false, onDelete="CASCADE")
	 */
	public MediaElement $mediaElement;
}