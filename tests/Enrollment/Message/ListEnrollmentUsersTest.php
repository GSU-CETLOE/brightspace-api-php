<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Enrollment\Message;

use Brightspace\Api\Enrollment\Message\ListEnrollmentUsers;
use Brightspace\Api\Enrollment\Model\UserEnrollment;
use Brightspace\Api\OrgUnit\Model\OrgUnit;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use GuzzleHttp\Psr7\Response;

final class ListEnrollmentUsersTest extends BrightspaceTestCase
{
    /**
     * @param int $userId
     * @return array<string,mixed>
     */
    private static function item(int $userId): array
    {
        return [
            'User' => self::userData($userId, 'user' . $userId),
            'Role' => ['Id' => 110, 'Code' => 'STU', 'Name' => 'Student'],
        ];
    }


    public function testFetchesTheOrgUnitOnceAndWalksEveryPageKeyedByUser(): void
    {
        $this->transport->append(self::pagedResponse([self::item(1), self::item(2)], 'page-2'));
        $this->transport->append(self::jsonResponse(self::orgUnitData(1234)));
        $this->transport->append(self::pagedResponse([self::item(3)]));

        $enrollments = self::collect($this->invoke(new ListEnrollmentUsers(1234)));

        self::assertSame([1, 2, 3], array_keys($enrollments));
        self::assertContainsOnlyInstancesOf(UserEnrollment::class, $enrollments);
        self::assertCount(3, $this->sentRequests);
        self::assertSame(
            self::API_BASE . '/enrollments/orgUnits/1234/users/',
            (string) $this->sentRequest(0)->getUri()
        );
        self::assertSame(self::API_BASE . '/orgstructure/1234', (string) $this->sentRequest(1)->getUri());
        self::assertSame(['bookmark' => 'page-2'], self::queryOf($this->sentRequest(2)));
        foreach ($enrollments as $enrollment) {
            self::assertNotNull($enrollment->OrgUnit);
            self::assertSame(1234, $enrollment->OrgUnit->Identifier);
        }
    }


    public function testUsesTheGivenOrgUnitInsteadOfFetchingIt(): void
    {
        $this->transport->append(self::pagedResponse([self::item(1)]));
        $orgUnit = OrgUnit::create(self::orgUnitData(1234));

        $enrollments = self::collect($this->invoke(new ListEnrollmentUsers($orgUnit)));

        self::assertCount(1, $this->sentRequests);
        self::assertNotNull($enrollments[1]->OrgUnit);
        self::assertSame('Intro to Testing', $enrollments[1]->OrgUnit->Name);
    }


    public function testSendsTheFilters(): void
    {
        $this->transport->append(self::pagedResponse([]));

        $orgUnit = OrgUnit::create(self::orgUnitData(1234));

        self::collect($this->invoke(new ListEnrollmentUsers($orgUnit, 110, true, 'mark')));

        self::assertSame(
            ['roleId' => '110', 'isActive' => '1', 'bookmark' => 'mark'],
            self::queryOf($this->sentRequest())
        );
    }


    public function testSkipsItemsWithoutAUser(): void
    {
        $this->transport->append(self::pagedResponse([['Role' => ['Id' => 110]], self::item(1)]));

        $enrollments = self::collect($this->invoke(new ListEnrollmentUsers(OrgUnit::create(self::orgUnitData(1234)))));

        self::assertSame([1], array_keys($enrollments));
    }


    public function testAnythingButOkFailsWhenTheListIsRead(): void
    {
        $this->transport->append(new Response(404));

        $this->expectException(\RuntimeException::class);

        self::collect($this->invoke(new ListEnrollmentUsers(1234)));
    }
}
