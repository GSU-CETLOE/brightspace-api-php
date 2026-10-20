<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Message;

use Brightspace\Api\OrgUnit\Message\ListOrgUnitDescendants;
use Brightspace\Api\OrgUnit\Model\OrgUnit;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use GuzzleHttp\Psr7\Response;

final class ListOrgUnitDescendantsTest extends BrightspaceTestCase
{
    public function testWalksEveryPageOfDescendants(): void
    {
        $this->transport->append(self::pagedResponse([self::orgUnitData(10)], 'page-2'));
        $this->transport->append(self::pagedResponse([self::orgUnitData(20)]));

        $descendants = self::collect($this->invoke(new ListOrgUnitDescendants(1234)));

        self::assertSame([10, 20], array_keys($descendants));
        self::assertContainsOnlyInstancesOf(OrgUnit::class, $descendants);
        self::assertCount(2, $this->sentRequests);
        self::assertSame(
            self::API_BASE . '/orgstructure/1234/descendants/paged/',
            (string) $this->sentRequest(0)->getUri()
        );
        self::assertSame(['bookmark' => 'page-2'], self::queryOf($this->sentRequest(1)));
    }


    public function testCanAskForOnlyTheChildrenOfAGivenTypeFromABookmark(): void
    {
        $this->transport->append(self::pagedResponse([]));

        self::collect($this->invoke(new ListOrgUnitDescendants(1234, 3, 'mark', true)));

        self::assertSame(
            '/d2l/api/lp/1.46/orgstructure/1234/children/paged/',
            $this->sentRequest()->getUri()->getPath()
        );
        self::assertSame(['ouTypeId' => '3', 'bookmark' => 'mark'], self::queryOf($this->sentRequest()));
    }


    public function testAnythingButOkFailsWhenTheListIsRead(): void
    {
        $this->transport->append(new Response(404));

        $this->expectException(\RuntimeException::class);

        self::collect($this->invoke(new ListOrgUnitDescendants(1234)));
    }
}
