<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Enrollment\Message;

use Brightspace\Api\Enrollment\Message\GetEnrollment;
use Brightspace\Api\OrgUnit\Model\OrgUnit;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Model\User;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class GetEnrollmentTest extends BrightspaceTestCase
{
    /** @return array<string,mixed> */
    private static function enrollmentData(): array
    {
        return [
            'OrgUnitId' => 1234,
            'UserId' => 42,
            'Role' => ['Id' => 110, 'Code' => 'STU', 'Name' => 'Student'],
        ];
    }


    public function testFetchesTheOrgUnitAndUserWhenGivenIds(): void
    {
        $this->transport->append(self::jsonResponse(self::enrollmentData()));
        $this->transport->append(self::jsonResponse(self::orgUnitData(1234)));
        $this->transport->append(self::jsonResponse(self::userData(42)));

        $enrollment = $this->invoke(new GetEnrollment(1234, 42));

        $this->assertSentRequests([
            ['GET', self::API_BASE . '/enrollments/orgUnits/1234/users/42'],
            ['GET', self::API_BASE . '/orgstructure/1234'],
            ['GET', self::API_BASE . '/users/42'],
        ]);
        self::assertNotNull($enrollment);
        self::assertNotNull($enrollment->OrgUnit);
        self::assertSame(1234, $enrollment->OrgUnit->Identifier);
        self::assertNotNull($enrollment->User);
        self::assertSame(42, $enrollment->User->UserId);
        self::assertNotNull($enrollment->Role);
        self::assertSame(110, $enrollment->Role->Id);
    }


    public function testUsesTheGivenOrgUnitAndUserInsteadOfFetchingThem(): void
    {
        $this->transport->append(self::jsonResponse(self::enrollmentData()));
        $orgUnit = OrgUnit::create(self::orgUnitData(1234));
        $user = User::create(self::userData(42));

        $enrollment = $this->invoke(new GetEnrollment($orgUnit, $user));

        $this->assertSentRequests([['GET', self::API_BASE . '/enrollments/orgUnits/1234/users/42']]);
        self::assertNotNull($enrollment);
        self::assertNotNull($enrollment->OrgUnit);
        self::assertSame(1234, $enrollment->OrgUnit->Identifier);
        self::assertSame('Intro to Testing', $enrollment->OrgUnit->Name);
        self::assertNotNull($enrollment->User);
        self::assertSame(42, $enrollment->User->UserId);
        self::assertSame('jdoe', $enrollment->User->UserName);
    }


    public function testNoEnrollmentIsNull(): void
    {
        $this->transport->append(new Response(404));

        self::assertNull($this->invoke(new GetEnrollment(1234, 42)));
        self::assertCount(1, $this->sentRequests);
    }


    public function testAnyOtherStatusIsAResponseError(): void
    {
        $this->transport->append(new Response(500));

        $this->expectException(ResponseException::class);

        $this->invoke(new GetEnrollment(1234, 42));
    }
}
