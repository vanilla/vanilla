<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Addons\OpenApiEmbed\Embeds\OpenApiEmbed;
use Vanilla\EmbeddedContent\EmbedService;

$dic = Gdn::getContainer();

$dic->rule(EmbedService::class)
    ->addCall('registerEmbed', [OpenApiEmbed::class, OpenApiEmbed::TYPE]);
