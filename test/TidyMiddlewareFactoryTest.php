<?php

declare(strict_types=1);

namespace CtwTest\Middleware\TidyMiddleware;

use Ctw\Middleware\TidyMiddleware\TidyMiddleware;
use Ctw\Middleware\TidyMiddleware\TidyMiddlewareFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerInterface;

/**
 * @see TidyMiddlewareFactory
 */
#[CoversClass(TidyMiddlewareFactory::class)]
final class TidyMiddlewareFactoryTest extends AbstractCase
{
    /**
     * Test that the factory builds a TidyMiddleware using the documented
     * default configuration when the container has no config service.
     */
    public function testInvokeReturnsMiddlewareWithDefaultConfigWhenConfigAbsent(): void
    {
        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(false);

        $factory    = new TidyMiddlewareFactory();
        $middleware = $factory->__invoke($container);

        self::assertSame('html5', $middleware->getConfig()['doctype'] ?? null);
    }

    /**
     * Test that the factory keeps the default configuration when the config
     * service exists but contains no entry for the TidyMiddleware key.
     */
    public function testInvokeReturnsMiddlewareWithDefaultConfigWhenKeyMissing(): void
    {
        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn([
                'unrelated' => true,
            ]);

        $factory    = new TidyMiddlewareFactory();
        $middleware = $factory->__invoke($container);

        self::assertSame('html5', $middleware->getConfig()['doctype'] ?? null);
    }

    /**
     * Test that the factory keeps the default configuration when the config
     * entry for the TidyMiddleware key is an empty array.
     */
    public function testInvokeKeepsDefaultConfigWhenConfiguredArrayIsEmpty(): void
    {
        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn([
                TidyMiddleware::class => [],
            ]);

        $factory    = new TidyMiddlewareFactory();
        $middleware = $factory->__invoke($container);

        self::assertSame('html5', $middleware->getConfig()['doctype'] ?? null);
    }

    /**
     * Test that the factory applies a non-empty configuration array from the
     * container onto the constructed TidyMiddleware instance.
     */
    public function testInvokeAppliesConfiguredArrayOntoMiddleware(): void
    {
        $custom = [
            'doctype' => 'omit',
            'indent'  => true,
        ];

        $container = self::createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn([
                TidyMiddleware::class => $custom,
            ]);

        $factory    = new TidyMiddlewareFactory();
        $middleware = $factory->__invoke($container);

        self::assertSame($custom, $middleware->getConfig());
    }
}
