<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Embeds;

use Exception;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Vanilla\InjectableInterface;

/**
 * Basic embed class.
 */
abstract class AbstractEmbed implements InjectableInterface {

    /** @var bool Allow access to local resources? */
    private $allowLocal = false;

    /** @var HttpRequest HTTP request interface. */
    private $httpRequest;

    /** @var string[] Valid domains for this embed type. */
    protected $domains = [];

    /** @var string Rendered type of this embed (e.g. video, image). */
    protected $renderType;

    /** @var string[] Client-side scripts for handling this embed type.  */
    protected $scripts = [];

    /** @var string Primary type for this embed (e.g. youtube, twitter). */
    protected $type;

    /**
     * Get whether or not we're allowing access to local resources.
     *
     * @return bool
     */
    public function getAllowLocal(): bool {
        return $this->allowLocal;
    }

    /**
     * Get valid domains.
     *
     * @return string[] Returns the domains.
     */
    public function getDomains(): array {
        return $this->domains;
    }

    /**
     * Get the render type for this embed (e.g. video, image).
     * @return string
     */
    public function getRenderType(): string {
        return $this->renderType;
    }

    /**
     * Get scripts for this embed type.
     *
     * @return array
     */
    public function getScripts(): array {
        return $this->scripts;
    }

    /**
     * Get the type for this embed (e.g. youtube, twitter).
     *
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Get valid URL schemas for requests.
     *
     * @return string[]
     */
    public function getUrlSchemas() {
        $result = ['http', 'https'];

        if ($this->allowLocal) {
            $result[] = 'file';
        }

        return $result;
    }

    /**
     * Send an HTTP request.
     *
     * @param string $method The HTTP method of the request.
     * @param string $url The URL where the request will be sent.
     * @param string|array $body The body of the request.
     * @param array $headers An array of http headers to be sent with the request.
     * @return HttpResponse
     */
    protected function httpRequest(string $url = '', $body = '', array $headers = [], string $method = HttpRequest::METHOD_GET): HttpResponse {
        $this->httpRequest->setMethod(strtoupper($method));
        $this->httpRequest->setUrl($url);
        $this->httpRequest->setBody($body);
        $this->httpRequest->setHeaders($headers);
        $result = $this->httpRequest->send();
        return $result;
    }

    /**
     * Attempt to get the width and height of an image.
     *
     * @param string $url Full image URL.
     * @return array An array of two elements: width and height. Either element can be an integer or null.
     * @throws Exception if the URL is invalid.
     */
    protected function imageSize(string $url): array {
        $size = [null, null];

        // Make sure the URL is valid.
        $urlParts = parse_url($url);
        if ($urlParts === false || !in_array(val('scheme', $urlParts), ['http', 'https'])) {
            throw new Exception('Invalid URL.', 400);
        }

        $result = getimagesize($url);
        if (is_array($result) && count($result) >= 2) {
            $size = [$result[0], $result[1]];
        }

        return $size;
    }

    /**
     * Attempt to parse the contents of a URL for data relevant to this embed type.
     *
     * @param string $url Target URL.
     * @return array|null An array of data if successful. Otherwise, null.
     */
    abstract function matchUrl(string $url);

    /**
     * Normalize oEmbed fields.
     *
     * @param array $oembed
     * @return array
     */
    protected function normalizeOembed(array $oembed): array {
        // Simple renaming.
        $fields = ['name' => 'title', 'photoUrl' => 'thumbnail_url'];
        foreach ($fields as $new => $original) {
            $val = $oembed[$original] ?? null;
            $oembed[$new] = $val;
            unset($oembed[$original]);
        }

        $attributes = [];

        foreach (['width', 'height'] as $sizeAttribute) {
            $thumbField = "thumbnail_{$sizeAttribute}";
            $primary = $oembed[$sizeAttribute] ?? null;
            $thumb = $oembed[$thumbField] ?? null;
            if ($primary && $thumb) {
                $attributes[$thumbField] = $thumb;
            } elseif ($thumb) {
                $primary = $thumb;
            }
            $oembed[$sizeAttribute] = $primary;
            unset($oembed[$thumbField]);
        }

        if (!empty($attributes)) {
            $oembed['attributes'] = $attributes;
        }
        return $oembed;
    }

    /**
     * Get oEmbed data from a URL.
     *
     * @param string $url oEmbed data URL.
     * @return array Filtered oEmbed data.
     * @throws Exception if the URL is invalid.
     * @throws Exception if the URL failed to load.
     */
    protected function oembed(string $url): array {
        $result = [];

        // Make sure the URL is valid.
        $urlParts = parse_url($url);
        if ($urlParts === false || !in_array(val('scheme', $urlParts), ['http', 'https'])) {
            throw new Exception('Invalid URL.', 400);
        }

        $response = $this->httpRequest($url);
        if (!$response->isSuccessful()) {
            throw new Exception('Failed to load oEmbed URL');
        }

        $responseBody = $response->getBody();
        if (is_array($responseBody)) {
            $validAttributes = ['type', 'version', 'title', 'author_name', 'author_url', 'provider_name', 'provider_url',
                'cache_age', 'thumbnail_url', 'thumbnail_width', 'thumbnail_height'];

            $type = $responseBody['type'] ?? null;
            switch ($type) {
                case 'photo':
                case 'video':
                case 'rich':
                    $validAttributes = array_merge(['url', 'width', 'height'], $validAttributes);
                    break;
            }

            // Make it easier to compare by key.
            $validAttributes = array_combine($validAttributes, $validAttributes);
            $result = array_intersect_key($responseBody, $validAttributes);
        }

        return $result;
    }

    /**
     * Generate markup to render this embed, based on provided data.
     *
     * @param array $data Structured data for this embed type.
     * @return string Embed code.
     */
    abstract function renderData(array $data): string;

    /**
     * Set whether or not access is allowed to local resources.
     *
     * @param bool $allowLocal
     * @return $this
     */
    public function setAllowLocal(bool $allowLocal) {
        $this->allowLocal = $allowLocal;
        return $this;
    }

    /**
     * @param HttpRequest $httpRequest
     */
    public function setDependencies(HttpRequest $httpRequest) {
        $this->httpRequest = $httpRequest;
    }
}
