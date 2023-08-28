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
            $config = $container->get('config');
            assert(is_array($config));
            $config = $config[TidyMiddleware::class] ?? [];
        }

        $middleware = new TidyMiddleware();

        if ((is_countable($config) ? count($config) : 0) > 0) {
            $middleware->setConfig($config);
        }

        return $middleware;
    }
}
