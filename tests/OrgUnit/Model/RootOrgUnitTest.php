<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Model;

use Brightspace\Api\OrgUnit\Model\RootOrgUnit;
use PHPUnit\Framework\TestCase;

final class RootOrgUnitTest extends TestCase
{
    public function testCreateMapsEveryField(): void
    {
        $root = RootOrgUnit::create([
            'Identifier' => '6606',
            'Name' => 'Example University',
            'TimeZone' => 'America/New_York',
            'PrimaryUrl' => 'https://lms.example.edu/',
        ]);

        self::assertSame('6606', $root->Identifier);
        self::assertSame('Example University', $root->Name);
        self::assertSame('America/New_York', $root->TimeZone);
        self::assertSame('https://lms.example.edu/', $root->PrimaryUrl);
    }
}
