<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\OrgUnit;

use Brightspace\Api\OrgUnit\OrgUnitApi;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use GuzzleHttp\Psr7\Response;

final class OrgUnitApiTest extends BrightspaceTestCase
{
    private OrgUnitApi $api;


    protected function setUp(): void
    {
        parent::setUp();
        $this->api = $this->createApi(OrgUnitApi::class);
    }


    public function testGetRootCombinesTheOrganizationInfoAndPrimaryUrl(): void
    {
        $this->transport->append(self::jsonResponse([
            'Identifier' => '6606',
            'Name' => 'Example',
            'TimeZone' => 'UTC',
        ]));
        $this->transport->append(new Response(200, [], '"https://lms.example.edu/"'));

        $root = $this->api->getRoot();

        self::assertSame('6606', $root->Identifier);
        self::assertSame('"https://lms.example.edu/"', $root->PrimaryUrl);
    }


    public function testListPassesTheFiltersThrough(): void
    {
        $this->transport->append(self::pagedResponse([self::orgUnitData(1)]));

        $orgUnits = self::collect($this->api->list(orgUnitType: 3, exactOrgUnitCode: 'TEST-1'));

        self::assertSame([1], array_keys($orgUnits));
        self::assertSame(['orgUnitType' => '3', 'exactOrgUnitCode' => 'TEST-1'], self::queryOf($this->sentRequest()));
    }


    public function testGetFetchesOneOrgUnit(): void
    {
        $this->transport->append(self::jsonResponse(self::orgUnitData(1234)));

        $orgUnit = $this->api->get(1234);

        self::assertNotNull($orgUnit);
        self::assertSame(1234, $orgUnit->Identifier);
        $this->assertSentRequests([['GET', self::API_BASE . '/orgstructure/1234']]);
    }


    public function testAncestorsAndParentsUseTheirOwnEndpoints(): void
    {
        $this->transport->append(self::jsonResponse([self::orgUnitData(10)]));
        $this->transport->append(self::jsonResponse([self::orgUnitData(20)]));

        self::assertSame([10], array_keys(self::collect($this->api->listAncestors(1234))));
        self::assertSame([20], array_keys(self::collect($this->api->listParents(1234, 5))));

        self::assertSame('/d2l/api/lp/1.46/orgstructure/1234/ancestors/', $this->sentRequest(0)->getUri()->getPath());
        self::assertSame('/d2l/api/lp/1.46/orgstructure/1234/parents/', $this->sentRequest(1)->getUri()->getPath());
        self::assertSame(['ouTypeId' => '5'], self::queryOf($this->sentRequest(1)));
    }


    public function testDescendantsAndChildrenUseTheirOwnPagedEndpoints(): void
    {
        $this->transport->append(self::pagedResponse([self::orgUnitData(10)]));
        $this->transport->append(self::pagedResponse([self::orgUnitData(20)]));

        self::assertSame([10], array_keys(self::collect($this->api->listDescendants(1234))));
        self::assertSame([20], array_keys(self::collect($this->api->listChildren(1234, 3, 'mark'))));

        self::assertSame(
            '/d2l/api/lp/1.46/orgstructure/1234/descendants/paged/',
            $this->sentRequest(0)->getUri()->getPath()
        );
        self::assertSame(
            '/d2l/api/lp/1.46/orgstructure/1234/children/paged/',
            $this->sentRequest(1)->getUri()->getPath()
        );
        self::assertSame(['ouTypeId' => '3', 'bookmark' => 'mark'], self::queryOf($this->sentRequest(1)));
    }


    public function testRelationshipsAreCreatedAndDeletedOnTheChild(): void
    {
        $this->transport->append(new Response(200));
        $this->transport->append(new Response(200));

        self::assertTrue($this->api->createRelationship(1234, 5678));
        self::assertTrue($this->api->deleteRelationship(1234, 5678));

        $this->assertSentRequests([
            ['POST', self::API_BASE . '/orgstructure/1234/parents/'],
            ['DELETE', self::API_BASE . '/orgstructure/1234/parents/5678'],
        ]);
    }


    public function testTypesAreListedAndFetched(): void
    {
        $this->transport->append(self::jsonResponse([['Id' => 3, 'Code' => 'CO', 'Name' => 'Course Offering']]));
        $this->transport->append(self::jsonResponse(['Id' => 3, 'Code' => 'CO', 'Name' => 'Course Offering']));

        self::assertSame([3], array_keys(self::collect($this->api->listTypes())));
        $type = $this->api->getType(3);
        self::assertNotNull($type);
        self::assertSame('Course Offering', $type->Name);

        $this->assertSentRequests([
            ['GET', self::API_BASE . '/outypes/'],
            ['GET', self::API_BASE . '/outypes/3'],
        ]);
    }
}
