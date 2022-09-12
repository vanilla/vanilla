<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\EmojiExtender;

use Garden\EventManager;
use Gdn;
use VanillaTests\SiteTestCase;

/**
 * Basic tests for the EmojiExtenderPlugin
 */
class EmojiExtenderTests extends SiteTestCase
{
    public static $addons = ["emojiextender"];

    /*
     * Test that enabling the plugin & picking a custom emoji set affects `Emoji`'s assetPath.
     */
    public function testAssetPathHijack()
    {
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get("Config");
        $config->set("Garden.EmojiSet", "rice");
        $emoji = static::container()->get(\Emoji::class);

        $this->assertEquals("/plugins/emojiextender/emoji/rice", $emoji->getAssetPath());
    }
}
