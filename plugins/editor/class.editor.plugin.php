<?php
/**
 * Editor Plugin
 *
 * @author Dane MacMillan
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package editor
 */

$PluginInfo['editor'] = array(
   'Name' => 'Advanced Editor',
   'Description' => 'Enables advanced editing of posts in several formats, including WYSIWYG, simple HTML, Markdown, and BBCode.',
   'Version' => '1.7.2',
   'Author' => "Dane MacMillan",
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/dane',
   'RequiredApplications' => array('Vanilla' => '>=2.2'),
   'RequiredTheme' => false,
   'RequiredPlugins' => false,
   'HasLocale' => false,
   'MobileFriendly' => true,
   'RegisterPermissions' => array(
      'Plugins.Attachments.Upload.Allow' => 'Garden.Profiles.Edit'
   ),
   'SettingsUrl' => '/settings/editor',
   'SettingsPermission' => 'Garden.Settings.Manage'
);

/**
 * Class EditorPlugin
 */
class EditorPlugin extends Gdn_Plugin {

   /** Base string to be used for generating a memcached key. */
    const DISCUSSION_MEDIA_CACHE_KEY = 'media.discussion.%d';

   /** @var bool  */
    protected $canUpload = false;

   /** @var array Give class access to PluginInfo */
    protected $pluginInfo = array();

   /** @var array List of possible formats the editor supports. */
    protected $Formats = array('Wysiwyg', 'Html', 'Markdown', 'BBCode', 'Text', 'TextEx');

   /** @var string Default format being used for current rendering. Can be one of the formats listed in $Formats. */
    protected $Format;

   /** @var string Asset path for this plugin, set in Gdn_Form_BeforeBodyBox_Handler. */
    protected $AssetPath;

   /**
    * @var string This is used as the input name for file uploads. It will be
    * passed to JS as well. Note that it can be defined as an array, by adding square brackets, e.g., `editorupload[]`,
    * but that will make all the Vanilla upload classes incompatible because they are hardcoded to handle only
    * single files at a time, not an array of files. Perhaps in future make core upload classes more flexible.
    */
    protected $editorFileInputName = 'editorupload';

   /** @var string */
    protected $editorBaseUploadDestinationDir = '';

   /** @var int|mixed  */
    public $ForceWysiwyg = 0;

   /** @var array This will cache the discussion media results for the page request. Populated from either the db or memcached. */
    protected $mediaCache;

   /** @var int How long memcached holds data until it expires. */
    protected $mediaCacheExpire;

   /**
    * Setup some variables for instance.
    */
    public function __construct() {
        parent::__construct();
        $this->mediaCache = null;
        $this->mediaCacheExpire = 60 * 60 * 6;
        $this->AssetPath = Asset('/plugins/editor');
        $this->pluginInfo = Gdn::pluginManager()->getPluginInfo('editor', Gdn_PluginManager::ACCESS_PLUGINNAME);
        $this->ForceWysiwyg = c('Plugins.editor.ForceWysiwyg', false);

       // Check upload permissions
        $this->canUpload = Gdn::session()->checkPermission('Plugins.Attachments.Upload.Allow', false);

        if ($this->canUpload) {
            $PermissionCategory = CategoryModel::permissionCategory(Gdn::controller()->data('Category'));
            if (!val('AllowFileUploads', $PermissionCategory, true)) {
                $this->canUpload = false;
            }
        }

       // Check against config, too
        if (!c('Garden.AllowFileUploads', false)) {
            $this->canUpload = false;
        }
    }

   /**
    * Set the editor actions to true or false to enable or disable the action
    * from displaying in the editor toolbar. This will also let you toggle
    * the separators from appearing between the loosely grouped actions.
    *
    * @return array List of allowed editor actions
    */
    public function getAllowedEditorActions() {
        static $allowedEditorActions = array(
         'bold' => true,
         'italic' => true,
         'strike' => true,
         'orderedlist' => true,
         'unorderedlist' => true,
         'indent' => false,
         'outdent' => false,

         'sep-format' => true, // separator
         'color' => false,
         'highlightcolor' => false, // Dependent on color. TODO add multidim support.
         'format' => true,
         'fontfamily' => false,


         'sep-media' => true, // separator
         'emoji' => true,
         'links' => true,
         'images' => true,
         'uploads' => false,

         'sep-align' => true, // separator
         'alignleft' => true,
         'aligncenter' => true,
         'alignright' => true,

         'sep-switches' => true, // separator
         'togglehtml' => true,
         'fullpage' => true,
         'lights' => true
        );

        return $allowedEditorActions;
    }

   /**
    * To enable more colors in the dropdown, simply expand the array to
    * include more human-readable font color names.
    *
    * Note: in building the dropdown, each color is styled inline, but it will
    * still be required to add the appropriate post-color-* CSS class selectors
    * in the external stylesheet, so that when viewing a posted comment, the
    * color will appear. In addition, the class names must be whitelisted in
    * advanced.js. Not all colors in the CSS stylesheet are included here.
    *
    * Note: use these http://clrs.cc/ and purple: #7b11d0
    *
    * @return array Returns array of font colors to use in dropdown
    */
    protected function getFontColorList() {
        $fontColorList = array(
         'black',
         //'white',
         'gray',
         'red',
         'green',
         'purple',
         'yellow',
         'blue',
         'orange'
         //'olive',
         //'navy',
         //'lime',
         //'silver',
         //'maroon'
        );

        return $fontColorList;
    }

   /**
    * Generate list of font families. Remember to create corresponding CSS.
    *
    * @return array
    */
    public function getFontFamilyOptions() {
        $fontFamilyOptions = array(

         'separator' => array(
            'text' => '',
            'command' => '',
            'value' => '',
            'class' => 'dd-separator',
            'html_tag' => 'div'
         ),

         'default' => array(
            'text' => 'Default font',
            'font-family' => "",
            'command' => 'fontfamily',
            'value' => 'default',
            'class' => 'post-fontfamily-default'
         ),

         'arial' => array(
            'text' => 'Arial',
            'font-family' => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
            'command' => 'fontfamily',
            'value' => 'arial',
            'class' => 'post-fontfamily-arial'
         ),
         'comicsansms' => array(
            'text' => 'Comic Sans MS',
            'font-family' => "'Comic Sans MS', cursive",
            'command' => 'fontfamily',
            'value' => 'comicsansms',
            'class' => 'post-fontfamily-comicsansms'
         ),
         'couriernew' => array(
            'text' => 'Courier New',
            'font-family' => "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
            'command' => 'fontfamily',
            'value' => 'couriernew',
            'class' => 'post-fontfamily-couriernew'
         ),
         'georgia' => array(
            'text' => 'Georgia',
            'font-family' => "Georgia, Times, 'Times New Roman', serif",
            'command' => 'fontfamily',
            'value' => 'georgia',
            'class' => 'post-fontfamily-georgia'
         ),
         'impact' => array(
            'text' => 'Impact',
            'font-family' => "Impact, Haettenschweiler, 'Franklin Gothic Bold', Charcoal, 'Helvetica Inserat', 'Bitstream Vera Sans Bold', 'Arial Black', sans-serif",
            'command' => 'fontfamily',
            'value' => 'impact',
            'class' => 'post-fontfamily-impact'
         ),
         'timesnewroman' => array(
            'text' => 'Times New Roman',
            'font-family' => "'Times New Roman', Times, Baskerville, Georgia, serif",
            'command' => 'fontfamily',
            'value' => 'timesnewroman',
            'class' => 'post-fontfamily-timesnewroman'
         ),
         'trebuchetms' => array(
            'text' => 'Trebuchet MS',
            'font-family' => "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
            'command' => 'fontfamily',
            'value' => 'trebuchetms',
            'class' => 'post-fontfamily-trebuchetms'
         ),
         'verdana' => array(
            'text' => 'Verdana',
            'font-family' => "Verdana, Geneva, sans-serif",
            'command' => 'fontfamily',
            'value' => 'verdana',
            'class' => 'post-fontfamily-verdana'
         )
        );

        return $fontFamilyOptions;
    }

   /**
    * Default formatting options available in the formatting dropdown.
    *
    * Visit https://github.com/xing/wysihtml5/wiki/Supported-Commands for a
    * list of default commands and their allowed values. The array below has
    * custom commands that must exist in the JavaScript, whitelist, and CSS to
    * function.
    *
    * Formatting options can be ordered after the default list has been added.
    * This is done by providing a sort weight to each editor action. If one
    * weight is greater than another, it will be displayed higher than the
    * other.
    *
    * @return array
    */
    protected function getFontFormatOptions() {

       // Stuff like 'heading1' is the editor-action.
        $fontFormatOptions = array(
         'heading1' => array(
            'text' => sprintf(t('Heading %s'), 1),
            'command' => 'formatBlock',
            'value' => 'h1',
            'class' => 'post-font-size-h1',
            'sort' => 100
         ),
         'heading2' => array(
            'text' => sprintf(t('Heading %s'), 2),
            'command' => 'formatBlock',
            'value' => 'h2',
            'class' => 'post-font-size-h2',
            'sort' => 99
         ),
         'separator' => array(
            'text' => '',
            'command' => '',
            'value' => '',
            'class' => 'dd-separator',
            'html_tag' => 'div',
            'sort' => 98
         ),
         'blockquote' => array(
            'text' => t('Quote'),
            'command' => 'blockquote',
            'value' => 'blockquote',
            'class' => '',
            'sort' => 10
         ),
         'code' => array(
            'text' => t('Source Code', 'Code'),
            'command' => 'code',
            'value' => 'code',
            'class' => '',
            'sort' => 9
         ),
         'spoiler' => array(
            'text' => t('Spoiler'),
            'command' => 'spoiler',
            'value' => 'spoiler',
            'class' => '',
            'sort' => 8
         )
        );

        return $fontFormatOptions;
    }

   /**
    * Sort dropdown options by given weight.
    *
    * Currently this is only in use for the formatting options.
    *
    * @param array &$options Options to sort.
    */
    public function sortWeightedOptions(&$options) {
        if (is_array($options)) {
            uasort($options, function ($a, $b) {
                if (!empty($a['sort']) && !empty($b['sort'])) {
                    return ($a['sort'] < $b['sort']);
                }
            });
        }
    }

   /**
    * This method will grab the permissions array from getAllowedEditorActions,
    * build the "kitchen sink" editor toolbar, then filter out the allowed
    * ones and return it.
    *
    * @param array $editorToolbar Holds the final copy of allowed editor actions
    * @param array $editorToolbarAll Holds the "kitchen sink" of editor actions
    * @return array Returns the array of allowed editor toolbar actions
    */
    protected function getEditorToolbar($attributes = array()) {
        $editorToolbar = array();
        $editorToolbarAll = array();
        $allowedEditorActions = $this->getAllowedEditorActions();
        $allowedEditorActions['emoji'] = Emoji::instance()->hasEditorList();
        if (val('FileUpload', $attributes)) {
            $allowedEditorActions['uploads'] = true;
            $allowedEditorActions['images'] = false;
        }
        $fontColorList = $this->getFontColorList();
        $fontFormatOptions = $this->getFontFormatOptions();
        $fontFamilyOptions = $this->getFontFamilyOptions();

       // Let plugins and themes override the defaults.
        $this->EventArguments['actions'] =& $allowedEditorActions;
        $this->EventArguments['colors'] =& $fontColorList;
        $this->EventArguments['format'] =& $fontFormatOptions;
        $this->EventArguments['font'] =& $fontFamilyOptions;
        $this->fireEvent('toolbarConfig');

       // Order the specified dropdowns.
        $this->sortWeightedOptions($fontFormatOptions);

       /**
       * Build color dropdown from array
       */
        $toolbarColorGroups = array();
        $toolbarDropdownFontColor = array();
        $toolbarDropdownFontColorHighlight = array();
        foreach ($fontColorList as $fontColor) {
           // Fore color
            $editorDataAttr = '{"action":"color","value":"'.$fontColor.'"}';
            $toolbarDropdownFontColor[] = array('edit' => 'basic', 'action' => 'color', 'type' => 'button', 'html_tag' => 'span', 'attr' => array('class' => 'color cell-color-'.$fontColor.' editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => $fontColor, /*'title' => t($fontColor),*/
            'data-editor' => $editorDataAttr));

           // Highlight color
            if ($fontColor == 'black') {
                $fontColor = 'white';
            }
            $editorDataAttrHighlight = '{"action":"highlightcolor","value":"'.$fontColor.'"}';
            $toolbarDropdownFontColorHighlight[] = array('edit' => 'basic', 'action' => 'highlightcolor', 'type' => 'button', 'html_tag' => 'span', 'attr' => array('class' => 'color cell-color-'.$fontColor.' editor-dialog-fire-close', 'data-wysihtml5-command' => 'highlightcolor', 'data-wysihtml5-command-value' => $fontColor, /*'title' => t($fontColor),*/
            'data-editor' => $editorDataAttrHighlight));
        }

        $toolbarColorGroups['text'] = $toolbarDropdownFontColor;
        if ($allowedEditorActions['highlightcolor']) {
            $toolbarColorGroups['highlight'] = $toolbarDropdownFontColorHighlight;
        }

       // Build formatting options
        $toolbarFormatOptions = array();
        foreach ($fontFormatOptions as $editorAction => $actionValues) {
            $htmlTag = (!empty($actionValues['html_tag']))
            ? $actionValues['html_tag']
            : 'a';

            $toolbarFormatOptions[] = array(
            'edit' => 'format',
            'action' => $editorAction,
            'type' => 'button',
            'text' => $actionValues['text'],
            'html_tag' => $htmlTag,
            'attr' => array(
               'class' => "editor-action editor-action-{$editorAction} editor-dialog-fire-close {$actionValues['class']}",
               'data-wysihtml5-command' => $actionValues['command'],
               'data-wysihtml5-command-value' => $actionValues['value'],
               'title' => $actionValues['text'],
               'data-editor' => '{"action":"'.$editorAction.'","value":"'.$actionValues['value'].'"}'
            )
            );
        }

       /**
       * Build emoji dropdown from array
       *
       * Using CSS background images instead of img tag, because CSS images
       * do not download until actually displayed on page. display:none
       * prevents browsers from loading the resources.
       */
        $toolbarDropdownEmoji = array();
        $emoji = Emoji::instance();
        $emojiAliasList = $emoji->getEditorList();
        foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
            $emojiFilePath = $emoji->getEmojiPath($emojiCanonical);
            $editorDataAttr = '{"action":"emoji","value":'.json_encode($emojiAlias).'}';

            $toolbarDropdownEmoji[] = array(
            'edit' => 'media',
            'action' => 'emoji',
            'type' => 'button',
            'html_tag' => 'span',
            'text' => $emoji->img($emojiFilePath, $emojiAlias),
            'attr' => array(
               'class' => 'editor-action emoji-'.$emojiCanonical.' editor-dialog-fire-close emoji-wrap',
               'data-wysihtml5-command' => 'insertHTML',
               'data-wysihtml5-command-value' => ' '.$emojiAlias.' ',
               'title' => $emojiAlias,
               'data-editor' => $editorDataAttr));
        }

       // Font family options.
        $toolbarFontFamilyOptions = array();
        foreach ($fontFamilyOptions as $editorAction => $actionValues) {
            $htmlTag = (!empty($actionValues['html_tag']))
            ? $actionValues['html_tag']
            : 'a';

            $toolbarFontFamilyOptions[] = array(
            'edit' => 'fontfamily',
            'action' => $editorAction,
            'type' => 'button',
            'text' => $actionValues['text'],
            'html_tag' => $htmlTag,
            'attr' => array(
               'class' => "editor-action editor-action-{$editorAction} editor-dialog-fire-close {$actionValues['class']}",
               'data-wysihtml5-command' => $actionValues['command'],
               'data-wysihtml5-command-value' => $actionValues['value'],
               'title' => $actionValues['text'],
               'data-editor' => '{"action":"'.$actionValues['command'].'","value":"'.$actionValues['value'].'"}'
            )
            );
        }

       // If enabled, just merge with current formatting dropdown.
        if ($allowedEditorActions['fontfamily']) {
            $toolbarFormatOptions = array_merge($toolbarFormatOptions, $toolbarFontFamilyOptions);
        }


       /**
       * Compile whole list of editor actions into single $editorToolbarAll
       * array. Once complete, loop through allowedEditorActions and filter
       * out the actions that will not be allowed.
       *
       * TODO this is ugly. Pop everything into array, and build this in a loop.
       */

        $editorToolbarAll['bold'] = array('edit' => 'basic', 'action' => 'bold', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-bold editor-dialog-fire-close', 'data-wysihtml5-command' => 'bold', 'title' => t('Bold'), 'data-editor' => '{"action":"bold","value":""}'));
        $editorToolbarAll['italic'] = array('edit' => 'basic', 'action' => 'italic', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-italic editor-dialog-fire-close', 'data-wysihtml5-command' => 'italic', 'title' => t('Italic'), 'data-editor' => '{"action":"italic","value":""}'));
        $editorToolbarAll['strike'] = array('edit' => 'basic', 'action' => 'strike', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-strikethrough editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'strikethrough', 'title' => t('Strikethrough'), 'data-editor' => '{"action":"strike","value":""}'));

        $editorToolbarAll['color'] = array('edit' => 'basic', 'action' => 'color', 'type' =>
         $toolbarColorGroups,
         'attr' => array('class' => 'editor-action icon icon-font editor-dd-color editor-optional-button', 'data-wysihtml5-command-group' => 'foreColor', 'title' => t('Color'), 'data-editor' => '{"action":"color","value":""}'));

        $editorToolbarAll['orderedlist'] = array('edit' => 'format', 'action' => 'orderedlist', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-list-ol editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'insertOrderedList', 'title' => t('Ordered list'), 'data-editor' => '{"action":"orderedlist","value":""}'));
        $editorToolbarAll['unorderedlist'] = array('edit' => 'format', 'action' => 'unorderedlist', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-list-ul editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'insertUnorderedList', 'title' => t('Unordered list'), 'data-editor' => '{"action":"unorderedlist","value":""}'));
        $editorToolbarAll['indent'] = array('edit' => 'format', 'action' => 'indent', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-indent-right editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'indent', 'title' => t('Indent'), 'data-editor' => '{"action":"indent","value":""}'));
        $editorToolbarAll['outdent'] = array('edit' => 'format', 'action' => 'outdent', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-indent-left editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'outdent', 'title' => t('Outdent'), 'data-editor' => '{"action":"outdent","value":""}'));

        $editorToolbarAll['sep-format'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-headers editor-optional-button'));
        $editorToolbarAll['format'] = array('edit' => 'format', 'action' => 'headers', 'type' =>
         $toolbarFormatOptions,
         'attr' => array('class' => 'editor-action icon icon-paragraph editor-dd-format', 'title' => t('Format'), 'data-editor' => '{"action":"format","value":""}'));

        $editorToolbarAll['sep-media'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-media editor-optional-button'));
        $editorToolbarAll['emoji'] = array('edit' => 'media', 'action' => 'emoji', 'type' => $toolbarDropdownEmoji, 'attr' => array('class' => 'editor-action icon icon-smile editor-dd-emoji', 'data-wysihtml5-command' => '', 'title' => t('Emoji'), 'data-editor' => '{"action":"emoji","value":""}'));
        $editorToolbarAll['links'] = array('edit' => 'media', 'action' => 'link', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-link editor-dd-link editor-optional-button', 'data-wysihtml5-command' => 'createLink', 'title' => t('Url'), 'data-editor' => '{"action":"url","value":""}'));
        $editorToolbarAll['images'] = array('edit' => 'media', 'action' => 'image', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-picture editor-dd-image', 'data-wysihtml5-command' => 'insertImage', 'title' => t('Image'), 'data-editor' => '{"action":"image","value":""}'));

        $editorToolbarAll['uploads'] = array('edit' => 'media', 'action' => 'upload', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-file editor-dd-upload', 'data-wysihtml5-command' => '', 'title' => t('Attach image/file'), 'data-editor' => '{"action":"upload","value":""}'));

        $editorToolbarAll['sep-align'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-align editor-optional-button'));
        $editorToolbarAll['alignleft'] = array('edit' => 'format', 'action' => 'alignleft', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-left editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'justifyLeft', 'title' => t('Align left'), 'data-editor' => '{"action":"alignleft","value":""}'));
        $editorToolbarAll['aligncenter'] = array('edit' => 'format', 'action' => 'aligncenter', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-center editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'justifyCenter', 'title' => t('Align center'), 'data-editor' => '{"action":"aligncenter","value":""}'));
        $editorToolbarAll['alignright'] = array('edit' => 'format', 'action' => 'alignright', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-right editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'justifyRight', 'title' => t('Align right'), 'data-editor' => '{"action":"alignright","value":""}'));

        $editorToolbarAll['sep-switches'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-switches editor-optional-button'));
        $editorToolbarAll['togglehtml'] = array('edit' => 'switches', 'action' => 'togglehtml', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-source editor-toggle-source editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-action' => 'change_view', 'title' => t('Toggle HTML view'), 'data-editor' => '{"action":"togglehtml","value":""}'));
        $editorToolbarAll['fullpage'] = array('edit' => 'switches', 'action' => 'fullpage', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-resize-full editor-toggle-fullpage-button editor-dialog-fire-close editor-optional-button', 'title' => t('Toggle full page'), 'data-editor' => '{"action":"fullpage","value":""}'));
        $editorToolbarAll['lights'] = array('edit' => 'switches', 'action' => 'lights', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-adjust editor-toggle-lights-button editor-dialog-fire-close editor-optional-button', 'title' => t('Toggle lights'), 'data-editor' => '{"action":"lights","value":""}'));

       // Filter out disallowed editor actions
        foreach ($allowedEditorActions as $editorAction => $allowed) {
            if ($allowed && isset($editorToolbarAll[$editorAction])) {
                $editorToolbar[$editorAction] = $editorToolbarAll[$editorAction];
            }
        }

        return $editorToolbar;
    }


   /**
    *
    * Vanilla event handlers
    *
    */

   /**
    * Load CSS into head for editor
    */
    public function assetModel_styleCss_handler($Sender) {
        $Sender->addCssFile('vanillicon.css', 'static');
        $Sender->addCssFile('editor.css', 'plugins/editor');
    }


   /**
    * Check if comments are embedded.
    *
    * When editing embedded comments, the editor will still load its assets and
    * render. This method will check whether content is embedded or not. This
    * might not be the best way to do this, but there does not seem to be any
    * easy way to determine whether content is embedded or not.
    *
    * @param Controller $Sender
    * @return bool
    */
    public function isEmbeddedComment($Sender) {
        $isEmbeddedComment = false;
        $requestMethod = array();

        if (isset($Sender->RequestMethod)) {
            $requestMethod[] = strtolower($Sender->RequestMethod);
        }

        if (isset($Sender->OriginalRequestMethod)) {
            $requestMethod[] = strtolower($Sender->OriginalRequestMethod);
        }

        if (count($requestMethod)) {
            $requestMethod = array_map('strtolower', $requestMethod);
            if (in_array('embed', $requestMethod)) {
                $isEmbeddedComment = true;
            }
        }

        return $isEmbeddedComment;
    }

   /**
    * Placed these components everywhere due to some Web sites loading the
    * editor in some areas where the values were not yet injected into HTML.
    */
    public function base_render_before(&$Sender) {

       // Don't render any assets for editor if it's embedded. This effectively
       // disables the editor from embedded comments. Some HTML is still
       // inserted, because of the BeforeBodyBox handler, which does not contain
       // any data relating to embedded content.
        if ($this->isEmbeddedComment($Sender)) {
            return false;
        }

        $c = Gdn::controller();

       // If user wants to modify styling of Wysiwyg content in editor,
       // they can override the styles with this file.
        $CssInfo = AssetModel::cssPath('wysiwyg.css', 'plugins/editor');
        if ($CssInfo) {
            $CssPath = Asset($CssInfo[1]);
        }

       // Load JavaScript used by every editor view.
        $c->addJsFile('editor.js', 'plugins/editor');

       // Fileuploads
        $c->addJsFile('jquery.ui.widget.js', 'plugins/editor');
        $c->addJsFile('jquery.iframe-transport.js', 'plugins/editor');
        $c->addJsFile('jquery.fileupload.js', 'plugins/editor');

       // Set definitions for JavaScript to read
        $c->addDefinition('editorVersion', $this->pluginInfo['Version']);
        $c->addDefinition('editorInputFormat', $this->Format);
        $c->addDefinition('editorPluginAssets', $this->AssetPath);
        $c->addDefinition('wysiwygHelpText', t('editor.WysiwygHelpText', 'You are using <a href="https://en.wikipedia.org/wiki/WYSIWYG" target="_new">Wysiwyg</a> in your post.'));
        $c->addDefinition('bbcodeHelpText', t('editor.BBCodeHelpText', 'You can use <a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a> in your post.'));
        $c->addDefinition('htmlHelpText', t('editor.HtmlHelpText', 'You can use <a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple Html</a> in your post.'));
        $c->addDefinition('markdownHelpText', t('editor.MarkdownHelpText', 'You can use <a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a> in your post.'));
        $c->addDefinition('textHelpText', t('editor.TextHelpText', 'You are using plain text in your post.'));
        $c->addDefinition('editorWysiwygCSS', $CssPath);

       // Set variables for file uploads
        $PostMaxSize = Gdn_Upload::unformatFileSize(ini_get('post_max_size'));
        $FileMaxSize = Gdn_Upload::unformatFileSize(ini_get('upload_max_filesize'));
        $ConfigMaxSize = Gdn_Upload::unformatFileSize(c('Garden.Upload.MaxFileSize', '1MB'));
        $MaxSize = min($PostMaxSize, $FileMaxSize, $ConfigMaxSize);
        $c->addDefinition('maxUploadSize', $MaxSize);
       // Set file input name
        $c->addDefinition('editorFileInputName', $this->editorFileInputName);
        $Sender->setData('_editorFileInputName', $this->editorFileInputName);
       // Save allowed file types
        $c->addDefinition('allowedFileExtensions', json_encode(c('Garden.Upload.AllowedFileExtensions')));
       // Get max file uploads, to be used for max drops at once.
        $c->addDefinition('maxFileUploads', ini_get('max_file_uploads'));
       // Set canUpload definition here, but not Data (set in BeforeBodyBox) because it overwrites.
        $c->addDefinition('canUpload', $this->canUpload);
    }

   /**
    * Attach editor anywhere 'BodyBox' is used. It is not being used for
    * editing a posted reply, so find another event to hook into.
    *
    * @param Gdn_Form $Sender
    */
    public function gdn_form_beforeBodyBox_handler($Sender, $Args) {
       // TODO have some way to prevent this content from getting loaded
       // when in embedded. The only problem is figuring out how to know when
       // content is embedded.

        $attributes = array();
        if (val('Attributes', $Args)) {
            $attributes = val('Attributes', $Args);
        }

       // TODO move this property to constructor
        $this->Format = $Sender->getValue('Format');

       // Make sure we have some sort of format.
        if (!$this->Format) {
            $this->Format = c('Garden.InputFormatter', 'Html');
            $Sender->setValue('Format', $this->Format);
        }

       // If force Wysiwyg enabled in settings
        if (c('Garden.InputFormatter', 'Wysiwyg') == 'Wysiwyg'
         //&& strcasecmp($this->Format, 'wysiwyg') != 0
         && $this->ForceWysiwyg == true
        ) {
            $wysiwygBody = Gdn_Format::to($Sender->getValue('Body'), $this->Format);
            $Sender->setValue('Body', $wysiwygBody);

            $this->Format = 'Wysiwyg';
            $Sender->setValue('Format', $this->Format);
        }

        if (in_array(strtolower($this->Format), array_map('strtolower', $this->Formats))) {
            $c = Gdn::controller();

           // Set minor data for view
            $c->setData('_EditorInputFormat', $this->Format);

           /**
          * Get the generated editor toolbar from getEditorToolbar, and assign
          * it data object for view.
          */
            if (!isset($c->Data['_EditorToolbar'])) {
                $editorToolbar = $this->getEditorToolbar($attributes);
                $this->EventArguments['EditorToolbar'] =& $editorToolbar;
                $this->fireEvent('InitEditorToolbar');

               // Set data for view
                $c->setData('_EditorToolbar', $editorToolbar);
            }

            $c->addDefinition('canUpload', $this->canUpload);
            $c->setData('_canUpload', $this->canUpload);

           // Determine which controller (post or discussion) is invoking this.
           // At the moment they're both the same, but in future you may want
           // to know this information to modify it accordingly.
            $View = $c->fetchView('editor', '', 'plugins/editor');

            $Args['BodyBox'] .= $View;
        }
    }

   /**
    *
    * @param PostController $Sender
    * @param array $Args
    */
    public function postController_editorUpload_create($Sender, $Args = array()) {

       // Require new image thumbnail generator function. Currently it's
       // being symlinked from my vhosts/tests directory. When it makes it
       // into core, it will be available in functions.general.php
        require 'generate_thumbnail.php';

       // Grab raw upload data ($_FILES), essentially. It's only needed
       // because the methods on the Upload class do not expose all variables.
        $fileData = Gdn::request()->getValueFrom(Gdn_Request::INPUT_FILES, $this->editorFileInputName, false);

        $discussionID = ($Sender->Request->post('DiscussionID'))
         ? $Sender->Request->post('DiscussionID')
         : '';

       // JSON payload of media info will get sent back to the client.
        $json = array(
         'error' => 1,
         'feedback' => 'There was a problem.',
         'errors' => array(),
         'payload' => array()
        );

       // New upload instance
        $Upload = new Gdn_Upload();

       // This will validate, such as size maxes, file extensions. Upon doing
       // this, $_FILES is set as a protected property, so all the other
       // Gdn_Upload methods work on it.
        $tmpFilePath = $Upload->validateUpload($this->editorFileInputName);

       // Get base destination path for editor uploads
        $this->editorBaseUploadDestinationDir = $this->getBaseUploadDestinationDir();

       // Pass path, if doesn't exist, will create, and determine if valid.
        $canUpload = Gdn_Upload::canUpload($this->editorBaseUploadDestinationDir);

        if ($tmpFilePath && $canUpload) {
            $fileExtension = strtolower($Upload->getUploadedFileExtension());
            $fileName = $Upload->getUploadedFileName();
            list($tmpwidth, $tmpheight, $imageType) = getimagesize($tmpFilePath);

           // This will return the absolute destination path, including generated
           // filename based on md5_file, and the full path. It
           // will create a filename, with extension, and check if its dir can
           // be writable.
            $absoluteFileDestination = $this->getAbsoluteDestinationFilePath($tmpFilePath, $fileExtension);

           // This is returned by SaveAs
           //$filePathparsed = Gdn_Upload::Parse($absoluteFileDestination);

           // Save original file to uploads, then manipulate from this location if
           // it's a photo. This will also call events in Vanilla so other
           // plugins can tie into this.
            if (empty($imageType)) {
                $filePathParsed = $Upload->saveAs($tmpFilePath, $absoluteFileDestination, array('source' => 'content'));
            } else {
                $filePathParsed = Gdn_UploadImage::saveImageAs($tmpFilePath, $absoluteFileDestination);
                $tmpwidth = $filePathParsed['Width'];
                $tmpheight = $filePathParsed['Height'];
            }

           // Determine if image, and thus requires thumbnail generation, or
           // simply saving the file.

           // Not all files will be images.
            $thumbHeight = '';
            $thumbWidth = '';
            $imageHeight = '';
            $imageWidth = '';
            $thumbPathParsed = array('SaveName' => '');
            $thumbUrl = '';

           // This is a redundant check, because it's in the thumbnail function,
           // but there's no point calling it blindly on every file, so just
           // check here before calling it.
            $generate_thumbnail = false;
            if (in_array($fileExtension, array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'ico'))) {
                $imageHeight = $tmpheight;
                $imageWidth = $tmpwidth;
                $generate_thumbnail = true;
            }

           // Save data to database using model with media table
            $Model = new Gdn_Model('Media');

           // Will be passed to model for database insertion/update.
           // All thumb vars will be empty.
            $Media = array(
            'Name' => $fileName,
            'Type' => $fileData['type'],
            'Size' => $fileData['size'],
            'ImageWidth' => $imageWidth,
            'ImageHeight' => $imageHeight,
            'ThumbWidth' => $thumbWidth,
            'ThumbHeight' => $thumbHeight,
            'InsertUserID' => Gdn::session()->UserID,
            'DateInserted' => date('Y-m-d H:i:s'),
            'StorageMethod' => 'local',
            'Path' => $filePathParsed['SaveName'],
            'ThumbPath' => $thumbPathParsed['SaveName']
            );

           // Get MediaID and pass it to client in payload
            $MediaID = $Model->save($Media);
            $Media['MediaID'] = $MediaID;

           // Clear Media cache for discussion, if any.
           /*if ($discussionID) {
            $cacheKey = sprintf(self::DISCUSSION_MEDIA_CACHE_KEY, $discussionID);
            Gdn::cache()->Remove($cacheKey);
           }*/

            if ($generate_thumbnail) {
                $thumbUrl = url('/utility/mediathumbnail/'.$MediaID, true);
            }

            $payload = array(
            'MediaID' => $MediaID,
            'Filename' => htmlspecialchars($fileName),
            'Filesize' => $fileData['size'],
            'FormatFilesize' => Gdn_Format::bytes($fileData['size'], 1),
            'type' => $fileData['type'],
            'Thumbnail' => '',
            'FinalImageLocation' => '',
            'Parsed' => $filePathParsed,
            'Media' => (array)$Media,
            'original_url' => $Upload->url($filePathParsed['SaveName']),
            'thumbnail_url' => $thumbUrl,
            'original_width' => $imageWidth,
            'original_height' => $imageHeight
            );

            $json = array(
            'error' => 0,
            'feedback' => 'Editor received file successfully.',
            'payload' => $payload
            );
        }

       // Return JSON payload
        echo json_encode($json);
    }


   /**
    * Attach a file to a foreign table and ID.
    *
    * @access protected
    * @param int $FileID
    * @param int $ForeignID
    * @param string $ForeignType Lowercase.
    * @return bool Whether attach was successful.
    */
    protected function attachEditorUploads($FileID, $ForeignID, $ForeignType) {

       // Save data to database using model with media table
        $Model = new Gdn_Model('Media');

        $Media = $Model->getID($FileID);
        if ($Media) {
            $Media->ForeignID = $ForeignID;
            $Media->ForeignTable = $ForeignType;

            try {
                $Model->save($Media);
            } catch (Exception $e) {
                die($e->getMessage());
                return false;
            }
            return true;
        }
        return false;
    }

   /**
    * Remove file from filesystem, and clear db entry.
    *
    * @param type $FileID
    * @param type $ForeignID
    * @param type $ForeignType
    * @return boolean
    */
    protected function deleteEditorUploads($MediaID, $ForeignID = '', $ForeignType = '') {

       // Save data to database using model with media table
        $Model = new Gdn_Model('Media');
        $Media = (array)$Model->getID($MediaID);

        $IsOwner = (!empty($Media['InsertUserID']) && Gdn::session()->UserID == $Media['InsertUserID']);
       // @todo Per-category edit permission would be better, but this global is far simpler to check here.
       // However, this currently matches the permission check in views/attachments.php so keep that in sync.
        $CanDelete = ($IsOwner || Gdn::session()->checkPermission('Garden.Moderation.Manage'));
        if ($Media && $CanDelete) {
            try {
                if ($Model->delete($MediaID)) {
                   // unlink the images.
                    $path = PATH_UPLOADS.'/'.$Media['Path'];
                    $thumbPath = PATH_UPLOADS.'/'.$Media['ThumbPath'];

                    if (file_exists($path)) {
                        unlink($path);
                    }

                    if (file_exists($thumbPath)) {
                        unlink($thumbPath);
                    }

                   // Clear the cache, if exists.
                   /*$discussionID = '';
                   if ($Media['ForeignTable'] == 'discussion') {
                    $discussionID = $Media['ForeignID'];
                   } elseif ($Media['ForeignTable'] == 'comment') {
                    $commentModel = new CommentModel();
                    $commentRow = $commentModel->getID($Media['ForeignID'], DATASET_TYPE_ARRAY);
                    if ($commentRow) {
                     $discussionID = $commentRow['DiscussionID'];
                    }
                   }
                   if ($discussionID) {
                    $cacheKey = sprintf(self::DISCUSSION_MEDIA_CACHE_KEY, $discussionID);
                    Gdn::cache()->Remove($cacheKey);
                   }*/
                }
            } catch (Exception $e) {
                die($e->getMessage());
                return false;
            }
            return true;
        }
        return false;
    }

   /**
    * Save uploads.
    *
    * @param $id
    * @param $type
    */
    public function saveUploads($id, $type) {

       // Array of Media IDs, as input is MediaIDs[]
        $mediaIds = (array)Gdn::request()->getValue('MediaIDs');

        if (count($mediaIds)) {
            foreach ($mediaIds as $mediaId) {
                $this->attachEditorUploads($mediaId, $id, $type);
            }
        }

       // Array of Media IDs to remove, if any.
        $removeMediaIds = (array)Gdn::request()->getValue('RemoveMediaIDs');
       // Clean it if it's empty.
        $removeMediaIds = array_filter($removeMediaIds);

        if (count($removeMediaIds)) {
            foreach ($removeMediaIds as $mediaId) {
                $this->deleteEditorUploads($mediaId, $id, $type);
            }
        }
    }

   /**
    * Attach files to a comment during save.
    *
    * @access public
    * @param object $Sender
    * @param array $Args
    */
    public function postController_afterCommentSave_handler($Sender, $Args) {
        if (!$Args['Comment']) {
            return;
        }

        $CommentID = $Args['Comment']->CommentID;
        if (!$CommentID) {
            return;
        }

        $this->saveUploads($CommentID, 'comment');
    }

   /**
    * Attach files to a discussion during save.
    *
    * @access public
    * @param object $Sender
    * @param array $Args
    */
    public function postController_afterDiscussionSave_handler($Sender, $Args) {
        if (!$Args['Discussion']) {
            return;
        }

        $DiscussionID = $Args['Discussion']->DiscussionID;
        if (!$DiscussionID) {
            return;
        }

        $this->saveUploads($DiscussionID, 'discussion');
    }

   /**
    * Attach files to a message during save.
    *
    * @access public
    * @param object $Sender
    * @param array $Args
    */
    public function messagesController_afterMessageSave_handler($Sender, $Args) {
        if (!$Args['MessageID']) {
            return;
        }

        $MessageID = $Args['MessageID'];
        if (!$MessageID) {
            return;
        }

        $this->saveUploads($MessageID, 'message');
    }

   /**
    * Attach files to a message during conversation save.
    *
    * @access public
    * @param object $Sender
    * @param array $Args
    */
    public function messagesController_afterConversationSave_handler($Sender, $Args) {
        if (!$Args['MessageID']) {
            return;
        }

        $MessageID = $Args['MessageID'];
        if (!$MessageID) {
            return;
        }

        $this->saveUploads($MessageID, 'message');
    }

   /**
    * Attach image to each discussion or comment. It will first perform a
    * single request against the Media table, then filter out the ones that
    * exist per discussion or comment.
    *
    * @param multiple $Controller The controller.
    * @param string $Type The type of row, either discussion or comment.
    * @param array|object $row The row of data being attached to.
    */
    protected function attachUploadsToComment($Sender, $Type = 'comment', $row = null) {

        $param = ucfirst($Type).'ID';
        $foreignId = val($param, val(ucfirst($Type), $Sender->EventArguments));

       // Get all media for the page.
        $mediaList = $this->mediaCache($Sender);

        if (is_array($mediaList)) {
           // Filter out the ones that don't match.
            $attachments = array_filter($mediaList, function ($attachment) use ($foreignId, $Type) {
                if (isset($attachment['ForeignID'])
                && $attachment['ForeignID'] == $foreignId
                && $attachment['ForeignTable'] == $Type
                ) {
                    return true;
                }
            });

            if (count($attachments)) {
               // Loop through the attachments and add a flag if they are found in the source or not.
                $body = Gdn_Format::to(val('Body', $row), val('Format', $row));
                foreach ($attachments as &$attachment) {
                    $src = Gdn_Upload::url($attachment['Path']);
                    $src = preg_replace('`^https?:?`i', '', $src);
                    $src = preg_quote($src);

                    $regex = '`src=["\'](https?:?)?'.$src.'["\']`i';
                    $inbody = (bool)preg_match($regex, $body);

                    $attachment['InBody'] = $inbody;
                }

                $Sender->setData('_attachments', $attachments);
                $Sender->setData('_editorkey', strtolower($param.$foreignId));
                echo $Sender->fetchView($this->getView('attachments.php'));
            }
        }
    }

    /**
     *
     *
     * @param $id
     * @return array
     */
    protected function getConversationMessageIDList($id) {
        $Conversations = array();
        $ConversationMessageModel = new Gdn_Model('ConversationMessage');

       // Query the Media table for discussion media.
        if (is_numeric($id)) {
            $sqlWhere = array(
            'ConversationID' => $id
            );

            $Conversations = $ConversationMessageModel->getWhere(
                $sqlWhere
            )->resultArray();
        }

        $MessageIDList = array();
        foreach ($Conversations as $Conversation) {
            $MessageIDList[] = val('MessageID', $Conversation);
        }
        return $MessageIDList;
    }

   /**
    * Called to prepare data grab, and then cache the results on the software
    * level for the request. This will call PreloadDiscussionMedia, which
    * will either query the db, or query memcached.
    *
    * @param mixed $Sender
    */
    protected function cacheAttachedMedia($Sender) {
        if ($Sender->data('Conversation')) {
            $ConversationMessageIDList = $this->getConversationMessageIDList(val('ConversationID', $Sender->data('Conversation')));
            if (count($ConversationMessageIDList)) {
                $MediaData = $this->preloadDiscussionMedia(val('ConversationID', $Sender->data('Conversation')), $ConversationMessageIDList, 'conversation');
            }
            $this->mediaCache = $MediaData;
            return;
        }

        if ($Sender->data('Messages')) {
            $Message = $Sender->data('Messages')->Result();
            $MessageID = val(0, $Message)->MessageID;
            $MessageIDList = array($MessageID);
            if (count($MessageIDList)) {
                $MediaData = $this->preloadDiscussionMedia(val('ConversationID', $Sender->data('Messages')), $MessageIDList, 'conversation');
            }
            $this->mediaCache = $MediaData;
            return;
        }

        $DiscussionID = null;
        $Comments = $Sender->data('Comments');
        $CommentIDList = array();
        $MediaData = array();

        if ($Sender->data('Discussion.DiscussionID')) {
            $DiscussionID = $Sender->data('Discussion.DiscussionID');
        }

        if (is_null($DiscussionID) && !empty($Comments)) {
            $DiscussionID = $Comments->firstRow()->DiscussionID;
        }

        if ($DiscussionID) {
            if ($Comments instanceof Gdn_DataSet && $Comments->numRows()) {
                $Comments->dataSeek(-1);
                while ($Comment = $Comments->nextRow()) {
                    $CommentIDList[] = $Comment->CommentID;
                }
            } elseif (!empty($Sender->Discussion)) {
                $CommentIDList[] = $Sender->DiscussionID = $Sender->Discussion->DiscussionID;
            }

            if (isset($Sender->Comment) && isset($Sender->Comment->CommentID)) {
                $CommentIDList[] = $Sender->Comment->CommentID;
            }

           // TODO
           // Added note for caching here because it was the CommentIDList that
           // is the main problem.
           // Note about memcaching:
           // Main problem with this is when a new comment is posted. It will only
           // have that current comment in the list, which, after calling
           // PreloadDiscussionMedia, means it will be the only piece of data added
           // to the cache, which prevents all the rest of the comments from loading
           // their own attachments. Consider either adding to the cache when a new
           // file is uploaded, or just getting a list of all comments for a
           // discussion.
           // This is why memcaching has been disabled for now. There are a couple
           // ways to prevent this, but they all seem unnecessary.

            if (count($CommentIDList)) {
                $MediaData = $this->preloadDiscussionMedia($DiscussionID, $CommentIDList);
            }

            $this->mediaCache = $MediaData;
        }
    }

   /**
    * Get media list for inserting into discussion and comments.
    */
    public function mediaCache($Sender) {
        if ($this->mediaCache === null) {
            $this->cacheAttachedMedia($Sender);
        }

        return $this->mediaCache;
    }

   /**
    * Query the Media table for any media related to the current discussion,
    * including all the comments. This will be cached per discussion.
    *
    * @param int $discussionID
    * @param array $commentIDList
    * @return array
    */
    public function preloadDiscussionMedia($discussionID, $commentIDList, $type = 'discussion') {
        $mediaData = array();
        $mediaDataDiscussion = array();
        $mediaDataComment = array();

       /*$cacheKey = sprintf(self::DISCUSSION_MEDIA_CACHE_KEY, $discussionID);
       $cacheResponse = Gdn::cache()->get($cacheKey);
       if ($cacheResponse === Gdn_Cache::CACHEOP_FAILURE) {*/
        $mediaModel = new Gdn_Model('Media');

       // Query the Media table for discussion media.
        if ($type === 'discussion') {
            if (is_numeric($discussionID)) {
                $sqlWhere = array(
                'ForeignTable' => 'discussion',
                'ForeignID' => $discussionID
                );

                $mediaDataDiscussion = $mediaModel->getWhere(
                    $sqlWhere
                )->resultArray();
            }
        }

       // Query the Media table for comment media.

        if (is_numeric($commentIDList)) {
            $commentIDList[] = $commentIDList;
        }

        if (is_array($commentIDList) && count($commentIDList)) {
            $commentIDList = array_filter($commentIDList);

            $sqlWhere = array(
            'ForeignTable' => ($type == 'discussion') ? 'comment' : 'message',
            'ForeignID' => $commentIDList
            );

            $mediaDataComment = $mediaModel->getWhere(
                $sqlWhere
            )->resultArray();
        }

        $mediaData = array_merge($mediaDataDiscussion, $mediaDataComment);
       /*
         Gdn::cache()->store($cacheKey, $mediaData, array(
                Gdn_Cache::FEATURE_EXPIRY => $this->mediaCacheExpire
         ));
       } else {
         $mediaData = $cacheResponse;
       }*/

        return $mediaData;
    }

    public function postController_discussionFormOptions_handler($Sender, $Args) {
        if (!is_null($Discussion = val('Discussion', $Sender, null))) {
            $Sender->EventArguments['Type'] = 'Discussion';
            $Sender->EventArguments['Discussion'] = $Discussion;
            $this->attachUploadsToComment($Sender, 'discussion');
        }
    }

    public function discussionController_afterCommentBody_handler($Sender, $Args) {
        $this->attachUploadsToComment($Sender, 'comment', val('Comment', $Args));
    }

    public function discussionController_afterDiscussionBody_handler($Sender, $Args) {
        $this->attachUploadsToComment($Sender, 'discussion', val('Discussion', $Args));
    }

    public function postController_afterCommentBody_handler($Sender, $Args) {
        $this->attachUploadsToComment($Sender);
    }

    public function messagesController_afterConversationMessageBody_handler($Sender, $Args) {
        $this->attachUploadsToComment($Sender, 'message', val('Message', $Args));
    }

   /**
    * Specific to editor upload paths
    */
    public function getBaseUploadDestinationDir($subdir = false) {
       // Set path
        $basePath = PATH_UPLOADS.'/editor';

        $uploadTargetPath = ($subdir)
         ? $basePath.'/'.$subdir
         : $basePath;

        return $uploadTargetPath;
    }

   /**
    * Instead of using Gdn_Upload->GenerateTargetName, create one that
    * depends on SHA1s, to reduce space for duplicates, and use smarter
    * folder sorting based off the SHA1s.
    *
    * @param type $file
    */
    public function getAbsoluteDestinationFilePath($tmpFilePath, $fileExtension, $uploadDestinationDir = '') {
        $absolutePath = '';

        $basePath = $this->editorBaseUploadDestinationDir;

        if ($basePath != '') {
            $basePath = $this->getBaseUploadDestinationDir();
        }

        if ($uploadDestinationDir) {
            $basePath = $uploadDestinationDir;
        }

       // SHA1 of the tmp file
       //$fileSHA1 = sha1_file($tmpFilePath);
       // Instead just use the RandomString function that
       // Gdn_Upload->GenerateTargetName is using.
        $fileRandomString = strtolower(RandomString(14));

       // Use first two characters from fileMD5 as subdirectory,
       // and use the rest as the file name.
        $dirlen = 2;
        $subdir = substr($fileRandomString, 0, $dirlen);
        $filename = substr($fileRandomString, $dirlen);
        $fileExtension = strtolower($fileExtension);
        $fileDirPath = $basePath.'/'.$subdir;

        if ($this->validateUploadDestinationPath($fileDirPath)) {
            $absolutePath = $fileDirPath.'/'.$filename;
            if ($fileExtension) {
                $absolutePath .= '.'.$fileExtension;
            }
        }

        return $absolutePath;
    }

   /**
    * Check if provided path is valid, creates it if it does not exist, and
    * verifies that it is writable.
    *
    * @param string $path Path to validate
    */
    public function validateUploadDestinationPath($path) {
        $validDestination = true;

       // Check if path exists, and if not, create it.
        if (!file_exists($path)
         && !mkdir($path, 0777, true)
         && !is_writable($path)
        ) {
            $validDestination = false;
        }

        return $validDestination;
    }

   /**
    * Add upload option checkbox to custom permissions for categories.
    *
    * @param Gdn_Controller $Sender
    */
    public function settingsController_addEditCategory_handler($Sender) {
        $Sender->Data['_PermissionFields']['AllowFileUploads'] = array('Control' => 'CheckBox');
    }

   /**
    *
    * @param SettingsController $Sender
    * @param array $Args
    */
    public function settingsController_editor_create($Sender, $Args) {
        $Sender->permission('Garden.Settings.Manage');
        $Cf = new ConfigurationModule($Sender);

        $Formats = array_combine($this->Formats, $this->Formats);

        $Cf->initialize(array(
         'Garden.InputFormatter' => array('LabelCode' => 'Post Format', 'Control' => 'DropDown', 'Description' => '<p>Select the default format of the editor for posts in the community.</p> <p><small><strong>Note:</strong> the editor will auto-detect the format of old posts when editing them and load their original formatting rules. Aside from this exception, the selected post format below will take precedence.</small></p>', 'Items' => $Formats),
         'Plugins.editor.ForceWysiwyg' => array('LabelCode' => 'Reinterpret All Posts As Wysiwyg', 'Control' => 'Checkbox', 'Description' => '<p>Check the below option to tell the editor to reinterpret all old posts as Wysiwyg.</p> <p><small><strong>Note:</strong> This setting will only take effect if Wysiwyg was chosen as the Post Format above. The purpose of this option is to normalize the editor format. If older posts edited with another format, such as markdown or BBCode, are loaded, this option will force Wysiwyg.</p>'),
         'Garden.MobileInputFormatter' => array('LabelCode' => 'Mobile Format', 'Control' => 'DropDown', 'Description' => '<p>Specify an editing format for mobile devices. If mobile devices should have the same experience, specify the same one as above. If users report issues with mobile editing, this is a good option to change.</p>', 'Items' => $Formats, 'DefaultValue' => c('Garden.MobileInputFormatter'))
        ));

       // Add some JS and CSS to blur out option when Wysiwyg not chosen.
        $c = Gdn::controller();
        $c->addJsFile('settings.js', 'plugins/editor');
        $Sender->addCssFile('settings.css', 'plugins/editor');

        $Sender->addSideMenu();
        $Sender->setData('Title', t('Advanced Editor Settings'));
        $Cf->renderAll();
       //$Sender->Cf = $Cf;
       //$Sender->render('settings', '', 'plugins/editor');
    }

   /*
   public function base_GetAppSettingsMenuItems_handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Appearance', t('Appearance'));
      $Menu->addLink('Appearance', 'Advanced Editor', 'settings/editor', 'Garden.Settings.Manage');
   }
   */

   /**
    * Every time editor plugin is enabled, disable other known editors that
    * may clash with this one. If editor is loaded, then thes other
    * editors loaded after, there are CSS rules that hide them. This way,
    * the editor plugin always takes precedence.
    */
    public function setup() {
        $pluginEditors = array(
         'cleditor',
         'ButtonBar',
         'Emotify',
         'FileUpload'
        );

        foreach ($pluginEditors as $pluginName) {
            Gdn::pluginManager()->disablePlugin($pluginName);
        }

        touchConfig(array(
         'Garden.MobileInputFormatter' => 'TextEx',
         'Plugins.editor.ForceWysiwyg' => false
        ));

        $this->structure();
    }

    /**
     *
     *
     * @throws Exception
     */
    public function structure() {
       // Set to false by default, so change in config if uploads allowed.
        touchConfig('Garden.AllowFileUploads', true);

        $Structure = Gdn::structure();
        $Structure
         ->table('Category')
         ->column('AllowFileUploads', 'tinyint(1)', '1')
         ->set();
    }

    public function onDisable() {
       //RemoveFromConfig('Plugin.editor.DefaultView');
    }

    public function cleanUp() {
       //RemoveFromConfig('Plugin.editor.DefaultView');
    }

   /**
    * Create and display a thumbnail of an uploaded file.
    */
    public function utilityController_mediaThumbnail_create($sender, $media_id) {
       // When it makes it into core, it will be available in
       // functions.general.php
        require 'generate_thumbnail.php';

        $model = new Gdn_Model('Media');
        $media = $model->getID($media_id, DATASET_TYPE_ARRAY);

        if (!$media) {
            throw notFoundException('File');
        }

       // Get actual path to the file.
        $local_path = Gdn_Upload::copyLocal($media['Path']);
        if (!file_exists($local_path)) {
            throw notFoundException('File');
        }

        $file_extension = pathinfo($local_path, PATHINFO_EXTENSION);

       // Generate new path for thumbnail
        $thumb_path = $this->getBaseUploadDestinationDir().'/'.'thumb';

       // Grab full path with filename, and validate it.
        $thumb_destination_path = $this->getAbsoluteDestinationFilePath($local_path, $file_extension, $thumb_path);

       // Create thumbnail, and grab debug data from whole process.
        $thumb_payload = generate_thumbnail($local_path, $thumb_destination_path, array(
         // Give preference to height for thumbnail, so height controls.
         'height' => c('Plugins.FileUpload.ThumbnailHeight', 128)
        ));

        if ($thumb_payload['success'] === true) {
           // Thumbnail dimensions
            $thumb_height = round($thumb_payload['result_height']);
            $thumb_width = round($thumb_payload['result_width']);

           // Move the thumbnail to its proper location. Calling SaveAs with
           // cloudfiles enabled will trigger the move to cloudfiles, so use
           // same path for each arg in SaveAs. The file will be removed from
           // the local filesystem.
            $parsed = Gdn_Upload::parse($thumb_destination_path);
            $target = $thumb_destination_path; // $parsed['Name'];
            $Upload = new Gdn_Upload();
            $filepath_parsed = $Upload->saveAs($thumb_destination_path, $target, array('source' => 'content'));

           // Save thumbnail information to DB.
            $model->save(array(
            'MediaID' => $media_id,
            'StorageMethod' => $filepath_parsed['Type'],
            'ThumbWidth' => $thumb_width,
            'ThumbHeight' => $thumb_height,
            'ThumbPath' => $filepath_parsed['SaveName']
            ));

           // Remove cf scratch copy, typically in cftemp, if there was actually
           // a file pulled in from CF.
            if (strpos($local_path, 'cftemp') !== false) {
                if (!unlink($local_path)) {
                   // Maybe add logging for local cf copies not deleted.
                }
            }

            $url = $filepath_parsed['Url'];
        } else {
           // Fix the thumbnail information so this isn't requested again and again.
            $model->save(array(
            'MediaID' => $media_id,
            'ImageWidth' => 0,
            'ImageHeight' => 0,
            'ThumbPath' => ''
            ));

            $url = asset('/plugins/FileUpload/images/file.png');
        }

        redirect($url, 301);
    }

   // Copy the Spoilers plugin functionality into the editor so that plugin
   // can be deprecated without introducing compatibility issues on forums
   // that make heavy use of the spoilers plugin, as well as users who have
   // become accustom to its [spoiler][/spoiler] syntax. This will also allow
   // the spoiler styling and experience to standardize, instead of using
   // two distinct styles and experiences.
    protected function renderSpoilers(&$Sender) {
        $FormatBody = &$Sender->EventArguments['Object']->FormatBody;
       // Fix a wysiwyg but where spoilers
        $FormatBody = preg_replace('`<.+>\s*(\[/?spoiler\])\s*</.+>`', '$1', $FormatBody);

        $FormatBody = preg_replace_callback("/(\[spoiler(?:=(?:&quot;)?([\d\w_',.? ]+)(?:&quot;)?)?\])/siu", array($this, 'SpoilerCallback'), $FormatBody);
        $FormatBody = str_ireplace('[/spoiler]', '</div>', $FormatBody);
    }

    protected function spoilerCallback($Matches) {
        $SpoilerText = (count($Matches) > 2)
         ? $Matches[2]
         : null;

        $SpoilerText = (is_null($SpoilerText))
         ? ''
         : $SpoilerText;

        return '<div class="Spoiler">'.$SpoilerText.'</div>';
    }
}
