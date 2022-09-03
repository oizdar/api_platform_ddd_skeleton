<?php

namespace App\Validator;

use App\Entity\UserAccount;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsValidOwnerValidator extends ConstraintValidator
{
    public function __construct(private Security $security)
    {
    }

    /**
     * @param mixed        $value
     * @param IsValidOwner $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof UserAccount) {
            $this->context->buildViolation($constraint->anonymousMessage)
                ->addViolation();

            return;
        }

        if (!$value instanceof UserAccount) {
            throw new \InvalidArgumentException('@IsValidOwner constraint must be put on a UserAccount object');
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($value->getId() !== $user->getId()) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
