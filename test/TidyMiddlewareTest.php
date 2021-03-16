<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TidyMiddleware;

use Ctw\Middleware\TidyMiddleware\TidyMiddleware;
use Ctw\Middleware\TidyMiddleware\TidyMiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;

class TidyMiddlewareTest extends AbstractCase
{
    public function dataProvider(): array
    {
        return [
            [
                'text/html',
                trim((string) file_get_contents(__DIR__ . '/TestAsset/test1_input.htm')),
                [
                    '<!-- html',
                    '% -->',
                    '<p>header</p>',
                    '<p>main</p>',
                    '<p>footer</p>',
                ],
            ],
            [
                'text/html',
                trim((string) file_get_contents(__DIR__ . '/TestAsset/test2_input.htm')),
                [
                    '<!-- html',
                    '% -->',
                    '<script type="text/javascript" src="https://s1-www.example.com/55db9daf/dist/js/app.min.js">',
                ],
            ],
        ];
    }

    /**
     * @param string $contentType
     * @param string $content
     * @param array  $expected
     *
     * @dataProvider dataProvider
     */
    public function testTidyMiddleware(string $contentType, string $content, array $expected): void
    {
        $stack = [
            $this->getInstance(),
            function () use ($contentType, $content): ResponseInterface {
                $response = Factory::createResponse();
                $body     = Factory::getStreamFactory()->createStream($content);

                return $response->withHeader('Content-Type', $contentType)->withBody($body);
            },
        ];

        $response = Dispatcher::run($stack);
        $haystack = $response->getBody()->getContents();
        foreach ($expected as $needle) {
            $this->assertStringContainsString($needle, $haystack);
        }
    }

    private function getInstance(): TidyMiddleware
    {
        $config = [
            TidyMiddleware::class => [
                'char-encoding'    => 'utf8',
                'doctype'          => 'html5',
                'bare'             => true,
                'break-before-br'  => true,
                'indent'           => false,
                'indent-spaces'    => 0,
                'logical-emphasis' => true,
                'numeric-entities' => true,
                'quiet'            => true,
                'quote-ampersand'  => false,
                'tidy-mark'        => false,
                'uppercase-tags'   => false,
                'vertical-space'   => false,
                'wrap'             => 10000,
                'wrap-attributes'  => false,
                'write-back'       => true,
            ],
        ];

        $container = new ServiceManager();
        $container->setService('config', $config);

        $factory = new TidyMiddlewareFactory();

        return $factory->__invoke($container);
    }
}
