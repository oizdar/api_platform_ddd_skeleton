<?php

namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use App\Entity\UserAccount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserDataPersister implements DataPersisterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function supports($data): bool
    {
        return $data instanceof UserAccount;
    }

    /**
     * @param UserAccount $data
     */
    public function persist($data): UserAccount
    {
        if ($data->getPlainPassword()) {
            $data->setPassword(
                $this->passwordHasher->hashPassword($data, $data->getPlainPassword())
            );
            $data->eraseCredentials();
        }
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }

    /**
     * @param UserAccount $data
     */
    public function remove($data): void
    {
        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}
