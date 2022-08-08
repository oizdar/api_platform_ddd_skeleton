<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserAccount implements UserInterface, PasswordAuthenticatedUserInterface
{
    private ?int $id;

    private ?string $username;

    private ?string $email;

    private ?string $password;

    private ?string $plainPassword;

    /**
     * @var string[]|null
     */
    private ?array $roles = [];

    private ?bool $active = true;

    /**
     * @var Collection<int, ExampleResourceEntity>
     */
    private Collection $exampleResourceEntities;

    public function __construct()
    {
        $this->exampleResourceEntities = new ArrayCollection();
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param string[]|null $roles
     */
    public function setRoles(?array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, ExampleResourceEntity>
     */
    public function getExampleResourceEntities(): Collection
    {
        return $this->exampleResourceEntities;
    }

    public function addExampleResourceEntity(ExampleResourceEntity $exampleResourceEntity): self
    {
        if (!$this->exampleResourceEntities->contains($exampleResourceEntity)) {
            $this->exampleResourceEntities->add($exampleResourceEntity);
            $exampleResourceEntity->setOwner($this);
        }

        return $this;
    }

    public function removeExampleResourceEntity(ExampleResourceEntity $exampleResourceEntity): self
    {
        if ($this->exampleResourceEntities->removeElement($exampleResourceEntity)) {
            // set the owning side to null (unless already changed)
            if ($exampleResourceEntity->getOwner() === $this) {
                $exampleResourceEntity->setOwner(null);
            }
        }

        return $this;
    }
}
