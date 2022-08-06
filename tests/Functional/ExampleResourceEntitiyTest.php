<?php

namespace App\Tests\Functional;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\UserAccount;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class ExampleResourceEntitiyTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateExampleResourceEntityNotAuthenicatedThrowsError(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/example_resource_entities');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateExampleResourceEntity(): void
    {
        $user = new UserAccount();
        $user->setEmail('test@example.com');
        $user->setUsername('test');
        $user->setPassword('$2y$13$nueGy0ESaeq9zV8xmkmR8OO1xMFWYyLpLWfg845fbfdD1q72oyKAi'); // 12345

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($user);
        $em->flush();

        $client = self::createClient();

        $client->request('POST', '/login', [
            'json' => [
                'email' => 'test@example.com',
                'password' => '12345',
            ],
        ]);

        $this->assertResponseStatusCodeSame(204);
    }
}
