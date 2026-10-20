<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\User\Message;

use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Message\GetWhoAmI;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class GetWhoAmITest extends BrightspaceTestCase
{
    public function testGetsTheWhoAmIEndpointWithTheBearerToken(): void
    {
        $this->transport->append(self::jsonResponse([
            'Identifier' => '42',
            'FirstName' => 'Jane',
            'LastName' => 'Doe',
            'UniqueName' => 'jdoe',
            'ProfileIdentifier' => 'p42',
            'Pronouns' => '',
        ]));

        $me = $this->invoke(new GetWhoAmI());

        $this->assertSentRequests([['GET', self::API_BASE . '/users/whoami']]);
        self::assertSame('jdoe', $me->UniqueName);
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(403, [], 'forbidden'));

        $this->expectException(ResponseException::class);

        $this->invoke(new GetWhoAmI());
    }
}
