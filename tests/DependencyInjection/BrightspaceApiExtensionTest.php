<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\DependencyInjection;

use Brightspace\Api\BrightspaceApi;
use Brightspace\Api\Core\DependencyInjection\BrightspaceApiCoreExtension;
use Brightspace\Api\DependencyInjection\BrightspaceApiExtension;
use Brightspace\Api\Enrollment\EnrollmentApi;
use Brightspace\Api\OrgUnit\OrgUnitApi;
use Brightspace\Api\Outcome\OutcomeApi;
use Brightspace\Api\User\UserApi;
use Gadget\Cache\CacheItemPool;
use Gadget\Http\DependencyInjection\GadgetHttpExtension;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BrightspaceApiExtensionTest extends TestCase
{
    private static function loadedContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new BrightspaceApiExtension())->load([], $container);
        return $container;
    }


    /** @return iterable<string,array{class-string}> */
    public static function services(): iterable
    {
        yield 'BrightspaceApi' => [BrightspaceApi::class];
        yield 'EnrollmentApi' => [EnrollmentApi::class];
        yield 'OrgUnitApi' => [OrgUnitApi::class];
        yield 'OutcomeApi' => [OutcomeApi::class];
        yield 'UserApi' => [UserApi::class];
    }


    /**
     * @dataProvider services
     * @param class-string $service
     */
    public function testRegistersAnAutowiredService(string $service): void
    {
        $container = self::loadedContainer();

        self::assertTrue($container->hasDefinition($service));
        $definition = $container->getDefinition($service);
        self::assertTrue($definition->isAutowired());
        self::assertTrue($definition->isAutoconfigured());
    }


    public function testCompiledContainerBuildsTheApiOnTopOfTheCoreBundle(): void
    {
        $container = self::loadedContainer();
        (new BrightspaceApiCoreExtension())->load([], $container);
        (new GadgetHttpExtension())->load([], $container);
        $container->register(ClientInterface::class, GuzzleClient::class);
        $container->register(HttpFactory::class, HttpFactory::class);
        foreach (
            [
                RequestFactoryInterface::class,
                ResponseFactoryInterface::class,
                ServerRequestFactoryInterface::class,
                StreamFactoryInterface::class,
                UploadedFileFactoryInterface::class,
                UriFactoryInterface::class,
            ] as $factoryInterface
        ) {
            $container->setAlias($factoryInterface, HttpFactory::class);
        }
        $container->register(CacheItemPoolInterface::class, ArrayAdapter::class);
        $container->register(CacheItemPool::class, CacheItemPool::class)->setAutowired(true);
        foreach (
            [
                'D2L_HOST' => 'lms.example.edu',
                'D2L_ROOT_ORGUNIT' => '6606',
                'D2L_CLIENT_ID' => 'client-id',
                'D2L_CLIENT_SECRET' => 'client-secret',
                'D2L_REDIRECT_URI' => 'https://app.example.edu/callback',
                'D2L_SCOPE' => 'core:*:*',
                'D2L_USERNAME' => 'svc-user',
                'D2L_PASSWORD' => 'secret',
                'D2L_MFA_KEY' => '',
            ] as $name => $value
        ) {
            $container->setParameter("env({$name})", $value);
        }
        $container->getDefinition(BrightspaceApi::class)->setPublic(true);

        $container->compile(true);

        $api = $container->get(BrightspaceApi::class);
        self::assertInstanceOf(BrightspaceApi::class, $api);
        self::assertInstanceOf(EnrollmentApi::class, $api->enrollment);
        self::assertInstanceOf(OrgUnitApi::class, $api->orgUnit);
        self::assertInstanceOf(OutcomeApi::class, $api->outcome);
        self::assertInstanceOf(UserApi::class, $api->user);
    }
}
