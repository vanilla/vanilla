<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

class RichEditorPlugin extends Gdn_Plugin {

    /** @var integer */
    private static $editorID = 0;

    /**
     * Set some properties we always need.
     */
    public function __construct() {
        parent::__construct();
        self::$editorID++;
    }

    /**
     * {@inheritDoc}
     */
    public function setup() {
        $this->structure();
    }

    public function structure() {
        saveToConfig('Garden.InputFormatter', 'Rich');
        saveToConfig('Garden.MobileInputFormatter', 'Rich');
    }

    /**
     * @return int
     */
    public static function getEditorID(): int {
        return self::$editorID;
    }

    /**
     * Check to see if we should be using the Rich Editor
     * @param Gdn_Controller $sender
     */
    public function isRichFormat($sender):bool {
        $form = val('Form', $sender, $sender); // May already be "Form" object
        $data = $form->formData();
        return strcmp(val('Format', $data, "Rich"), "Rich") === 0;
    }

    /**
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeCommentForm_handler($sender) {
        if ($this->isRichFormat($sender)) {
            $sender->CssClass .= ' hasRichEditor';
        }
    }

    /**
     *
     * @param Gdn_Controller $sender
     * @throws Exception
     */
    public function postController_render_before($sender) {
        if ($this->isRichFormat($sender)) {
            $sender->CssClass .= ' hasRichEditor';
        }
    }


    /**
     * Attach editor anywhere 'BodyBox' is used.
     *
     * It is not being used for editing a posted reply, so find another event to hook into.
     *
     * @param Gdn_Form $sender
     */
    public function gdn_form_beforeBodyBox_handler($sender, $args) {
        if ($this->isRichFormat($sender)) {
            /** @var Gdn_Controller $controller */
            $controller = Gdn::controller();
            $editorID = $this->getEditorID();
            $controller->setData('editorData', [
                'editorID' => $editorID,
                'editorDescriptionID' => 'richEditor-'.$editorID.'-description',
                'hasUploadPermission' => checkPermission('uploads.add'),
            ]);

            // Render the editor view.
            $args['BodyBox'] = $controller->fetchView('rich-editor', '', 'plugins/rich-editor');
        }
    }

}
