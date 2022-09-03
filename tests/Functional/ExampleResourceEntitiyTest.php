<?php

namespace App\Tests\Functional;

use App\Entity\ExampleResourceEntity;
use App\Tests\CustomApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class ExampleResourceEntitiyTest extends CustomApiTestCase
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
        $client = self::createClient();

        $this->createUserAccountAndLogIn($client, 'test@example.com', 'test12345');

        $client->request('POST', '/api/example_resource_entities', [
            'json' => [],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateExampleResourceEntityOwnerValidation(): void
    {
        $client = self::createClient();

        $authenticatedUser = $this->createUserAccountAndLogIn($client, 'test@example.com', 'test12345');
        $otherUser = $this->createUserAccount('test2@example.com', 'test12345');

        $exampleResourceEntityData = [
            'title' => 'example title',
            'description' => 'description example ',
        ];

        $client->request('POST', '/api/example_resource_entities', [
            'json' => $exampleResourceEntityData + ['owner' => '/api/user_accounts/'.$otherUser->getId()],
        ]);

        $this->assertResponseStatusCodeSame(422, 'not passing the correct owner');

        $client->request('POST', '/api/example_resource_entities', [
            'json' => $exampleResourceEntityData + ['owner' => '/api/user_accounts/'.$authenticatedUser->getId()],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateExampleResourceEnityDoesntRequireOwnerAndSetsDefaultAsCurrent(): void
    {
        $client = self::createClient();

        $authenticatedUser = $this->createUserAccountAndLogIn($client, 'test@example.com', 'test12345');

        $client->request('POST', '/api/example_resource_entities', [
            'json' => [
                'title' => 'example title',
                'description' => 'description example ',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceItemJsonSchema(ExampleResourceEntity::class);
        $this->assertJsonContains(['owner' => '/api/user_accounts/'.$authenticatedUser->getId()]);
    }

    public function testGetExampleResourceEntitiesCollection(): void
    {
        $client = self::createClient();

        $user = $this->createUserAccount('test@example.com', 'test12345');
        $exampleResourceEntity1 = new ExampleResourceEntity();
        $exampleResourceEntity1->setTitle('test1');
        $exampleResourceEntity1->setDescription('description');
        $exampleResourceEntity1->setOwner($user);

        $exampleResourceEntity2 = new ExampleResourceEntity();
        $exampleResourceEntity2->setTitle('test2');
        $exampleResourceEntity2->setDescription('description');
        $exampleResourceEntity2->setOwner($user);
        $exampleResourceEntity2->setPublished(true);

        $exampleResourceEntity3 = new ExampleResourceEntity();
        $exampleResourceEntity3->setTitle('test3');
        $exampleResourceEntity3->setDescription('description');
        $exampleResourceEntity3->setOwner($user);
        $exampleResourceEntity3->setPublished(true);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($exampleResourceEntity1);
        $em->persist($exampleResourceEntity2);
        $em->persist($exampleResourceEntity3);
        $em->flush();

        $client->request('GET', '/api/example_resource_entities');

        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(ExampleResourceEntity::class);
        $this->assertJsonContains(['hydra:totalItems' => 2]);

        $this->createUserAccountAndLogIn($client, 'test@example.com', 'test12345', ['ROLE_ADMIN']);

        $client->request('GET', '/api/example_resource_entities');
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(ExampleResourceEntity::class);
        $this->assertJsonContains(['hydra:totalItems' => 3]);
    }

    public function testGetExampleResourceEntityItem(): void
    {
        $client = self::createClient();

        $user = $this->createUserAccount('test@example.com', 'test12345');
        $exampleResourceEntity1 = new ExampleResourceEntity();
        $exampleResourceEntity1->setTitle('test1');
        $exampleResourceEntity1->setDescription('description');
        $exampleResourceEntity1->setOwner($user);

        $exampleResourceEntity2 = new ExampleResourceEntity();
        $exampleResourceEntity2->setTitle('test2');
        $exampleResourceEntity2->setDescription('description');
        $exampleResourceEntity2->setOwner($user);
        $exampleResourceEntity2->setPublished(true);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($exampleResourceEntity1);
        $em->persist($exampleResourceEntity2);
        $em->flush();

        $client->request('GET', '/api/example_resource_entities/'.$exampleResourceEntity2->getId());

        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceItemJsonSchema(ExampleResourceEntity::class);

        $client->request('GET', '/api/example_resource_entities/'.$exampleResourceEntity1->getId());
        $this->assertResponseStatusCodeSame(404);

        $this->createUserAccountAndLogIn($client, 'test2@example.com', 'test12345', ['ROLE_ADMIN']);

        $client->request('GET', '/api/example_resource_entities/'.$exampleResourceEntity1->getId());
        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceItemJsonSchema(ExampleResourceEntity::class);
    }

    public function testUpdateWithPatchExampleResourceEntity(): void
    {
        $client = self::createClient();

        $user = $this->createUserAccountAndLogIn($client, 'test@example.com', 'test12345');

        $exampleResourceEntity = new ExampleResourceEntity();
        $exampleResourceEntity->setTitle('test');
        $exampleResourceEntity->setDescription('description');
        $exampleResourceEntity->setOwner($user);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($exampleResourceEntity);
        $em->flush();

        $client->request('PATCH', "/api/example_resource_entities/{$exampleResourceEntity->getId()}", [
            'json' => ['title' => 'updated'],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceItemJsonSchema(ExampleResourceEntity::class);
        $this->assertJsonContains([
            'title' => 'updated',
            'description' => 'description',
        ]);
    }

    public function testUpdateWithPatchExampleResourceEntityWhichUserDoNotOwn(): void
    {
        $client = self::createClient();

        $user = $this->createUserAccount('test@example.com', 'test12345');

        $exampleResourceEntity = new ExampleResourceEntity();
        $exampleResourceEntity->setTitle('test');
        $exampleResourceEntity->setDescription('description');
        $exampleResourceEntity->setOwner($user);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($exampleResourceEntity);
        $em->flush();

        $this->createUserAccountAndLogIn($client, 'test2@example.com', 'test12345', ['ROLE_ADMIN']);

        $client->request('PATCH', "/api/example_resource_entities/{$exampleResourceEntity->getId()}", [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'updated',
                'owner' => "/api/user_accounts/{$user->getId()}",
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceItemJsonSchema(ExampleResourceEntity::class);
        $this->assertJsonContains([
            'title' => 'updated',
            'owner' => "/api/user_accounts/{$user->getId()}",
        ]);
    }

    public function testUpdateWithPatchExampleResourceEntityWhichUserDoNotOwnButIsAdmin(): void
    {
        $client = self::createClient();

        $user = $this->createUserAccount('test@example.com', 'test12345');

        $exampleResourceEntity = new ExampleResourceEntity();
        $exampleResourceEntity->setTitle('test');
        $exampleResourceEntity->setDescription('description');
        $exampleResourceEntity->setOwner($user);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($exampleResourceEntity);
        $em->flush();

        $this->createUserAccountAndLogIn($client, 'test2@example.com', 'test12345');

        $client->request('PATCH', "/api/example_resource_entities/{$exampleResourceEntity->getId()}", [
            'json' => [
                'title' => 'updated',
                'owner' => "/api/user_accounts/{$user->getId()}",
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateWithPutExampleResourceEntity(): void
    {
        $client = self::createClient();

        $user = $this->createUserAccountAndLogIn($client, 'test@example.com', 'test12345');

        $exampleResourceEntity = new ExampleResourceEntity();
        $exampleResourceEntity->setTitle('test');
        $exampleResourceEntity->setDescription('description');
        $exampleResourceEntity->setOwner($user);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($exampleResourceEntity);
        $em->flush();

        $client->request('PUT', "/api/example_resource_entities/{$exampleResourceEntity->getId()}", [
            'json' => ['title' => 'updated'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceItemJsonSchema(ExampleResourceEntity::class);
        $this->assertJsonContains([
            'title' => 'updated',
            'description' => 'description',
        ]);
    }

    public function testUpdateWithPutExampleResourceEntityWhichUserDoNotOwn(): void
    {
        $client = self::createClient();

        $user = $this->createUserAccount('test@example.com', 'test12345');

        $exampleResourceEntity = new ExampleResourceEntity();
        $exampleResourceEntity->setTitle('test');
        $exampleResourceEntity->setDescription('description');
        $exampleResourceEntity->setOwner($user);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($exampleResourceEntity);
        $em->flush();

        $this->createUserAccountAndLogIn($client, 'test2@example.com', 'test12345');

        $client->request('PUT', "/api/example_resource_entities/{$exampleResourceEntity->getId()}", [
            'json' => [
                'title' => 'updated',
                'owner' => "/api/user_accounts/{$user->getId()}",
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
