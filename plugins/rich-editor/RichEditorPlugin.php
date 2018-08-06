<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;

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
    public function isFormRich(Gdn_Form $form): bool {
        $data = $form->formData();
        $format = $data['Format'] ?? 'Rich';

        return $format === self::FORMAT_NAME;
    }

    public function isInputFormatterRich(): bool {
        return Gdn_Format::defaultFormat() === "Rich";
    }

    /**
     * Add the rich editor format to the posting page.
     *
     * @param string[] $postFormats Existing post formats.
     *
     * @return string[] Additional post formats.
     */
    public function getPostFormats_handler(array $postFormats): array {
        $postFormats[] = self::FORMAT_NAME;
        return $postFormats;
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
        if ($this->isFormRich($sender)) {
            /** @var Gdn_Controller $controller */
            $controller = Gdn::controller();
            $controller->CssClass .= ' hasRichEditor';
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

    /**
     * Add 'Quote' option to discussion via the reactions row after each post.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_afterFlag_handler($sender, $args) {
        if ($this->isInputFormatterRich()) {
            $this->addQuoteButton($sender, $args);
        }
    }

    /**
     * Output Quote link.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    protected function addQuoteButton($sender, $args) {
        // There are some case were Discussion is not set as an event argument so we use the sender data instead.
        $discussion = $sender->data('Discussion');
        if (!$discussion) {
            return;
        }

        if (!Gdn::session()->UserID) {
            return;
        }

        if (!Gdn::session()->checkPermission('Vanilla.Comments.Add', false, 'Category', $discussion->PermissionCategoryID)) {
            return;
        }

        if (isset($args['Comment'])) {
            $object = $args['Comment'];
            $resourceType = 'comment';
            $resourceID = $object->CommentID;
            $url = commentUrl($object);
        } elseif ($discussion) {
            $object = $discussion;
            $resourceType = 'discussion';
            $resourceID = $object->DiscussionID;
            $url = discussionUrl($object);
        } else {
            return;
        }

        $icon = sprite('ReactQuote', 'ReactSprite');
        $linkText = $icon.' '.t('Quote');
        $classes = 'ReactButton Quote Visible js-quoteButton';

        echo Gdn_Theme::bulletItem('Flags');
        echo "<a href='#' data-resource-type='$resourceType' data-resource-id='$resourceID' data-url='$url'
 role='button' 
class='$classes'>$linkText</a>";
        echo ' ';
    }
}
