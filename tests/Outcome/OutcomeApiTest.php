<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Outcome;

use Brightspace\Api\Outcome\Model\OutcomeRegistry;
use Brightspace\Api\Outcome\OutcomeApi;
use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use GuzzleHttp\Psr7\Response;

final class OutcomeApiTest extends BrightspaceTestCase
{
    private const REGISTRY_URL = 'https://lores-us-east-1.brightspace.com/api/lores/1.0/registries/reg-1';
    private const PAGE_URL = 'https://lms.example.edu/d2l/le/lo/1234/outcomes-management';

    private OutcomeApi $api;


    protected function setUp(): void
    {
        parent::setUp();
        $this->markLoggedIn();
        $this->api = $this->createApi(OutcomeApi::class);
    }


    private static function managementPage(): Response
    {
        return new Response(
            200,
            ['Content-Type' => 'text/html'],
            '<html><body><d2l-outcomes-management registry-id="reg-1"></d2l-outcomes-management></body></html>'
        );
    }


    public function testGetIdScrapesTheOutcomesManagementPage(): void
    {
        $this->transport->append(self::managementPage());

        self::assertSame('reg-1', $this->api->getId(1234));
        self::assertSame(self::PAGE_URL, (string) $this->sentRequest()->getUri());
    }


    public function testGetByRegistryIdGoesStraightToTheRegistry(): void
    {
        $this->transport->append(self::jsonResponse(['id' => 'reg-1', 'objectives' => []]));

        $registry = $this->api->get('reg-1');

        self::assertNotNull($registry);
        self::assertSame('reg-1', $registry->id);
        $this->assertSentRequests([['GET', self::REGISTRY_URL]]);
    }


    public function testGetByOrgUnitLooksTheRegistryIdUpFirst(): void
    {
        $this->transport->append(self::managementPage());
        $this->transport->append(self::jsonResponse(['id' => 'reg-1', 'objectives' => []]));

        $registry = $this->api->get(1234);

        self::assertNotNull($registry);
        self::assertSame('reg-1', $registry->id);
        self::assertCount(2, $this->sentRequests);
        self::assertSame(self::PAGE_URL, (string) $this->sentRequest(0)->getUri());
        self::assertSame(self::REGISTRY_URL, (string) $this->sentRequest(1)->getUri());
    }


    public function testUpdatePutsTheRegistry(): void
    {
        $this->transport->append(new Response(200));

        self::assertTrue($this->api->update(new OutcomeRegistry('reg-1')));

        $this->assertSentRequests([['PUT', self::REGISTRY_URL]]);
    }
}
