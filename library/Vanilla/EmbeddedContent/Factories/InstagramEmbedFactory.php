<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\InstagramEmbed;

/**
 * Factory for InstagramEmbed.
 *
 * Please note, previously this embed would go to the instagram OEmbed API (legacy).
 * @see https://developers.facebook.com/docs/instagram/oembed-legacy
 * That endpoint was removed and replaced with a new API that requires access credentials.
 * @see https://developers.facebook.com/docs/instagram/oembed
 *
 * The rate limits seem troublesome and we don't _actually_ need their API to give their JS client library the ability
 * to create a post.
 *
 * As a result we just parse the URL and let their JS library handle the rest now.
 */
class InstagramEmbedFactory extends AbstractEmbedFactory {

    const NAME = "Instagram Post";
    const DOMAINS = ["instagram.com", "instagr.am"];

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
        return "`/?p/.*`";
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $data = [
            "embedType" => InstagramEmbed::TYPE,
            "url" => $url,
            "postID" => $this->idFromUrl($url),
            // Stubbed.
            "name" => self::NAME,
        ];

        return new InstagramEmbed($data);
    }

    /**
     * Given an Instagram post URL, extract the post ID.
     *
     * @param string $url
     *
     * @return string|null
     */
    private function idFromUrl(string $url): ?string {
        return preg_match("`/?p/(?<postID>[\w-]+)`", parse_url($url, PHP_URL_PATH) ?? "", $matches) ? $matches["postID"] : null;
    }
}
