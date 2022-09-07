<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\EmojiExtender;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Logging\ErrorLogger;

/**
 * Extend the emoji class with additional sets.
 */
class ExtendedEmoji extends \Emoji
{
    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     * @param EmojiExtenderModel $emojiExtenderModel
     */
    public function __construct(ConfigurationInterface $config, EmojiExtenderModel $emojiExtenderModel)
    {
        parent::__construct();

        // Get the currently selected emoji set & switch to it.
        $emojiSetKey = $config->get("Garden.EmojiSet");

        if (empty($emojiSetKey)) {
            // Nothing to do here.
            return;
        }

        // First grab the manifest to the emoji.
        $emojiSet = $emojiExtenderModel->getEmojiSets()[$emojiSetKey] ?? null;
        if ($emojiSet === null) {
            ErrorLogger::warning("Emoji set not found: $emojiSetKey", ["emojiExtender"]);
            return;
        }

        $manifest = $emojiExtenderModel->getManifest($emojiSet);
        if ($manifest === null) {
            ErrorLogger::warning("Emoji manifest not found: $emojiSetKey", ["emojiExtender"]);
            return;
        }
        $this->setFromManifest($manifest, $emojiSet["basePath"]);
    }
}
