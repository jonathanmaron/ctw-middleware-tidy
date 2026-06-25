<?php

declare(strict_types=1);

namespace CtwTest\Middleware\TidyMiddleware;

use Ctw\Middleware\TidyMiddleware\AbstractTidyMiddleware;
use Ctw\Middleware\TidyMiddleware\TidyMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;

/**
 * @see AbstractTidyMiddleware
 */
#[CoversClass(AbstractTidyMiddleware::class)]
final class AbstractTidyMiddlewareTest extends AbstractCase
{
    /**
     * Test that getConfig returns the documented default Tidy configuration
     * for a freshly constructed middleware instance.
     */
    public function testGetConfigReturnsDefaultConfigForNewInstance(): void
    {
        $middleware = new TidyMiddleware();

        $config = $middleware->getConfig();

        self::assertArrayHasKey('doctype', $config);
        self::assertSame('html5', $config['doctype']);
        self::assertSame('utf8', $config['char-encoding']);
    }

    /**
     * Test that setConfig overwrites the stored configuration and that
     * getConfig subsequently returns the exact array that was provided.
     */
    public function testSetConfigReplacesConfigAndReturnsSameInstance(): void
    {
        $middleware = new TidyMiddleware();

        $custom = [
            'doctype' => 'omit',
            'indent'  => true,
        ];

        $returned = $middleware->setConfig($custom);

        self::assertSame($middleware, $returned);
        self::assertSame($custom, $middleware->getConfig());
    }

    /**
     * Test that setConfig accepts an empty array and that getConfig then
     * reports the configuration as empty.
     */
    public function testSetConfigAcceptsEmptyArray(): void
    {
        $middleware = new TidyMiddleware();

        $middleware->setConfig([]);

        self::assertSame([], $middleware->getConfig());
    }

    /**
     * Test that postProcess trims surrounding whitespace from the modified
     * HTML before applying any doctype handling.
     */
    public function testPostProcessTrimsSurroundingWhitespace(): void
    {
        $middleware = new TidyMiddleware();
        $middleware->setConfig([]);

        $result = $this->invokePostProcess($middleware, '   <p>body</p>   ');

        self::assertSame('<p>body</p>', $result);
    }

    /**
     * Test that the doctype handling prepends an HTML5 doctype when the
     * configuration requests html5 and the modified HTML lacks one.
     */
    public function testDoctypePrependsHtml5DoctypeWhenMissing(): void
    {
        $middleware = new TidyMiddleware();
        $middleware->setConfig([
            'doctype' => 'html5',
        ]);

        $result = $this->invokeDoctype($middleware, '<html></html>');

        self::assertSame('<!DOCTYPE html>' . PHP_EOL . '<html></html>', $result);
    }

    /**
     * Test that the doctype handling leaves the HTML unchanged when the
     * configuration does not define a doctype key at all.
     */
    public function testDoctypeReturnsHtmlUnchangedWhenDoctypeKeyAbsent(): void
    {
        $middleware = new TidyMiddleware();
        $middleware->setConfig([
            'quiet' => true,
        ]);

        $result = $this->invokeDoctype($middleware, '<html></html>');

        self::assertSame('<html></html>', $result);
    }

    /**
     * Test that the doctype handling leaves the HTML unchanged when the
     * configured doctype is something other than html5.
     */
    public function testDoctypeReturnsHtmlUnchangedWhenDoctypeIsNotHtml5(): void
    {
        $middleware = new TidyMiddleware();
        $middleware->setConfig([
            'doctype' => 'omit',
        ]);

        $result = $this->invokeDoctype($middleware, '<html></html>');

        self::assertSame('<html></html>', $result);
    }

    /**
     * Test that the doctype handling does not add a second doctype when the
     * modified HTML already starts with an HTML5 doctype declaration.
     */
    public function testDoctypeReturnsHtmlUnchangedWhenDoctypeAlreadyPresent(): void
    {
        $middleware = new TidyMiddleware();
        $middleware->setConfig([
            'doctype' => 'html5',
        ]);

        $html = '<!DOCTYPE html>' . PHP_EOL . '<html></html>';

        $result = $this->invokeDoctype($middleware, $html);

        self::assertSame($html, $result);
    }

    /**
     * Invoke the protected postProcess method through reflection.
     */
    private function invokePostProcess(AbstractTidyMiddleware $middleware, string $html): string
    {
        $method = new ReflectionMethod($middleware, 'postProcess');
        $result = $method->invoke($middleware, $html);
        self::assertIsString($result);

        return $result;
    }

    /**
     * Invoke the private doctype method through reflection.
     */
    private function invokeDoctype(AbstractTidyMiddleware $middleware, string $html): string
    {
        $method = new ReflectionMethod($middleware, 'doctype');
        $result = $method->invoke($middleware, $html);
        self::assertIsString($result);

        return $result;
    }
}
