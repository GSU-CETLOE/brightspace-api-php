<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\User\Message;

use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Message\GetUser;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class GetUserTest extends BrightspaceTestCase
{
    public function testGetsTheUserById(): void
    {
        $this->transport->append(self::jsonResponse(self::userData(42)));

        $user = $this->invoke(new GetUser(42));

        $this->assertSentRequests([['GET', self::API_BASE . '/users/42']]);
        self::assertNotNull($user);
        self::assertSame(42, $user->UserId);
    }


    public function testAnUnknownUserIsNull(): void
    {
        $this->transport->append(new Response(404));

        self::assertNull($this->invoke(new GetUser(42)));
    }


    public function testAnyOtherStatusIsAResponseError(): void
    {
        $this->transport->append(new Response(500));

        $this->expectException(ResponseException::class);

        $this->invoke(new GetUser(42));
    }
}
