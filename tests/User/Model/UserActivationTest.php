<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\User\Model;

use Brightspace\Api\User\Model\UserActivation;
use PHPUnit\Framework\TestCase;

final class UserActivationTest extends TestCase
{
    public function testCreateReadsIsActive(): void
    {
        self::assertTrue(UserActivation::create(['IsActive' => true])->IsActive);
        self::assertFalse(UserActivation::create(['IsActive' => false])->IsActive);
    }


    public function testSerializesAsBrightspaceExpects(): void
    {
        self::assertSame('{"IsActive":false}', json_encode(new UserActivation(false)));
    }
}
