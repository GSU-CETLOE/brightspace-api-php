<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Enrollment\Message;

use Brightspace\Api\Enrollment\Message\EnrollUser;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class EnrollUserTest extends BrightspaceTestCase
{
    public function testPostsTheEnrollmentAsJson(): void
    {
        $this->transport->append(new Response(200));

        self::assertTrue($this->invoke(new EnrollUser(1234, 42, 110)));

        $this->assertSentRequests([['POST', self::API_BASE . '/enrollments/']]);
        self::assertSame('application/json', $this->sentRequest()->getHeaderLine('Content-Type'));
        self::assertSame(
            ['orgUnitId' => 1234, 'userId' => 42, 'roleId' => 110],
            self::jsonBodyOf($this->sentRequest())
        );
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(400));

        $this->expectException(ResponseException::class);

        $this->invoke(new EnrollUser(1234, 42, 110));
    }
}
