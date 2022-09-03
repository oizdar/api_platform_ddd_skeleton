<?php

namespace App\Entity;

class ExampleResourceEntity
{
    private ?int $id = null;

    private ?string $title = null;

    private ?string $description = null;

    private ?bool $published = false;

    private ?UserAccount $owner = null;

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

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(?bool $published): self
    {
        $this->published = $published;

        return $this;
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
