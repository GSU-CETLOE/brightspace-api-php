<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Message;

use Brightspace\Api\OrgUnit\Message\CreateOrgUnitRelationship;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class CreateOrgUnitRelationshipTest extends BrightspaceTestCase
{
    public function testPostsTheParentIdToTheChildsParents(): void
    {
        $this->transport->append(new Response(200));

        self::assertTrue($this->invoke(new CreateOrgUnitRelationship(1234, 5678)));

        $this->assertSentRequests([['POST', self::API_BASE . '/orgstructure/1234/parents/']]);
        self::assertSame('application/json', $this->sentRequest()->getHeaderLine('Content-Type'));
        self::assertSame('5678', (string) $this->sentRequest()->getBody());
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(400));

        $this->expectException(ResponseException::class);

        $this->invoke(new CreateOrgUnitRelationship(1234, 5678));
    }
}
