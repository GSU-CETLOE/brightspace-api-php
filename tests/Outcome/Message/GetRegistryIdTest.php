<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Outcome\Message;

use Brightspace\Api\Outcome\Message\GetRegistryId;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class GetRegistryIdTest extends BrightspaceTestCase
{
    private const PAGE_URL = 'https://lms.example.edu/d2l/le/lo/1234/outcomes-management';


    private static function page(string $element): Response
    {
        return new Response(200, ['Content-Type' => 'text/html'], "<html><body>{$element}</body></html>");
    }


    public function testReadsTheRegistryIdOffTheOutcomesManagementPageUsingTheWebSession(): void
    {
        $this->markLoggedIn();
        $this->transport->append(self::page('<d2l-outcomes-management registry-id="reg-1"></d2l-outcomes-management>'));

        self::assertSame('reg-1', $this->invoke(new GetRegistryId(1234)));

        self::assertCount(1, $this->sentRequests);
        self::assertSame('GET', $this->sentRequest()->getMethod());
        self::assertSame(self::PAGE_URL, (string) $this->sentRequest()->getUri());
        self::assertFalse($this->sentRequest()->hasHeader('Authorization'));
        self::assertStringContainsString('d2lSessionVal=', $this->sentRequest()->getHeaderLine('Cookie'));
    }


    public function testLogsInFirstWhenThereIsNoWebSession(): void
    {
        $this->transport->append(new Response(302, [
            'Location' => '/d2l/home',
            'Set-Cookie' => [
                'd2lSessionVal=session-value; Path=/; Secure; HttpOnly',
                'd2lSecureSessionVal=secure-session-value; Path=/; Secure; HttpOnly',
            ],
        ]));
        $this->transport->append(self::page('<d2l-outcomes-management registry-id="reg-1"></d2l-outcomes-management>'));

        self::assertSame('reg-1', $this->invoke(new GetRegistryId(1234)));

        self::assertCount(2, $this->sentRequests);
        self::assertSame('/d2l/lp/auth/login/login.d2l', $this->sentRequest(0)->getUri()->getPath());
        self::assertSame(self::PAGE_URL, (string) $this->sentRequest(1)->getUri());
    }


    public function testAPageWithoutTheElementIsAnError(): void
    {
        $this->markLoggedIn();
        $this->transport->append(self::page('<p>nothing here</p>'));

        try {
            $this->invoke(new GetRegistryId(1234));
            self::fail('A page without the element should be an error');
        } catch (ResponseException $e) {
            $cause = $e->getPrevious();
            self::assertInstanceOf(\RuntimeException::class, $cause);
            self::assertSame("Element 'd2l-outcomes-management' not found", $cause->getMessage());
        }
    }


    public function testAnElementWithoutTheAttributeIsAnError(): void
    {
        $this->markLoggedIn();
        $this->transport->append(self::page('<d2l-outcomes-management></d2l-outcomes-management>'));

        try {
            $this->invoke(new GetRegistryId(1234));
            self::fail('A page without a registry id should be an error');
        } catch (ResponseException $e) {
            $cause = $e->getPrevious();
            self::assertInstanceOf(\RuntimeException::class, $cause);
            self::assertSame("Attribute 'registry-id' not found", $cause->getMessage());
        }
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->markLoggedIn();
        $this->transport->append(new Response(403));

        $this->expectException(ResponseException::class);

        $this->invoke(new GetRegistryId(1234));
    }
}
