<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

class RichEditorPlugin extends Gdn_Plugin {

    /**
     * Add the style script to the head
     *
     * @param Gdn_Controller $sender
     * @return void
     */
    public function base_render_before($sender) {
        if (inSection("Dashboard")) {
            return;
        }

        $sender->addCssFile("//cdn.quilljs.com/1.3.4/quill.bubble.css");
        $sender->addDefinition("editor", "RichEditor");
    }

    /**
     * Attach editor anywhere 'BodyBox' is used.
     *
     * It is not being used for editing a posted reply, so find another event to hook into.
     *
     * @param Gdn_Form $sender
     */
    public function gdn_form_beforeBodyBox_handler($sender, $args) {
//        require_once Gdn::controller()->fetchViewLocation("helper_functions", "", "plugins/rich-editor");

        require_once Gdn::controller()->fetchViewLocation("icons", "", "plugins/rich-editor");
//        $view = renderEditorShell();
        $view = "<div class='js-richText'></div>";

        $args['BodyBox'] .= $view;

        $sender->setValue('Format', 'Rich');
    }
}
