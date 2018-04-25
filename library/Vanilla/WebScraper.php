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

class WebScraper {

    /** @var HttpRequest */
    private $httpRequest;

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
        $info = [
            'Title' => '',
            'Description' => '',
            'Images' => []
        ];

        $response = $this->getUrl($url);

        if (!$response->isResponseClass('2xx')) {
            throw new Exception('Unable to get URL contents.');
        }

        $document = new DOMDocument();
        $rawBody = $response->getRawBody();
        $loadResult = $document->loadHTML($rawBody);
        if ($loadResult === false) {
            throw new Exception('Failed to load document for parsing.');
        }
        $meta = $document->getElementsByTagName('meta');

        $openGraph = $this->parseOpengraphMeta($meta);
        if ($openGraph) {
            $info = array_merge($info, $openGraph);
        }

        if (empty($info['Title'])) {
            $titleTags = $document->getElementsByTagName('title');
            $titleTag = $titleTags->item(0);
            if ($titleTag) {
                $info['Title'] = $titleTag->textContent;
            }
        }

        if (empty($info['Description'])) {
            $description = $this->parseDescription($document, $meta);
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
     * Parse meta tags for OpenGraph info.
     *
     * @param DOMNodeList $meta A list of meta tags in the document.
     * @return array
     */
    private function parseOpenGraphMeta(DOMNodeList $meta): array {
        $result = [];
        $ogTags = [];

        /** @var DOMElement $tag */
        foreach ($meta as $tag) {
            if ($tag->hasAttribute('property') === false) {
                continue;
            } elseif (substr($tag->getAttribute('property'), 0, 3) !== 'og:') {
                continue;
            }

            $property = $tag->getAttribute('property');
            $content = $tag->getAttribute('content');
            $ogTags[] = [
                'property' => $property,
                'content' => $content
            ];

            if ($property === 'og:title') {
                $result['Title'] = $content;
            } elseif ($property === 'og:description') {
                $result['Description'] = $content;
            }
        }

        // Harvest those OpenGraph images.
        foreach ($ogTags as $node) {
            $property = $node['property'];
            $content = $node['content'];
            if ($property == 'og:image') {
                // Only allow valid URLs.
                if (filter_var($content, FILTER_VALIDATE_URL) === false) {
                    continue;
                }
                if (!array_key_exists('Images', $result)) {
                    $result['Images'] = [];
                }
                $result['Images'][] = $content;
            }
        }

        return $result;
    }
}
