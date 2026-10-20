<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\User\Message;

use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Message\UpdateUser;
use Brightspace\Api\User\Model\User;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class UpdateUserTest extends BrightspaceTestCase
{
    public function testPutsTheUpdatePayloadToTheUsersId(): void
    {
        $this->transport->append(self::jsonResponse(self::userData(42)));
        $user = User::create(self::userData(42));
        $user->FirstName = 'Janet';

        $updated = $this->invoke(new UpdateUser($user));

        $this->assertSentRequests([['PUT', self::API_BASE . '/users/42']]);
        $body = self::jsonBodyOf($this->sentRequest());
        self::assertIsArray($body);
        self::assertSame('Janet', $body['FirstName']);
        self::assertSame(['IsActive' => true], $body['Activation']);
        self::assertArrayNotHasKey('RoleId', $body);
        self::assertSame(42, $updated->UserId);
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(404));

        $this->expectException(ResponseException::class);

        $this->invoke(new UpdateUser(new User(UserId: 42)));
    }
}
