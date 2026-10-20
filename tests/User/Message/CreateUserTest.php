<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\User\Message;

use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Message\CreateUser;
use Brightspace\Api\User\Model\User;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class CreateUserTest extends BrightspaceTestCase
{
    public function testPostsTheCreatePayloadAsJson(): void
    {
        $this->transport->append(self::jsonResponse(self::userData(99, 'new')));
        $newUser = new User(FirstName: 'New', LastName: 'Person', UserName: 'new', RoleId: 110);

        $created = $this->invoke(new CreateUser($newUser));

        $this->assertSentRequests([['POST', self::API_BASE . '/users/']]);
        self::assertSame('application/json', $this->sentRequest()->getHeaderLine('Content-Type'));
        self::assertSame($newUser->getCreatePayload(), self::jsonBodyOf($this->sentRequest()));
        self::assertSame(99, $created->UserId);
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(400));

        $this->expectException(ResponseException::class);

        $this->invoke(new CreateUser(new User()));
    }
}
