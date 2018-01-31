<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

class RichEditorPlugin extends Gdn_Plugin {

    /** @var integer */
    private static $editorID;
    /** @var integer */
    private $editorNumber;

    /**
     * Set some properties we always need.
     */
    public function __construct() {
        parent::__construct();
        $this->editorNumber = ++$this->editorID;
    }

    /**
     * Add the style script to the head
     *
     * @param Gdn_Controller $sender
     * @return void
     */

    public function get_editorID() {
        return $this->editorNumber;
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

//        $sender->addCssFile("//cdn.quilljs.com/1.3.4/quill.bubble.css");
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
        /** @var Gdn_Controller $controller */
        $controller = Gdn::controller();
        /** @var int $editorID */
        $editorID = $this->get_editorID();

        $controller->setData('editorData', [
            'editorID' => $editorID,
            'editorDescriptionID' => 'richEditor-'.$editorID.'-description',

        ]);

        // Load up the helper functions for the editor views.
        //$controller->fetchView('helper_functions', '', 'plugins/rich-editor');

        // Render the editor view.
        $args['BodyBox'] = $controller->fetchView('rich-editor', '', 'plugins/rich-editor');

        // Set the format on the form.
        $sender->setValue('Format', 'Rich');
    }
}
