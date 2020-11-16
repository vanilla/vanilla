<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\BrightcoveEmbed;

/**
 * BrightcoveEmbedFactory class.
 */
class BrightcoveEmbedFactory extends AbstractEmbedFactory {

    /** @var array DOMAINS */
    const DOMAINS = [
        'players.brightcove.net',
    ];

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $path = parse_url($url, PHP_URL_HOST);
        $videoID = $this->videoIDFromUrl($url);
        $playerMeta = $this->playerMetaFromUrl($url);

        return new BrightcoveEmbed([
            'embedType' => BrightcoveEmbed::TYPE,
            'domain' => $path,
            'url' => $url,
            "videoID" => $videoID,
            "account" => $playerMeta['account'],
            "playerID" => $playerMeta['player'],
            "playerEmbed" => $playerMeta['embed'],
        ]);
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
        return "`[\w]+\/[\w]+_[\w]+\/index\.html$`";
    }

    /**
     * Given a Brightcove video URL, extract its video ID.
     *
     * @param string $url
     * @return string|null
     */
    private function videoIDFromUrl(string $url): ?string {
        $parameters = [];
        parse_str(parse_url($url, PHP_URL_QUERY) ?? "", $parameters);

        return $parameters["videoId"] ?? null;
    }

    /**
     * Given a Brightcove video URL, extract account, player ID and embed information.
     *
     * @param string $url
     * @return array
     */
    private function playerMetaFromUrl(string $url): array {
        $path = parse_url($url, PHP_URL_PATH) ?? "";
        $pathSegments = explode('/', $path);
        $playerSegment = $pathSegments[2] ?? '';
        $playerInfo = explode('_', $playerSegment);

        return [
            'account' => $pathSegments[1],
            'player' => $playerInfo[0],
            'embed' => $playerInfo[1],
        ];
    }
}
