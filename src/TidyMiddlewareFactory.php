<?php
declare(strict_types=1);

namespace Ctw\Middleware\MinifyTidy;

use Psr\Container\ContainerInterface;

class TidyMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): TidyMiddleware
    {
        $config = $container->get('config');
        $config = $config[TidyMiddleware::class]['tidy_config'] ?? [];

        $middleware = new TidyMiddleware();

        if (count($config) > 0) {
            $middleware->setConfig($config);
        }

        return $middleware;
    }
}
