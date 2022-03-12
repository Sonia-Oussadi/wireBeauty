<?php

namespace App\Entity;

use App\Repository\CompagnyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompagnyRepository::class)]
class Compagny
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $name;

    #[ORM\OneToOne(mappedBy: 'compagny', targetEntity: User::class, cascade: ['persist', 'remove'])]
    private $owner;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        // unset the owning side of the relation if necessary
        if ($owner === null && $this->owner !== null) {
            $this->owner->setCompagny(null);
        }

        // set the owning side of the relation if necessary
        if ($owner !== null && $owner->getCompagny() !== $this) {
            $owner->setCompagny($this);
        }

        $this->owner = $owner;

        return $this;
    }
}
