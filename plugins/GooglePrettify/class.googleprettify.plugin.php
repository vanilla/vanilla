<?php
/**
 * GooglePrettify Plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package GooglePrettify
 */

// Changelog
// v1.1 Add Tabby, docs/cleanup  -Lincoln, Aug 2012

/**
 * Class GooglePrettifyPlugin
 */
class GooglePrettifyPlugin extends Gdn_Plugin {

    /**
     * Add Prettify to page text.
     */
    public function addPretty($sender) {
        $sender->Head->addTag('script', ['type' => 'text/javascript', '_sort' => 100], $this->getJs());
        $sender->addJsFile('prettify.js', 'plugins/GooglePrettify', ['_sort' => 101]);
        if ($language = c('Plugins.GooglePrettify.Language')) {
            $sender->addJsFile("lang-$language.js", 'plugins/GooglePrettify', ['_sort' => 102]);
        }
    }

    /**
     * Add Tabby to a page's text areas.
     */
    public function addTabby($sender) {
        if (c('Plugins.GooglePrettify.UseTabby', false)) {
            $sender->addJsFile('jquery.textarea.js', 'plugins/GooglePrettify');
            $sender->Head->addTag('script', ['type' => 'text/javascript', '_sort' => 100], '
        function init() {
            $("textarea").not(".Tabby").addClass("Tabby").tabby();
        }
        $(document).on("contentLoad", init);');
        }
    }

    /**
     * Prettify script initializer.
     *
     * @return string
     */
    public function getJs() {
        $class = '';
        if (c('Plugins.GooglePrettify.LineNumbers')) {
            $class .= ' linenums';
        }
        if ($language = c('Plugins.GooglePrettify.Language')) {
            $class .= " lang-$language";
        }

        $result = "
            function init() {
                $('.Message').each(function () {
                    if ($(this).data('GooglePrettify')) {
                        return;
                    }
                    $(this).data('GooglePrettify', '1');

                    pre = $('pre', this).addClass('prettyprint$class');

                    // Let prettyprint determine styling, rather than the editor.
                    $('code', this).removeClass('CodeInline');
                    pre.removeClass('CodeBlock');

                    prettyPrint();

                    pre.removeClass('prettyprint');
                });
            }

            $(document).on('contentLoad', init);";
        return $result;
    }

    public function assetModel_styleCss_handler($sender) {
        if (!c('Plugins.GooglePrettify.NoCssFile')) {
            $sender->addCssFile('prettify.css', 'plugins/GooglePrettify');
        }
    }

    public function assetModel_generateETag_handler($sender, $args) {
        if (!c('Plugins.GooglePrettify.NoCssFile')) {
            $args['ETagData']['Plugins.GooglePrettify.NoCssFile'] = true;
        }
    }

    /**
     * Add Prettify formatting to discussions.
     *
     * @param DiscussionController $sender
     */
    public function discussionController_render_before($sender) {
        $this->addPretty($sender);
        $this->addTabby($sender);
    }

    /**
     * Add Tabby to post textarea.
     *
     * @param PostController $sender
     */
    public function postController_render_before($sender) {
        $this->addPretty($sender);
        $this->addTabby($sender);
    }

    /**
     * Add Tabby to conversations textarea.
     *
     * @param MessagesController $sender
     */
    public function messagesController_render_before($sender) {
        $this->addPretty($sender);
        $this->addTabby($sender);
    }

    /**
     * Add Prettify formatting to profile posts.
     *
     * @param DiscussionController $sender
     */
    public function profileController_render_before($sender) {
        $this->addPretty($sender);
        $this->addTabby($sender);
    }

    /**
     * Settings page.
     *
     * @param unknown_type $sender
     * @param unknown_type $args
     */
    public function settingsController_googlePrettify_create($sender, $args) {
        $cf = new ConfigurationModule($sender);
        $cssUrl = asset('/plugins/GooglePrettify/design/prettify.css', true);

        $languages = [
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
        ];

        $cf->initialize([
            'Plugins.GooglePrettify.LineNumbers' => ['Control' => 'CheckBox', 'Description' => 'Add line numbers to source code.', 'Default' => false],
            'Plugins.GooglePrettify.NoCssFile' => ['Control' => 'CheckBox', 'LabelCode' => 'Exclude Default CSS File', 'Description' => "If you want to define syntax highlighting in your custom theme you can disable the <a href='$cssUrl'>default css</a> with this setting.", 'Default' => false],
            'Plugins.GooglePrettify.UseTabby' => ['Control' => 'CheckBox', 'LabelCode' => 'Allow Tab Characters', 'Description' => "If users enter a lot of source code then enable this setting to make the tab key enter a tab instead of skipping to the next control.", 'Default' => false],
            'Plugins.GooglePrettify.Language' => ['Control' => 'DropDown', 'Items' => $languages, 'Options' => ['IncludeNull' => true],
                'Description' => 'We try our best to guess which language you are typing in, but if you have a more obscure language you can force all highlighting to be in that language. (Not recommended)']
        ]);


        $sender->setData('Title', t('Syntax Prettifier Settings'));
        $cf->renderAll();
    }
}
