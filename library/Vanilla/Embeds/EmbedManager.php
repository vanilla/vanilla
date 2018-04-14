<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Embeds;

use Garden\Container\Container;

/**
 * Embeds go through
 */
class EmbedManager {
    /**
     * @var AbstractEmbed
     */
    private $defaultEmbed;

    public function __construct(Container $container) {
        $this->addEmbed(new TwitterEmbed());
    }

    /**
     * @param string $url
     */
    public function matchUrl(string $url) {
        // Parse the URL and find the embed for the domain.
        $domain = parse_url($url, PHP_URL_HOST);
        // Error here.

        // No specific embed so use the default web scrape embed.

        /* @var AbstractEmbed $embed */
        $embed = null;

        $data = $embed->matchUrl($url);

        if ($data === null) {
            // The specific embed scraper didn't match the URL so use the default.
            $data = $this->defaultEmbed->matchUrl($url);
        }

        return $data;
    }

    public function renderData(array $data) {

    }

    public function addEmbed(AbstractEmbed $embed) {

    }

    /**
     * Get the defaultEmbed.
     *
     * @return mixed Returns the defaultEmbed.
     */
    public function getDefaultEmbed(): mixed {
        return $this->defaultEmbed;
    }

    /**
     * Set the defaultEmbed.
     *
     * @param mixed $defaultEmbed
     * @return $this
     */
    public function setDefaultEmbed(mixed $defaultEmbed) {
        $this->defaultEmbed = $defaultEmbed;
        return $this;
    }
}
