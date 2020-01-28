<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Addons\OpenApiEmbed;

/**
 * Plugin for the OpenAPI embed.
 */
class OpenApiEmbedPlugin extends \Gdn_Plugin {

    /**
     * Scrape another OpenAPI spec to get around CORS.
     *
     * @param array $body
     */
    public function openApiApiController_post_scrape(array $body) {

    }
}
