<?php

namespace App\Tests\Functional;

use App\Entity\UserAccount;
use App\Tests\CustomApiTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class UserAccountTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateUser(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/user_accounts', [
            'json' => [
                'email' => 'test@example.com',
                'username' => 'test',
                'password' => 'test12345',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceItemJsonSchema(UserAccount::class);

        $this->login($client, 'test@example.com', 'test12345');
    }

    public function testGetUserAccountsCollectionNotAuthenticatedThrowsError(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/user_accounts');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUserAccountsCollection(): void
    {
        $client = self::createClient();
        $this->createUserAccount('test@example.com', 'test');
        $this->createUserAccountAndLogIn($client, 'test2@example.com', 'test');
        $client->request('GET', '/api/user_accounts');

        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceCollectionJsonSchema(UserAccount::class);
    }

    public function testUpdateWithPatchUserAccount(): void
    {
        $client = self::createClient();

        $user = $this->createUserAccountAndLogIn($client, 'test@example.com', 'test12345');

        $client->request('PATCH', "/api/user_accounts/{$user->getId()}", [
            'json' => ['username' => 'updated'],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesResourceItemJsonSchema(UserAccount::class);

        $this->assertJsonContains(['username' => 'updated']);
    }

    public function testUpdateWithPatchUserAccountOfOtherUserIsImpossible(): void
    {
        $client = self::createClient();

        $user = $this->createUserAccount('test@example.com', 'test12345');
        $this->createUserAccountAndLogIn($client, 'logged@example.com', 'test12345');

        $client->request('PATCH', "/api/user_accounts/{$user->getId()}", [
             'json' => ['username' => 'updated'],
             'headers' => ['Content-Type' => 'application/merge-patch+json'],
         ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteUserAccountUnavailableForRoleUser(): void
    {
        $client = self::createClient();

        $user = $this->createUserAccount('test@example.com', 'test12345');
        $loggedInUser = $this->createUserAccountAndLogIn($client, 'logged@example.com', 'test12345');

        // not self
        $client->request('DELETE', "/api/user_accounts/{$loggedInUser->getId()}");
        $this->assertResponseStatusCodeSame(403);

        // not anybody
        $client->request('DELETE', "/api/user_accounts/{$user->getId()}");
        $this->assertResponseStatusCodeSame(403);
    }
}
