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
    }

    /**
     * @return int
     */
    public static function getEditorID(): int {
        return self::$editorID;
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

        // Check to see if we need to add a class on the body for the rich editor.
        $form = val('Form', $sender, false);
        if ($form) {
            $formData = $form->formData();
            if ($formData) {
                if (val('Format', $formData) === "Rich" || val('Body', $formData, false) === null) { // New Discussion or edit discussion
                    $sender->CssClass .= ' hasRichEditor';
                }
            } elseif ($sender->CommentModel) { // New Comments should be using Rich Editor
                $sender->CssClass .= ' hasRichEditor';
            }
        }

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

        $data = [];
        if ($sender->formData()) {
            $data = $sender->formData();
        }

        if (val('Format', $data) === "Rich") {
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
