<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Model;

use Brightspace\Api\OrgUnit\Model\OrgUnit;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;

final class OrgUnitTest extends BrightspaceTestCase
{
    public function testCreateMapsEveryField(): void
    {
        $orgUnit = OrgUnit::create(self::orgUnitData(1234, 'Intro to Testing'));

        self::assertSame(1234, $orgUnit->Identifier);
        self::assertSame('Intro to Testing', $orgUnit->Name);
        self::assertSame('TEST-1234', $orgUnit->Code);
        self::assertSame('/content/enforced/1234/', $orgUnit->Path);
        self::assertSame(3, $orgUnit->Type->Id);
        self::assertSame('Course Offering', $orgUnit->Type->Name);
    }


    public function testCreateFallsBackToIdWhenThereIsNoIdentifier(): void
    {
        $data = self::orgUnitData(1234);
        unset($data['Identifier']);
        $data['Id'] = 1234;

        self::assertSame(1234, OrgUnit::create($data)->Identifier);
    }


    public function testCreateLeavesCodeAndPathNullWhenMissing(): void
    {
        $orgUnit = OrgUnit::create([
            'Identifier' => 1,
            'Name' => 'Root',
            'Code' => null,
            'Path' => null,
            'Type' => ['Id' => 1, 'Code' => 'Org', 'Name' => 'Organization'],
        ]);

        self::assertNull($orgUnit->Code);
        self::assertNull($orgUnit->Path);
    }


    public function testCreateAcceptsAnOrgUnitObject(): void
    {
        $original = OrgUnit::create(self::orgUnitData(1234));

        $copy = OrgUnit::create($original);

        self::assertNotSame($original, $copy);
        self::assertSame(1234, $copy->Identifier);
        self::assertSame(3, $copy->Type->Id);
    }


    public function testDefaultsToAnEmptyOrgUnit(): void
    {
        $orgUnit = new OrgUnit();

        self::assertSame(0, $orgUnit->Identifier);
        self::assertSame('', $orgUnit->Name);
        self::assertSame(0, $orgUnit->Type->Id);
    }
}
