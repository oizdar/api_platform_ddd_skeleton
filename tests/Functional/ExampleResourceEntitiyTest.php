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
    public function testGetExampleResourceEntitiesNotAuthenticatedThrowsError(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/example_resource_entities');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetExampleResourceEntities(): void
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

        $client->request('GET', '/api/example_resource_entities');

        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(ExampleResourceEntity::class);
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
            'json' => ['op' => 'replace', 'path' => 'title', 'value' => 'updated'],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceItemJsonSchema(ExampleResourceEntity::class);
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

        $this->createUserAccountAndLogIn($client, 'test2@example.com', 'test12345');

        $client->request('PATCH', "/api/example_resource_entities/{$exampleResourceEntity->getId()}", [
            'json' => [
                ['op' => 'replace', 'path' => 'title', 'value' => 'updated'],
                ['op' => 'replace', 'path' => 'owner', 'value' => "/api/user_accounts/{$user->getId()}"],
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
