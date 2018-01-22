<?php
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

class RichEditorPlugin extends Gdn_Plugin {

    /**
     * Setup some variables for instance.
     */
    public function __construct() {
        parent::__construct();

        // Check for additional formats
        $this->EventArguments['formats'] = &$this->Formats;
        $this->fireEvent('GetFormats');
    }

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
        $view = '<div class="QuillContainer"></div>';

        $args['BodyBox'] .= $view;

        $sender->setValue('Format', 'Rich');
    }
}
