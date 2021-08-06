<?php
declare(strict_types=1);

namespace Ctw\Middleware\TidyMiddleware;

use Ctw\Middleware\AbstractMiddleware;

abstract class AbstractTidyMiddleware extends AbstractMiddleware
{
    /**
     * Default Tidy config (overwrite in site config)
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

    protected function postProcess(string $htmlModified): string
    {
        $htmlModified = $this->trim($htmlModified);
        $htmlModified = $this->doctype($htmlModified);

        return $htmlModified;
    }

    private function trim(string $htmlModified): string
    {
        return trim($htmlModified);
    }

    /**
     * Tidy removes the doctype when parsing HTML5 (bug?).
     * This causes the browser to switch to quirks mode, which is undesirable.
     * This method re-adds the doctype in the case of HTML5.
     *
     * @param string $htmlModified
     *
     * @return string
     */
    private function doctype(string $htmlModified): string
    {
        $config = $this->getConfig();

        if (!isset($config['doctype'])) {
            return $htmlModified;
        }

        if ('html5' !== $config['doctype']) {
            return $htmlModified;
        }

        $prefix = '<!DOCTYPE html>';

        if ($prefix === substr($htmlModified, 0, strlen($prefix))) {
            return $htmlModified;
        }

        return $prefix . PHP_EOL . $htmlModified;
    }
}
