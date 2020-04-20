<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\PanoptoEmbed;

/**
 * PanoptoEmbedFactory class.
 */
class PanoptoEmbedFactory extends AbstractEmbedFactory {

    /** @var int DEFAULT_HEIGHT */
    private const DEFAULT_HEIGHT = 360;

    /** @var int DEFAULT_WIDTH */
    private const DEFAULT_WIDTH = 640;

    /** @var array DOMAINS */
    private const DOMAINS = [
        'hosted.panopto.com',
        'ca.panopto.com',
        'cloud.panopto.eu',
        'ap.panopto.com',
    ];

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $path = parse_url($url, PHP_URL_HOST);

        return new PanoptoEmbed([
            'embedType' => PanoptoEmbed::TYPE,
            'domain' => $path,
            'url' => $url,
            'height' => self::DEFAULT_HEIGHT,
            'width' => self::DEFAULT_WIDTH,
            'sessionId' => $this->sessionIDFromUrl($url),
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
        return "`^/?Panopto/Pages/(Viewer|Embed)\.aspx`";
    }

    /**
     * Given a Panopto URL, return the session ID.
     *
     * @param string $url
     * @return string|null
     */
    private function sessionIDFromUrl(string $url): ?string {
        $parameters = [];
        parse_str(
            parse_url($url, PHP_URL_QUERY) ?? "",
            $parameters
        );
        return $parameters['id'] ?? null;
    }
}
