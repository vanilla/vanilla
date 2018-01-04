<?php
/**
 * ButtonBar Plugin
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package ButtonBar
 */

class ButtonBarPlugin extends Gdn_Plugin {

    /** @var array  */
    protected $Formats = ['Html', 'BBCode', 'Markdown', 'Wysiwyg'];

    /**
     * Insert ButtonBar resource files on every page so they are available
     * to any new uses of BodyBox in plugins and applications.
     *
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        $formatter = c('Garden.InputFormatter', 'Html');
        $this->attachButtonBarResources($sender, $formatter);
    }

    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('buttonbar.css', 'plugins/ButtonBar');
    }

    /**
     * Insert buttonbar resources
     *
     * This method is abstracted because it is invoked by multiple controllers.
     *
     * @param Gdn_Controller $sender
     */
    protected function attachButtonBarResources($sender, $formatter) {
        if (!in_array($formatter, $this->Formats)) {
            return;
        }
        $sender->addJsFile('buttonbar.js', 'plugins/ButtonBar');
        $sender->addJsFile('jquery.hotkeys.js', 'plugins/ButtonBar');

        $sender->addDefinition('ButtonBarLinkUrl', t('ButtonBar.LinkUrlText', 'Enter your URL:'));
        $sender->addDefinition('ButtonBarImageUrl', t('ButtonBar.ImageUrlText', 'Enter image URL:'));
        $sender->addDefinition('ButtonBarBBCodeHelpText', t('ButtonBar.BBCodeHelp', 'You can use <b><a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a></b> in your post.'));
        $sender->addDefinition('ButtonBarHtmlHelpText', t('ButtonBar.HtmlHelp', 'You can use <b><a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple HTML</a></b> in your post.'));
        $sender->addDefinition('ButtonBarMarkdownHelpText', t('ButtonBar.MarkdownHelp', 'You can use <b><a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a></b> in your post.'));

        $sender->addDefinition('InputFormat', $formatter);
    }

    /**
     * Attach ButtonBar anywhere 'BodyBox' is used.
     *
     * @param Gdn_Controller $sender
     */
    public function gdn_form_beforeBodyBox_handler($sender) {
        $wrap = false;
        if (Gdn::controller() instanceof PostController) {
            $wrap = true;
        }
        $this->attachButtonBar($sender, $wrap);
    }
//   public function discussionController_BeforeBodyField_handler($Sender) {
//      $this->attachButtonBar($Sender);
//   }
//   public function postController_BeforeBodyField_handler($Sender) {
//      $this->attachButtonBar($Sender);
//   }

    /**
     * Attach button bar in place
     *
     * This method is abstracted because it is called from multiple places, due
     * to the way that the comment.php view is invoked both by the DiscussionController
     * and the PostController.
     *
     * @param Gdn_Controller $sender
     */
    protected function attachButtonBar($sender, $wrap = false) {
        $formatter = c('Garden.InputFormatter', 'Html');
        if (!in_array($formatter, $this->Formats)) {
            return;
        }

        $view = Gdn::controller()->fetchView('buttonbar', '', 'plugins/ButtonBar');

        if ($wrap) {
            echo wrap($view, 'div', ['class' => 'P']);
        } else {
            echo $view;
        }
    }
}
