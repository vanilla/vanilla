<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Http\HttpClient;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\GettyImagesEmbed;
use Vanilla\EmbeddedContent\EmbedUtils;

/**
 * Factory for GettyImagesEmbed.
 */
class GettyImagesEmbedFactory extends AbstractEmbedFactory {

    const DOMAINS = ["gettyimages.ca", "gty.im", "gettyimages.com"];

    const OEMBED_URL_BASE = "https://embed.gettyimages.com/oembed";

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
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string {
        return "`^/?detail/photo/[\w-]+/\d+`";
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $photoID = $this->idFromUrl($url);

        $response = $this->httpClient->get(
            self::OEMBED_URL_BASE,
            ["url" => "https://gty.im/{$photoID}"]
        );

        // Example Response JSON
        // phpcs:disable Generic.Files.LineLength
        // {
        //     "type": "rich",
        //     "version": "1.0",
        //     "height": 360,
        //     "width": 480,
        //     "html": "<a id='lxyvCsYwR-pWsnGRRtcTFw' class='gie-single' href='http://www.gettyimages.com/detail/840894796' target='_blank' style='color:#a7a7a7;text-decoration:none;font-weight:normal !important;border:none;display:inline-block;'>Embed from Getty Images</a><script>window.gie=window.gie||function(c){(gie.q=gie.q||[]).push(c)};gie(function(){gie.widgets.load({id:'lxyvCsYwR-pWsnGRRtcTFw',sig:'hEvB-nIzdwAH4hElxYgbaupP4gPn42N1gNyunZfqD2E=',w:'480px',h:'360px',items:'840894796',caption: false ,tld:'com',is360: false })});</script><script src='//embed-cdn.gettyimages.com/widgets.js' charset='utf-8' async></script>",
        //     "title": "sunset view of Parc Jean-Drapeau",
        //     "caption": "Sunset aerial view of Jean-Drapeau Island besides Montreal city",
        //     "photographer": "Zhou Jiang",
        //     "collection": "Moment",
        //     "thumbnail_url": "http://media.gettyimages.com/photos/sunset-view-of-parc-jeandrapeau-picture-id840894796?s=170x170",
        //     "thumbnail_height": 127,
        //     "thumbnail_width": 170,
        //     "terms_of_use_url": "http://www.gettyimages.com/Corporate/Terms.aspx"
        // }
        // phpcs:enable Generic.Files.LineLength

        $data = [
            "embedType" => GettyImagesEmbed::TYPE,
            "url" => $url,
            "name" => $response["title"] ?? null,
            "height" => $response["height"] ?? null,
            "width" => $response["width"] ?? null,
            "photoUrl" => $response["thumbnail_url"] ?? null,
            "photoID" => $photoID,
        ];

        $config = $this->configFromHtml($response["html"]);
        $data = array_merge($config, $data);

        return new GettyImagesEmbed($data);
    }

    /**
     * Given Getty Images embed HTML, return an array of embed config options.
     *
     * @param string $html
     * @return array
     */
    private function configFromHtml(string $html): array {
        if (!preg_match("`gie\.widgets\.load\((?<config>{[^\}]+})\)`", $html, $matches)) {
            return [];
        }

        $dirty = str_replace("'", '"', preg_replace("`({|,)(\w+)(:)`", '$1"$2"$3', $matches["config"]));
        $raw = json_decode($dirty, true);
        if (!$raw) {
            return [];
        }

        $config = EmbedUtils::remapProperties($raw, [
            "foreignID" => "id",
            "embedSignature" => "sig",
        ]);
        return $config;
    }

    /**
     * Given a Getty Images photo URL, extract its ID.
     *
     * @param string $url
     * @return string|null
     */
    private function idFromUrl(string $url): ?string {
        return preg_match("`^/?detail/photo/[\w-]+/(?<photoID>\d+)`", parse_url($url, PHP_URL_PATH) ?? "", $matches) ? $matches["photoID"] : null;
    }
}
