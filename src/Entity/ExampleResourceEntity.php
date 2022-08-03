<?php

namespace App\Entity;

class ExampleResourceEntity
{
    private ?int $id;

    private ?string $title;

    private ?string $description;

    private ?UserAccount $owner;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?UserAccount
    {
        return $this->owner;
    }

    public function setOwner(?UserAccount $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
