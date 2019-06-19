<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\CodePenEmbed;
use Vanilla\EmbeddedContent\EmbedUtils;
use Vanilla\Utility\HtmlParserTrait;

/**
 * Factory for the CodePenEmbed.
 */
class CodePenEmbedFactory extends AbstractEmbedFactory {

    use HtmlParserTrait;

    const CODEPEN_UI = 'codepen.io';
    const OEMBED_URL_BASE = "https://codepen.io/api/oembed";

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
        return [self::CODEPEN_UI];
    }

    /**
     * We pass along to the oembed service. If it can't parse the URL, then we definitely can't.
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string {
        return '/\/pen\/[a-zA-Z0-9]+$/';
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     * @throws \Exception If the scrape fails.
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $response = $this->httpClient->get(
            self::OEMBED_URL_BASE,
            [
                'url' => $url,
                'format' => 'json'
            ]
        );

        // Example Response JSON
        // {
        //    "success": true,
        //    "type": "rich",
        //    "version": "1.0",
        //    "provider_name": "CodePen",
        //    "provider_url": "https://codepen.io",
        //    "title": "Smoke Effect",
        //    "author_name": "Hiroshi Muto",
        //    "author_url": "https://codepen.io/hiroshi_m/",
        //    "height": "300",
        //    "width": "800",
        //    "thumbnail_width": "384",
        //    "thumbnail_height": "225",
        //    "thumbnail_url": "https://screenshot.codepen.io/3290550.YoKYVv.small.09ec4a42-8ad0-4d6e-a7d8-c0897ad7f34f.png",
        //    "html": "<iframe
        //          id='cp_embed_YoKYVv'
        //          src='https://codepen.io/hiroshi_m/embed/preview/YoKYVv?height=300&amp;slug-hash=YoKYVv&amp;default-tabs=css,result&amp;host=https://codepen.io'
        //      ></iframe>"
        // }

        [$height, $width] = EmbedUtils::extractDimensions($response);
        $frameAttrs = $this->parseSimpleAttrs($response['html'] ?? '', 'iframe') ?? [];
        $data = [
            'embedType' => CodePenEmbed::TYPE,
            'url' => $url,
            'name' => $response['title'] ?? null,
            'height' => $height,
            'width' => $width,
            'codepenID' => $frameAttrs['id'] ?? null,
            'frameSrc' => $frameAttrs['src'] ?? null,
        ];

        return new CodePenEmbed($data);
    }
}
