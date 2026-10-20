<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\User;

use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Model\User;
use Brightspace\Api\User\UserApi;
use GuzzleHttp\Psr7\Response;

final class UserApiTest extends BrightspaceTestCase
{
    private UserApi $api;


    protected function setUp(): void
    {
        parent::setUp();
        $this->api = $this->createApi(UserApi::class);
    }


    public function testWhoAmI(): void
    {
        $this->transport->append(self::jsonResponse([
            'Identifier' => '42',
            'FirstName' => 'Jane',
            'LastName' => 'Doe',
            'UniqueName' => 'jdoe',
            'ProfileIdentifier' => 'p42',
            'Pronouns' => '',
        ]));

        self::assertSame('42', $this->api->whoAmI()->Identifier);
        $this->assertSentRequests([['GET', self::API_BASE . '/users/whoami']]);
    }


    public function testListPassesTheFiltersThrough(): void
    {
        $this->transport->append(self::jsonResponse([self::userData(7)]));

        $users = self::collect($this->api->list(externalEmail: 'jdoe@example.edu'));

        self::assertSame([7], array_keys($users));
        self::assertSame(['externalEmail' => 'jdoe@example.edu'], self::queryOf($this->sentRequest()));
    }


    public function testGetByIdFetchesTheUser(): void
    {
        $this->transport->append(self::jsonResponse(self::userData(42)));

        $user = $this->api->get(42);

        self::assertNotNull($user);
        self::assertSame(42, $user->UserId);
        $this->assertSentRequests([['GET', self::API_BASE . '/users/42']]);
    }


    public function testGetByIdIsNullForAnUnknownUser(): void
    {
        $this->transport->append(new Response(404));

        self::assertNull($this->api->get(42));
    }


    public function testGetByUserNameLooksTheNameUp(): void
    {
        $this->transport->append(self::jsonResponse(self::userData(42, 'jdoe')));

        $user = $this->api->get('jdoe');

        self::assertNotNull($user);
        self::assertSame(42, $user->UserId);
        self::assertSame(['userName' => 'jdoe'], self::queryOf($this->sentRequest()));
    }


    public function testGetByUserNameIsNullWhenTheLookupHasNoBody(): void
    {
        $this->transport->append(new Response(200, [], 'null'));

        self::assertNull($this->api->get('nobody'));
    }


    public function testCreateUpdateAndDeleteDelegate(): void
    {
        $this->transport->append(self::jsonResponse(self::userData(99)));
        $this->transport->append(self::jsonResponse(self::userData(99)));
        $this->transport->append(new Response(200));

        self::assertSame(99, $this->api->create(new User(UserName: 'new'))->UserId);
        self::assertSame(99, $this->api->update(User::create(self::userData(99)))->UserId);
        self::assertTrue($this->api->delete(99));

        $this->assertSentRequests([
            ['POST', self::API_BASE . '/users/'],
            ['PUT', self::API_BASE . '/users/99'],
            ['DELETE', self::API_BASE . '/users/99'],
        ]);
    }
}
