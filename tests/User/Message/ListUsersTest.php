<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\User\Message;

use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Message\ListUsers;
use Brightspace\Api\User\Model\User;
use GuzzleHttp\Psr7\Response;

final class ListUsersTest extends BrightspaceTestCase
{
    public function testWalksEveryPageAndKeysUsersById(): void
    {
        $this->transport->append(self::pagedResponse([self::userData(1, 'a'), self::userData(2, 'b')], 'page-2'));
        $this->transport->append(self::pagedResponse([self::userData(3, 'c')]));

        $users = self::collect($this->invoke(new ListUsers()));

        self::assertSame([1, 2, 3], array_keys($users));
        self::assertContainsOnlyInstancesOf(User::class, $users);
        self::assertSame('c', $users[3]->UserName);
        self::assertCount(2, $this->sentRequests);
        self::assertSame(self::API_BASE . '/users/', (string) $this->sentRequest(0)->getUri());
        self::assertSame(self::API_BASE . '/users/', (string) $this->sentRequest(1)->getUri()->withQuery(''));
        self::assertSame(['bookmark' => 'page-2'], self::queryOf($this->sentRequest(1)));
    }


    public function testStartsFromTheGivenBookmark(): void
    {
        $this->transport->append(self::pagedResponse([]));

        self::collect($this->invoke(new ListUsers(bookmark: 'resume-here')));

        self::assertSame(['bookmark' => 'resume-here'], self::queryOf($this->sentRequest()));
    }


    public function testAUserNameLookupReadsASingleUserAndIgnoresTheOtherFilters(): void
    {
        $this->transport->append(self::jsonResponse(self::userData(7, 'jdoe')));

        $users = self::collect($this->invoke(new ListUsers(
            userName: 'jdoe',
            externalEmail: 'ignored@example.edu',
            bookmark: 'ignored'
        )));

        self::assertSame(['userName' => 'jdoe'], self::queryOf($this->sentRequest()));
        self::assertSame([7], array_keys($users));
        self::assertSame('jdoe', $users[7]->UserName);
    }


    public function testAnOrgDefinedIdLookupReadsAListAndWinsOverEveryOtherFilter(): void
    {
        $this->transport->append(self::jsonResponse([self::userData(7, 'jdoe')]));

        $users = self::collect($this->invoke(new ListUsers(
            orgDefinedId: '0007',
            userName: 'ignored',
            externalEmail: 'ignored@example.edu',
            bookmark: 'ignored'
        )));

        self::assertSame(['orgDefinedId' => '0007'], self::queryOf($this->sentRequest()));
        self::assertSame([7], array_keys($users));
    }


    public function testAnExternalEmailLookupReadsAListAndDropsTheBookmark(): void
    {
        $this->transport->append(self::jsonResponse([self::userData(7, 'jdoe'), self::userData(8, 'jdoe2')]));

        $users = self::collect($this->invoke(new ListUsers(
            externalEmail: 'jdoe@example.edu',
            bookmark: 'ignored'
        )));

        self::assertSame(['externalEmail' => 'jdoe@example.edu'], self::queryOf($this->sentRequest()));
        self::assertSame([7, 8], array_keys($users));
    }


    public function testAnythingButOkFailsWhenTheListIsRead(): void
    {
        $this->transport->append(new Response(500));

        $this->expectException(\RuntimeException::class);

        self::collect($this->invoke(new ListUsers()));
    }
}
