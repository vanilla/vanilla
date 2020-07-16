<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Pagination;

use Exception;
use Garden\Http\HttpClient;
use League\Uri\Http;
use Traversable;
use Vanilla\Utility\UrlUtils;

/**
 * An iterator the fetches API results and iterates using it's pagination headers.
 */
class ApiPaginationIterator implements \IteratorAggregate {

    /** @var HttpClient */
    private $httpClient;

    /** @var string */
    private $initialUrl;

    /** @var string|null */
    private $currentUrl;

    /**
     * Constructor.
     *
     * @param HttpClient $httpClient
     * @param string $initialUrl
     */
    public function __construct(HttpClient $httpClient, string $initialUrl) {
        $this->httpClient = $httpClient;
        $this->initialUrl = $initialUrl;
        $this->currentUrl = $initialUrl;
    }

    /**
     * Internal generator function. This is our iterator.
     *
     * @return \Generator
     */
    protected function internalGenerator(): \Generator {
        if ($this->currentUrl === null) {
            $this->currentUrl = $this->initialUrl;
        }

        while ($this->currentUrl !== null) {
            $result = $this->httpClient->get($this->currentUrl);
            $body = $result->getBody();
            yield $body;

            $linkHeaders = WebLinking::parseLinkHeaders($result->getHeader(WebLinking::HEADER_NAME));
            $next = $linkHeaders['next'] ?? null;

            $this->currentUrl = $next;
        }
    }

    /**
     * @inheritdoc
     */
    public function getIterator() {
        return $this->internalGenerator();
    }
}
