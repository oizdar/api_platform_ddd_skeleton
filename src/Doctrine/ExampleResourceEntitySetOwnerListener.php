<?php

namespace App\Doctrine;

use App\Entity\ExampleResourceEntity;
use App\Entity\UserAccount;
use Symfony\Component\Security\Core\Security;

class ExampleResourceEntitySetOwnerListener
{
    public function __construct(protected Security $security)
    {
    }

    public function prePersist(ExampleResourceEntity $exampleResourceEntity): void
    {
        if ($exampleResourceEntity->getOwner()) {
            return;
        }
        if ($this->security->getUser() instanceof UserAccount) {
            $exampleResourceEntity->setOwner($this->security->getUser());
        }
    }
}
