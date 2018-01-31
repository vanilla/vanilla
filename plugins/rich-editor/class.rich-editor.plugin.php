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
        require_once Gdn::controller()->fetchViewLocation("helper_functions", "", "plugins/rich-editor");
        $smarty = new Gdn_Smarty();
        $sender->setData('editorData', ['editorID' => $this->get_editorID()]);
        $args['BodyBox'] = $smarty->render(PATH_PLUGINS."/rich-editor/views/richEditor.tpl", $sender);
        $sender->setValue('Format', 'Rich');
    }
}
