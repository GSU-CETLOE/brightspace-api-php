<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Message;

use Brightspace\Api\OrgUnit\Message\GetOrgUnitType;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class GetOrgUnitTypeTest extends BrightspaceTestCase
{
    public function testGetsTheTypeById(): void
    {
        $this->transport->append(self::jsonResponse([
            'Id' => 3,
            'Code' => 'Course Offering',
            'Name' => 'Course Offering',
        ]));

        $type = $this->invoke(new GetOrgUnitType(3));

        $this->assertSentRequests([['GET', self::API_BASE . '/outypes/3']]);
        self::assertNotNull($type);
        self::assertSame(3, $type->Id);
    }


    public function testAnUnknownTypeIsNull(): void
    {
        $this->transport->append(new Response(404));

        self::assertNull($this->invoke(new GetOrgUnitType(3)));
    }


    public function testAnyOtherStatusIsAResponseError(): void
    {
        $this->transport->append(new Response(500));

        $this->expectException(ResponseException::class);

        $this->invoke(new GetOrgUnitType(3));
    }
}
