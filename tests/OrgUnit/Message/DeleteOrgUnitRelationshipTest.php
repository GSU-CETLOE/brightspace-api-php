<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit\Message;

use Brightspace\Api\OrgUnit\Message\DeleteOrgUnitRelationship;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Gadget\Http\Exception\ResponseException;
use GuzzleHttp\Psr7\Response;

final class DeleteOrgUnitRelationshipTest extends BrightspaceTestCase
{
    public function testDeletesTheParentFromTheChild(): void
    {
        $this->transport->append(new Response(200));

        self::assertTrue($this->invoke(new DeleteOrgUnitRelationship(1234, 5678)));

        $this->assertSentRequests([['DELETE', self::API_BASE . '/orgstructure/1234/parents/5678']]);
    }


    public function testAnythingButOkIsAResponseError(): void
    {
        $this->transport->append(new Response(404));

        $this->expectException(ResponseException::class);

        $this->invoke(new DeleteOrgUnitRelationship(1234, 5678));
    }
}
