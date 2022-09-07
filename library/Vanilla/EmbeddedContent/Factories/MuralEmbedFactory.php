<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Web\Exception\ClientException;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\MuralEmbed;

/**
 * MuralEmbedFactory class.
 */
class MuralEmbedFactory extends AbstractEmbedFactory
{
    /** @var array DOMAINS */
    const DOMAINS = ["app.mural.co", "app.mural.com"];

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed
    {
        $pattern = $this->getSupportedPathRegex($url);

        // Provided url needs to validate against regex pattern.
        if (preg_match($pattern, $url) === 1) {
            $urlDomain = parse_url($url, PHP_URL_HOST);
            if (in_array($urlDomain, $this->getSupportedDomains())) {
                return new MuralEmbed([
                    "embedType" => MuralEmbed::TYPE,
                    "url" => $url,
                ]);
            }
        }

        $message = "Invalid url '$url' for Mural embed";
        throw new ClientException($message);
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedDomains(): array
    {
        return self::DOMAINS;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string
    {
        return "`/embed/.+$`";
    }
}
