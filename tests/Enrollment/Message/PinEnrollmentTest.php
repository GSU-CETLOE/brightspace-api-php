<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Enrollment\Message;

use Brightspace\Api\Enrollment\Message\PinEnrollment;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class PinEnrollmentTest extends BrightspaceTestCase
{
    public function testPostsToThePinEndpoint(): void
    {
        $this->transport->append(new Response(200));

        self::assertTrue($this->invoke(new PinEnrollment(1234, 42)));

        $this->assertSentRequests([['POST', self::API_BASE . '/enrollments/orgUnits/1234/users/42/pin']]);
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(404));

        $this->expectException(ResponseException::class);

        $this->invoke(new PinEnrollment(1234, 42));
    }
}
