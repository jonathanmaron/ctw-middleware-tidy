<?php

declare(strict_types=1);

namespace CtwTest\Middleware\TidyMiddleware;

use Ctw\Middleware\TidyMiddleware\ConfigProvider;
use Ctw\Middleware\TidyMiddleware\TidyMiddleware;
use Ctw\Middleware\TidyMiddleware\TidyMiddlewareFactory;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @see ConfigProvider
 */
#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends AbstractCase
{
    /**
     * Test that invoking the config provider returns the dependency
     * configuration wrapped under the expected top-level key.
     */
    public function testInvokeReturnsDependenciesConfiguration(): void
    {
        $configProvider = new ConfigProvider();

        $expected = [
            'dependencies' => [
                'factories' => [
                    TidyMiddleware::class => TidyMiddlewareFactory::class,
                ],
            ],
        ];

        self::assertSame($expected, $configProvider->__invoke());
    }

    /**
     * Test that getDependencies maps the TidyMiddleware service to its
     * factory under the factories key.
     */
    public function testGetDependenciesMapsMiddlewareToFactory(): void
    {
        $configProvider = new ConfigProvider();

        $expected = [
            'factories' => [
                TidyMiddleware::class => TidyMiddlewareFactory::class,
            ],
        ];

        self::assertSame($expected, $configProvider->getDependencies());
    }
}
