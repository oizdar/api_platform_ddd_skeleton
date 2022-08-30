<?php

namespace App\Tests\Functional;

use App\Entity\UserAccount;
use App\Tests\CustomApiTestCase;
use Doctrine\ORM\EntityManager;
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
        $this->assertJsonContains([
            '@type' => 'user_account',
            'email' => 'test@example.com',
            'username' => 'test',
        ]);
        $this->login($client, 'test@example.com', 'test12345');
    }

    public function testCreateUserInvalidData(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/user_accounts', [
            'json' => [
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'username',
                    'message' => 'This value should not be blank.',
                ],
                [
                    'propertyPath' => 'email',
                    'message' => 'This value should not be blank.',
                ],
                [
                    'propertyPath' => 'password',
                    'message' => 'This value should not be blank.',
                ],
            ],
        ]);

        $client->request('POST', '/api/user_accounts', [
            'json' => [
                'email' => 'invalidemail',
                'username' => 'test',
                'password' => '1234',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'email',
                    'message' => 'This value is not a valid email address.',
                ],
            ],
        ]);
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

    public function testPhoneNumberField(): void
    {
        $client = self::createClient();
        $user = $this->createUserAccount('userphonetest@ex.com', 'test12345');
        $this->createUserAccountAndLogIn($client, 'differentuser@example.com', 'test21235');

        $user->setPhoneNumber('999-999-999');
        /** @var EntityManager $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->flush();

        $client->request('GET', '/api/user_accounts/'.$user->getId());
        $this->assertJsonContains([
            'username' => 'userphonetest',
        ]);

        $data = $client->getResponse()?->toArray() ?: [];
        $this->assertArrayNotHasKey('phoneNumber', $data);

        /** @var UserAccount $user */
        $user = $em->getRepository(UserAccount::class)->find($user->getId());
        $user->setRoles(['ROLE_ADMIN']);
        $em->flush();

        $this->login($client, 'userphonetest@ex.com', 'test12345');

        $client->request('GET', '/api/user_accounts/'.$user->getId());
        $this->assertJsonContains([
            'username' => 'userphonetest',
            'phoneNumber' => '999-999-999',
        ]);
    }

    public function testPhoneNumberFieldUserCanSeeOnlyOwnPhoneNumber(): void
    {
        $client = self::createClient();
        $user = $this->createUserAccount('userphonetest@ex.com', 'test12345');
        $user->setPhoneNumber('999-999-999');
        $user2 = $this->createUserAccountAndLogIn($client, 'userphonetest2@ex.com', 'test12345');
        $user2->setPhoneNumber('111-222-333');

        /** @var EntityManager $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->flush();

        $client->request('GET', '/api/user_accounts/'.$user->getId());
        $this->assertJsonContains([
            'username' => 'userphonetest',
        ]);

        $data = $client->getResponse()?->toArray() ?: [];
        $this->assertArrayNotHasKey('phoneNumber', $data);

        $client->request('GET', '/api/user_accounts/'.$user2->getId());
        $this->assertJsonContains([
            'username' => 'userphonetest2',
            'phoneNumber' => '111-222-333',
        ]);
    }

    public function testOnlyAdminCanUpdateRole(): void
    {
        $client = self::createClient();
        $user = $this->createUserAccountAndLogIn($client, 'usertest@ex.com', 'test12345');

        /** @var EntityManager $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->flush();

        $client->request('GET', '/api/user_accounts/'.$user->getId());
        $this->assertJsonContains([
            'username' => 'usertest',
            'roles' => ['ROLE_USER'],
        ]);

        $data = $client->getResponse()?->toArray() ?: [];
        $this->assertArrayNotHasKey('role', $data);

        $client->request('PATCH', "/api/user_accounts/{$user->getId()}", [
            'json' => ['roles' => ['ROLE_ADMIN']],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        // not available for  basic user then does not update role
        $client->request('GET', '/api/user_accounts/'.$user->getId());
        $this->assertJsonContains([
            'username' => 'usertest',
            'roles' => ['ROLE_USER'],
        ]);

        /** @var UserAccount $user */
        $user = $em->getRepository(UserAccount::class)->find($user->getId());
        $user->setRoles(['ROLE_ADMIN']);
        $em->flush();

        $this->login($client, 'usertest@ex.com', 'test12345');
        // test proper role is returned
        $client->request('GET', '/api/user_accounts/'.$user->getId());
        $this->assertJsonContains([
            'username' => 'usertest',
            'roles' => ['ROLE_ADMIN'],
        ]);

        // create next user accout to test
        $user2 = $this->createUserAccount('user2@test.pl', 'test2');
        $client->request('GET', '/api/user_accounts/'.$user2->getId());
        $this->assertJsonContains([
            'username' => 'user2',
            'roles' => ['ROLE_USER'],
        ]);

        $this->login($client, 'usertest@ex.com', 'test12345');
        $client->request('PATCH', "/api/user_accounts/{$user2->getId()}", [
            'json' => ['roles' => ['ROLE_ADMIN']],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->login($client, 'usertest@ex.com', 'test12345');
        $client->request('GET', '/api/user_accounts/'.$user2->getId());
        $this->assertJsonContains([
            'username' => 'user2',
            'roles' => ['ROLE_ADMIN'],
        ]);
    }
}
