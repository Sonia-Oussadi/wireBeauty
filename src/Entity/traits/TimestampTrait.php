<?php

namespace App\Entity\Traits;

use Gedmo\Mapping\Annotation as Gedmo;

trait TimestampTrait
{
    /**
     * @ORM\Column(type="datetime_immutable")
     * @Gedmo\Timestampable(on="create")
     */
    private $created_at; 

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Gedmo\Timestampable(on="update")
     */
    private $updated_at;
     /** @phpstan-ignore-next-line */
    public function getCreatedAt(): ?\DateTimeImmutable /** @phpstan-ignore-line */
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }
    /** @phpstan-ignore-next-line */
    public function getUpdatedAt(): ?\DateTimeImmutable  /** @phpstan-ignore-line */
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
