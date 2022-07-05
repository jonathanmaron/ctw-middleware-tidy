<?php
declare(strict_types=1);

namespace Ctw\Middleware\TidyMiddleware;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class TidyMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): TidyMiddleware
    {
        $config = [];
        if ($container->has('config')) {
            $config = $container->get('config');
            assert(is_array($config));
            $config = $config[TidyMiddleware::class] ?? [];
        }

        $middleware = new TidyMiddleware();

        if (count($config) > 0) {
            $middleware->setConfig($config);
        }

        return $middleware;
    }
}
