<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Message;

use Brightspace\Api\OrgUnit\Message\ListOrgUnits;
use Brightspace\Api\OrgUnit\Model\OrgUnit;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use GuzzleHttp\Psr7\Response;

final class ListOrgUnitsTest extends BrightspaceTestCase
{
    public function testWalksEveryPageAndKeysOrgUnitsByIdentifier(): void
    {
        $this->transport->append(self::pagedResponse([self::orgUnitData(1), self::orgUnitData(2)], 'page-2'));
        $this->transport->append(self::pagedResponse([self::orgUnitData(3)]));

        $orgUnits = self::collect($this->invoke(new ListOrgUnits()));

        self::assertSame([1, 2, 3], array_keys($orgUnits));
        self::assertContainsOnlyInstancesOf(OrgUnit::class, $orgUnits);
        self::assertCount(2, $this->sentRequests);
        self::assertSame(self::API_BASE . '/orgstructure/', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['bookmark' => 'page-2'], self::queryOf($this->sentRequest(1)));
    }


    public function testSendsEveryFilter(): void
    {
        $this->transport->append(self::pagedResponse([]));

        self::collect($this->invoke(new ListOrgUnits(3, 'TEST', 'Intro', 'mark')));

        self::assertSame([
            'orgUnitType' => '3',
            'orgUnitCode' => 'TEST',
            'orgUnitName' => 'Intro',
            'bookmark' => 'mark',
        ], self::queryOf($this->sentRequest()));
    }


    public function testAnExactCodeOrNameReplacesTheLooseOne(): void
    {
        $this->transport->append(self::pagedResponse([]));

        self::collect($this->invoke(new ListOrgUnits(
            orgUnitCode: 'TEST',
            orgUnitName: 'Intro',
            exactOrgUnitCode: 'TEST-1',
            exactOrgUnitName: 'Intro to Testing'
        )));

        self::assertSame([
            'exactOrgUnitCode' => 'TEST-1',
            'exactOrgUnitName' => 'Intro to Testing',
        ], self::queryOf($this->sentRequest()));
    }


    public function testAnythingButOkFailsWhenTheListIsRead(): void
    {
        $this->transport->append(new Response(500));

        $this->expectException(\RuntimeException::class);

        self::collect($this->invoke(new ListOrgUnits()));
    }
}
