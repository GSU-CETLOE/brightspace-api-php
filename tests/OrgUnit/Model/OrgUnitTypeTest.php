<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Model;

use Brightspace\Api\OrgUnit\Model\OrgUnitType;
use PHPUnit\Framework\TestCase;

final class OrgUnitTypeTest extends TestCase
{
    public function testCreateMapsEveryField(): void
    {
        $type = OrgUnitType::create(['Id' => 3, 'Code' => 'Course Offering', 'Name' => 'Course Offering']);

        self::assertSame(3, $type->Id);
        self::assertSame('Course Offering', $type->Code);
        self::assertSame('Course Offering', $type->Name);
    }


    public function testDefaultsToAnEmptyType(): void
    {
        $type = new OrgUnitType();

        self::assertSame(0, $type->Id);
        self::assertSame('', $type->Code);
        self::assertSame('', $type->Name);
    }
}
