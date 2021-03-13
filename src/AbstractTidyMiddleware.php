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
}
