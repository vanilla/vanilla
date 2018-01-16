<?php
/**
 * Emoji.
 *
 * @author Dane MacMillan <dane@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.2
 */

/**
 * Interpreting Emoji emoticons.
 */
class Emoji {

    /**
     * The emoji aliases are an array where each key is an alias and each value is the name of an emoji.
     *
     * @var array All of the emoji aliases.
     */
    protected $aliases;

    /**
     * The archive is an array of deprecated emoji to new emoji that allows us to rename emoji with compatibility.
     *
     * The archive can be used for a couple of purposes.
     * 1. If you want to remove an emoji from the lookup list then you can just move the entry from the `$emoji` array to
     * the `$archive` array.
     * 2. If you want to rename an emoji then copy it to the `$archive` array and then rename it in the `$emoji` array.
     *
     * @var array All of the emoji archive.
     */
    protected $archive;

    /** @var string The base path where the emoji are located. */
    protected $assetPath;

    /** @var string If assetPath is modified, this will hold the original path. */
    protected $assetPathOriginal;

    /**
     * @var array An emoji alias list that represents the emoji that display
     * in an editor drop down. Typically, it is a copy of the alias list.
     */
    protected $editorList;

    /**
     * This array contains all of the emoji avaliable in the system. The array is in the following format:
     *
     * ~~~
     * array (
     *     'emoji_name' => 'filename.png'
     * )
     * ~~~
     *
     * @var array All of the available emoji.
     */
    protected $emoji;

    /**
     * @var array The original emoji that are not accounted for in the custom
     * set of emoji supplied by plugin, if any. This is useful when merging the
     * custom ones with the original ones, which have different assetPaths.
     */
    protected $emojiOriginalUnaccountedFor;

    /**
     * This is the emoji name that will represent the error emoji.
     *
     * @var string If emoji is missing, use grey_question emoji.
     */
    protected $errorEmoji = 'error';

    /** @var bool Setting to true will allow editor to interpret emoji aliases as Html equivalent markup. */
    public $enabled = true;

    /**
     * @var string The sprintf format for emoji with the following parameters.
     * - %1$s: The emoji path.
     * - %2$s: The emoji code.
     */
    protected $format = '<img class="emoji" src="%1$s" title="%2$s" alt="%2$s" height="20" />';

    /** @var Emoji The singleton instance of this class. */
    public static $instance;

    /** @var string left-side delimiter surrounding emoji, typically a full-colon. */
    public $ldelim = ':';

    /** @var string right-side delimiter surrounding emoji, typically a full-colon */
    public $rdelim = ':';

    /**
     *
     */
    protected function __construct() {
        $this->assetPath = asset('/resources/emoji', true);

        // Initialize the canonical list. (emoji)
        $this->emoji = [
            // Smileys
            'smile' => 'smile.png',
            'smiley' => 'smiley.png',
            'wink' => 'wink.png',
            'blush' => 'blush.png',
            'neutral' => 'neutral.png',

            'relaxed' => 'relaxed.png',
            'grin' => 'grin.png',
            'joy' => 'joy.png',
            'sweat_smile' => 'sweat_smile.png',
            'lol' => 'lol.png',
            'innocent' => 'innocent.png',
            'naughty' => 'naughty.png',
            'yum' => 'yum.png',
            'relieved' => 'relieved.png',
            'love' => 'love.png',
            'sunglasses' => 'sunglasses.png',
            'smirk' => 'smirk.png',
            'expressionless' => 'expressionless.png',
            'unamused' => 'unamused.png',
            'sweat' => 'sweat.png',
            'pensive' => 'pensive.png',
            'confused' => 'confused.png',
            'confounded' => 'confounded.png',
            'kissing' => 'kissing.png',
            'kissing_heart' => 'kissing_heart.png',
            'kissing_smiling_eyes' => 'kissing_smiling_eyes.png',
            'kissing_closed_eyes' => 'kissing_closed_eyes.png',
            'tongue' => 'tongue.png',
            'disappointed' => 'disappointed.png',
            'worried' => 'worried.png',
            'angry' => 'angry.png',
            'rage' => 'rage.png',
            'cry' => 'cry.png',
            'persevere' => 'persevere.png',
            'triumph' => 'triumph.png',
            'frowning' => 'frowning.png',
            'anguished' => 'anguished.png',
            'fearful' => 'fearful.png',
            'weary' => 'weary.png',
            'sleepy' => 'sleepy.png',
            'tired_face' => 'tired_face.png',
            'grimace' => 'grimace.png',
            'bawling' => 'bawling.png',
            'open_mouth' => 'open_mouth.png',
            'hushed' => 'hushed.png',
            'cold_sweat' => 'cold_sweat.png',
            'scream' => 'scream.png',
            'astonished' => 'astonished.png',
            'flushed' => 'flushed.png',
            'sleeping' => 'sleeping.png',
            'dizzy' => 'dizzy.png',
            'no_mouth' => 'no_mouth.png',
            'mask' => 'mask.png',
            'star' => 'star.png',
            'cookie' => 'cookie.png',
            'warning' => 'warning.png',
            'mrgreen' => 'mrgreen.png',

            // Love
            'heart' => 'heart.png',
            'heartbreak' => 'heartbreak.png',
            'kiss' => 'kiss.png',

            // Hand gestures
            '+1' => '+1.png',
            '-1' => '-1.png',

            // This is used for aliases that are set incorrectly or point
            // to items not listed in the emoji list.
            // errorEmoji
            'grey_question' => 'grey_question.png',

            // Custom icons, canonical naming
            'trollface' => 'trollface.png'
        ];

        // Some aliases self-referencing the canonical list. Use this syntax.

        // This is used in cases where emoji image cannot be found.
        $this->emoji['error'] = &$this->emoji['grey_question'];

        // Initialize the alias list. (emoticons)
        $this->aliases = [
            ':)' => 'smile',
            ':D' => 'lol',
            '=)' => 'smiley',
            ':(' => 'frowning',
            ';)' => 'wink',
            ':\\' => 'confused',
            ':/' => 'confused',
            ':o' => 'open_mouth',
            ':s' => 'confounded',
            ':p' => 'tongue',
            ":'(" => 'cry',
            ':|' => 'neutral',
            'D:' => 'anguished',
            'B)' => 'sunglasses',
            ':#' => 'grimace',
            ':*' => 'kiss',
            ':3' => 'blush',
            'o:)' => 'innocent',
            '<3' => 'heart',
            '>:)' => 'naughty'
        ];

        $this->archive = [
            'disappointed_relieved' => 'disappointed_relieved.png',
            'dizzy_face' => 'dizzy.png',
            'broken_heart' => 'heartbreak.png',
            'grinning' => 'grin.png',
            'heart_eyes' => 'love.png',
            'neutral_face' => 'neutral.png',
            'smiling_imp' => 'naughty.png',
            'sob' => 'bawling.png',
            'stuck_out_tongue' => 'tongue.png',
            'stuck_out_tongue_winking_eye' => 'stuck_out_tongue_winking_eye.png',
            'stuck_out_tongue_closed_eyes' => 'stuck_out_tongue_closed_eyes.png',
        ];

        $this->editorList = [
            ':)' => 'smile',
            ':D' => 'lol',
            ':(' => 'disappointed',
            ';)' => 'wink',
            ':/' => 'confused',
            ':o' => 'open_mouth',
            ':s' => 'confounded',
            ':p' => 'stuck_out_tongue',
            ":'(" => 'cry',
            ':|' => 'neutral',
            'B)' => 'sunglasses',
            ':#' => 'grimace',
            ':*' => 'kiss',
            '<3' => 'heart',
            'o:)' => 'innocent',
            '>:)' => 'naughty'
        ];

        if (c('Garden.EmojiSet') === 'none') {
            $this->enabled = false;
        }

        Gdn::pluginManager()->callEventHandlers($this, 'Emoji', 'Init', 'Handler');

        // Add emoji to definition list for whole site. This used to be in the
        // advanced editor plugin, but since moving atmentions to core, had to
        // make sure they were still being added. This will make sure that
        // emoji autosuggest works. Note: emoji will not be core yet, so the only
        // way that this gets called is by the editor when it instantiates. Core
        // does not instantiate this class anywhere, so there will not be any
        // suggestions for emoji yet, but keep here for whenever Advanced Editor
        // is running.
        $c = Gdn::controller();
        if ($c && $this->enabled) {
            $emojis = $this->getEmoji();
            $emojiAssetPath = $this->getAssetPath();
            $emoji = [];

            foreach ($emojis as $name => $data) {
                $emoji[] = [
                    "name" => "".$name."",
                    "url" => asset($emojiAssetPath.'/'.$data, true)
                ];
            }

            $emoji = [
                'assetPath' => asset($this->getAssetPath(), true),
                'format' => $this->getFormat(),
                'emoji' => $this->getEmoji()
            ];

            $c->addDefinition('emoji', $emoji);
        }
    }

    /**
     * This method is deprecated. See {@link Emoji::getEmojiPath()}.
     *
     * @param string $emojiName
     * @return string
     */
    public function buildEmojiPath($emojiName) {
        deprecated('buildEmojiPath', 'getEmojiPath');
        return $this->getEmojiPath($emojiName);
    }

    /**
     * Check the alias array and filter out all of the emoji that are not present in the main emoji list.
     */
    protected function checkAliases() {
        $this->aliases = array_filter($this->aliases, function ($emojiName) {
            return isset($this->emoji[$emojiName]);
        });
    }

    /**
     * Populate this with any aliases required for plugin, make sure they point
     * to canonical translation, and plugin will add everything to drop down that
     * is listed. To expand, simply define more aliases that corresponded with
     * canonical list.
     *
     * Note: some aliases require {@link htmlentities()} filtering, which is done directly
     * before output in the drop down, and while searching for the string to
     * replace in the regex, NOT here. The reason for this is so the alias
     * list does not get littered with characters entity encodings like `&lt;`,
     * which makes it difficult to immediately know what the aliases do. Also,
     * {@link htmlentities} would have to be revered in areas such as title attributes,
     * which counteracts the usefulness of having it done here.
     *
     * @return array Returns an array of alias to emoji name entries.
     */
    public function getAliases() {
        return $this->aliases;
    }

    /**
     * Gets the asset path location.
     *
     * @return string The asset path location
     */
    public function getAssetPath() {
        return $this->assetPath;
    }

    /**
     * Gets the emoji archive.
     *
     * @return array Returns an array of emoji name to emoji file names representing the emoji archie.
     */
    public function getArchive() {
        return $this->archive;
    }

    /**
     * Set the emoji archive.
     *
     * @param array $archive
     * @return Emoji Returns $this for fluent calls.
     */
    public function setArchive($archive) {
        $this->archive = $archive;
        return $this;
    }

    /**
     * Get the emoji editor list.
     *
     * @return array Returns an array of Emojis that can appear in an editor drop down.
     */
    public function getEditorList() {
        if ($this->editorList === null) {
            return $this->getAliases();
        }

        return $this->editorList;
    }

    /**
     * This is the canonical, e.g., official, list of emoji names along with
     * their associatedwith image file name. For an exhaustive list of emoji
     * names visit http://www.emoji-cheat-sheet.com/ and for the original image
     * files being used, visit https://github.com/taninamdar/Apple-Color-Emoji
     *
     * @return string|array File name or full canonical array
     */
    public function getEmoji() {
        // Return first value from canonical array
        return $this->emoji;
    }

    /**
     *
     *
     * @return array List of Emojis that will appear in the editor.
     */
    public function getEmojiEditorList() {
        deprecated('getEmojiEditorList', 'getEditorList');
        return $this->getEditorList();
    }

    /**
     * Provide this method with the official emoji filename and it will return the correct path.
     *
     * @param string $emojiName File name of emoji icon.
     * @return string Root-relative path.
     */
    public function getEmojiPath($emojiName) {

        // By default, just characters will be outputted (img alt text)
        $filePath = $emojiFileName = '';

        if (isset($this->emoji[$emojiName])) {
            $filePath = $this->assetPath;
            $emojiFileName = $this->emoji[$emojiName];
        } elseif (isset($this->archive[$emojiName])) {
            $filePath = $this->assetPath;
            $emojiFileName = $this->archive[$emojiName];
        } else {
            return '';
        }

        return $filePath.'/'.$emojiFileName;
    }

    /**
     * Checks whether or not the emoji has an editor list.
     *
     * @return bool Returns true if there is an editor list or false otherwise.
     */
    public function hasEditorList() {
        $editorList = $this->getEditorList();
        return $this->enabled && !empty($editorList);
    }

    /**
     * Set the list of emoji that can be used by the editor.
     *
     * @param array $value The new editor list.
     */
    public function setEmojiEditorList($value) {
        deprecated('setEmojiEditorList', 'setEditorList');
        return $this->setEditorList($value);
    }


    /**
     * Set the list of emoji that can be used by the editor.
     *
     * @param array $value The new editor list.
     * @return Emoji Returns $this for fluent calls.
     */
    public function setEditorList($value) {
        // Convert the editor list to the proper format.
        $list = [];
        $aliases2 = array_flip($this->aliases);
        foreach ($value as $emoji) {
            if (isset($this->aliases[$emoji])) {
                $list[$emoji] = $this->aliases[$emoji];
            } elseif (isset($aliases2[$emoji])) {
                $list[$aliases2[$emoji]] = $emoji;
            } elseif (isset($this->emoji[$emoji])) {
                $list[$this->ldelim.$emoji.$this->rdelim] = $emoji;
            }
        }
        $this->editorList = $list;
        return $this;
    }

    /**
     * Gets the emoji format used in {@link Emoji::img()}.
     *
     * @return string Returns the current emoji format.
     */
    public function getFormat() {
        return $this->format;
    }

    /**
     * Sets the emoji format used in {@link Emoji::img()}.
     *
     * @param string $format
     * @return Emoji Returns $this for fluent calls.
     */
    public function setFormat($format) {
        $this->format = $format;
        return $this;
    }

    /**
     * Accept an Emoji path and name, and return the corresponding HTML IMG tag.
     *
     * @param string $emoji_path The full path to Emoji file.
     * @param string $emoji_name The name given to Emoji.
     * @return string The html that represents the emoji.
     */
    public function img($emoji_path, $emoji_name) {
        $dir = asset(dirname($emoji_path));
        $filename = basename($emoji_path);
        $ext = '.'.pathinfo($filename, PATHINFO_EXTENSION);
        $basename = basename($filename, $ext);
        $src = asset($emoji_path, true);

        $attributes = [$src, $emoji_name, $src, $emoji_name, $dir, $filename, $basename, $ext];
        $attributes = array_map('htmlspecialchars', $attributes);
        $img = str_replace(
            ['%1$s', '%2$s', '{src}', '{name}', '{dir}', '{filename}', '{basename}', '{ext}'],
            $attributes,
            $this->format
        );

        return $img;
    }

    /**
     * Set the aliases array.
     *
     * @param array $aliases The new aliases array.
     * @return Emoji Returns $this for fluent calls.
     */
    public function setAliases($aliases) {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     *
     * @param string $assetPath
     */
    public function setAssetPath($assetPath) {
        $this->assetPath = $assetPath;
    }

    /**
     * Sets custom emoji, and saves the original ones that are unaccounted for.
     *
     * @param array $emoji
     */
    public function setEmoji($emoji) {
        $this->emoji = $emoji;
    }

    /**
     * Set the emoji from a manifest.
     *
     * @param array $manifest An emoji manifest with the following keys:
     * - emoji: An array in the form: name => filename (ex. ['smile' => 'smile.png'])
     * - aliases (optional): An array of emoji short forms: alias => emojiName  (ex. [':)' => 'smile'])
     * - editor (optional): An array of emoji that will display in the editor: emojiName (ex: [smile,..])
     * - format (optional): The string format of the emoji replacement.
     * @param string $assetPath The asset path root to all of the emoji files.
     */
    public function setFromManifest($manifest, $assetPath = '') {
        // Set the default asset root.
        if ($assetPath) {
            $this->setAssetPath(stringBeginsWith($assetPath, PATH_ROOT, true, true));
        }

        // Set the emoji settings from the manifest.
        if (array_key_exists('emoji', $manifest)) {
            $this->setEmoji($manifest['emoji']);
        }

        if (array_key_exists('aliases', $manifest)) {
            $this->setAliases($manifest['aliases']);
        } else {
            $this->checkAliases();
        }

        if (array_key_exists('archive', $manifest)) {
            $this->setArchive($manifest['archive']);
        } else {
            $this->setArchive([]);
        }

        if (!empty($manifest['format'])) {
            $this->format = $manifest['format'];
        }

        if (array_key_exists('editor', $manifest)) {
            $this->setEditorList($manifest['editor']);
        }
    }

    /**
     * Translate all emoji aliases to their corresponding Html image tags.
     *
     * Thanks to punbb 1.3.5 (GPL License) for function, which was largely
     * inspired from their do_smilies function.
     *
     * @param string $text The actual user-submitted post
     * @return string Return the emoji-formatted post
     */
    public function translateToHtml($text) {
        if (!$this->enabled) {
            return $text;
        }

        $text = ' '.$text.' ';

        // First, translate all aliases. Canonical emoji will get translated
        // out of a loop.
        $emojiAliasList = $this->aliases;

        // Loop through and apply changes to all visible aliases from dropdown
        foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
            $emojiFilePath = $this->getEmojiPath($emojiCanonical);

            if (strpos($text, htmlentities($emojiAlias)) !== false) {
                $text = Gdn_Format::replaceButProtectCodeBlocks(
                    '`(?<=[>\s]|(&nbsp;))'.preg_quote(htmlentities($emojiAlias), '`').'(?=\W)`m',
                    $this->img($emojiFilePath, $emojiAlias),
                    $text
                );
            }
        }

        // Second, translate canonical list, without looping.
        $ldelim = preg_quote($this->ldelim, '`');
        $rdelim = preg_quote($this->rdelim, '`');
        $emoji = $this;

        $text = Gdn_Format::replaceButProtectCodeBlocks("`({$ldelim}\S+?{$rdelim})`i", function ($m) use ($emoji) {
            $emoji_name = trim($m[1], ':');
            $emoji_path = $emoji->getEmojiPath($emoji_name);
            if ($emoji_path) {
                return $emoji->img($emoji_path, $emoji->ldelim.$emoji_name.$emoji->rdelim);
            } else {
                return $m[0];
            }
        }, $text, true);

        return substr($text, 1, -1);
    }

    /**
     * Get the singleton instance of this class.
     * @return Emoji
     */
    public static function instance() {
        if (Emoji::$instance === null) {
            Emoji::$instance = new Emoji();
        }

        return Emoji::$instance;
    }
}
