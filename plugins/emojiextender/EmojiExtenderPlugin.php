<?php
/**
 * @author Becky Van Bussel <rvanbussel@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Addons\EmojiExtender;

use ConfigurationModule;
use Emoji;
use Exception;
use Gdn_Plugin;
use SettingsController;
use Gdn_Format;
use Vanilla\Web\TwigRenderTrait;

/**
 * Emoji Extender Plugin
 *
 * Users can change or delete emoji sets for their forums.
 */
class EmojiExtenderPlugin extends Gdn_Plugin {

    use TwigRenderTrait;

    /**
     * @var array List of all available emoji sets.
     */
    protected $emojiSets;

    /**
     * Setup some variables and change emoji set.
     */
    public function __construct() {
        parent::__construct();
        $this->ClassName = "EmojiExtenderPlugin";
    }

    /**
     * Change the emoji set used, either by merging or or overriding the default set.
     *
     * @param Emoji $emoji The emoji object to change.
     * @param string $emojiSetKey The name of the emoji set to enable.
     */
    public function changeEmojiSet($emoji, $emojiSetKey) {
        if (!array_key_exists($emojiSetKey, $this->getEmojiSets())) {
            trigger_error("Emoji set not found: $emojiSetKey.", E_USER_NOTICE);

            return;
        }

        // First grab the manifest to the emoji.
        $emojiSet = $this->emojiSets[$emojiSetKey];
        $manifest = $this->getManifest($emojiSet);

        if ($manifest) {
            $emoji->setFromManifest($manifest, $emojiSet['basePath']);
        }
    }

    /**
     * Get the manifest for am emoji set.
     *
     * @param array $emojiSet The emoji set to look up.
     * @return array|null Returns the manifest on success,
     * `false` if the emoji should be disabled, or `null` otherwise.
     */
    protected function getManifest($emojiSet) {
        $manifest = val('manifest', $emojiSet);

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
    public function addEmojiSet($key, $manifest, $basePath) {
        $this->emojiSets[$key] = [
            'manifest' => $manifest,
            'basePath' => $basePath,
        ];
    }

    /**
     * Get all of the registered emoji sets.
     *
     * @return array Returns an array of all of the emoji sets.
     */
    public function getEmojiSets() {
        if (!isset($this->emojiSets)) {
            $root = '/plugins/emojiextender/emoji';

            $this->addEmojiSet(
                '',
                [
                    'name' => 'Apple Emoji',
                    'author' => 'Apple Inc.',
                    'description' => 'A modern set of emoji you might recognize from any of your ubiquitous iDevices.',
                    'icon' => 'icon.png',

                ],
                '/resources/emoji'
            );

            $this->addEmojiSet('twitter', PATH_ROOT . "$root/twitter/manifest.php", "$root/twitter");
            $this->addEmojiSet('little', PATH_ROOT . "$root/little/manifest.php", "$root/little");
            $this->addEmojiSet('rice', PATH_ROOT . "$root/rice/manifest.php", "$root/rice");
            $this->addEmojiSet('yahoo', PATH_ROOT . "$root/yahoo/manifest.php", "$root/yahoo");

            $this->fireEvent('Init');

            $this->addEmojiSet('none', PATH_ROOT . "$root/none/manifest.php", "$root/none");
        }

        return $this->emojiSets;
    }

    /**
     * Subscribe to event in Emoji class instance method.
     *
     * @param Emoji $sender
     */
    public function emoji_init_handler($sender) {
        // Get the currently selected emoji set & switch to it.
        $emojiSetKey = c('Garden.EmojiSet');
        if (!$emojiSetKey || !array_key_exists($emojiSetKey, $this->getEmojiSets())) {
            return;
        }
        $this->changeEmojiSet($sender, $emojiSetKey);
    }

    /**
     * Configure settings page in dashboard.
     *
     * @param SettingsController $sender
     */
    public function settingsController_emojiExtender_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $items = [];
        foreach ($this->getEmojiSets() as $key => $emojiSet) {
            $manifest = $this->getManifest($emojiSet);
            $data = [
                'iconUrl' => isset($manifest['icon'])
                    ? asset($emojiSet['basePath'] . '/' . $manifest['icon'], true, true)
                    : '',
                'name' => $manifest['name'],
                'author' => !empty($manifest['author']) ? sprintf(t('by %s'), $manifest['author']) : null,
                'descriptionHtml' => Gdn_Format::text($manifest['description'])
            ];

            $items[$key] = $this->renderTwig('/plugins/emojiextender/views/settings.twig', $data);
        }
        $cf = new ConfigurationModule($sender);
        $cf->initialize([
            'Garden.EmojiSet' => [
                'LabelCode' => 'Emoji Set',
                'Control' => 'radiolist',
                'Items' => $items,
                'Options' => [
                    'list' => true,
                    'list-item-class' => 'label-selector-item',
                    'listclass' => 'emojiext-list label-selector',
                    'display' => 'after',
                    'class' => 'label-selector-input',
                    'no-grid' => true
                ],
            ],
        ]);


        $sender->setData('Title', t('Choose Your Emoji Set'));
        $cf->renderAll();
    }
}
