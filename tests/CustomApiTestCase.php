<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use App\Entity\UserAccount;
use Doctrine\ORM\EntityManagerInterface;

class CustomApiTestCase extends ApiTestCase
{
    protected function createUserAccount(string $email, string $password): UserAccount
    {
        $user = new UserAccount();
        $user->setEmail($email);
        $user->setUsername(substr($email, 0, strpos($email, '@')));

        $encodedPassword = self::getContainer()->get('security.password_hasher')->hashPassword($user, $password);
        $user->setPassword($encodedPassword);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function login(Client $client, string $email, string $password): void
    {
        $client->request('POST', '/login', [
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    protected function createUserAccountAndLogIn(Client $client, string $email, string $password): UserAccount
    {
        $user = $this->createUserAccount($email, $password);
        $this->login($client, $email, $password);

        return $user;
    }
}
