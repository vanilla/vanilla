<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Embeds;

use InvalidArgumentException;

/**
 * Manage scraping embed data and generating markup.
 */
class EmbedManager {
    /** @var AbstractEmbed The default embed type. */
    private $defaultEmbed;

    /** @var AbstractEmbed[] Available embed types. */
    private $embeds = [];

    /**
     * Is the provided domain associated with the embed type?
     *
     * @param string $domain The domain to test.
     * @param AbstractEmbed $embed An embed object to test against.
     * @return bool
     */
    private function isEmbedDomain(string $domain, AbstractEmbed $embed): bool {
        $result = false;
        foreach ($embed->getDomains() as $testDomain) {

        }
        return $result;
    }

    /**
     * @param string $url
     */
    public function matchUrl(string $url) {
        // Parse the URL and find the embed for the domain.
        $domain = parse_url($url, PHP_URL_HOST);

        if (!$domain) {
            throw new InvalidArgumentException('Invalid URL.');
        }

        // No specific embed so use the default web scrape embed.
        foreach ($this->embeds as $testEmbed) {
            if ($this->isEmbedDomain($domain, $testEmbed)) {
                $embed = $testEmbed;
                break;
            }
        }

        /* @var AbstractEmbed $embed */
        $embed = null;

        $data = $embed->matchUrl($url);

        if ($data === null) {
            // The specific embed scraper didn't match the URL so use the default.
            $data = $this->defaultEmbed->matchUrl($url);
        }

        return $data;
    }

    /**
     * Given structured data, generate markup for an embed.
     *
     * @param array $data
     * @return string
     */
    public function renderData(array $data): string {
    }

    /**
     * Add a new embed type.
     *
     * @param AbstractEmbed $embed
     * @return $this
     */
    public function addEmbed(AbstractEmbed $embed) {
        $this->embeds[] = $embed;
        return $this;
    }

    /**
     * Get the default embed type.
     *
     * @return AbstractEmbed Returns the defaultEmbed.
     */
    public function getDefaultEmbed(): AbstractEmbed {
        return $this->defaultEmbed;
    }

    /**
     * Set the defaultEmbed.
     *
     * @param AbstractEmbed $defaultEmbed
     * @return $this
     */
    public function setDefaultEmbed(AbstractEmbed $defaultEmbed) {
        $this->defaultEmbed = $defaultEmbed;
        return $this;
    }
}
