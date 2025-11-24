<?php
declare(strict_types=1);

namespace CtwTest\Middleware\TidyMiddleware;

use Ctw\Middleware\TidyMiddleware\TidyMiddleware;
use Ctw\Middleware\TidyMiddleware\TidyMiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

class TidyMiddlewareTest extends AbstractCase
{
    #[DataProvider('dataProvider')]
    public function testTidyMiddleware(string $contentType, string $content, array $expected): void
    {
        $stack = [
            $this->getInstance(),
            static function () use ($contentType, $content): ResponseInterface {
                $response = Factory::createResponse();
                $body     = Factory::getStreamFactory()->createStream($content);
                $response = $response->withHeader('Content-Type', $contentType);

                return $response->withBody($body);
            },
        ];

        $response = Dispatcher::run($stack);
        $body     = $response->getBody();
        $haystack = $body->getContents();

        if ([] === $expected) {
            self::assertEmpty($haystack);

            return;
        }

        foreach ($expected as $needle) {
            assert(is_string($needle));
            self::assertStringContainsString($needle, $haystack);
        }
    }

    public static function dataProvider(): array
    {
        $buffer1 = (string) file_get_contents(__DIR__ . '/TestAsset/test0_input.htm');
        $buffer2 = (string) file_get_contents(__DIR__ . '/TestAsset/test1_input.htm');
        $buffer3 = (string) file_get_contents(__DIR__ . '/TestAsset/test2_input.htm');

        return [
            ['text/html', trim($buffer1), []],
            [
                'text/html',
                trim($buffer2),
                [
                    '<!-- html',
                    '% -->',
                    '<script type="text/javascript" src="https://s1-www.example.com/55db9daf/dist/js/app.min.js">',
                ],
            ],
            ['text/html', trim($buffer3), ['<!-- html', '% -->', '<p>header</p>', '<p>main</p>', '<p>footer</p>']],
        ];
    }

    public function testTidyMiddlewareContainsJson(): void
    {
        $content = json_encode([
            'test' => true,
        ]);
        assert(is_string($content));

        $stack = [
            $this->getInstance(),
            static function () use ($content): ResponseInterface {
                $contentType = 'application/json';
                $response    = Factory::createResponse();
                $body        = Factory::getStreamFactory()->createStream($content);
                $response    = $response->withHeader('Content-Type', $contentType);

                return $response->withBody($body);
            },
        ];

        $response = Dispatcher::run($stack);
        $body     = $response->getBody();
        $actual   = $body->getContents();

        self::assertSame($content, $actual);
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
