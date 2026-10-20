<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Message;

use Brightspace\Api\OrgUnit\Message\ListOrgUnitAncestors;
use Brightspace\Api\OrgUnit\Model\OrgUnit;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use GuzzleHttp\Psr7\Response;

final class ListOrgUnitAncestorsTest extends BrightspaceTestCase
{
    public function testListsTheAncestorsKeyedByIdentifier(): void
    {
        $this->transport->append(self::jsonResponse([self::orgUnitData(10), self::orgUnitData(20)]));

        $ancestors = self::collect($this->invoke(new ListOrgUnitAncestors(1234)));

        $this->assertSentRequests([['GET', self::API_BASE . '/orgstructure/1234/ancestors/']]);
        self::assertSame([10, 20], array_keys($ancestors));
        self::assertContainsOnlyInstancesOf(OrgUnit::class, $ancestors);
    }


    public function testCanAskForOnlyTheParentsOfAGivenType(): void
    {
        $this->transport->append(self::jsonResponse([]));

        self::collect($this->invoke(new ListOrgUnitAncestors(1234, 5, true)));

        self::assertSame('/d2l/api/lp/1.46/orgstructure/1234/parents/', $this->sentRequest()->getUri()->getPath());
        self::assertSame(['ouTypeId' => '5'], self::queryOf($this->sentRequest()));
    }


    public function testAnythingButOkFailsWhenTheListIsRead(): void
    {
        $this->transport->append(new Response(404));

        $this->expectException(\RuntimeException::class);

        self::collect($this->invoke(new ListOrgUnitAncestors(1234)));
    }
}
