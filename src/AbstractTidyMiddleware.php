<?php
declare(strict_types=1);

namespace Ctw\Middleware\MinifyTidy;

use Psr\Http\Message\ResponseInterface;

abstract class AbstractTidyMiddleware
{
    /**
     * Suffix added to HTML
     */
    protected const SUFFIX = '<!-- minify html: in %d b | out %d b | diff %01.4f %% -->';

    /**
     * Responses with these MIME types are HTML Responses
     */
    private const   MIME_TYPES
        = [
            'text/html',
            'application/xhtml',
        ];


    /**
     * Default HTML Tidy config (overwrite in site config)
     *
     * @var array
     */
    private array $config
        = [
            'char-encoding'    => 'utf8',
            'doctype'          => 'html5',
            //'new-blocklevel-tags' => 'article,header,footer,section,nav,aside',
            //'new-inline-tags'     => 'video,audio,canvas,ruby,rt,rp',
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

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }


    protected function containsHtml(ResponseInterface $response): bool
    {
        $mimeTypes = [
            'text/html',
            'application/xhtml',
        ];

        $header = $response->getHeader('Content-Type');

        if (0 === count($header)) {
            return false;
        }

        foreach (self::MIME_TYPES as $needle) {
            foreach ($header as $haystack) {
                $pos = strpos($haystack, $needle);
                if (is_int($pos)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return an array of statistics for use in the suffix added to the HTML
     *
     * @param string $original
     * @param string $minified
     *
     * @return array
     */
    protected function getSuffixStatistics(string $original, string $minified): array
    {
        $in      = mb_strlen($original);
        $out     = mb_strlen($minified);
        $percent = 100 * ($out / $in);
        $diff    = 100 - $percent;

        return [$in, $out, $diff];
    }
}
