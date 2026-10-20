<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\User\Model;

use Brightspace\Api\User\Model\WhoAmI;
use PHPUnit\Framework\TestCase;

final class WhoAmITest extends TestCase
{
    public function testCreateMapsEveryField(): void
    {
        $me = WhoAmI::create([
            'Identifier' => '42',
            'FirstName' => 'Jane',
            'LastName' => 'Doe',
            'UniqueName' => 'jdoe',
            'ProfileIdentifier' => 'p42',
            'Pronouns' => 'she/her',
        ]);

        self::assertSame('42', $me->Identifier);
        self::assertSame('Jane', $me->FirstName);
        self::assertSame('Doe', $me->LastName);
        self::assertSame('jdoe', $me->UniqueName);
        self::assertSame('p42', $me->ProfileIdentifier);
        self::assertSame('she/her', $me->Pronouns);
    }
}
