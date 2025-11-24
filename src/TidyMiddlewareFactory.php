<?php
declare(strict_types=1);

namespace Ctw\Middleware\TidyMiddleware;

use Psr\Container\ContainerInterface;

class TidyMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): TidyMiddleware
    {
        $config = [];
        if ($container->has('config')) {
            $containerConfig = $container->get('config');
            assert(is_array($containerConfig));
            $config = $containerConfig[TidyMiddleware::class] ?? [];
            assert(is_array($config));
        }

        $middleware = new TidyMiddleware();

        if ([] !== $config) {
            $middleware->setConfig($config);
        }

        return $middleware;
    }
}
