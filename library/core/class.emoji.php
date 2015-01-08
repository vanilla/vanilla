<?php if (!defined('APPLICATION')) exit();

/**
 * Interpreting Emoji emoticons
 *
 *
 * @author Dane MacMillan <dane@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.2.3.11
 */

class Emoji {
   /// Properties ///

   /**
    * The emoji aliases are an array where each key is an alias and each value is the name of an emoji.
    *
    * @var array All of the emoji aliases.
    */
   protected $aliases;

   /**
    * @var string The base path where the emoji are located.
    */
   protected $assetPath = '/resources/emoji';

   /**
    *
    * @var string If assetPath is modified, this will hold the original path.
    */
   protected $assetPathOriginal;

   /**
    * @var array An emoji alias list that represents the emoji that display
    * in an editor dropdown. Typically, it is a copy of the alias list.
    */
   protected $editorList;

   /**
    * This array contains all of the emoji avaliable in the system. The array
    * is in the following format:
    * ~~~
    * array (
    *     'emoji_name' => array('filename.png', 'misc info'...)
    * )
    * ~~~
    *
    * @var array All of the available emoji.
    */
   protected $emoji;

   /**
    *
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

   /**
    *
    * @var bool Setting to true will allow editor to interpret emoji aliases as
    *           Html equivalent markup.
    */
   public $enabled = true;

   /**
    * @var string The sprintf format for emoji with the following parameters.
    * - %1$s: The emoji path.
    * - %2$s: The emoji code.
    */
   public $format = '<img class="emoji" src="%1$s" title="%2$s" alt="%2$s" height="20" />';

   /**
    *
    * @var Emoji The singleton instance of this class.
    */
   public static $instance;

   /**
    *
    * @var string left-side delimiter surrounding emoji, typically a full-colon
    */
   public $ldelim = ':';

   /**
    *
    * @var string right-side delimiter surrounding emoji, typically a full-colon
    */
   public $rdelim = ':';

   /**
    *
    * @var bool If set to true, original unaccounted for emoji will get merged
    * into the custom set.
    */
   protected $mergeOriginals = false;

   /// Methods ///

   protected function __construct() {
      // Initialize the canonical list. (emoji)
      $this->emoji = array(
        // Smileys
        'smile'                        => 'smile.png',
        'smiley'                       => 'smiley.png',
        'wink'                         => 'wink.png',
        'blush'                        => 'blush.png',
        'neutral_face'                 => 'neutral_face.png',

        'relaxed'                      => 'relaxed.png',
        'grinning'                     => 'grinning.png',
        'grin'                         => 'grin.png',
        'joy'                          => 'joy.png',
        'sweat_smile'                  => 'sweat_smile.png',
        'lol'                          => 'lol.png',
        'innocent'                     => 'innocent.png',
        'smiling_imp'                  => 'smiling_imp.png',
        'yum'                          => 'yum.png',
        'relieved'                     => 'relieved.png',
        'heart_eyes'                   => 'heart_eyes.png',
        'sunglasses'                   => 'sunglasses.png',
        'smirk'                        => 'smirk.png',
        'neutral_face'                 => 'neutral_face.png',
        'expressionless'               => 'expressionless.png',
        'unamused'                     => 'unamused.png',
        'sweat'                        => 'sweat.png',
        'pensive'                      => 'pensive.png',
        'confused'                     => 'confused.png',
        'confounded'                   => 'confounded.png',
        'kissing'                      => 'kissing.png',
        'kissing_heart'                => 'kissing_heart.png',
        'kissing_smiling_eyes'         => 'kissing_smiling_eyes.png',
        'kissing_closed_eyes'          => 'kissing_closed_eyes.png',
        'stuck_out_tongue'             => 'stuck_out_tongue.png',
        'stuck_out_tongue_winking_eye' => 'stuck_out_tongue_winking_eye.png',
        'stuck_out_tongue_closed_eyes' => 'stuck_out_tongue_closed_eyes.png',
        'disappointed'                 => 'disappointed.png',
        'worried'                      => 'worried.png',
        'angry'                        => 'angry.png',
        'rage'                         => 'rage.png',
        'cry'                          => 'cry.png',
        'persevere'                    => 'persevere.png',
        'triumph'                      => 'triumph.png',
        'disappointed_relieved'        => 'disappointed_relieved.png',
        'frowning'                     => 'frowning.png',
        'anguished'                    => 'anguished.png',
        'fearful'                      => 'fearful.png',
        'weary'                        => 'weary.png',
        'sleepy'                       => 'sleepy.png',
        'tired_face'                   => 'tired_face.png',
        'grimacing'                    => 'grimacing.png',
        'sob'                          => 'sob.png',
        'open_mouth'                   => 'open_mouth.png',
        'hushed'                       => 'hushed.png',
        'cold_sweat'                   => 'cold_sweat.png',
        'scream'                       => 'scream.png',
        'astonished'                   => 'astonished.png',
        'flushed'                      => 'flushed.png',
        'sleeping'                     => 'sleeping.png',
        'dizzy_face'                   => 'dizzy_face.png',
        'no_mouth'                     => 'no_mouth.png',
        'mask'                         => 'mask.png',
        'star'                         => 'star.png',
        'cookie'                       => 'cookie.png',
        'warning'                      => 'warning.png',
        'mrgreen'                      => 'mrgreen.png',

        // Love
        'heart'                        => 'heart.png',
        'broken_heart'                 => 'broken_heart.png',
        'kiss'                         => 'kiss.png',

        // Hand gestures
        '+1'                           => '+1.png',
        '-1'                           => '-1.png',

        // This is used for aliases that are set incorrectly or point
        // to items not listed in the emoji list.
        // errorEmoji
        'grey_question'                => 'grey_question.png',

        // Custom icons, canonical naming
        'trollface'                    => 'trollface.png'
      );

      // Some aliases self-referencing the canonical list. Use this syntax.

      // This is used in cases where emoji image cannot be found.
      $this->emoji['error'] = &$this->emoji['grey_question'];

      // Vanilla reactions, non-canonical referencing canonical
      $this->emoji['lol']       = &$this->emoji['smile'];
      $this->emoji['wtf']       = &$this->emoji['dizzy_face'];
      $this->emoji['agree']     = &$this->emoji['grinning'];
      $this->emoji['disagree']  = &$this->emoji['stuck_out_tongue_closed_eyes'];
      $this->emoji['awesome']   = &$this->emoji['heart'];

      // Initialize the alias list. (emoticons)
      $this->aliases = array(
         ':)'          => 'smile',
         ':D'          => 'smiley',
         ':('          => 'frowning',
         ';)'          => 'wink',
         ':\\'         => 'confused',
         ':o'          => 'open_mouth',
         ':s'          => 'confounded',
         ':p'          => 'stuck_out_tongue',
         ":'("         => 'cry',
         ':|'          => 'neutral_face',
       //'D:'          => 'anguished',
         'B)'          => 'sunglasses',
         ':#'          => 'grin',
         'o:)'         => 'innocent',
         '<3'          => 'heart',
         '(*)'         => 'star',
         '>:)'         => 'smiling_imp'
       );

      Gdn::PluginManager()->CallEventHandlers($this, 'Emoji', 'Init', 'Handler');

      // Add emoji to definition list for whole site. This used to be in the
      // advanced editor plugin, but since moving atmentions to core, had to
      // make sure they were still being added. This will make sure that
      // emoji autosuggest works. Note: emoji will not be core yet, so the only
      // way that this gets called is by the editor when it instantiates. Core
      // does not instantiate this class anywhere, so there will not be any
      // suggestions for emoji yet, but keep here for whenever Advanced Editor
      // is running.
      $c = Gdn::Controller();
      if ($c) {
         $emojis = $this->getEmoji();
         $emojiAssetPath = $this->getAssetPath();
         $emoji = array();

         foreach ($emojis as $name => $data) {
            $emoji[] = array(
                "name" => "". $name ."",
                "url" =>  Asset($emojiAssetPath . '/' . $data)
            );
         }

         $c->AddDefinition('emoji', json_encode($emoji));
      }

      if(C('Garden.EmojiSet') === 'none') {
         $this->enabled = false;
      }
   }

   /**
    * Callback method for buildHiddenAliasListFromCanonicalList.
    *
    * Array passed as reference, to be used in above method,
    * buildHiddenAliasListFromCanonicalLi, when calling array_walk withthis
    * callback, which requires that the method as callback also specify object
    * it belongs to.
    *
    * @param string $val Reference to passed array value
    * @param string $key Reference to passed array key
    */
   public function buildAliasFormat(&$val, $key) {
      $val = ":$val:";
   }

   /**
    * Provide this method with the official emoji filename and it will return
    * the correct path.
    *
    * @param string $emojiFileName File name of emoji icon.
    * @return string Root-relative path.
    */
   public function buildFilePath($emojiName) {

      // By default, just characters will be outputted (img alt text)
      $filePath = $emojiFileName = '';

      if (isset($this->emoji[$emojiName])) {
         $filePath = $this->assetPath;
         $emojiFileName = $this->emoji[$emojiName];
      } elseif ($this->mergeOriginals && isset($this->emojiOriginalUnaccountedFor[$emojiName])) {
         $filePath = $this->assetPathOriginal;
         $emojiFileName = $this->emojiOriginalUnaccountedFor[$emojiName];
      } else {
         return '';
      }

      return $filePath . '/' . $emojiFileName;
   }

   /**
    * This is to easily match the array of the visible alias list that all
    * users will be able to select from. Call the mergeAliasAndEmojiLists()
    * method to merge this array with the alias list, which will then be easy
    * to loop through all the possible emoji displayable in the forum.
    *
    * An alias is [:)]=>[smile], and canonical alias is [:smile:]=>[smile]
    *
    * @return array Returns array that matches format of original alias list
    */
   public function buildHiddenAliasListFromCanonicalList() {
      $caonicalListEmojiNamesCanonical = array_keys($this->getEmoji());
      $caonicalListEmojiNamesAliases = $caonicalListEmojiNamesCanonical;
      array_walk($caonicalListEmojiNamesAliases, array($this, 'buildAliasFormat'));

      return array_combine($caonicalListEmojiNamesAliases, $caonicalListEmojiNamesCanonical);
   }

   /**
    * Populate this with any aliases required for plugin, make sure they point
    * to canonical translation, and plugin will add everything to dropdown that
    * is listed. To expand, simply define more aliases that corresponded with
    * canonical list.
    *
    * Note: some aliases require htmlentities filtering, which is done directly
    * before output in the dropdown, and while searching for the string to
    * replace in the regex, NOT here. The reason for this is so the alias
    * list does not get littered with characters entity encodings like &lt;,
    * which makes it difficult to immediately know what the aliases do. Also,
    * htmlentities would have to be revered in areas such as title attributes,
    * which counteracts the usefulness of having it done here.
    *
    * @param string $emojiAlias Optional string to return matching translation
    * @return string|array Canonical translation or full alias array
    */
   public function getAliases($emojiAlias = '') {
      return (!$emojiAlias)
         ? $this->aliases
         : $this->aliases[$emojiAlias];
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
    * This is the canonical, e.g., official, list of emoji names along with
    * their associatedwith image file name. For an exhaustive list of emoji
    * names visit http://www.emoji-cheat-sheet.com/ and for the original image
    * files being used, visit https://github.com/taninamdar/Apple-Color-Emoji
    *
    * Note: every canonical emoji name points to an array of strings. This
    * string is ordered CurrentName, OriginalName. Due to the reset()
    * before returning the filename, the first element in the array will be
    * returned, so in this instance CurrentName will be returned. The second,
    * OriginalName, does not have to be written. If ever integrating more emoji
    * files from Apple-Color-Emoji, and wanting to rename them from numbered
    * files, use emojirename.php located in design/images/emoji/.
    *
    * @param type $emojiCanonical Optional string to return matching file name.
    * @return string|array File name or full canonical array
    */
   public function getEmoji($emojiCanonical = '') {
      // Return first value from canonical array
      return (!$emojiCanonical)
         ? $this->emoji
         : $this->buildFilePath($emojiCanonical);
   }

   /**
    *
    * @return array List of Emojis that will appear in the editor.
    */
   public function getEmojiEditorList() {
      if ($this->editorList === null) {
         return $this->getAliases();
      }

      return $this->editorList;
   }

   public function hasEditorList() {
      $editorList = $this->getEmojiEditorList();
      return !empty($editorList);
   }

   /**
    * Set the list of emoji that can be used by the editor.
    *
    * @param array $value The new editor list.
    */
   public function setEmojiEditorList($value) {
      // Convert the editor list to the proper format.
      $list = array();
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
   }

   /**
    * Accept an Emoji path and name, and return the corresponding HTML IMG tag.
    *
    * @param string $emoji_path The full path to Emoji file.
    * @param string $emoji_name The name given to Emoji.
    * @return string The html that represents the emiji.
    */
   public function img($emoji_path, $emoji_name) {
      return sprintf($this->format, Asset($emoji_path), $emoji_name);
   }

   /**
    * This is in case you want to merge the alias list with the canonical list
    * and easily loop through the entire possible set of translations to
    * perform in, for example, the translateToHtml() method, which
    * loops through all the visible emojis, and the hidden canonical ones.
    *
    * @return array Returns array of alias list and canonical list, easily
    *               loopable.
    */
   public function mergeAliasAndEmojiLists() {
      return array_merge($this->getEmojiEditorList(), $this->buildHiddenAliasListFromCanonicalList());
   }

   /**
    * This is useful in case the custom set should be merged with the default
    * set. Any custom emoji tags that match the default will overwrite the
    * default.
    *
    * TODO: this will require the original assetPath to be stored if it's been
    * overwritten.
    *
    * @param array $additionEmoji
    */
   public function mergeAdditionalEmoji($additionEmoji) {
      return array_merge($this->emoji, $additionEmoji);
   }

   /**
    * Note: if setting this to true, it must be the first method called in a
    * plugin that will use custom emojis, but also want to merge the unaccounted
    * for original ones, otherwise the original emojis and path will not be
    * stored.
    *
    * @param bool $bool
    */
   public function mergeOriginals($bool) {
      $this->mergeOriginals = $bool;
   }

   /**
    *
    * @param array $aliases
    */
   public function setAliases($aliases) {
      if (count(array_filter($aliases))) {
         $this->aliases = $aliases;
      }
   }

   /**
    *
    * @param string $assetPath
    */
   public function setAssetPath($assetPath) {
      // Save original asset path for merging default emoji.
      if ($this->mergeOriginals) {
         $this->assetPathOriginal = $this->assetPath;
      }

      $this->assetPath = $assetPath;
   }

   /**
    * Sets custom emoji, and saves the original ones that are unaccounted for.
    *
    * @param array $emoji
    */
   public function setEmoji($emoji) {
      if (count(array_filter($emoji))) {
         // Save the emoji that are unaccounted for in their custom set.
         // This can be used if merging them with the custom set, as they
         // have different assetPaths.
         if ($this->mergeOriginals) {
            $this->emojiOriginalUnaccountedFor = array_diff_key($this->emoji, $emoji);
         }

         // Set custom emoji.
         $this->emoji = $emoji;
      }
   }

   /**
    * Set the emoji from a manifest.
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
         $this->setAssetPath(StringBeginsWith($assetPath, PATH_ROOT, true, true));
      }

      // Set the emoji settings from the manifest.
      if (array_key_exists('emoji', $manifest)) {
         $this->setEmoji($manifest['emoji']);
      }

      if (array_key_exists('aliases', $manifest)) {
         $this->setAliases($manifest['aliases']);
      }

      if (!empty($manifest['format'])) {
         $this->format = $manifest['format'];
      }

      if (array_key_exists('editor', $manifest)) {
         $this->setEmojiEditorList($manifest['editor']);
      }
   }

   /**
    * Translate all emoji aliases to their corresponding Html image tags.
    *
    * Thanks to punbb 1.3.5 (GPL License) for function, which was largely
    * inspired from their do_smilies function.
    *
    * @param string $Text The actual user-submitted post
    * @return string Return the emoji-formatted post
    */
   public function translateToHtml($Text) {
      if (!$this->enabled) {
         return $Text;
      }

		$Text = ' '. $Text .' ';

      // First, translate all aliases. Canonical emoji will get translated
      // out of a loop.
      $emojiAliasList = $this->aliases;

      // Loop through and apply changes to all visible aliases from dropdown
		foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
         $emojiFilePath  = $this->getEmoji($emojiCanonical);

			if (strpos($Text, htmlentities($emojiAlias)) !== false) {
				$Text = preg_replace(
               '`(?<=[>\s]|(&nbsp;))'.preg_quote(htmlentities($emojiAlias), '`').'(?=\W)`m',
               $this->img($emojiFilePath, $emojiAlias),
					$Text
				);
         }
		}

      // Second, translate canonical list, without looping.
      $ldelim = preg_quote($this->ldelim, '`');
      $rdelim = preg_quote($this->rdelim, '`');
      $emoji = $this;

      $Text = preg_replace_callback("`({$ldelim}[a-z0-9_+-]+{$rdelim})`i", function($m) use ($emoji) {
         $emoji_name = trim($m[1], ':');
         $emoji_path = $emoji->getEmoji($emoji_name);
         if ($emoji_path) {
            return $emoji->img($emoji_path, $emoji->ldelim.$emoji_name.$emoji->rdelim);
         } else {
            return $m[0];
         }
      }, $Text);

		return substr($Text, 1, -1);
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
