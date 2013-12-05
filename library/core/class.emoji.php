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
   public $assetPath = '/resources/emoji';

   /**
    * @var string The sprintf format for emoji with the following parameters.
    * - %1$s: The emoji path.
    * - %2$s: The emoji code.
    */
   public $format = '<img class="emoji" src="%1$s" title="%2$s" alt="%2$s" height="20" />';

   /**
    * @var array An emoji alias list that represents the emoji that display in an editor dropdown.
    */
   protected $editorList;

   /**
    * This array contains all of the emoji avaliable in the system. The array is in the following format:
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
    * @var bool Setting to true will allow editor to interpret emoji aliases as
    *           Html equivalent markup.
    */
   public $emojiInterpretAllow = true;

   /**
    *
    * @var bool Same as above, except interpret all hidden aliases as well. This
    *           var will have no affect if the above is set to false.
    */
   public $emojiInterpretAllowHidden = true;

   /**
    *
    * @var Emoji The singleton instance of this class.
    */
   public static $instance;

   public $ldelim = ':';

   public $rdelim = ':';

   /// Methods ///

   protected function __construct() {
      // Initialize the canonical list. (emoji)
      $this->emojiCanonicalList = array();

      // Initialize the alias list. (emoticons)
      $this->emojiAliasList = array();

      // Translate the emoji.

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
   public function getEmojiAliasList($emojiAlias = '') {
      $emojiAliasList = array(
         ':)'          => 'smile',
         ':D'          => 'smiley',
         ':('          => 'disappointed',
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

      return (!$emojiAlias)
         ? $emojiAliasList
         : $emojiAliasList[$emojiAlias];
   }

   /**
    *
    * @return array List of Emojis that will appear in the editor.
    */
   public function getEmojiEditorList() {
      if ($this->editorList === null)
         return $this->getEmojiAliasList();

      return $this->editorList;
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
   public function getEmojiCanonicalList($emojiCanonical = '') {
      $emojiCanonicalList = array(
        // Smileys
        'relaxed'                      => array('relaxed.png', '50'),
        'grinning'                     => array('grinning.png', '701'),
        'grin'                         => array('grin.png', '702'),
        'joy'                          => array('joy.png', '703'),
        'smiley'                       => array('smiley.png', '704'),
        'smile'                        => array('smile.png', '705'),
        'sweat_smile'                  => array('sweat_smile.png', '706'),
        'satisfied'                    => array('satisfied.png', '707'),
        'innocent'                     => array('innocent.png', '708'),
        'smiling_imp'                  => array('smiling_imp.png', '709'),
        'wink'                         => array('wink.png', '710'),
        'blush'                        => array('blush.png', '711'),
        'yum'                          => array('yum.png', '712'),
        'relieved'                     => array('relieved.png', '713'),
        'heart_eyes'                   => array('heart_eyes.png', '714'),
        'sunglasses'                   => array('sunglasses.png', '715'),
        'smirk'                        => array('smirk.png', '716'),
        'neutral_face'                 => array('neutral_face.png', '717'),
        'expressionless'               => array('expressionless.png', '718'),
        'unamused'                     => array('unamused.png', '719'),
        'sweat'                        => array('sweat.png', '720'),
        'pensive'                      => array('pensive.png', '721'),
        'confused'                     => array('confused.png', '722'),
        'confounded'                   => array('confounded.png', '723'),
        'kissing'                      => array('kissing.png', '724'),
        'kissing_heart'                => array('kissing_heart.png', '725'),
        'kissing_smiling_eyes'         => array('kissing_smiling_eyes.png', '726'),
        'kissing_closed_eyes'          => array('kissing_closed_eyes.png', '727'),
        'stuck_out_tongue'             => array('stuck_out_tongue.png', '728'),
        'stuck_out_tongue_winking_eye' => array('stuck_out_tongue_winking_eye.png', '729'),
        'stuck_out_tongue_closed_eyes' => array('stuck_out_tongue_closed_eyes.png', '730'),
        'disappointed'                 => array('disappointed.png', '731'),
        'worried'                      => array('worried.png', '732'),
        'angry'                        => array('angry.png', '733'),
        'rage'                         => array('rage.png', '734'),
        'cry'                          => array('cry.png', '735'),
        'persevere'                    => array('persevere.png', '736'),
        'triumph'                      => array('triumph.png', '737'),
        'disapponted_relieved'         => array('disappointed_relieved.png', '738'),
        'frowning'                     => array('frowning.png', '739'),
        'anguished'                    => array('anguished.png', '740'),
        'fearful'                      => array('fearful.png', '741'),
        'weary'                        => array('weary.png', '742'),
        'sleepy'                       => array('sleepy.png', '743'),
        'tired_face'                   => array('tired_face.png', '744'),
        'grimacing'                    => array('grimacing.png', '745'),
        'sob'                          => array('sob.png', '746'),
        'open_mouth'                   => array('open_mouth.png', '747'),
        'hushed'                       => array('hushed.png', '748'),
        'cold_sweat'                   => array('cold_sweat.png', '749'),
        'scream'                       => array('scream.png', '750'),
        'astonished'                   => array('astonished.png', '751'),
        'flushed'                      => array('flushed.png', '752'),
        'sleeping'                     => array('sleeping.png', '753'),
        'dizzy_face'                   => array('dizzy_face.png', '754'),
        'no_mouth'                     => array('no_mouth.png', '755'),
        'mask'                         => array('mask.png', '756'),
        'star'                         => array('star.png', '123'),
        'cookie'                       => array('cookie.png', '262'),
        'warning'                      => array('warning.png', '71'),
        'mrgreen'                      => array('mrgreen.png'),

        // Love
        'heart'                        => array('heart.png', '109'),
        'broken_heart'                 => array('broken_heart.png', '506'),
        'kiss'                         => array('kiss.png', '497'),

        // Hand gestures
        '+1'                           => array('+1.png', '435'),
        '-1'                           => array('-1.png', '436'),

        // Custom icons, canonical naming
        'trollface'                    => array('trollface.png', 'trollface'),

        // This is used for aliases that are set incorrectly above or point
        // to items not listed in the canonical list.
        'grey_question'                => array('grey_question.png', '106')
      );

      // Some aliases self-referencing the canonical list. Use this syntax.

      // Vanilla reactions, non-canonical referencing canonical
      $emojiCanonicalList['lol']       = &$emojiCanonicalList['smile'];
      $emojiCanonicalList['wtf']       = &$emojiCanonicalList['dizzy_face'];
      $emojiCanonicalList['agree']     = &$emojiCanonicalList['grinning'];
      $emojiCanonicalList['disagree']  = &$emojiCanonicalList['stuck_out_tongue_closed_eyes'];
      $emojiCanonicalList['awesome']   = &$emojiCanonicalList['heart'];

      // If the $emojiCanonical does not exist in the list, deliver a
      // warning emoji, to degrade gracefully.
      if ($emojiCanonical && !isset($emojiCanonicalList[$emojiCanonical])) {
         $emojiCanonical = 'grey_question';
      }

      // Return first value from canonical array
      return (!$emojiCanonical)
         ? $emojiCanonicalList
         : $this->buildEmojiFilePath(reset($emojiCanonicalList[$emojiCanonical]));
   }

   /**
    * Provide this method with the official emoji filename and it will return
    * the correct path.
    *
    * @param string $emojiFileName File name of emoji icon.
    * @return string Root-relative path.
    */
   public function buildEmojiFilePath($emojiFileName) {
      return $this->assetPath.'/'.$emojiFileName;
   }

   /**
    * This is in case you want to merge the alias list with the canonical list
    * and easily loop through the entire possible set of translations to
    * perform in, for example, the translateEmojiAliasesToHtml() method, which
    * loops through all the visible emojis, and the hidden canonical ones.
    *
    * @return array Returns array of alias list and canonical list, easily
    *               loopable.
    */
   public function mergeAliasAndCanonicalList() {
      return array_merge($this->getEmojiEditorList(), $this->buildHiddenAliasListFromCanonicalList());
   }

   /**
    * This is to easily match the array of the visible alias list that all
    * users will be able to select from. Call the mergeAliasAndCanonicalList()
    * method to merge this array with the alias list, which will then be easy
    * to loop through all the possible emoji displayable in the forum.
    *
    * An alias is [:)]=>[smile], and canonical alias is [:smile:]=>[smile]
    *
    * @return array Returns array that matches format of original alias list
    */
   public function buildHiddenAliasListFromCanonicalList() {
      $caonicalListEmojiNamesCanonical = array_keys($this->getEmojiCanonicalList());
      $caonicalListEmojiNamesAliases = $caonicalListEmojiNamesCanonical;
      array_walk($caonicalListEmojiNamesAliases, array($this, 'buildAliasFormat'));
      return array_combine($caonicalListEmojiNamesAliases, $caonicalListEmojiNamesCanonical);
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
    * Translate all emoji aliases to their corresponding Html image tags.
    *
    * Thanks to punbb 1.3.5 (GPL License) for function, which was largely
    * inspired from their do_smilies function.
    *
    * @param string $Text The actual user-submitted post
    * @return string Return the emoji-formatted post
    */
   public function translateEmojiAliasesToHtml($Text) {
		$Text = ' '. $Text .' ';

      // Determine if hidden emoji aliases are allowed, i.e., the emojis that
      // are not listed in the official alias list array.
      // Note: this was removed to perform the translations in two parts,
      // in lieu of translating a single and potentially large array of Emoji,
      // many of which will not even be in a body of text.
      // TODO: remove.
      /*
      $emojiAliasList = ($this->emojiInterpretAllowHidden)
              ? $this->mergeAliasAndCanonicalList()
              : $this->getEmojiEditorList();
      */

      // First, translate all aliases. Canonical emoji will get translated
      // out of a loop.
      $emojiAliasList = $this->getEmojiEditorList();

      // Loop through and apply changes to all visible aliases from dropdown
		foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
         $emojiFilePath  = $this->getEmojiCanonicalList($emojiCanonical);

			if (strpos($Text, htmlentities($emojiAlias)) !== false) {
				$Text = preg_replace(
               '/(?<=[>\s]|(&nbsp;))'.preg_quote(htmlentities($emojiAlias)).'(?=\W)/m',
               $this->img($emojiFilePath, $emojiAlias),
					$Text
				);
         }
		}

      // Second, translate canonical list, without looping.
      $ldelim = preg_quote($this->ldelim, '`');
      $rdelim = preg_quote($this->rdelim, '`');

      $Text = preg_replace_callback("`({$ldelim}[a-z_+-]+{$rdelim})`i", function($m) {
         $emoji_name = trim($m[1], ':');
         $emoji_path = $this->getEmojiCanonicalList($emoji_name);
         if ($emoji_path) {
            return $this->img($emoji_path, $this->ldelim.$emoji_name.$this->rdelim);
         } else {
            return $m[0];
         }
      }, $Text);

		return substr($Text, 1, -1);
	}

   /**
    * Accept an Emoji path and name, and return the corresponding HTML IMG tag.
    *
    * @param string $emoji_path The full path to Emoji file.
    * @param string $emoji_name The name given to Emoji.
    * @return string The html that represents the emiji.
    */
   public function img($emoji_path, $emoji_name) {
      return sprintf($this->format, $emoji_path, $emoji_name);
   }

   /**
    * Get the singleton instance of this class.
    * @return Emoji
    */
   public static function instance() {
      if (Emoji::$instance === null) {
         Emoji::$instance = new Emoji();
         Gdn::PluginManager()->CallEventHandlers(Emoji::instance(), 'Emoji', 'Init', 'Handler');
      }

      return Emoji::$instance;
   }
}