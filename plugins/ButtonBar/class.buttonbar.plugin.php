<?php
/**
 * ButtonBar Plugin
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package ButtonBar
 */

$PluginInfo['ButtonBar'] = array(
    'Name' => 'Button Bar',
    'Description' => 'Adds several simple buttons above comment boxes, allowing additional formatting.',
    'Version' => '1.7',
    'MobileFriendly' => true,
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

class ButtonBarPlugin extends Gdn_Plugin {

    /** @var array  */
    protected $Formats = array('Html', 'BBCode', 'Markdown', 'Wysiwyg');

    /**
     * Insert ButtonBar resource files on every page so they are available
     * to any new uses of BodyBox in plugins and applications.
     *
     * @param Gdn_Controller $Sender
     */
    public function base_render_before($Sender) {
        $Formatter = c('Garden.InputFormatter', 'Html');
        $this->attachButtonBarResources($Sender, $Formatter);
    }

    public function assetModel_styleCss_handler($Sender) {
        $Sender->addCssFile('buttonbar.css', 'plugins/ButtonBar');
    }

    /**
     * Insert buttonbar resources
     *
     * This method is abstracted because it is invoked by multiple controllers.
     *
     * @param Gdn_Controller $Sender
     */
    protected function attachButtonBarResources($Sender, $Formatter) {
        if (!in_array($Formatter, $this->Formats)) {
            return;
        }
        $Sender->addJsFile('buttonbar.js', 'plugins/ButtonBar');
        $Sender->addJsFile('jquery.hotkeys.js', 'plugins/ButtonBar');

        $Sender->addDefinition('ButtonBarLinkUrl', t('ButtonBar.LinkUrlText', 'Enter your URL:'));
        $Sender->addDefinition('ButtonBarImageUrl', t('ButtonBar.ImageUrlText', 'Enter image URL:'));
        $Sender->addDefinition('ButtonBarBBCodeHelpText', t('ButtonBar.BBCodeHelp', 'You can use <b><a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a></b> in your post.'));
        $Sender->addDefinition('ButtonBarHtmlHelpText', t('ButtonBar.HtmlHelp', 'You can use <b><a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple Html</a></b> in your post.'));
        $Sender->addDefinition('ButtonBarMarkdownHelpText', t('ButtonBar.MarkdownHelp', 'You can use <b><a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a></b> in your post.'));

        $Sender->addDefinition('InputFormat', $Formatter);
    }

    /**
     * Attach ButtonBar anywhere 'BodyBox' is used.
     *
     * @param Gdn_Controller $Sender
     */
    public function gdn_form_beforeBodyBox_handler($Sender) {
        $Wrap = false;
        if (Gdn::controller() instanceof PostController) {
            $Wrap = true;
        }
        $this->attachButtonBar($Sender, $Wrap);
    }
//   public function discussionController_BeforeBodyField_handler($Sender) {
//      $this->AttachButtonBar($Sender);
//   }
//   public function postController_BeforeBodyField_handler($Sender) {
//      $this->AttachButtonBar($Sender);
//   }

    /**
     * Attach button bar in place
     *
     * This method is abstracted because it is called from multiple places, due
     * to the way that the comment.php view is invoked both by the DiscussionController
     * and the PostController.
     *
     * @param Gdn_Controller $Sender
     */
    protected function attachButtonBar($Sender, $Wrap = false) {
        $Formatter = c('Garden.InputFormatter', 'Html');
        if (!in_array($Formatter, $this->Formats)) {
            return;
        }

        $View = Gdn::controller()->fetchView('buttonbar', '', 'plugins/ButtonBar');

        if ($Wrap) {
            echo wrap($View, 'div', array('class' => 'P'));
        } else {
            echo $View;
        }
    }
}
