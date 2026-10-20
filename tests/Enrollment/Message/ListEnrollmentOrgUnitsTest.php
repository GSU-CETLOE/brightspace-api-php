<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Enrollment\Message;

use Brightspace\Api\Enrollment\Message\ListEnrollmentOrgUnits;
use Brightspace\Api\Enrollment\Model\UserEnrollment;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Model\User;
use GuzzleHttp\Psr7\Response;

final class ListEnrollmentOrgUnitsTest extends BrightspaceTestCase
{
    /**
     * @param int $orgUnitId
     * @return array<string,mixed>
     */
    private static function item(int $orgUnitId): array
    {
        return [
            'OrgUnit' => self::orgUnitData($orgUnitId),
            'Role' => ['Id' => 110, 'Code' => 'STU', 'Name' => 'Student'],
        ];
    }


    public function testFetchesTheUserOnceAndWalksEveryPageKeyedByOrgUnit(): void
    {
        $this->transport->append(self::pagedResponse([self::item(10), self::item(20)], 'page-2'));
        $this->transport->append(self::jsonResponse(self::userData(42)));
        $this->transport->append(self::pagedResponse([self::item(30)]));

        $enrollments = self::collect($this->invoke(new ListEnrollmentOrgUnits(42)));

        self::assertSame([10, 20, 30], array_keys($enrollments));
        self::assertContainsOnlyInstancesOf(UserEnrollment::class, $enrollments);
        self::assertCount(3, $this->sentRequests);
        self::assertSame(self::API_BASE . '/enrollments/users/42/orgUnits/', (string) $this->sentRequest(0)->getUri());
        self::assertSame(self::API_BASE . '/users/42', (string) $this->sentRequest(1)->getUri());
        self::assertSame(['bookmark' => 'page-2'], self::queryOf($this->sentRequest(2)));
        foreach ($enrollments as $enrollment) {
            self::assertNotNull($enrollment->User);
            self::assertSame(42, $enrollment->User->UserId);
        }
    }


    public function testUsesTheGivenUserInsteadOfFetchingIt(): void
    {
        $this->transport->append(self::pagedResponse([self::item(10)]));
        $user = User::create(self::userData(42));

        $enrollments = self::collect($this->invoke(new ListEnrollmentOrgUnits($user)));

        self::assertCount(1, $this->sentRequests);
        self::assertSame('/d2l/api/lp/1.46/enrollments/users/42/orgUnits/', $this->sentRequest()->getUri()->getPath());
        self::assertNotNull($enrollments[10]->User);
        self::assertSame('jdoe', $enrollments[10]->User->UserName);
    }


    public function testSendsTheFiltersAndJoinsSeveralOrgUnitTypes(): void
    {
        $this->transport->append(self::pagedResponse([]));

        self::collect($this->invoke(new ListEnrollmentOrgUnits(User::create(self::userData(42)), [3, 4], 110, 'mark')));

        self::assertSame(
            ['orgUnitType' => '3,4', 'roleId' => '110', 'bookmark' => 'mark'],
            self::queryOf($this->sentRequest())
        );
    }


    public function testASingleOrgUnitTypeIsSentAsIs(): void
    {
        $this->transport->append(self::pagedResponse([]));

        self::collect($this->invoke(new ListEnrollmentOrgUnits(User::create(self::userData(42)), 3)));

        self::assertSame(['orgUnitType' => '3'], self::queryOf($this->sentRequest()));
    }


    public function testSkipsItemsWithoutAnOrgUnit(): void
    {
        $this->transport->append(self::pagedResponse([['Role' => ['Id' => 110]], self::item(10)]));

        $enrollments = self::collect($this->invoke(new ListEnrollmentOrgUnits(User::create(self::userData(42)))));

        self::assertSame([10], array_keys($enrollments));
    }


    public function testAnythingButOkFailsWhenTheListIsRead(): void
    {
        $this->transport->append(new Response(404));

        $this->expectException(\RuntimeException::class);

        self::collect($this->invoke(new ListEnrollmentOrgUnits(42)));
    }
}
