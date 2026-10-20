<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Outcome\Message;

use Brightspace\Api\Outcome\Message\UpdateRegistry;
use Brightspace\Api\Outcome\Model\OutcomeDetail;
use Brightspace\Api\Outcome\Model\OutcomeRegistry;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class UpdateRegistryTest extends BrightspaceTestCase
{
    private const REGISTRY_URL = 'https://lores-us-east-1.brightspace.com/api/lores/1.0/registries/reg-1';


    public function testPutsTheObjectiveTreeAsIdsOnly(): void
    {
        $this->transport->append(new Response(200));
        $registry = new OutcomeRegistry('reg-1', [
            new OutcomeDetail('a', 'A', [new OutcomeDetail('a1', 'A1')]),
            new OutcomeDetail('b', 'B'),
        ]);

        self::assertTrue($this->invoke(new UpdateRegistry($registry)));

        $this->assertSentRequests([['PUT', self::REGISTRY_URL]]);
        self::assertSame('lores-us-east-1.brightspace.com', $this->sentRequest()->getHeaderLine('Host'));
        self::assertSame('application/json', $this->sentRequest()->getHeaderLine('Content-Type'));
        self::assertSame([
            'objectives' => [
                ['id' => 'a', 'children' => [['id' => 'a1', 'children' => []]]],
                ['id' => 'b', 'children' => []],
            ],
        ], self::jsonBodyOf($this->sentRequest()));
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(400));

        $this->expectException(ResponseException::class);

        $this->invoke(new UpdateRegistry(new OutcomeRegistry('reg-1')));
    }
}
