<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Addons\OpenApiEmbed;

use Garden\Container\Container;
use Vanilla\Addons\OpenApiEmbed\Embeds\OpenApiEmbed;
use Vanilla\EmbeddedContent\EmbedService;

/**
 * Plugin for the OpenApi Embed.
 */
class OpenApiEmbedPlugin extends \Gdn_Plugin {

    /**
     * Register the embed.
     *
     * @param Container $dic
     */
    public function container_init(Container $dic) {
        $dic->rule(EmbedService::class)
            ->addCall('registerEmbed', [OpenApiEmbed::class, OpenApiEmbed::TYPE]);
    }
}
