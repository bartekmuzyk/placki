<?php

namespace App\Entities;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * @ORM\Entity()
 */
class Event
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public int $id;

    /**
     * @ORM\Column(type="text")
     */
    public string $title;

    /**
     * @ORM\Column(type="text")
     */
    public string $description;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTimeInterface $at;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="organisedEvents")
     * @ORM\JoinColumn(name="organiser_username", referencedColumnName="username", nullable=false, onDelete="CASCADE")
     */
    public User $organiser;

    /**
     * @var PersistentCollection $partakingUsers
     * @ORM\ManyToMany(targetEntity="User", inversedBy="partakingInEvents")
     * @ORM\JoinTable(name="user_partaking_in_event",
     *     joinColumns={@ORM\JoinColumn(name="event_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_username", referencedColumnName="username")}
     *     )
     */
    public $partakingUsers;

    public function __construct()
    {
        $this->partakingUsers = new ArrayCollection();
    }
}