<?php
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package EmojiExtender
 */

$PluginInfo['EmojiExtender'] = array(
    'Name' => "Emoji Sets",
    'Description' => "Change your emoji set!",
    'Version' => '1.1',
    'Author' => "Becky Van Bussel",
    'AuthorEmail' => 'rvanbussel@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com',
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/settings/EmojiExtender',
    'MobileFriendly' => true
);

/**
 * Emoji Extender Plugin
 *
 * @author    Becky Van Bussel <rvanbussel@vanillaforums.com>
 * @copyright 2015 Vanilla Forums Inc.
 * @license   GNU GPL2
 * @package   EmojiExtender
 * @since     1.0.0
 *
 * Users can change or delete emoji sets for their forums.
 */
class EmojiExtenderPlugin extends Gdn_Plugin {

    /**
     * @var array List of all available emoji sets.
     */
    protected $emojiSets;

    /**
     * Setup some variables and change emoji set.
     */
    public function __construct() {
        parent::__construct();
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
     * @param string $iconPath The url path to the icon.
     */
    public function addEmojiSet($key, $manifest, $basePath) {
        $this->emojiSets[$key] = array(
            'manifest' => $manifest,
            'basePath' => $basePath
        );
    }

    /**
     * Get all of the registered emoji sets.
     *
     * @return array Returns an array of all of the emoji sets.
     */
    public function getEmojiSets() {
        if (!isset($this->emojiSets)) {
            $root = '/plugins/EmojiExtender/emoji';

            $this->addEmojiSet(
                '',
                array(
                    'name' => 'Apple Emoji',
                    'author' => 'Apple Inc.',
                    'description' => 'A modern set of emoji you might recognize from any of your ubiquitous iDevices.',
                    'icon' => 'icon.png'

                ),
                '/resources/emoji'
            );

                $this->addEmojiSet('twitter', PATH_ROOT."$root/twitter/manifest.php", "$root/twitter");
                $this->addEmojiSet('little', PATH_ROOT."$root/little/manifest.php", "$root/little");
                $this->addEmojiSet('rice', PATH_ROOT."$root/rice/manifest.php", "$root/rice");
                $this->addEmojiSet('yahoo', PATH_ROOT."$root/yahoo/manifest.php", "$root/yahoo");

                $this->fireEvent('Init');

                $this->addEmojiSet('none', PATH_ROOT."$root/none/manifest.php", "$root/none");
        }

        return $this->emojiSets;
    }

    /**
     * Subscribe to event in Emoji class instance method.
     *
     * @param Emoji $sender
     * @param array $args
     */
    public function emoji_init_handler($sender, $args) {
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
     * @param array $args
     */
    public function settingsController_emojiExtender_create($sender, $args) {
        $cf = new ConfigurationModule($sender);
        $items = array();

        foreach ($this->getEmojiSets() as $key => $emojiSet) {
            $manifest = $this->getManifest($emojiSet);

            $icon = (isset($manifest['icon'])) ? img($emojiSet['basePath'].'/'.$manifest['icon'], array('alt' => $manifest['name'])) : '';
            $items[$key] = '@'.$icon.
                '<div emojiset-body>'.
                '<div><b>'.htmlspecialchars($manifest['name']).'</b></div>'.
                (empty($manifest['author']) ? '' : '<div class="emojiset-author">'.sprintf(t('by %s'), $manifest['author']).'</div>').
                (empty($manifest['description']) ? '' : '<p class="emojiset-description">'.Gdn_Format::wysiwyg($manifest['description']).'</p>').
                '</div>';
        }
        $cf->initialize(array(
            'Garden.EmojiSet' => array(
                'LabelCode' => 'Emoji Set',
                'Control' => 'radiolist',
                'Description' => '<p>Which emoji set would you like to use?</p>',
                'Items' => $items,
                'Options' => array('list' => true, 'listclass' => 'emojiext-list', 'display' => 'after')
            ),
            //If ever you want the functionality to merge the custom emoji set with the default set, uncomment below
            //'Plugins.EmojiExtender.merge' => array('LabelCode' => 'Merge set', 'Control' => 'Checkbox', 'Description' => '<p>Would you like to merge the selected emoji set with the default set?</p> <p><small><strong>Note:</strong> Some emojis in the default set may not be represented in the selected set and vice-versa.</small></p>'),
        ));

        $sender->addCssFile('settings.css', 'plugins/EmojiExtender');
        $sender->addSideMenu();
        $sender->setData('Title', sprintf(t('%s Settings'), 'Emoji'));
        $cf->renderAll();
    }
}
