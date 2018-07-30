<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use Exception;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use InvalidArgumentException;
use Vanilla\Metadata\Parser\Parser;

class PageScraper {

    /** @var HttpRequest */
    private $httpRequest;

    /** @var array */
    private $metadataParsers = [];

    /** @var array Valid URL schemes. */
    protected $validSchemes = ['http', 'https'];

    /**
     * PageInfo constructor.
     *
     * @param HttpRequest $httpRequest
     */
    public function __construct(HttpRequest $httpRequest) {
        $this->httpRequest = $httpRequest;
    }

    /**
     * Fetch page info from a URL.
     *
     * @param string $url Target URL.
     * @return array Structured page information.
     * @throws Exception
     */
    public function pageInfo(string $url): array {
        $response = $this->getUrl($url);

        if (!$response->isResponseClass('2xx')) {
            throw new Exception('Unable to get URL contents.');
        }

        $document = new DOMDocument();
        $rawBody = $response->getRawBody();

        // Add charset information for HTML 5 pages.
        // https://stackoverflow.com/questions/39148170/utf-8-with-php-domdocument-loadhtml#39148511
        if (!preg_match('`\s*<?xml`', $rawBody)) {
            $encoding = mb_detect_encoding($rawBody);
            $rawBody = "<?xml version=\"1.0\" encoding=\"$encoding\"?>$rawBody";
        }

        $err = libxml_use_internal_errors(true);
        $loadResult = $document->loadHTML($rawBody);
        libxml_use_internal_errors($err);
        if ($loadResult === false) {
            throw new Exception('Failed to load document for parsing.');
        }

        $metaData = $this->parseMetaData($document);
        $info = array_merge([
            'Title' => '',
            'Description' => '',
            'Images' => []
        ], $metaData);

        if (empty($info['Title'])) {
            $titleTags = $document->getElementsByTagName('title');
            $titleTag = $titleTags->item(0);
            if ($titleTag) {
                $info['Title'] = $titleTag->textContent;
            }
        }

        if (empty($info['Description'])) {
            $metaTags = $document->getElementsByTagName('meta');
            $description = $this->parseDescription($document, $metaTags);
            if ($description) {
                $info['Description'] = $description;
            }
        }

        $info['Url'] = $url;
        $info['Title'] = htmlEntityDecode($info['Title']);
        $info['Description'] = htmlEntityDecode($info['Description']);

        return $info;
    }

    /**
     * Send an HTTP GET request.
     *
     * @param string $url The URL where the request will be sent.
     * @return HttpResponse
     */
    private function getUrl(string $url): HttpResponse {
        $urlParts = parse_url($url);
        if ($urlParts === false) {
            throw new InvalidArgumentException('Invalid URL.');
        } elseif (!in_array(val('scheme', $urlParts), $this->validSchemes)) {
            throw new InvalidArgumentException('Unsupported URL scheme.');
        }

        $this->httpRequest->setMethod(HttpRequest::METHOD_GET);
        $this->httpRequest->setHeader('User-Agent', $this->userAgent());
        $this->httpRequest->setUrl($url);
        $result = $this->httpRequest->send();
        return $result;
    }

    /**
     * Parse the document to find a suitable description.
     *
     * @param DOMDocument $document
     * @param DOMNodeList $meta
     * @return string|null
     */
    private function parseDescription(DOMDocument $document, DomNodeList $meta) {
        $result = null;

        // Try to get it from the meta.
        /** @var DOMElement $tag */
        foreach ($meta as $tag) {
            if ($tag->hasAttribute('name') === false) {
                continue;
            } elseif ($tag->getAttribute('name') === 'description') {
                $result = $tag->getAttribute('content');
            }
        }

        // Try looking in paragraph tags.
        if ($result === null) {
            $paragraphs = $document->getElementsByTagName('p');
            foreach ($paragraphs as $tag) {
                if (strlen($tag->textContent) > 150) {
                    $result = $tag->textContent;
                    break;
                }
            }
            if ($result && strlen($result) > 400) {
                $result = sliceParagraph($result, 400);
            }
        }

        if ($result === null) {
            $paragraphs = $paragraphs ?? $document->getElementsByTagName('p');
            foreach ($paragraphs as $tag) {
                if (trim($tag->textContent) !== '') {
                    $result = $tag->textContent;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Iterate through the configured metadata parsers and gather document information.
     *
     * @param DOMDocument $document
     * @return array
     */
    private function parseMetaData(DOMDocument $document): array {
        $result = [];
        /** @var Parser $parser */
        foreach ($this->metadataParsers as $parser) {
            $parsed = $parser->parse($document);
            $result = array_merge($result, $parsed);
        }
        return $result;
    }

    /**
     * Register a parser for document metadata.
     *
     * @param Metadata\Parser\Parser $parser
     * @return array
     */
    public function registerMetadataParser(Parser $parser) {
        $this->metadataParsers[] = $parser;
        return $this->metadataParsers;
    }

    /**
     * User agent to identify the client to remote services.
     *
     * @return string
     */
    private function userAgent() {
        $version = defined('APPLICATION_VERSION') ? APPLICATION_VERSION : '0.0';
        return "Vanilla/{$version}";
    }
}
