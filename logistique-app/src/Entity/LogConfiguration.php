<?php
// src/Entity/LogConfiguration.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'log_configuration')]
class LogConfiguration
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $cleaningFrequency;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCleaningFrequency(): ?int
    {
        return $this->cleaningFrequency;
    }

    public function setCleaningFrequency(int $cleaningFrequency): self
    {
        $this->cleaningFrequency = $cleaningFrequency;

        return $this;
    }
}