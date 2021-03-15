<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TidyMiddleware;

use Ctw\Middleware\TidyMiddleware\TidyMiddleware;
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
                trim((string) file_get_contents(__DIR__ . '/TestAsset/test1_output.htm')),
            ],
            [
                'text/html',
                trim((string) file_get_contents(__DIR__ . '/TestAsset/test2_input.htm')),
                trim((string) file_get_contents(__DIR__ . '/TestAsset/test2_output.htm')),
            ],
        ];
    }

    /**
     * @param string $contentType
     * @param string $content
     * @param string $expected
     *
     * @dataProvider dataProvider
     */
    public function testTidyMiddleware(string $contentType, string $content, string $expected): void
    {
        $config = [
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
        ];

        $middleware = new TidyMiddleware();
        $middleware->setConfig($config);

        $stack = [
            $middleware,
            function () use ($contentType, $content): ResponseInterface {
                $response = Factory::createResponse();
                $body     = Factory::getStreamFactory()->createStream($content);

                return $response->withHeader('Content-Type', $contentType)->withBody($body);
            },
        ];

        $response = Dispatcher::run($stack);

        $this->assertEquals($expected, $response->getBody()->getContents());
    }
}
