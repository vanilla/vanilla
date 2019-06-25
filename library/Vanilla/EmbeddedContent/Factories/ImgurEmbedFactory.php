<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\ImgurEmbed;
use Vanilla\Utility\HtmlParserTrait;

/**
 * Factory for the ImgurEmbed.
 */
class ImgurEmbedFactory extends AbstractEmbedFactory {

    use HtmlParserTrait;

    const DOMAINS = ["imgur.com"];

    const OEMBED_URL_BASE = "https://api.imgur.com/oembed";

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
        return self::DOMAINS;
    }

    /**
     * We pass along to the oembed service. If it can't parse the URL, then we definitely can't.
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string {
        // Imgur paths are incredibly complicated.
        // See https://www.reddit.com/r/redditdev/comments/35bb7i/imgur_link_format/ for examples.
        // We will pretty much always hit their API, so a pretty freeform slug should suffice.
        return "`.*`";
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $response = $this->httpClient->get(
            self::OEMBED_URL_BASE,
            ["url" => $url]
        );

        // Example Response JSON
        // phpcs:disable Generic.Files.LineLength
        // {
        //     "version": "1.0",
        //     "type": "rich",
        //     "provider_name": "Imgur",
        //     "provider_url": "https://imgur.com",
        //     "width": 540,
        //     "height": 500,
        //     "html": "<blockquote class=\"imgur-embed-pub\" lang=\"en\" data-id=\"a/Pt2cHff\"><a href=\"https://imgur.com/a/Pt2cHff\">Very scary birb </a></blockquote><script async src=\"//s.imgur.com/min/embed.js\" charset=\"utf-8\"></script>",
        //     "author_name": "monalistic",
        //     "author_url": "https://imgur.com/user/monalistic"
        // }
        // phpcs:enable Generic.Files.LineLength

        $blockAttributes = $this->parseSimpleAttrs($response["html"], "blockquote");

        $data = [
            'embedType' => ImgurEmbed::TYPE,
            'url' => $url,
            'name' => $response['title'] ?? '',
            'height' => $response['height'] ?? null,
            'width' => $response['width'] ?? null,
            "imgurID" => $blockAttributes["data-id"] ?? null,
        ];

        return new ImgurEmbed($data);
    }
}
