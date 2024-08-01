<?php
/**
 * @author Becky Van Bussel <rvanbussel@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmojiExtender;

use Exception;
use Garden\EventManager;
use Vanilla\Web\TwigRenderTrait;

/**
 * Emoji Extender Plugin
 *
 * Users can change or delete emoji sets for their forums.
 */
class EmojiExtenderModel
{
    use TwigRenderTrait;

    /**
     * @var array List of all available emoji sets.
     */
    protected $emojiSets;

    /** @var EventManager */
    private $eventManager;

    /**
     * Class constructor.
     *
     * @param EventManager $eventManager The event manager dependency.
     */
    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Get the manifest for am emoji set.
     *
     * @param array $emojiSet The emoji set to look up.
     * @return array|null Returns the manifest on success,
     * `false` if the emoji should be disabled, or `null` otherwise.
     */
    public function getManifest(array $emojiSet)
    {
        $manifest = val("manifest", $emojiSet);

        if (!$manifest) {
            return null; // this is the default emoji set.
        } elseif (is_string($manifest)) {
            if (!file_exists($manifest)) {
                trigger_error("Emoji manifest does not exist: $manifest.", E_USER_NOTICE);

                return null;
            }

            try {
                $manifest = require $manifest;
            } catch (Exception $ex) {
                trigger_error($ex->getMessage(), E_USER_NOTICE);

                return null;
            }
        }

        if (!is_array($manifest)) {
            trigger_error("Invalid emoji manifest. The manifest is not an array.", E_USER_NOTICE);

            return null;
        }

        return $manifest;
    }

    /**
     * Add an emoji set.
     *
     * @param string $key The key that defines the emoji set.
     * @param string|array $manifest The path to the manifest or the manifest itself.
     * @param string $basePath The url path to the emoji.
     */
    public function addEmojiSet($key, $manifest, $basePath)
    {
        $this->emojiSets[$key] = [
            "manifest" => $manifest,
            "basePath" => $basePath,
        ];
    }

    /**
     * Get every registered emoji sets.
     *
     * @return array Returns an array of every emoji sets.
     */
    public function getEmojiSets()
    {
        if (!isset($this->emojiSets)) {
            $root = "/plugins/emojiextender/emoji";

            $this->addEmojiSet(
                "",
                [
                    "name" => "Apple Emoji",
                    "author" => "Apple Inc.",
                    "description" => "A modern set of emoji you might recognize from any of your ubiquitous iDevices.",
                    "icon" => "icon.png",
                ],
                "/resources/emoji"
            );

            $this->addEmojiSet("twitter", PATH_ROOT . "$root/twitter/manifest.php", "$root/twitter");
            $this->addEmojiSet("little", PATH_ROOT . "$root/little/manifest.php", "$root/little");
            $this->addEmojiSet("rice", PATH_ROOT . "$root/rice/manifest.php", "$root/rice");
            $this->addEmojiSet("yahoo", PATH_ROOT . "$root/yahoo/manifest.php", "$root/yahoo");
            $this->eventManager->fire("emojiExtenderPlugin_init", $this);
            $this->addEmojiSet("none", PATH_ROOT . "$root/none/manifest.php", "$root/none");
        }

        return $this->emojiSets;
    }
}
