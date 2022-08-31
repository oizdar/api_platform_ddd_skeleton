<?php

namespace App\Serializer\Normalizer;

use App\Entity\UserAccount;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserAccountNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'USER_ACCOUNT_NORMALIZER_ALREADY_CALLED';

    public function __construct(private Security $security)
    {
    }

    /**
     * @param UserAccount             $object
     * @param array<string, string[]> $context
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if ($this->userIsOwner($object)) {
            $context['groups'][] = 'owner:read';
        }

        $context[self::ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     * @param object                  $data
     * @param array<string, string[]> $context
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof UserAccount;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return false;
    }

    private function userIsOwner(UserAccount $object): bool
    {
        return $this->security->getUser()?->getUserIdentifier() === $object->getUserIdentifier();
    }
}
