<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Support;

use Brightspace\Api\Core\Client\ApiClient;
use Brightspace\Api\Core\Client\CoreApiClient;
use Brightspace\Api\Core\Client\CoreClient;
use Brightspace\Api\Core\Config;
use Brightspace\Api\Core\Message\MessageHandler;
use Brightspace\Api\Core\Model\ProductVersion;
use Gadget\Cache\CacheItemPool;
use Gadget\Http\Client\Client;
use Gadget\Http\Message\MessageFactory;
use Gadget\Oauth\Model\Token;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Builds the gadget-http Client on a real Guzzle client whose transport is a MockHandler queue, the
 * same way the core package's own suite does. Every request Guzzle would have put on the wire is
 * recorded in $sentRequests. A valid token and the product version table are seeded into the cache
 * before each test so no login or version lookup round trip is needed to send an API request.
 */
abstract class BrightspaceTestCase extends TestCase
{
    protected const D2L_HOST = 'lms.example.edu';
    protected const ROOT_ORG_UNIT = 6606;
    protected const LP_VERSION = '1.46';
    protected const API_BASE = 'https://lms.example.edu/d2l/api/lp/1.46';

    protected MockHandler $transport;

    /** @var RequestInterface[] */
    protected array $sentRequests = [];

    protected GuzzleClient $guzzle;

    protected ArrayAdapter $adapter;

    protected Client $client;

    protected Config $config;

    protected CoreClient $coreClient;


    protected function setUp(): void
    {
        $this->transport = new MockHandler();
        $this->sentRequests = [];

        $stack = HandlerStack::create($this->transport);
        $stack->push(fn(callable $next): callable => function (RequestInterface $request, array $options) use ($next) {
            $this->sentRequests[] = $request;
            return $next($request, $options);
        });

        $this->guzzle = new GuzzleClient([
            'handler' => $stack,
            'allow_redirects' => false,
            'http_errors' => false,
        ]);
        $this->adapter = new ArrayAdapter();

        $factory = new HttpFactory();
        $this->client = new Client(
            $this->guzzle,
            new MessageFactory($factory, $factory, $factory, $factory, $factory, $factory),
            new CacheItemPool($this->adapter)
        );
        $this->config = new Config(
            d2lHost: self::D2L_HOST,
            rootOrgUnit: self::ROOT_ORG_UNIT,
            clientId: 'client-id',
            clientSecret: 'client-secret',
            redirectUri: 'https://app.example.edu/callback',
            scope: 'core:*:*',
            username: 'svc-user',
            password: 'secret',
            mfa: null
        );
        $this->coreClient = new CoreClient($this->client, new CoreApiClient($this->client, $this->config));

        $this->seedTokenAndVersions();
    }


    /**
     * Puts a valid token and a product version table in the cache namespace the gadget-http Client
     * and the CoreClient read from.
     *
     * @return void
     */
    private function seedTokenAndVersions(): void
    {
        $cache = (new CacheItemPool($this->adapter))->withNamespace(Client::class);
        $cache->set('token', new Token(
            type: 'Bearer',
            scope: 'core:*:*',
            createdOn: time(),
            expiresIn: 3600,
            accessToken: 'the-access-token',
            refreshToken: 'the-refresh-token'
        ));
        $cache->set('productVersions', ['lp' => new ProductVersion('lp', self::LP_VERSION)]);
    }


    /**
     * Builds one of this package's API clients over the test transport.
     *
     * @template TApi of ApiClient
     * @param class-string<TApi> $apiClass
     * @return TApi
     */
    protected function createApi(string $apiClass): ApiClient
    {
        return new $apiClass($this->client, $this->config, $this->coreClient);
    }


    /**
     * Initializes a handler with the test config and core client and sends it, exactly as an
     * ApiClient would.
     *
     * @template TResponse
     * @param MessageHandler<TResponse> $handler
     * @return TResponse
     */
    protected function invoke(MessageHandler $handler): mixed
    {
        return $handler->init($this->config, $this->coreClient)->invoke($this->client);
    }


    /**
     * Drains a lazy result set into an array, keeping the keys the handler yields.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey,TValue> $items
     * @return array<TKey,TValue>
     */
    protected static function collect(iterable $items): array
    {
        return is_array($items) ? $items : iterator_to_array($items);
    }


    /**
     * Puts the two Brightspace session cookies into the client's jar as a completed web login would.
     *
     * @return void
     */
    protected function markLoggedIn(): void
    {
        foreach (['d2lSessionVal', 'd2lSecureSessionVal'] as $name) {
            $this->client->getCookieJar()->setCookie([
                'Name' => $name,
                'Value' => 'session-' . $name,
                'Domain' => self::D2L_HOST,
                'Path' => '/',
                'Expires' => null,
            ]);
        }
    }


    /**
     * Builds a 200 JSON response.
     *
     * @param mixed $body
     * @return Response
     */
    protected static function jsonResponse(mixed $body): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }


    /**
     * Builds the PagedResultSet body Brightspace returns from a paged endpoint.
     *
     * @param mixed[] $items
     * @param string|null $bookmark
     * @return Response
     */
    protected static function pagedResponse(array $items, string|null $bookmark = null): Response
    {
        return self::jsonResponse([
            'PagingInfo' => [
                'Bookmark' => $bookmark ?? '',
                'HasMoreItems' => $bookmark !== null,
            ],
            'Items' => $items,
        ]);
    }


    /**
     * Returns the request that reached the transport at the given position.
     *
     * @param int $index
     * @return RequestInterface
     */
    protected function sentRequest(int $index = 0): RequestInterface
    {
        self::assertArrayHasKey($index, $this->sentRequests, "No request reached the transport at position {$index}");
        return $this->sentRequests[$index];
    }


    /**
     * Asserts that exactly the given requests reached the transport, in order, each with the bearer token.
     *
     * @param array<int,array{string,string}> $expected method and full URL per request
     * @return void
     */
    protected function assertSentRequests(array $expected): void
    {
        self::assertCount(count($expected), $this->sentRequests);
        foreach ($expected as $index => [$method, $url]) {
            $sent = $this->sentRequest($index);
            self::assertSame($method, $sent->getMethod(), "Request {$index}");
            self::assertSame($url, (string) $sent->getUri(), "Request {$index}");
            self::assertSame('Bearer the-access-token', $sent->getHeaderLine('Authorization'), "Request {$index}");
        }
    }


    /**
     * Parses the query string of a request into name => value pairs.
     *
     * @param RequestInterface $request
     * @return array<array-key,mixed>
     */
    protected static function queryOf(RequestInterface $request): array
    {
        parse_str($request->getUri()->getQuery(), $query);
        return $query;
    }


    /**
     * Decodes the JSON body of a request.
     *
     * @param RequestInterface $request
     * @return mixed
     */
    protected static function jsonBodyOf(RequestInterface $request): mixed
    {
        return json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }


    /**
     * The JSON Brightspace returns for a user record.
     *
     * @param int $userId
     * @param string $userName
     * @return array<string,mixed>
     */
    protected static function userData(int $userId = 42, string $userName = 'jdoe'): array
    {
        return [
            'OrgId' => 6606,
            'UserId' => $userId,
            'OrgDefinedId' => '00' . $userId,
            'FirstName' => 'Jane',
            'MiddleName' => null,
            'LastName' => 'Doe',
            'UserName' => $userName,
            'ExternalEmail' => 'jdoe@example.edu',
            'UniqueIdentifier' => $userName,
            'Activation' => ['IsActive' => true],
            'DisplayName' => 'Jane Doe',
            'LastAccessedDate' => '2026-01-02T03:04:05.000Z',
            'Pronouns' => 'she/her',
        ];
    }


    /**
     * The JSON Brightspace returns for an org unit.
     *
     * @param int $id
     * @param string $name
     * @param int $typeId
     * @return array<string,mixed>
     */
    protected static function orgUnitData(int $id = 1234, string $name = 'Intro to Testing', int $typeId = 3): array
    {
        return [
            'Identifier' => $id,
            'Name' => $name,
            'Code' => 'TEST-' . $id,
            'Path' => '/content/enforced/' . $id . '/',
            'Type' => ['Id' => $typeId, 'Code' => 'Course Offering', 'Name' => 'Course Offering'],
        ];
    }
}
