<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Message;

use Brightspace\Api\OrgUnit\Message\ListOrgUnitTypes;
use Brightspace\Api\OrgUnit\Model\OrgUnitType;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use GuzzleHttp\Psr7\Response;

final class ListOrgUnitTypesTest extends BrightspaceTestCase
{
    public function testListsTheTypesKeyedById(): void
    {
        $this->transport->append(self::jsonResponse([
            ['Id' => 1, 'Code' => 'Organization', 'Name' => 'Organization'],
            ['Id' => 3, 'Code' => 'Course Offering', 'Name' => 'Course Offering'],
        ]));

        $types = self::collect($this->invoke(new ListOrgUnitTypes()));

        $this->assertSentRequests([['GET', self::API_BASE . '/outypes/']]);
        self::assertSame([1, 3], array_keys($types));
        self::assertContainsOnlyInstancesOf(OrgUnitType::class, $types);
        self::assertSame('Course Offering', $types[3]->Name);
    }


    public function testAnythingButOkFailsWhenTheListIsRead(): void
    {
        $this->transport->append(new Response(500));

        $this->expectException(\RuntimeException::class);

        self::collect($this->invoke(new ListOrgUnitTypes()));
    }
}
