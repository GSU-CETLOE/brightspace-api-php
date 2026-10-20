<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\User\Message;

use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Message\DeleteUser;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class DeleteUserTest extends BrightspaceTestCase
{
    public function testDeletesTheUserById(): void
    {
        $this->transport->append(new Response(200));

        self::assertTrue($this->invoke(new DeleteUser(42)));

        $this->assertSentRequests([['DELETE', self::API_BASE . '/users/42']]);
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(404));

        $this->expectException(ResponseException::class);

        $this->invoke(new DeleteUser(42));
    }
}
