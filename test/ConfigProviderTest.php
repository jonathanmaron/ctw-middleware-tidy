<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TidyMiddleware;

use Ctw\Middleware\TidyMiddleware\ConfigProvider;
use Ctw\Middleware\TidyMiddleware\TidyMiddleware;
use Ctw\Middleware\TidyMiddleware\TidyMiddlewareFactory;

class ConfigProviderTest extends AbstractCase
{
    public function testConfigProvider(): void
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
}
