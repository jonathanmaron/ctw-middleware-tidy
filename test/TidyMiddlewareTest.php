<?php

declare(strict_types=1);

namespace CtwTest\Middleware\TidyMiddleware;

use Ctw\Middleware\TidyMiddleware\AbstractTidyMiddleware;
use Ctw\Middleware\TidyMiddleware\TidyMiddleware;
use Ctw\Middleware\TidyMiddleware\TidyMiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Http\Message\ResponseInterface;

// Load the namespaced tidy_clean_repair() override so the repair-failure guard
// in TidyMiddleware::process() can be exercised deterministically. The override
// delegates to the real function unless a test opts in via the global flag.
require_once __DIR__ . '/TestAsset/TidyFunctionOverride.php';

/**
 * @see TidyMiddleware
 */
#[CoversClass(TidyMiddleware::class)]
#[CoversClass(AbstractTidyMiddleware::class)]
#[UsesClass(TidyMiddlewareFactory::class)]
final class TidyMiddlewareTest extends AbstractCase
{
    /**
     * Reset the repair-failure override flag before every test so that one
     * test cannot leak the forced-failure state into the next.
     */
    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['__ctw_force_repair_fail']);
    }

    /**
     * Reset the repair-failure override flag after every test.
     */
    protected function tearDown(): void
    {
        unset($GLOBALS['__ctw_force_repair_fail']);

        parent::tearDown();
    }

    /**
     * Test that the middleware tidies HTML responses, leaving an empty
     * response empty and injecting the expected markup and statistics suffix
     * for non-empty HTML responses.
     *
     * @param list<string> $expected
     */
    #[DataProvider('htmlResponseProvider')]
    public function testProcessTidiesHtmlResponseAccordingToContentType(
        string $contentType,
        string $content,
        array $expected
    ): void {
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
            self::assertStringContainsString($needle, $haystack);
        }
    }

    /**
     * @return array<string, array{string, string, list<string>}>
     */
    public static function htmlResponseProvider(): array
    {
        $buffer1 = (string) file_get_contents(__DIR__ . '/TestAsset/test0_input.htm');
        $buffer2 = (string) file_get_contents(__DIR__ . '/TestAsset/test1_input.htm');
        $buffer3 = (string) file_get_contents(__DIR__ . '/TestAsset/test2_input.htm');

        return [
            'empty html response stays empty'        => ['text/html', trim($buffer1), []],
            'large html response is tidied'          => [
                'text/html',
                trim($buffer2),
                [
                    '<!-- html',
                    '% -->',
                    '<script type="text/javascript" src="https://s1-www.example.com/55db9daf/dist/js/app.min.js">',
                ],
            ],
            'html5 sectioning response is preserved' => [
                'text/html',
                trim($buffer3),
                ['<!-- html', '% -->', '<p>header</p>', '<p>main</p>', '<p>footer</p>'],
            ],
        ];
    }

    /**
     * Test that a non-HTML JSON response passes through the middleware
     * unchanged because its content type is not an HTML MIME type.
     */
    public function testProcessLeavesJsonResponseUnchanged(): void
    {
        $content = json_encode([
            'test' => true,
        ]);
        self::assertIsString($content);

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

    /**
     * Test that an HTML response without a Content-Type header is returned
     * unchanged because it is not recognized as HTML.
     */
    public function testProcessLeavesResponseWithoutContentTypeUnchanged(): void
    {
        $content = '<html><body><p>hello</p></body></html>';

        $stack = [
            $this->getInstance(),
            static function () use ($content): ResponseInterface {
                $response = Factory::createResponse();
                $body     = Factory::getStreamFactory()->createStream($content);

                return $response->withBody($body);
            },
        ];

        $response = Dispatcher::run($stack);
        $actual   = $response->getBody()
            ->getContents();

        self::assertSame($content, $actual);
    }

    /**
     * Test that an HTML response with an empty body is returned unchanged
     * because there is nothing for Tidy to process.
     */
    public function testProcessLeavesEmptyHtmlBodyUnchanged(): void
    {
        $stack = [
            $this->getInstance(),
            static function (): ResponseInterface {
                $response = Factory::createResponse();
                $body     = Factory::getStreamFactory()->createStream('');
                $response = $response->withHeader('Content-Type', 'text/html');

                return $response->withBody($body);
            },
        ];

        $response = Dispatcher::run($stack);
        $actual   = $response->getBody()
            ->getContents();

        self::assertSame('', $actual);
    }

    /**
     * Test that the middleware returns the original response untouched, with
     * no statistics suffix, when the Tidy repair step reports failure.
     */
    public function testProcessReturnsOriginalResponseWhenRepairFails(): void
    {
        $content = '<html><body><p>hello</p></body></html>';

        $GLOBALS['__ctw_force_repair_fail'] = true;

        $stack = [
            $this->getInstance(),
            static function () use ($content): ResponseInterface {
                $response = Factory::createResponse();
                $body     = Factory::getStreamFactory()->createStream($content);
                $response = $response->withHeader('Content-Type', 'text/html');

                return $response->withBody($body);
            },
        ];

        $response = Dispatcher::run($stack);

        // process() consumes the body stream via getContents() before the
        // repair-failure guard returns the original response, leaving the
        // pointer at end-of-stream. Rewind to read the untouched content back.
        $body = $response->getBody();
        $body->rewind();

        $actual = $body->getContents();

        self::assertSame($content, $actual);
        self::assertStringNotContainsString('<!-- html', $actual);
    }

    /**
     * Build a TidyMiddleware instance configured through its factory, using
     * the documented default Tidy options.
     */
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
