<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use Exception;
use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;
use InvalidArgumentException;
use Vanilla\Metadata\Parser\Parser;
use Vanilla\Web\RequestValidator;

class PageScraper {

    /** @var HttpClient */
    private $httpClient;

    /** @var RequestValidator */
    private $requestValidator;

    /** @var array */
    private $metadataParsers = [];

    /** @var array Valid URL schemes. */
    protected $validSchemes = ['http', 'https'];

    /**
     * PageInfo constructor.
     *
     * @param HttpClient $httpClient
     * @param RequestValidator $requestValidator
     */
    public function __construct(HttpClient $httpClient, RequestValidator $requestValidator) {
        $this->httpClient = $httpClient;
        $this->requestValidator = $requestValidator;

        $this->httpClient->setDefaultHeader("User-Agent", $this->userAgent());
    }

    /**
     * Fetch page info from a URL.
     *
     * @param string $url Target URL.
     * @return array Structured page information.
     * @throws Exception
     */
    public function pageInfo(string $url): array {
        // Ensure that this function is never called during a GET request.
        // This function makes some potentially very expensive calls
        // It can also be used to force the site into an infinite loop (eg. GET page hits the scraper which hits the same page again).
        // @see https://github.com/vanilla/dev-inter-ops/issues/23
        // We've had some situations where the site gets in an infinite loop requesting itself.
        $this->requestValidator->blockRequestType('GET', __METHOD__ . ' may not be called during a GET request.');

        $response = $this->getUrl($url);

        if (!$response->isResponseClass('2xx')) {
            throw new Exception('Unable to get URL contents.');
        }

        $rawBody = $response->getRawBody();

        $document = $this->createDom($rawBody);

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
    protected function getUrl(string $url): HttpResponse {
        $urlParts = parse_url($url);
        if ($urlParts === false) {
            throw new InvalidArgumentException('Invalid URL.');
        } elseif (!in_array(val('scheme', $urlParts), $this->validSchemes)) {
            throw new InvalidArgumentException('Unsupported URL scheme.');
        }

        $result = $this->httpClient->get($url);
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

    /**
     * Create the DOM from an HTML string.
     *
     * @param string $html The HTML to create the body from.
     * @return DOMDocument Returns the document.
     * @throws Exception Throws an exception if the HTML is so malformed that it couldn't be parsed.
     */
    private function createDom(string $html): DOMDocument {
        // Add charset information for HTML 5 pages.
        // https://stackoverflow.com/questions/39148170/utf-8-with-php-domdocument-loadhtml#39148511
        if (!preg_match('`^\s*<?xml`', $html) && $encoding = mb_detect_encoding($html)) {
            $html = "<?xml version=\"1.0\" encoding=\"$encoding\"?>$html";
        }

        $document = $this->loadDocument($html);

        if (!$document->encoding && !in_array($encoding, ['ASCII'], true)) {
            $document = $this->fixDocumentEncoding($document, $html);
        }

        return $document;
    }

    /**
     * Attempt to fix the document encoding of an incorrect document.
     *
     * @param DOMDocument $document The document to fix.
     * @param string $raw The raw HTML the document was originally created with.
     * @return DOMDocument Returns a newly loaded document or the same one if the encoding could not be fixed.
     * @throws Exception
     */
    private function fixDocumentEncoding(DOMDocument $document, string $raw): DOMDocument {
        $encoding = $this->determineDocumentEncoding($document);

        if ($encoding) {
            $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
            $result = $this->loadDocument($raw);
        } else {
            $result = $document;
        }
        return $result;
    }

    /**
     * Load an HTML document from a string with some error checking.
     *
     * @param string $html The HTML string to load.
     * @return DOMDocument Returns the loaded document.
     * @throws Exception Throws an exception if libxml can't load the document.
     */
    private function loadDocument(string $html) {
        $document = new DOMDocument();

        $err = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $loadResult = $document->loadHTML($html);
        libxml_use_internal_errors($err);

        if ($loadResult === false) {
            throw new Exception('Failed to load document for parsing.', 400);
        }

        return $document;
    }

    /**
     * Try and determine a document's encoding from its content.
     *
     * @param DOMDocument $document The document to check.
     * @return string Returns the encoding or an empty string if it can't be determined.
     */
    private function determineDocumentEncoding(DOMDocument $document): string {
        $encoding = '';

        // Look in an XML declaration.
        foreach ($document->childNodes as $node) {
            if ($node instanceof \DOMProcessingInstruction) {
                if (preg_match('`encoding=[\'"]?([a-z0-9#-]+)`i', $node->textContent, $m)) {
                    $encoding = $m[1];
                }
                break;
            }
        }


        if (!$encoding) {
            // Look in a meta tag.
            foreach ($document->getElementsByTagName('meta') as $node) {
                /* @var DOMElement $node */
                if ($attr = $node->getAttribute('charset')) {
                    $encoding = $attr;
                    break;
                }
            }
        }

        if ($encoding && false !== mb_encoding_aliases($encoding)) {
            return $encoding;
        }

        return '';
    }
}
