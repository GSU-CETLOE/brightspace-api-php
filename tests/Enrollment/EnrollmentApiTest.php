<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Enrollment;

use Brightspace\Api\Enrollment\EnrollmentApi;
use Brightspace\Api\OrgUnit\Model\OrgUnit;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Model\User;
use GuzzleHttp\Psr7\Response;

final class EnrollmentApiTest extends BrightspaceTestCase
{
    private EnrollmentApi $api;


    protected function setUp(): void
    {
        parent::setUp();
        $this->api = $this->createApi(EnrollmentApi::class);
    }


    public function testListOrgUnitsPassesTheFiltersThrough(): void
    {
        $this->transport->append(self::pagedResponse([['OrgUnit' => self::orgUnitData(10), 'Role' => ['Id' => 110]]]));

        $enrollments = self::collect($this->api->listOrgUnits(User::create(self::userData(42)), [3, 4], 110));

        self::assertSame([10], array_keys($enrollments));
        self::assertSame('/d2l/api/lp/1.46/enrollments/users/42/orgUnits/', $this->sentRequest()->getUri()->getPath());
        self::assertSame(['orgUnitType' => '3,4', 'roleId' => '110'], self::queryOf($this->sentRequest()));
    }


    public function testListUsersPassesTheFiltersThrough(): void
    {
        $this->transport->append(self::pagedResponse([['User' => self::userData(42), 'Role' => ['Id' => 110]]]));

        $enrollments = self::collect($this->api->listUsers(OrgUnit::create(self::orgUnitData(1234)), 110, false));

        self::assertSame([42], array_keys($enrollments));
        self::assertSame(
            '/d2l/api/lp/1.46/enrollments/orgUnits/1234/users/',
            $this->sentRequest()->getUri()->getPath()
        );
        self::assertSame(['roleId' => '110', 'isActive' => '0'], self::queryOf($this->sentRequest()));
    }


    public function testGetReadsOneEnrollment(): void
    {
        $this->transport->append(self::jsonResponse(['OrgUnitId' => 1234, 'UserId' => 42, 'Role' => ['Id' => 110]]));

        $enrollment = $this->api->get(OrgUnit::create(self::orgUnitData(1234)), User::create(self::userData(42)));

        self::assertNotNull($enrollment);
        self::assertNotNull($enrollment->Role);
        self::assertSame(110, $enrollment->Role->Id);
        $this->assertSentRequests([['GET', self::API_BASE . '/enrollments/orgUnits/1234/users/42']]);
    }


    public function testEnrollUnenrollPinAndUnpinDelegate(): void
    {
        foreach (range(1, 4) as $_) {
            $this->transport->append(new Response(200));
        }

        self::assertTrue($this->api->enroll(1234, 42, 110));
        self::assertTrue($this->api->unenroll(1234, 42));
        self::assertTrue($this->api->pin(1234, 42));
        self::assertTrue($this->api->unpin(1234, 42));

        $this->assertSentRequests([
            ['POST', self::API_BASE . '/enrollments/'],
            ['DELETE', self::API_BASE . '/enrollments/orgUnits/1234/users/42/'],
            ['POST', self::API_BASE . '/enrollments/orgUnits/1234/users/42/pin'],
            ['DELETE', self::API_BASE . '/enrollments/orgUnits/1234/users/42/pin'],
        ]);
    }
}
