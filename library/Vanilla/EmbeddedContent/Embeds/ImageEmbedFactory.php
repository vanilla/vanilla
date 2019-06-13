<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Http\HttpClient;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ServerException;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;

/**
 * Factory for the ImgurEmbed.
 */
class ImageEmbedFactory extends AbstractEmbedFactory {

    /** @var HttpClient */
    private $httpClient;

    /**
     * DI.
     *
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient) {
        $this->httpClient = $httpClient;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedDomains(): array {
        return [self::WILDCARD_DOMAIN];
    }

    /**
     * We pass along to the oembed service. If it can't parse the URL, then we definitely can't.
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string {
        // Technically any URL can be an image.
        return '/.+/';
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     * @throws ClientException If we are trying to embed an invalid image.
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        // Get information about the image with a HEAD request.
        $response = $this->httpClient->head($url);

        // Let's do some super inconsistent validation of what file types are allowed.
        $contentType = $response->getHeaderLines('content-type');
        $contentType = reset($contentType);
        $isImage = $contentType && substr($contentType, 0, 6) === 'image/';
        if (!$isImage) {
            throw new ClientException("Content type $contentType is not a supported media type.", 415);
        }

        // Dimensions
        $result = getimagesize($url);
        $height = null;
        $width = null;
        if (is_array($result) && count($result) >= 2) {
            [$width, $height] = $result;
        }
        $data = [
            'url' => $url,
            'type' => ImageEmbed::TYPE,
            'name' => t('Untitled Image'),
            'height' => $height,
            'width' => $width,
        ];

        return new ImageEmbed($data);
    }

    /**
     * @inheritdoc
     */
    public function createEmbedFromData(array $data): AbstractEmbed {
        return new ImageEmbed($data);
    }
}
