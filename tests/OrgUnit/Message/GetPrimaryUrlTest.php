<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Message;

use Brightspace\Api\OrgUnit\Message\GetPrimaryUrl;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class GetPrimaryUrlTest extends BrightspaceTestCase
{
    public function testGetsThePrimaryUrlEndpointAndReturnsTheRawBody(): void
    {
        $this->transport->append(new Response(200, [], '"https://lms.example.edu/"'));

        $url = $this->invoke(new GetPrimaryUrl());

        $this->assertSentRequests([['GET', self::API_BASE . '/organization/primary-url']]);
        self::assertSame('"https://lms.example.edu/"', $url);
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(500));

        $this->expectException(ResponseException::class);

        $this->invoke(new GetPrimaryUrl());
    }
}
