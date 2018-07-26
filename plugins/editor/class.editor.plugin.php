<?php
/**
 * Editor Plugin
 *
 * @author Dane MacMillan
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package editor
 */

/**
 * Class EditorPlugin
 */
class EditorPlugin extends Gdn_Plugin {

    /** Base string to be used for generating a memcached key. */
    const DISCUSSION_MEDIA_CACHE_KEY = 'media.discussion.%d';

    /** @var bool  */
    protected $canUpload;

    /** @var array Give class access to PluginInfo */
    protected $pluginInfo = [];

    /** @var array List of possible formats the editor supports. */
    protected $Formats = ['Wysiwyg', 'Html', 'Markdown', 'BBCode', 'Text', 'TextEx'];

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
        $this->AssetPath = asset('/plugins/editor');
        $this->pluginInfo = Gdn::pluginManager()->getPluginInfo('editor', Gdn_PluginManager::ACCESS_PLUGINNAME);
        $this->ForceWysiwyg = c('Plugins.editor.ForceWysiwyg', false);

        // Check for additional formats
        $this->EventArguments['formats'] = &$this->Formats;
        $this->fireEvent('GetFormats');
    }

    /**
     * Set the editor actions to true or false to enable or disable the action
     * from displaying in the editor toolbar.
     *
     * This will also let you toggle the separators from appearing between the loosely grouped actions.
     *
     * @return array List of allowed editor actions
     */
    public function getAllowedEditorActions() {
        static $allowedEditorActions = [
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
            'fileuploads' => false,
            'imageuploads' => false,

            'sep-align' => true, // separator
            'alignleft' => true,
            'aligncenter' => true,
            'alignright' => true,

            'sep-switches' => true, // separator
            'togglehtml' => true,
            'fullpage' => true,
            'lights' => true
        ];

        return $allowedEditorActions;
    }

    /**
     * To enable more colors in the dropdown, simply expand the array to include more human-readable font color names.
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
        $fontColorList = [
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
        ];

        return $fontColorList;
    }

    /**
     * Generate list of font families. Remember to create corresponding CSS.
     *
     * @return array
     */
    public function getFontFamilyOptions() {
        $fontFamilyOptions = [
            'separator' => [
                'text' => '',
                'command' => '',
                'value' => '',
                'class' => 'dd-separator',
                'html_tag' => 'div'
            ],
            'default' => [
                'text' => 'Default font',
                'font-family' => "",
                'command' => 'fontfamily',
                'value' => 'default',
                'class' => 'post-fontfamily-default'
            ],
            'arial' => [
                'text' => 'Arial',
                'font-family' => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
                'command' => 'fontfamily',
                'value' => 'arial',
                'class' => 'post-fontfamily-arial'
            ],
            'comicsansms' => [
                'text' => 'Comic Sans MS',
                'font-family' => "'Comic Sans MS', cursive",
                'command' => 'fontfamily',
                'value' => 'comicsansms',
                'class' => 'post-fontfamily-comicsansms'
            ],
            'couriernew' => [
                'text' => 'Courier New',
                'font-family' => "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
                'command' => 'fontfamily',
                'value' => 'couriernew',
                'class' => 'post-fontfamily-couriernew'
            ],
            'georgia' => [
                'text' => 'Georgia',
                'font-family' => "Georgia, Times, 'Times New Roman', serif",
                'command' => 'fontfamily',
                'value' => 'georgia',
                'class' => 'post-fontfamily-georgia'
            ],
            'impact' => [
                'text' => 'Impact',
                'font-family' => "Impact, Haettenschweiler, 'Franklin Gothic Bold', Charcoal, 'Helvetica Inserat', 'Bitstream Vera Sans Bold', 'Arial Black', sans-serif",
                'command' => 'fontfamily',
                'value' => 'impact',
                'class' => 'post-fontfamily-impact'
            ],
            'timesnewroman' => [
                'text' => 'Times New Roman',
                'font-family' => "'Times New Roman', Times, Baskerville, Georgia, serif",
                'command' => 'fontfamily',
                'value' => 'timesnewroman',
                'class' => 'post-fontfamily-timesnewroman'
            ],
            'trebuchetms' => [
                'text' => 'Trebuchet MS',
                'font-family' => "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
                'command' => 'fontfamily',
                'value' => 'trebuchetms',
                'class' => 'post-fontfamily-trebuchetms'
            ],
            'verdana' => [
                'text' => 'Verdana',
                'font-family' => "Verdana, Geneva, sans-serif",
                'command' => 'fontfamily',
                'value' => 'verdana',
                'class' => 'post-fontfamily-verdana'
            ]
        ];

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
        $fontFormatOptions = [
            'heading1' => [
                'text' => sprintf(t('Heading %s'), 1),
                'command' => 'formatBlock',
                'value' => 'h1',
                'class' => 'post-font-size-h1',
                'sort' => 100
            ],
            'heading2' => [
                'text' => sprintf(t('Heading %s'), 2),
                'command' => 'formatBlock',
                'value' => 'h2',
                'class' => 'post-font-size-h2',
                'sort' => 99
            ],
            'separator' => [
                'text' => '',
                'command' => '',
                'value' => '',
                'class' => 'dd-separator',
                'html_tag' => 'div',
                'sort' => 98
            ],
            'blockquote' => [
                'text' => t('Quote'),
                'command' => 'blockquote',
                'value' => 'blockquote',
                'class' => '',
                'sort' => 10
            ],
            'code' => [
                'text' => t('Source Code', 'Code'),
                'command' => 'code',
                'value' => 'code',
                'class' => '',
                'sort' => 9
            ],
            'spoiler' => [
                'text' => t('Spoiler'),
                'command' => 'spoiler',
                'value' => 'spoiler',
                'class' => '',
                'sort' => 8
            ]
        ];

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
            uasort($options, function($a, $b) {
                if (!empty($a['sort']) && !empty($b['sort'])) {
                    return ($a['sort'] < $b['sort']);
                }
            });
        }
    }

    /**
     * This method will grab the permissions array from getAllowedEditorActions,
     * build the "kitchen sink" editor toolbar, then filter out the allowed ones and return it.
     *
     * @param array $editorToolbar Holds the final copy of allowed editor actions
     * @param array $editorToolbarAll Holds the "kitchen sink" of editor actions
     * @return array Returns the array of allowed editor toolbar actions
     */
    protected function getEditorToolbar($attributes = []) {
        $editorToolbar = [];
        $editorToolbarAll = [];
        $allowedEditorActions = $this->getAllowedEditorActions();
        $allowedEditorActions['emoji'] = Emoji::instance()->hasEditorList();
        $fileUpload = val('FileUpload', $attributes);
        $imageUpload = $fileUpload || val('ImageUpload', $attributes, true);
        if (($fileUpload || $imageUpload) && $this->canUpload()) {
            $allowedEditorActions['fileuploads'] = $fileUpload;
            $allowedEditorActions['imageuploads'] = $imageUpload;
            $allowedEditorActions['images'] = !$imageUpload;
        }
        $fontColorList = $this->getFontColorList();
        $fontFormatOptions = $this->getFontFormatOptions();
        $fontFamilyOptions = $this->getFontFamilyOptions();

        // Let plugins and themes override the defaults.
        $this->EventArguments['actions'] = &$allowedEditorActions;
        $this->EventArguments['colors'] = &$fontColorList;
        $this->EventArguments['format'] = &$fontFormatOptions;
        $this->EventArguments['font'] = &$fontFamilyOptions;
        $this->fireEvent('toolbarConfig');

        // Order the specified dropdowns.
        $this->sortWeightedOptions($fontFormatOptions);

        // Build color dropdown from array
        $toolbarColorGroups = [];
        $toolbarDropdownFontColor = [];
        $toolbarDropdownFontColorHighlight = [];
        foreach ($fontColorList as $fontColor) {
            // Fore color
            $editorDataAttr = '{"action":"color","value":"'.$fontColor.'"}';
            $toolbarDropdownFontColor[] = ['edit' => 'basic', 'action' => 'color', 'type' => 'button', 'html_tag' => 'span', 'attr' => ['class' => 'color cell-color-'.$fontColor.' editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => $fontColor, /*'title' => t($fontColor),*/
            'data-editor' => $editorDataAttr]];

            // Highlight color
            if ($fontColor == 'black') {
                $fontColor = 'white';
            }
            $editorDataAttrHighlight = '{"action":"highlightcolor","value":"'.$fontColor.'"}';
            $toolbarDropdownFontColorHighlight[] = ['edit' => 'basic', 'action' => 'highlightcolor', 'type' => 'button', 'html_tag' => 'span', 'attr' => ['class' => 'color cell-color-'.$fontColor.' editor-dialog-fire-close', 'data-wysihtml5-command' => 'highlightcolor', 'data-wysihtml5-command-value' => $fontColor, /*'title' => t($fontColor),*/
            'data-editor' => $editorDataAttrHighlight]];
        }

        $toolbarColorGroups['text'] = $toolbarDropdownFontColor;
        if ($allowedEditorActions['highlightcolor']) {
            $toolbarColorGroups['highlight'] = $toolbarDropdownFontColorHighlight;
        }

        // Build formatting options
        $toolbarFormatOptions = [];
        foreach ($fontFormatOptions as $editorAction => $actionValues) {
            $htmlTag = (!empty($actionValues['html_tag'])) ? $actionValues['html_tag'] : 'a';
            $toolbarFormatOptions[] = [
            'edit' => 'format',
            'action' => $editorAction,
            'type' => 'button',
            'text' => $actionValues['text'],
            'html_tag' => $htmlTag,
            'attr' => [
                'class' => "editor-action editor-action-{$editorAction} editor-dialog-fire-close {$actionValues['class']}",
                'data-wysihtml5-command' => $actionValues['command'],
                'data-wysihtml5-command-value' => $actionValues['value'],
                'title' => $actionValues['text'],
                'data-editor' => '{"action":"'.$editorAction.'","value":"'.$actionValues['value'].'"}'
            ]
            ];
        }

        // Build emoji dropdown from array.
        // Using CSS background images instead of img tag, because CSS images
        // do not download until actually displayed on page. display:none prevents browsers from loading the resources.
        $toolbarDropdownEmoji = [];
        $emoji = Emoji::instance();
        $emojiAliasList = $emoji->getEditorList();
        foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
            $emojiFilePath = $emoji->getEmojiPath($emojiCanonical);
            $editorDataAttr = '{"action":"emoji","value":'.json_encode($emojiAlias).'}';
            $toolbarDropdownEmoji[] = [
            'edit' => 'media',
            'action' => 'emoji',
            'type' => 'button',
            'html_tag' => 'span',
            'text' => $emoji->img($emojiFilePath, $emojiAlias),
            'attr' => [
                'class' => 'editor-action emoji-'.$emojiCanonical.' editor-dialog-fire-close emoji-wrap',
                'data-wysihtml5-command' => 'insertHTML',
                'data-wysihtml5-command-value' => ' '.$emojiAlias.' ',
                'title' => $emojiAlias,
                'data-editor' => $editorDataAttr]];
        }

        // Font family options.
        $toolbarFontFamilyOptions = [];
        foreach ($fontFamilyOptions as $editorAction => $actionValues) {
            $htmlTag = (!empty($actionValues['html_tag'])) ? $actionValues['html_tag'] : 'a';
            $toolbarFontFamilyOptions[] = [
                'edit' => 'fontfamily',
                'action' => $editorAction,
                'type' => 'button',
                'text' => $actionValues['text'],
                'html_tag' => $htmlTag,
                'attr' => [
                    'class' => "editor-action editor-action-{$editorAction} editor-dialog-fire-close {$actionValues['class']}",
                    'data-wysihtml5-command' => $actionValues['command'],
                    'data-wysihtml5-command-value' => $actionValues['value'],
                    'title' => $actionValues['text'],
                    'data-editor' => '{"action":"'.$actionValues['command'].'","value":"'.$actionValues['value'].'"}'
                ]
            ];
        }

        // If enabled, just merge with current formatting dropdown.
        if ($allowedEditorActions['fontfamily']) {
            $toolbarFormatOptions = array_merge($toolbarFormatOptions, $toolbarFontFamilyOptions);
        }

        // Compile whole list of editor actions into single $editorToolbarAll array.
        // Once complete, loop through allowedEditorActions and filter out the actions that will not be allowed.
        $editorToolbarAll['bold'] = ['edit' => 'basic', 'action' => 'bold', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-bold editor-dialog-fire-close', 'data-wysihtml5-command' => 'bold', 'title' => t('Bold'), 'data-editor' => '{"action":"bold","value":""}']];
        $editorToolbarAll['italic'] = ['edit' => 'basic', 'action' => 'italic', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-italic editor-dialog-fire-close', 'data-wysihtml5-command' => 'italic', 'title' => t('Italic'), 'data-editor' => '{"action":"italic","value":""}']];
        $editorToolbarAll['strike'] = ['edit' => 'basic', 'action' => 'strike', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-strikethrough editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'strikethrough', 'title' => t('Strikethrough'), 'data-editor' => '{"action":"strike","value":""}']];

        $editorToolbarAll['color'] = ['edit' => 'basic', 'action' => 'color', 'type' =>
            $toolbarColorGroups,
            'attr' => ['class' => 'editor-action icon icon-font editor-dd-color editor-optional-button', 'data-wysihtml5-command-group' => 'foreColor', 'title' => t('Color'), 'data-editor' => '{"action":"color","value":""}']];

        $editorToolbarAll['orderedlist'] = ['edit' => 'format', 'action' => 'orderedlist', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-list-ol editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'insertOrderedList', 'title' => t('Ordered list'), 'data-editor' => '{"action":"orderedlist","value":""}']];
        $editorToolbarAll['unorderedlist'] = ['edit' => 'format', 'action' => 'unorderedlist', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-list-ul editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'insertUnorderedList', 'title' => t('Unordered list'), 'data-editor' => '{"action":"unorderedlist","value":""}']];
        $editorToolbarAll['indent'] = ['edit' => 'format', 'action' => 'indent', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-indent-right editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'indent', 'title' => t('Indent'), 'data-editor' => '{"action":"indent","value":""}']];
        $editorToolbarAll['outdent'] = ['edit' => 'format', 'action' => 'outdent', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-indent-left editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'outdent', 'title' => t('Outdent'), 'data-editor' => '{"action":"outdent","value":""}']];

        $editorToolbarAll['sep-format'] = ['type' => 'separator', 'attr' => ['class' => 'editor-sep sep-headers editor-optional-button']];
        $editorToolbarAll['format'] = ['edit' => 'format', 'action' => 'headers', 'type' =>
            $toolbarFormatOptions,
            'attr' => ['class' => 'editor-action icon icon-paragraph editor-dd-format', 'title' => t('Format'), 'data-editor' => '{"action":"format","value":""}']];

        $editorToolbarAll['sep-media'] = ['type' => 'separator', 'attr' => ['class' => 'editor-sep sep-media editor-optional-button']];
        $editorToolbarAll['emoji'] = ['edit' => 'media', 'action' => 'emoji', 'type' => $toolbarDropdownEmoji, 'attr' => ['class' => 'editor-action icon icon-smile editor-dd-emoji', 'data-wysihtml5-command' => '', 'title' => t('Emoji'), 'data-editor' => '{"action":"emoji","value":""}']];
        $editorToolbarAll['links'] = ['edit' => 'media', 'action' => 'link', 'type' => [], 'attr' => ['class' => 'editor-action icon icon-link editor-dd-link editor-optional-button', 'data-wysihtml5-command' => 'createLink', 'title' => t('Url'), 'data-editor' => '{"action":"url","value":""}']];
        $editorToolbarAll['images'] = ['edit' => 'media', 'action' => 'image', 'type' => [], 'attr' => ['class' => 'editor-action icon icon-picture editor-dd-image', 'data-wysihtml5-command' => 'insertImage', 'title' => t('Image'), 'data-editor' => '{"action":"image","value":""}']];

        $editorToolbarAll['fileuploads'] = ['edit' => 'media', 'action' => 'fileupload', 'type' => [], 'attr' => ['class' => 'editor-action icon icon-file editor-dd-fileupload', 'data-wysihtml5-command' => '', 'title' => t('Attach file'), 'data-editor' => '{"action":"fileupload","value":""}']];
        $editorToolbarAll['imageuploads'] = ['edit' => 'media', 'action' => 'imageupload', 'type' => [], 'attr' => ['class' => 'editor-action icon icon-picture editor-dd-imageupload', 'data-wysihtml5-command' => '', 'title' => t('Attach image'), 'data-editor' => '{"action":"imageupload","value":""}']];

        $editorToolbarAll['sep-align'] = ['type' => 'separator', 'attr' => ['class' => 'editor-sep sep-align editor-optional-button']];
        $editorToolbarAll['alignleft'] = ['edit' => 'format', 'action' => 'alignleft', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-align-left editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'justifyLeft', 'title' => t('Align left'), 'data-editor' => '{"action":"alignleft","value":""}']];
        $editorToolbarAll['aligncenter'] = ['edit' => 'format', 'action' => 'aligncenter', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-align-center editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'justifyCenter', 'title' => t('Align center'), 'data-editor' => '{"action":"aligncenter","value":""}']];
        $editorToolbarAll['alignright'] = ['edit' => 'format', 'action' => 'alignright', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-align-right editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-command' => 'justifyRight', 'title' => t('Align right'), 'data-editor' => '{"action":"alignright","value":""}']];

        $editorToolbarAll['sep-switches'] = ['type' => 'separator', 'attr' => ['class' => 'editor-sep sep-switches editor-optional-button']];
        $editorToolbarAll['togglehtml'] = ['edit' => 'switches', 'action' => 'togglehtml', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-source editor-toggle-source editor-dialog-fire-close editor-optional-button', 'data-wysihtml5-action' => 'change_view', 'title' => t('Toggle HTML view'), 'data-editor' => '{"action":"togglehtml","value":""}']];
        $editorToolbarAll['fullpage'] = ['edit' => 'switches', 'action' => 'fullpage', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-resize-full editor-toggle-fullpage-button editor-dialog-fire-close editor-optional-button', 'title' => t('Toggle full page'), 'data-editor' => '{"action":"fullpage","value":""}']];
        $editorToolbarAll['lights'] = ['edit' => 'switches', 'action' => 'lights', 'type' => 'button', 'attr' => ['class' => 'editor-action icon icon-adjust editor-toggle-lights-button editor-dialog-fire-close editor-optional-button', 'title' => t('Toggle lights'), 'data-editor' => '{"action":"lights","value":""}']];

        // Filter out disallowed editor actions
        foreach ($allowedEditorActions as $editorAction => $allowed) {
            if ($allowed && isset($editorToolbarAll[$editorAction])) {
                $editorToolbar[$editorAction] = $editorToolbarAll[$editorAction];
            }
        }

        return $editorToolbar;
    }

    /**
     * Load CSS into head for editor
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('vanillicon.css', 'static');
        $sender->addCssFile('editor.css', 'plugins/editor');
    }


    /**
     * Check if comments are embedded.
     *
     * When editing embedded comments, the editor will still load its assets and
     * render. This method will check whether content is embedded or not. This
     * might not be the best way to do this, but there does not seem to be any
     * easy way to determine whether content is embedded or not.
     *
     * @param Controller $sender
     * @return bool
     */
    public function isEmbeddedComment($sender) {
        $isEmbeddedComment = false;
        $requestMethod = [];

        if (isset($sender->RequestMethod)) {
            $requestMethod[] = strtolower($sender->RequestMethod);
        }

        if (isset($sender->OriginalRequestMethod)) {
            $requestMethod[] = strtolower($sender->OriginalRequestMethod);
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
    public function base_render_before($sender) {
        // Don't render any assets for editor if it's embedded. This effectively
        // disables the editor from embedded comments. Some HTML is still
        // inserted, because of the BeforeBodyBox handler, which does not contain any data relating to embedded content.
        if ($this->isEmbeddedComment($sender)) {
            return false;
        }

        $c = Gdn::controller();

        // If user wants to modify styling of Wysiwyg content in editor,
        // they can override the styles with this file.
        $cssInfo = AssetModel::cssPath('wysiwyg.css', 'plugins/editor');
        if ($cssInfo) {
            $cssPath = asset($cssInfo[1]);
        }

        // Load JavaScript used by every editor view.
        $c->addJsFile('editor.js', 'plugins/editor');

        if (Gdn_Theme::inSection('Dashboard')) {
            // Add some JS and CSS to blur out option when Wysiwyg not chosen.
            $c->addJsFile('settings.js', 'plugins/editor');
            $c->addCssFile('settings.css', 'plugins/editor');
        }

        // Fileuploads
        $c->addJsFile('jquery.ui.widget.js', 'plugins/editor');
        $c->addJsFile('jquery.iframe-transport.js', 'plugins/editor');
        $c->addJsFile('jquery.fileupload.js', 'plugins/editor');

        // Set definitions for JavaScript to read
        $c->addDefinition('editorVersion', $this->pluginInfo['Version']);
        $c->addDefinition('editorInputFormat', $this->Format);
        $c->addDefinition('editorPluginAssets', $this->AssetPath);
        $c->addDefinition('fileUpload-remove', t('Remove file'));
        $c->addDefinition('fileUpload-reattach', t('Click to re-attach'));
        $c->addDefinition('fileUpload-inserted', t('Inserted'));
        $c->addDefinition('fileUpload-insertedTooltip', t('This image has been inserted into the body of text.'));
        $c->addDefinition('wysiwygHelpText', t('editor.WysiwygHelpText', 'You are using <a href="https://en.wikipedia.org/wiki/WYSIWYG" target="_new">WYSIWYG</a> in your post.'));
        $c->addDefinition('bbcodeHelpText', t('editor.BBCodeHelpText', 'You can use <a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a> in your post.'));
        $c->addDefinition('htmlHelpText', t('editor.HtmlHelpText', 'You can use <a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple HTML</a> in your post.'));
        $c->addDefinition('markdownHelpText', t('editor.MarkdownHelpText', 'You can use <a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a> in your post.'));
        $c->addDefinition('textHelpText', t('editor.TextHelpText', 'You are using plain text in your post.'));
        $c->addDefinition('editorWysiwygCSS', $cssPath);
        $c->addDefinition('canUpload', $this->canUpload());
        $c->addDefinition('fileErrorSize', t('editor.fileErrorSize', 'File size is too large.'));
        $c->addDefinition('fileErrorFormat', t('editor.fileErrorFormat', 'File format is not allowed.'));
        $c->addDefinition('fileErrorSizeFormat', t('editor.fileErrorSizeFormat', 'File size is too large and format is not allowed.'));

        $additionalDefinitions = [];
        $this->EventArguments['definitions'] = &$additionalDefinitions;
        $this->fireEvent('GetJSDefinitions');

        // Make sure we still have an array after all event handlers have had their turn and iterate through the result.
        if (is_array($additionalDefinitions)) {
            foreach ($additionalDefinitions as $defKey => $defVal) {
                $c->addDefinition($defKey, $defVal);
            }
            unset($defKey, $defVal);
        }

        // Set variables for file uploads
        $postMaxSize = Gdn_Upload::unformatFileSize(ini_get('post_max_size'));
        $fileMaxSize = Gdn_Upload::unformatFileSize(ini_get('upload_max_filesize'));
        $configMaxSize = Gdn_Upload::unformatFileSize(c('Garden.Upload.MaxFileSize', '1MB'));
        $maxSize = min($postMaxSize, $fileMaxSize, $configMaxSize);
        $c->addDefinition('maxUploadSize', $maxSize);

        // Set file input name
        $c->addDefinition('editorFileInputName', $this->editorFileInputName);
        $sender->setData('_editorFileInputName', $this->editorFileInputName);
        // Save allowed file types
        $allowedFileExtensions = c('Garden.Upload.AllowedFileExtensions');
        $imageExtensions = ['gif', 'png', 'jpeg', 'jpg', 'bmp', 'tif', 'tiff', 'svg'];

        $allowedImageExtensions = array_intersect($allowedFileExtensions, $imageExtensions);

        $c->addDefinition('allowedImageExtensions', json_encode($allowedImageExtensions));
        $c->addDefinition('allowedFileExtensions', json_encode($allowedFileExtensions));

        $allowedMimeTypes = $this->getAllowedMimeTypes();

        $allowedImageMimeTypes = [];
        foreach($allowedImageExtensions as $ext) {
            if ($mime = $this->lookupMime($ext)) {
                $allowedImageMimeTypes = array_merge($allowedImageMimeTypes, $mime);
            }
        }

        // Prefix extension strings with a dot.
        $prependDot = function($str) {
            return '.'.$str;
        };

        // prepend extensions with a '.'
        $allowedFileExtensions = array_map($prependDot, $allowedFileExtensions);
        $accept = implode(',', array_merge($allowedFileExtensions, $allowedMimeTypes));
        $sender->setData('Accept', $accept);

        // prepend extensions with a '.'
        $allowedImageExtensions = array_map($prependDot, $allowedImageExtensions);
        $acceptImage = implode(',', array_merge($allowedImageExtensions, $allowedImageMimeTypes));
        $sender->setData('AcceptImage', $acceptImage);

        // Get max file uploads, to be used for max drops at once.
        $c->addDefinition('maxFileUploads', ini_get('max_file_uploads'));
    }

    /**
     * Attach editor anywhere 'BodyBox' is used.
     *
     * It is not being used for editing a posted reply, so find another event to hook into.
     *
     * @param Gdn_Form $sender
     */
    public function gdn_form_beforeBodyBox_handler($sender, $args) {
        // TODO have some way to prevent this content from getting loaded when in embedded.
        // The only problem is figuring out how to know when content is embedded.

        $attributes = [];
        if (val('Attributes', $args)) {
            $attributes = val('Attributes', $args);
        }

        // TODO move this property to constructor
        $this->Format = $sender->getValue('Format');

        // Make sure we have some sort of format.
        if (!$this->Format) {
            $this->Format = c('Garden.InputFormatter', 'Html');
            $sender->setValue('Format', $this->Format);
        }

        // If force Wysiwyg enabled in settings
        $needsConversion = (!in_array($this->Format, ['Wysiwyg']));
        if (c('Garden.InputFormatter', 'Wysiwyg') == 'Wysiwyg' && $this->ForceWysiwyg == true && $needsConversion) {
            $wysiwygBody = Gdn_Format::to($sender->getValue('Body'), $this->Format);
            $sender->setValue('Body', $wysiwygBody);

            $this->Format = 'Wysiwyg';
            $sender->setValue('Format', $this->Format);
        }

        if (in_array(strtolower($this->Format), array_map('strtolower', $this->Formats))) {
            $c = Gdn::controller();

            // Set minor data for view
            $c->setData('_EditorInputFormat', $this->Format);

            // Get the generated editor toolbar from getEditorToolbar, and assign it data object for view.
            if (!isset($c->Data['_EditorToolbar'])) {
                $editorToolbar = $this->getEditorToolbar($attributes);
                $this->EventArguments['EditorToolbar'] = &$editorToolbar;
                $this->fireEvent('InitEditorToolbar');

                // Set data for view
                $c->setData('_EditorToolbar', $editorToolbar);
            }

            // Determine which controller (post or discussion) is invoking this.
            // At the moment they're both the same, but in future you may want
            // to know this information to modify it accordingly.
            $view = $c->fetchView('editor', '', 'plugins/editor');

            $args['BodyBox'] .= $view;
        }
    }

    /**
     * Get a list of valid MIME types for file uploads.
     *
     * @return array
     */
    private function getAllowedMimeTypes() {
        $result = [];

        $allowedExtensions = c('Garden.Upload.AllowedFileExtensions', []);
        if (is_array($allowedExtensions)) {
            foreach ($allowedExtensions as $extension) {
                if ($mimeTypes = $this->lookupMime($extension)) {
                    $result = array_merge($result, $mimeTypes);
                }
            }
        }

        return $result;
    }

    /**
     * Endpoint to upload files.
     *
     * @param PostController $sender
     * @param array $args Expects the first argument to be the type of the upload, either 'file', 'image', or 'unknown'.
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function postController_editorUpload_create($sender, $args = []) {
        $sender->permission('Garden.SignIn.Allow');

        // @Todo Move to a library/functions file.
        require 'generate_thumbnail.php';

        // Grab raw upload data ($_FILES), essentially. It's only needed
        // because the methods on the Upload class do not expose all variables.
        $fileData = Gdn::request()->getValueFrom(Gdn_Request::INPUT_FILES, $this->editorFileInputName, false);

        $mimeType = $fileData['type'];
        $allowedMimeTypes = $this->getAllowedMimeTypes();
        // When a MIME type fails validation, we set it to "application/octet-stream" to prevent a malicious type.
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $fileData['type'] = 'application/octet-stream';
        }

        $discussionID = ($sender->Request->post('DiscussionID')) ? $sender->Request->post('DiscussionID') : '';

        // JSON payload of media info will get sent back to the client.
        $json = [
            'error' => 1,
            'feedback' => 'There was a problem.',
            'errors' => [],
            'payload' => []
        ];

        // New upload instance
        $upload = new Gdn_Upload();

        // Upload type is either 'file', for an upload that adds an attachment to the post, or 'image' for an upload
        // that is automatically inserted into the post. If the user uploads using drag-and-drop rather than browsing
        // for the files using one of the dropdowns, we assume images-type uploads are to be inserted into the post
        // and other uploads are to be attached to the post.

        $uploadType = val(0, $args, 'unknown');
        $uploadType = strtolower($uploadType);
        if ($uploadType !== 'image' && $uploadType !== 'file') {
            $uploadType = 'unknown';
        }

        // This will validate, such as size maxes, file extensions. Upon doing
        // this, $_FILES is set as a protected property, so all the other Gdn_Upload methods work on it.
        $tmpFilePath = $upload->validateUpload($this->editorFileInputName);

        // Get base destination path for editor uploads
        $this->editorBaseUploadDestinationDir = $this->getBaseUploadDestinationDir();

        // Pass path, if doesn't exist, will create, and determine if valid.
        $canUpload = Gdn_Upload::canUpload($this->editorBaseUploadDestinationDir);

        if ($tmpFilePath && $canUpload) {
            $fileExtension = strtolower($upload->getUploadedFileExtension());
            $fileName = $upload->getUploadedFileName();
            list($tmpwidth, $tmpheight, $imageType) = getimagesize($tmpFilePath);

            // This will return the absolute destination path, including generated
            // filename based on md5_file, and the full path. It
            // will create a filename, with extension, and check if its dir can be writable.
            $absoluteFileDestination = $this->getAbsoluteDestinationFilePath($tmpFilePath, $fileExtension);

            // Save original file to uploads, then manipulate from this location if
            // it's a photo. This will also call events in Vanilla so other plugins can tie into this.
            $validImageTypes = [
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG
            ];
            $validImage = !empty($imageType) && in_array($imageType, $validImageTypes);

            $this->EventArguments['CategoryID'] = Gdn::request()->post('CategoryID');
            $this->EventArguments['TmpFilePath'] = &$tmpFilePath;
            $this->EventArguments['FileExtension'] = $fileExtension;
            $this->EventArguments['ValidImage'] = $validImage;
            $this->EventArguments['AbsoluteFileDestination'] = &$absoluteFileDestination;
            $this->EventArguments['DiscussionID'] = $discussionID;
            $this->fireEvent('BeforeSaveUploads');

            if (!$validImage) {
                if ($uploadType === 'unknown') {
                    $uploadType = 'file';
                }
                $filePathParsed = $upload->saveAs(
                    $tmpFilePath,
                    $absoluteFileDestination,
                    [
                        'OriginalFilename' => $fileName,
                        'source' => 'content'
                    ]
                );
            } else {
                if ($uploadType === 'unknown') {
                    $uploadType = 'image';
                }
                $filePathParsed = Gdn_UploadImage::saveImageAs(
                    $tmpFilePath,
                    $absoluteFileDestination,
                    '',
                    '',
                    [
                        'OriginalFilename' => $fileName,
                        'source' => 'content',
                        'SaveGif' => true
                    ]
                );
                $tmpwidth = $filePathParsed['Width'];
                $tmpheight = $filePathParsed['Height'];
            }

            // Determine if image, and thus requires thumbnail generation, or simply saving the file.
            // Not all files will be images.
            $thumbHeight = null;
            $thumbWidth = null;
            $imageHeight = null;
            $imageWidth = null;
            $thumbPathParsed = ['SaveName' => ''];
            $thumbUrl = '';

            // This is a redundant check, because it's in the thumbnail function,
            // but there's no point calling it blindly on every file, so just check here before calling it.
            $generate_thumbnail = false;
            if ($validImage) {
                $imageHeight = $tmpheight;
                $imageWidth = $tmpwidth;
                $generate_thumbnail = true;
            }

            // Save data to database using model with media table
            $model = new MediaModel();

            // Will be passed to model for database insertion/update. All thumb vars will be empty.
            $media = [
                'Name' => $fileName,
                'Type' => $fileData['type'],
                'Size' => $fileData['size'],
                'ImageWidth' => $imageWidth,
                'ImageHeight' => $imageHeight,
                'ThumbWidth' => $thumbWidth,
                'ThumbHeight' => $thumbHeight,
                'InsertUserID' => Gdn::session()->UserID,
                'DateInserted' => date('Y-m-d H:i:s'),
                'Path' => $filePathParsed['SaveName'],
                'ThumbPath' => $thumbPathParsed['SaveName']
            ];

            // Get MediaID and pass it to client in payload.
            $mediaID = $model->save($media);
            $media['MediaID'] = $mediaID;

            if ($generate_thumbnail) {
                $thumbUrl = url('/utility/mediathumbnail/'.$mediaID, true);
            }

            // Escape the media's name.
            $media['Name'] = htmlspecialchars($media['Name']);

            $payload = [
                'MediaID' => $mediaID,
                'Filename' => $media['Name'],
                'Filesize' => $fileData['size'],
                'FormatFilesize' => Gdn_Format::bytes($fileData['size'], 1),
                'type' => $fileData['type'],
                'Thumbnail' => '',
                'FinalImageLocation' => '',
                'Parsed' => $filePathParsed,
                'Media' => $media,
                'original_url' => $upload->url($filePathParsed['SaveName']),
                'thumbnail_url' => $thumbUrl,
                'original_width' => $imageWidth,
                'original_height' => $imageHeight,
                'upload_type' => $uploadType
            ];

            $json = [
                'error' => 0,
                'feedback' => 'Editor received file successfully.',
                'payload' => $payload
            ];
        }

        // Return JSON payload
        echo json_encode($json);
    }


    /**
     * Attach a file to a foreign table and ID.
     *
     * @access protected
     * @param int $fileID
     * @param int $foreignID
     * @param string $foreignType Lowercase.
     * @return bool Whether attach was successful.
     */
    protected function attachEditorUploads($fileID, $foreignID, $foreignType) {
        // Save data to database using model with media table
        $model = new MediaModel();
        $media = $model->getID($fileID, DATASET_TYPE_ARRAY);

        $isOwner = (!empty($media['InsertUserID']) && Gdn::session()->UserID == $media['InsertUserID']);

        if ($media && $isOwner) {
            $media['ForeignID'] = $foreignID;
            $media['ForeignTable'] = $foreignType;

            try {
                $model->save($media);
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
     * @param type $mediaID
     * @param type $foreignID
     * @param type $foreignType
     * @return boolean
     */
    protected function deleteEditorUploads($mediaID, $foreignID = '', $foreignType = '') {
        // Save data to database using model with media table
        $model = new MediaModel();
        $media = $model->getID($mediaID, DATASET_TYPE_ARRAY);

        $isOwner = (!empty($media['InsertUserID']) && Gdn::session()->UserID == $media['InsertUserID']);
        // @todo Per-category edit permission would be better, but this global is far simpler to check here.
        // However, this currently matches the permission check in views/attachments.php so keep that in sync.
        $canDelete = ($isOwner || Gdn::session()->checkPermission('Garden.Moderation.Manage'));
        if ($media && $canDelete) {
            try {
                $model->deleteID($mediaID, ['deleteFile' => true]);
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
     * @param object $sender
     * @param array $args
     */
    public function postController_afterCommentSave_handler($sender, $args) {
        if (!$args['Comment']) {
            return;
        }

        $commentID = $args['Comment']->CommentID;
        if (!$commentID) {
            return;
        }

        $this->saveUploads($commentID, 'comment');
    }

    /**
     * Attach files to a discussion during save.
     *
     * @access public
     * @param object $sender
     * @param array $args
     */
    public function postController_afterDiscussionSave_handler($sender, $args) {
        if (!$args['Discussion']) {
            return;
        }

        $discussionID = $args['Discussion']->DiscussionID;
        if (!$discussionID) {
            return;
        }

        $this->saveUploads($discussionID, 'discussion');
    }

    /**
     * Attach files to a message during save.
     *
     * @access public
     * @param object $sender
     * @param array $args
     */
    public function messagesController_afterMessageSave_handler($sender, $args) {
        if (!$args['MessageID']) {
            return;
        }

        $messageID = $args['MessageID'];
        if (!$messageID) {
            return;
        }

        $this->saveUploads($messageID, 'message');
    }

    /**
     * Attach files to a message during conversation save.
     *
     * @access public
     * @param object $sender
     * @param array $args
     */
    public function messagesController_afterConversationSave_handler($sender, $args) {
        if (!$args['MessageID']) {
            return;
        }

        $messageID = $args['MessageID'];
        if (!$messageID) {
            return;
        }

        $this->saveUploads($messageID, 'message');
    }

    /**
     * Attach image to each discussion or comment.
     *
     * It will first perform a single request against the Media table, then filter out the ones that
     * exist per discussion or comment.
     *
     * @param multiple $sender The controller.
     * @param string $type The type of row, either discussion or comment.
     * @param array|object $row The row of data being attached to.
     */
    protected function attachUploadsToComment($sender, $type = 'comment', $row = null) {
        $param = ucfirst($type).'ID';
        $foreignId = val($param, val(ucfirst($type), $sender->EventArguments));

        // Get all media for the page.
        $mediaList = $this->mediaCache($sender);

        if (is_array($mediaList)) {
            // Filter out the ones that don't match.
            $attachments = array_filter($mediaList, function($attachment) use ($foreignId, $type) {
                if (isset($attachment['ForeignID'])
                && $attachment['ForeignID'] == $foreignId
                && $attachment['ForeignTable'] == $type
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

                $sender->setData('_attachments', $attachments);
                $sender->setData('_editorkey', strtolower($param.$foreignId));
                echo $sender->fetchView('attachments', '', 'plugins/editor');
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
        $conversations = [];
        $conversationMessageModel = new Gdn_Model('ConversationMessage');

        // Query the Media table for discussion media.
        if (is_numeric($id)) {
            $sqlWhere = ['ConversationID' => $id];
            $conversations = $conversationMessageModel->getWhere($sqlWhere)->resultArray();
        }

        $messageIDList = [];
        foreach ($conversations as $conversation) {
            $messageIDList[] = val('MessageID', $conversation);
        }
        return $messageIDList;
    }

    /**
     * Called to prepare data grab, and then cache the results on the software level for the request.
     *
     * This will call PreloadDiscussionMedia, which will either query the db, or query memcached.
     *
     * @param mixed $sender
     */
    protected function cacheAttachedMedia($sender) {
        if ($sender->data('Conversation')) {
            $conversationMessageIDList = $this->getConversationMessageIDList(val('ConversationID', $sender->data('Conversation')));
            if (count($conversationMessageIDList)) {
                $mediaData = $this->preloadDiscussionMedia(val('ConversationID', $sender->data('Conversation')), $conversationMessageIDList, 'conversation');
            }
            $this->mediaCache = $mediaData;
            return;
        }

        if ($sender->data('Messages')) {
            $message = $sender->data('Messages')->result();
            $messageID = val(0, $message)->MessageID;
            $messageIDList = [$messageID];
            if (count($messageIDList)) {
                $mediaData = $this->preloadDiscussionMedia(val('ConversationID', $sender->data('Messages')), $messageIDList, 'conversation');
            }
            $this->mediaCache = $mediaData;
            return;
        }

        $discussionID = null;
        $comments = $sender->data('Comments');
        if ($answers = $sender->data('Answers')) {
            $commentsArray = $comments->resultObject();
            $commentsArray = array_merge($answers, $commentsArray);
            $commentsData = new Gdn_DataSet();
            $commentsData->importDataset($commentsArray);
            $comments = $commentsData;
        }
        $commentIDList = [];
        $mediaData = [];

        if ($sender->data('Discussion.DiscussionID')) {
            $discussionID = $sender->data('Discussion.DiscussionID');
        }

        if (is_null($discussionID) && !empty($comments)) {
            $discussionID = $comments->firstRow()->DiscussionID;
        }

        if ($discussionID) {
            if ($comments instanceof Gdn_DataSet && $comments->numRows()) {
                $comments->dataSeek(-1);
                while ($comment = $comments->nextRow()) {
                    $commentIDList[] = $comment->CommentID;
                }
            } elseif (!empty($sender->Discussion)) {
                $commentIDList[] = $sender->DiscussionID = $sender->Discussion->DiscussionID;
            }

            if (isset($sender->Comment) && isset($sender->Comment->CommentID)) {
                $commentIDList[] = $sender->Comment->CommentID;
            }

            // TODO
            // Added note for caching here because it was the CommentIDList that is the main problem.
            // Note about memcaching:
            // Main problem with this is when a new comment is posted. It will only
            // have that current comment in the list, which, after calling
            // PreloadDiscussionMedia, means it will be the only piece of data added
            // to the cache, which prevents all the rest of the comments from loading
            // their own attachments. Consider either adding to the cache when a new
            // file is uploaded, or just getting a list of all comments for a discussion.
            // This is why memcaching has been disabled for now. There are a couple
            // ways to prevent this, but they all seem unnecessary.
            if (count($commentIDList)) {
                $mediaData = $this->preloadDiscussionMedia($discussionID, $commentIDList);
            }

            $this->mediaCache = $mediaData;
        }
    }

    /**
     * Get media list for inserting into discussion and comments.
     */
    public function mediaCache($sender) {
        if ($this->mediaCache === null) {
            $this->cacheAttachedMedia($sender);
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
        $mediaData = [];
        $mediaDataDiscussion = [];
        $mediaDataComment = [];
        $mediaModel = new MediaModel();

        // Query the Media table for discussion media.
        if ($type === 'discussion') {
            if (is_numeric($discussionID)) {
                $sqlWhere = [
                    'ForeignTable' => 'discussion',
                    'ForeignID' => $discussionID
                ];
                $mediaDataDiscussion = $mediaModel->getWhere($sqlWhere)->resultArray();
            }
        }

        // Query the Media table for comment media.
        if (is_numeric($commentIDList)) {
            $commentIDList[] = $commentIDList;
        }

        if (is_array($commentIDList) && count($commentIDList)) {
            $commentIDList = array_filter($commentIDList);

            $sqlWhere = [
                'ForeignTable' => ($type == 'discussion') ? 'comment' : 'message',
                'ForeignID' => $commentIDList
            ];
            $mediaDataComment = $mediaModel->getWhere($sqlWhere)->resultArray();
        }

        $mediaData = array_merge($mediaDataDiscussion, $mediaDataComment);

        return $mediaData;
    }

    public function postController_discussionFormOptions_handler($sender, $args) {
        if (!is_null($discussion = val('Discussion', $sender, null))) {
            $sender->EventArguments['Type'] = 'Discussion';
            $sender->EventArguments['Discussion'] = $discussion;
            $this->attachUploadsToComment($sender, 'discussion');
        }
    }

    public function discussionController_afterCommentBody_handler($sender, $args) {
        $this->attachUploadsToComment($sender, 'comment', val('Comment', $args));
    }

    public function discussionController_afterDiscussionBody_handler($sender, $args) {
        $this->attachUploadsToComment($sender, 'discussion', val('Discussion', $args));
    }

    public function postController_afterCommentBody_handler($sender, $args) {
        $this->attachUploadsToComment($sender);
    }

    public function messagesController_afterConversationMessageBody_handler($sender, $args) {
        $this->attachUploadsToComment($sender, 'message', val('Message', $args));
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
        // $fileSHA1 = sha1_file($tmpFilePath);
        // Instead just use the RandomString function that Gdn_Upload->GenerateTargetName is using.
        $fileRandomString = strtolower(randomString(14));

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
        if (!file_exists($path) && !mkdir($path, 0777, true) && !is_writable($path)) {
            $validDestination = false;
        }

        return $validDestination;
    }

    /**
     * Add upload option checkbox to custom permissions for categories.
     *
     * @param Gdn_Controller $sender
     */
    public function settingsController_addEditCategory_handler($sender) {
        // Only put the checkbox on edit. On creation the default value will be used.
        if ($sender->data('CategoryID')) {
            $sender->Data['_PermissionFields']['AllowFileUploads'] = ['Control' => 'CheckBox'];
        }
    }

    /**
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_editor_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $cf = new ConfigurationModule($sender);

        $formats = array_combine($this->Formats, $this->Formats);

        $cf->initialize([
            'Garden.InputFormatter' => ['LabelCode' => 'Post Format', 'Control' => 'DropDown', 'Description' => '<p>Select the default format of the editor for posts in the community.</p><p><strong>Note:</strong> the editor will auto-detect the format of old posts when editing them and load their original formatting rules. Aside from this exception, the selected post format below will take precedence.</p>', 'Items' => $formats],
            'Plugins.editor.ForceWysiwyg' => ['LabelCode' => 'Reinterpret All Posts As Wysiwyg', 'Control' => 'Checkbox', 'Description' => '<p class="info">Check the below option to tell the editor to reinterpret all old posts as Wysiwyg.</p> <p class="info"><strong>Note:</strong> This setting will only take effect if Wysiwyg was chosen as the Post Format above. The purpose of this option is to normalize the editor format. If older posts edited with another format, such as markdown or BBCode, are loaded, this option will force Wysiwyg.</p>'],
            'Garden.MobileInputFormatter' => ['LabelCode' => 'Mobile Format', 'Control' => 'DropDown', 'Description' => '<p>Specify an editing format for mobile devices. If mobile devices should have the same experience, specify the same one as above. If users report issues with mobile editing, this is a good option to change.</p>', 'Items' => $formats, 'DefaultValue' => c('Garden.MobileInputFormatter')]
        ]);


        $sender->setData('Title', t('Advanced Editor Settings'));
        $cf->renderAll();
    }

    /**
     * If editor is loaded, then the other editors loaded after, there are CSS rules that hide them.
     * This way, the editor plugin always takes precedence.
     */
    public function setup() {
        touchConfig([
            'Garden.MobileInputFormatter' => 'TextEx',
            'Plugins.editor.ForceWysiwyg' => false
        ]);
        $this->structure();
    }

    /**
     * When enabled or on utility/update, disable other known editors that may clash with this one.
     *
     * @throws Exception
     */
    public function structure() {
         $pluginEditors = [
            'cleditor',
            'ButtonBar',
            'Emotify',
            'FileUpload'
        ];

        foreach ($pluginEditors as $pluginName) {
            Gdn::pluginManager()->disablePlugin($pluginName);
        }

        // Set to false by default, so change in config if uploads allowed.
        touchConfig('Garden.AllowFileUploads', true);

        $structure = Gdn::structure();
        $structure
            ->table('Category')
            ->column('AllowFileUploads', 'tinyint(1)', '1')
            ->set();
    }

    /**
     * Retrieve mime type from file extension.
     *
     * @param string $extension The extension to look up. (i.e., 'png')
     * @return bool|string The mime type associated with the file extension or false if it doesn't exist.
     */
    private function lookupMime($extension){
        global $mimeTypes;
        include_once 'mimetypes.php';
        return val($extension, $mimeTypes, false);
    }

    /**
     * Create and display a thumbnail of an uploaded file.
     *
     * @param Gdn_Controller $sender
     * @param int $mediaID
     */
    public function utilityController_mediaThumbnail_create($sender, $mediaID) {
        $sender->permission('Garden.SignIn.Allow');
        // When it makes it into core, it will be available in
        // functions.general.php
        require 'generate_thumbnail.php';

        $model = new MediaModel();
        $media = $model->getID($mediaID, DATASET_TYPE_ARRAY);

        if (!$media || val('InsertUserID', $media) != Gdn::session()->UserID) {
            throw notFoundException('File');
        }

        // Get actual path to the file.
        $upload = new Gdn_UploadImage();
        $local_path = $upload->copyLocal(val('Path', $media));
        if (!file_exists($local_path)) {
            throw notFoundException('File');
        }

        $file_extension = pathinfo($local_path, PATHINFO_EXTENSION);

        // Generate new path for thumbnail
        $thumb_path = $this->getBaseUploadDestinationDir().'/'.'thumb';

        // Grab full path with filename, and validate it.
        $thumb_destination_path = $this->getAbsoluteDestinationFilePath($local_path, $file_extension, $thumb_path);

        // Create thumbnail, and grab debug data from whole process.
        $thumb_payload = generate_thumbnail($local_path, $thumb_destination_path, [
            // Give preference to height for thumbnail, so height controls.
            'height' => c('Plugins.FileUpload.ThumbnailHeight', 128)
        ]);

        if ($thumb_payload['success'] === true) {
            // Thumbnail dimensions
            $thumb_height = round($thumb_payload['result_height']);
            $thumb_width = round($thumb_payload['result_width']);

            // Move the thumbnail to its proper location. Calling SaveAs with
            // a cloud storage plugin enabled will trigger the move to the cloud, so use
            // same path for each arg in SaveAs. The file will be removed from the local filesystem.
            $parsed = Gdn_Upload::parse($thumb_destination_path);
            $target = $thumb_destination_path; // $parsed['Name'];
            $upload = new Gdn_Upload();
            $filepath_parsed = $upload->saveAs($thumb_destination_path, $target, ['source' => 'content']);

            // Save thumbnail information to DB.
            $model->save([
                'MediaID' => $mediaID,
                'ThumbWidth' => $thumb_width,
                'ThumbHeight' => $thumb_height,
                'ThumbPath' => $filepath_parsed['SaveName']
            ]);

            // Remove cloud scratch copy, typically in /uploads/cftemp/ or /uploads/cloudtemp/, if there was actually a file pulled in from cloud storage.
            $uploadFolder = basename(PATH_UPLOADS);
            if (preg_match("`/{$uploadFolder}/[^/]+temp/`", $local_path)) {
                unlink($local_path);
            }

            $url = $filepath_parsed['Url'];
        } else {
            // Fix the thumbnail information so this isn't requested again and again.
            $model->save([
                'MediaID' => $mediaID,
                'ImageWidth' => 0,
                'ImageHeight' => 0,
                'ThumbPath' => ''
            ]);

            $url = asset('/plugins/FileUpload/images/file.png');
        }

        redirectTo($url, 301);
    }

    /**
     * Checks whether the canUpload property is set and if not, calculates it value.
     * The calculation is based on config, user permissions, and category permissions.
     *
     * @return bool Whether the session user is allowed to upload a file.
     */
    protected function canUpload() {
        // If the property has been set, return it
        if (isset($this->canUpload)) {
            return $this->canUpload;
        } else {
            // Check config and user role upload permission
            if (c('Garden.AllowFileUploads', true) && Gdn::session()->checkPermission('Plugins.Attachments.Upload.Allow', false)) {
                // Check category-specific permission
                $permissionCategory = CategoryModel::permissionCategory(Gdn::controller()->data('Category'));
                $this->canUpload = val('AllowFileUploads', $permissionCategory, true);
            } else {
                $this->canUpload = false;
            }
        }
        return $this->canUpload;
    }
}
