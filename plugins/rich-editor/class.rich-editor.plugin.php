<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

class RichEditorPlugin extends Gdn_Plugin {

    const FORMAT_NAME = "Rich";

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
        saveToConfig('Garden.InputFormatter', self::FORMAT_NAME);
        saveToConfig('Garden.MobileInputFormatter', self::FORMAT_NAME);
    }

    /**
     * @return int
     */
    public static function getEditorID(): int {
        return self::$editorID;
    }

    /**
     * Check to see if we should be using the Rich Editor
     *
     * @param Gdn_Form $form - A form instance.
     *
     * @return bool
     */
    public function isRichFormat(Gdn_Form $form): bool {
        $data = $form->formData();
        $format = $data['Format'] ?? 'Rich';

        return $format === self::FORMAT_NAME;
    }

    /**
     * Add the rich editor format to the posting page.
     *
     * @param VanillaSettingsController $sender
     * @param $args
     */
    public function vanillaSettingsController_getFormats_handler(VanillaSettingsController $sender, array $args) {
        $args['formats'][] = self::FORMAT_NAME;
    }

    /**
     * Add a rich editor CSS class.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeCommentForm_handler(Gdn_Controller $sender) {
        $form = $sender->Form ?? null;
        if ($form ? $this->isRichFormat($form) : false) {
            $sender->CssClass .= ' hasRichEditor';
        }
    }

    /**
     *
     * @param PostController $sender
     * @throws Exception
     */
    public function postController_render_before(PostController $sender) {
        if ($this->isRichFormat($sender->Form)) {
            $sender->CssClass .= ' hasRichEditor';
        }
    }


    /**
     * Attach editor anywhere 'BodyBox' is used.
     *
     * It is not being used for editing a posted reply, so find another event to hook into.
     *
     * @param Gdn_Form $sender The Form Object
     * @param array $args Arguments from the event.
     */
    public function gdn_form_beforeBodyBox_handler(Gdn_Form $sender, array $args) {
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
            $args['BodyBox'] .= $controller->fetchView('rich-editor', '', 'plugins/rich-editor');
        }
    }

}
