<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Enrollment\Model;

use Brightspace\Api\Enrollment\Model\UserEnrollment;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;

final class UserEnrollmentTest extends BrightspaceTestCase
{
    public function testCreateMapsTheNestedOrgUnitUserAndRole(): void
    {
        $enrollment = UserEnrollment::create([
            'OrgUnit' => self::orgUnitData(1234),
            'User' => self::userData(42),
            'Role' => ['Id' => 110, 'Code' => 'STU', 'Name' => 'Student'],
        ]);

        self::assertNotNull($enrollment->OrgUnit);
        self::assertSame(1234, $enrollment->OrgUnit->Identifier);
        self::assertNotNull($enrollment->User);
        self::assertSame(42, $enrollment->User->UserId);
        self::assertNotNull($enrollment->Role);
        self::assertSame(110, $enrollment->Role->Id);
        self::assertSame('Student', $enrollment->Role->Name);
    }


    public function testCreateLeavesMissingPartsNull(): void
    {
        $enrollment = UserEnrollment::create(['Role' => ['Id' => 110]]);

        self::assertNull($enrollment->OrgUnit);
        self::assertNull($enrollment->User);
        self::assertNotNull($enrollment->Role);
    }


    public function testDefaultsToAnEmptyEnrollment(): void
    {
        $enrollment = new UserEnrollment();

        self::assertNull($enrollment->OrgUnit);
        self::assertNull($enrollment->User);
        self::assertNull($enrollment->Role);
    }
}
