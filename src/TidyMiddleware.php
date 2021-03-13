<?php
declare(strict_types=1);

namespace Ctw\Middleware\TidyMiddleware;

use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use tidy;

class TidyMiddleware extends AbstractTidyMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$this->containsHtml($response)) {
            return $response;
        }

        $htmlOriginal = $response->getBody()->getContents();

        if (0 === strlen($htmlOriginal)) {
            return $response;
        }

        $minifier = tidy_parse_string($htmlOriginal, $this->getConfig(), 'utf8');
        assert($minifier instanceof tidy);

        if (!tidy_clean_repair($minifier)) {
            return $response;
        }

        // @phpstan-ignore-next-line
        $htmlModified = (string) $minifier->html();
        $htmlModified = trim($htmlModified);

        [$in, $out, $diff] = $this->getSuffixStatistics($htmlOriginal, $htmlModified);

        $htmlModified .= PHP_EOL . sprintf(self::SUFFIX, $in, $out, $diff);

        unset($minifier, $htmlOriginal);

        $body = Factory::getStreamFactory()->createStream($htmlModified);

        return $response->withBody($body);
    }
}
