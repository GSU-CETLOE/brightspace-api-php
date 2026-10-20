<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Outcome\Message;

use Brightspace\Api\Outcome\Message\GetRegistry;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class GetRegistryTest extends BrightspaceTestCase
{
    private const REGISTRY_URL = 'https://lores-us-east-1.brightspace.com/api/lores/1.0/registries/reg-1';


    public function testGetsTheRegistryFromTheLoresServiceWithTheBearerToken(): void
    {
        $this->transport->append(self::jsonResponse([
            'id' => 'reg-1',
            'objectives' => [['id' => 'a', 'description' => 'A', 'children' => []]],
        ]));

        $registry = $this->invoke(new GetRegistry('reg-1'));

        $this->assertSentRequests([['GET', self::REGISTRY_URL]]);
        self::assertNotNull($registry);
        self::assertSame('reg-1', $registry->id);
        self::assertSame('a', $registry->objectives[0]->id);
    }


    public function testAnUnknownRegistryIsNull(): void
    {
        $this->transport->append(new Response(404));

        self::assertNull($this->invoke(new GetRegistry('reg-1')));
    }


    public function testAnyOtherStatusIsAResponseError(): void
    {
        $this->transport->append(new Response(500));

        $this->expectException(ResponseException::class);

        $this->invoke(new GetRegistry('reg-1'));
    }
}
