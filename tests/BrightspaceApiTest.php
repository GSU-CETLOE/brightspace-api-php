<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests;

use Brightspace\Api\BrightspaceApi;
use Brightspace\Api\Enrollment\EnrollmentApi;
use Brightspace\Api\OrgUnit\OrgUnitApi;
use Brightspace\Api\Outcome\OutcomeApi;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\UserApi;

final class BrightspaceApiTest extends BrightspaceTestCase
{
    public function testExposesTheFourApiClientsItWasBuiltWith(): void
    {
        $enrollment = $this->createApi(EnrollmentApi::class);
        $orgUnit = $this->createApi(OrgUnitApi::class);
        $outcome = $this->createApi(OutcomeApi::class);
        $user = $this->createApi(UserApi::class);

        $api = new BrightspaceApi($enrollment, $orgUnit, $outcome, $user);

        self::assertSame($enrollment, $api->enrollment);
        self::assertSame($orgUnit, $api->orgUnit);
        self::assertSame($outcome, $api->outcome);
        self::assertSame($user, $api->user);
    }
}
