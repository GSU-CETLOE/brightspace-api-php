<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Message;

use Brightspace\Api\OrgUnit\Message\GetOrgUnit;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class GetOrgUnitTest extends BrightspaceTestCase
{
    public function testGetsTheOrgUnitById(): void
    {
        $this->transport->append(self::jsonResponse(self::orgUnitData(1234)));

        $orgUnit = $this->invoke(new GetOrgUnit(1234));

        $this->assertSentRequests([['GET', self::API_BASE . '/orgstructure/1234']]);
        self::assertNotNull($orgUnit);
        self::assertSame(1234, $orgUnit->Identifier);
    }


    public function testAnUnknownOrgUnitIsNull(): void
    {
        $this->transport->append(new Response(404));

        self::assertNull($this->invoke(new GetOrgUnit(1234)));
    }


    public function testAnyOtherStatusIsAResponseError(): void
    {
        $this->transport->append(new Response(500));

        $this->expectException(ResponseException::class);

        $this->invoke(new GetOrgUnit(1234));
    }
}
