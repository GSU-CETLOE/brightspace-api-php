<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Message;

use Brightspace\Api\OrgUnit\Message\GetRootOrgUnit;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class GetRootOrgUnitTest extends BrightspaceTestCase
{
    public function testGetsTheOrganizationInfoThenItsPrimaryUrl(): void
    {
        $this->transport->append(self::jsonResponse([
            'Identifier' => '6606',
            'Name' => 'Example University',
            'TimeZone' => 'America/New_York',
        ]));
        $this->transport->append(new Response(200, [], '"https://lms.example.edu/"'));

        $root = $this->invoke(new GetRootOrgUnit());

        $this->assertSentRequests([
            ['GET', self::API_BASE . '/organization/info'],
            ['GET', self::API_BASE . '/organization/primary-url'],
        ]);
        self::assertSame('6606', $root->Identifier);
        self::assertSame('Example University', $root->Name);
        self::assertSame('"https://lms.example.edu/"', $root->PrimaryUrl);
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(500));

        $this->expectException(ResponseException::class);

        $this->invoke(new GetRootOrgUnit());
    }
}
