<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests;

use Brightspace\Api\BrightspaceApiBundle;
use Brightspace\Api\DependencyInjection\BrightspaceApiExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class BrightspaceApiBundleTest extends TestCase
{
    public function testIsASymfonyBundle(): void
    {
        self::assertInstanceOf(Bundle::class, new BrightspaceApiBundle());
    }


    public function testExposesTheBrightspaceApiExtension(): void
    {
        $extension = (new BrightspaceApiBundle())->getContainerExtension();

        self::assertInstanceOf(BrightspaceApiExtension::class, $extension);
        self::assertSame('brightspace_api', $extension->getAlias());
    }
}
