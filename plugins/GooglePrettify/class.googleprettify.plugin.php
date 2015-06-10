<?php
/**
 * GooglePrettify Plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package GooglePrettify
 */

// Define the plugin:
$PluginInfo['GooglePrettify'] = array(
    'Name' => 'Syntax Prettifier',
    'Description' => 'Adds pretty syntax highlighting to code in discussions and tab support to the comment box. This is a great addon for communities that support programmers and designers.',
    'Version' => '1.2.1',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'MobileFriendly' => true,
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'SettingsUrl' => '/dashboard/settings/googleprettify',
    'SettingsPermission' => 'Garden.Settings.Manage',
);

// Changelog
// v1.1 Add Tabby, docs/cleanup  -Lincoln, Aug 2012

/**
 * Class GooglePrettifyPlugin
 */
class GooglePrettifyPlugin extends Gdn_Plugin {

    /**
     * Add Prettify to page text.
     */
    public function addPretty($Sender) {
        $Sender->Head->addTag('script', array('type' => 'text/javascript', '_sort' => 100), $this->GetJs());
        $Sender->addJsFile('prettify.js', 'plugins/GooglePrettify', array('_sort' => 101));
        if ($Language = c('Plugins.GooglePrettify.Language')) {
            $Sender->addJsFile("lang-$Language.js", 'plugins/GooglePrettify', array('_sort' => 102));
        }
    }

    /**
     * Add Tabby to a page's text areas.
     */
    public function addTabby($Sender) {
        if (c('Plugins.GooglePrettify.UseTabby', false)) {
            $Sender->addJsFile('jquery.textarea.js', 'plugins/GooglePrettify');
            $Sender->Head->addTag('script', array('type' => 'text/javascript', '_sort' => 100), 'jQuery(document).ready(function () {
     $("textarea").livequery(function () {$("textarea").tabby();})
});');
        }
    }

    /**
     * Prettify script initializer.
     *
     * @return string
     */
    public function getJs() {
        $Class = '';
        if (c('Plugins.GooglePrettify.LineNumbers')) {
            $Class .= ' linenums';
        }
        if ($Language = c('Plugins.GooglePrettify.Language')) {
            $Class .= " lang-$Language";
        }

        $Result = "jQuery(document).ready(function($) {
         var pp = false;

         $('.Message').livequery(function () {
            $('pre', this).addClass('prettyprint$Class');
            if (pp)
               prettyPrint();
            $('pre', this).removeClass('prettyprint')
         });

         prettyPrint();
         pp = true;
      });";
        return $Result;
    }

    public function assetModel_styleCss_handler($Sender) {
        if (!c('Plugins.GooglePrettify.NoCssFile')) {
            $Sender->addCssFile('prettify.css', 'plugins/GooglePrettify');
        }
    }

    public function assetModel_generateETag_handler($Sender, $Args) {
        if (!c('Plugins.GooglePrettify.NoCssFile')) {
            $Args['ETagData']['Plugins.GooglePrettify.NoCssFile'] = true;
        }
    }

    /**
     * Add Prettify formatting to discussions.
     *
     * @param DiscussionController $Sender
     */
    public function discussionController_render_before($Sender) {
        $this->addPretty($Sender);
        $this->addTabby($Sender);
    }

    /**
     * Add Tabby to post textarea.
     *
     * @param PostController $Sender
     */
    public function postController_render_before($Sender) {
        $this->addPretty($Sender);
        $this->addTabby($Sender);
    }

    /**
     * Settings page.
     *
     * @param unknown_type $Sender
     * @param unknown_type $Args
     */
    public function settingsController_googlePrettify_create($Sender, $Args) {
        $Cf = new ConfigurationModule($Sender);
        $CssUrl = asset('/plugins/GooglePrettify/design/prettify.css', true);

        $Languages = array(
            'apollo' => 'apollo',
            'clj' => 'clj',
            'css' => 'css',
            'go' => 'go',
            'hs' => 'hs',
            'lisp' => 'lisp',
            'lua' => 'lua',
            'ml' => 'ml',
            'n' => 'n',
            'proto' => 'proto',
            'scala' => 'scala',
            'sql' => 'sql',
            'text' => 'tex',
            'vb' => 'visual basic',
            'vhdl' => 'vhdl',
            'wiki' => 'wiki',
            'xq' => 'xq',
            'yaml' => 'yaml'
        );

        $Cf->initialize(array(
            'Plugins.GooglePrettify.LineNumbers' => array('Control' => 'CheckBox', 'Description' => 'Add line numbers to source code.', 'Default' => false),
            'Plugins.GooglePrettify.NoCssFile' => array('Control' => 'CheckBox', 'LabelCode' => 'Exclude Default CSS File', 'Description' => "If you want to define syntax highlighting in your custom theme you can disable the <a href='$CssUrl'>default css</a> with this setting.", 'Default' => false),
            'Plugins.GooglePrettify.UseTabby' => array('Control' => 'CheckBox', 'LabelCode' => 'Allow Tab Characters', 'Description' => "If users enter a lot of source code then enable this setting to make the tab key enter a tab instead of skipping to the next control.", 'Default' => false),
            'Plugins.GooglePrettify.Language' => array('Control' => 'DropDown', 'Items' => $Languages, 'Options' => array('IncludeNull' => true),
                'Description' => 'We try our best to guess which language you are typing in, but if you have a more obscure language you can force all highlighting to be in that language. (Not recommended)')
        ));

        $Sender->addSideMenu();
        $Sender->setData('Title', t('Syntax Prettifier Settings'));
        $Cf->renderAll();
    }
}
