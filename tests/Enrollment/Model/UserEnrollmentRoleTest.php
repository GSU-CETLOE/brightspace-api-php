<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Enrollment\Model;

use Brightspace\Api\Enrollment\Model\UserEnrollmentRole;
use PHPUnit\Framework\TestCase;

final class UserEnrollmentRoleTest extends TestCase
{
    public function testCreateMapsEveryField(): void
    {
        $role = UserEnrollmentRole::create(['Id' => 110, 'Code' => 'STU', 'Name' => 'Student']);

        self::assertSame(110, $role->Id);
        self::assertSame('STU', $role->Code);
        self::assertSame('Student', $role->Name);
    }


    public function testCodeAndNameAreOptional(): void
    {
        $role = UserEnrollmentRole::create(['Id' => 110]);

        self::assertSame(110, $role->Id);
        self::assertNull($role->Code);
        self::assertNull($role->Name);
    }
}
