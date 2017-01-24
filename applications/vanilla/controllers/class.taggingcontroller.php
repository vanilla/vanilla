<?php
/**
 * Tagging controller
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Tagging controller
 */
//class TaggingController extends Gdn_Controller {
//
//    public function add() {
//        $this->addSideMenu('settings/tagging');
//        $this->title('Add Tag');
//
//        // Set the model on the form.
//        $TagModel = new TagModel;
//        $this->Form->setModel($TagModel);
//
//        // Add types if allowed to add tags for it, and not '' or 'tags', which
//        // are the same.
//        $TagType = Gdn::request()->get('type');
//        if (strtolower($TagType) != 'tags'
//            && $TagModel->canAddTagForType($TagType)
//        ) {
//            $this->Form->addHidden('Type', $TagType, true);
//        }
//
//        if ($this->Form->authenticatedPostBack()) {
//            // Make sure the tag is valid
//            $TagName = $this->Form->getFormValue('Name');
//            if (!TagModel::validateTag($TagName)) {
//                $this->Form->addError('@'.t('ValidateTag', 'Tags cannot contain commas.'));
//            }
//
//            $TagType = $this->Form->getFormValue('Type');
//            if (!$TagModel->canAddTagForType($TagType)) {
//                $this->Form->addError('@'.t('ValidateTagType', 'That type does not accept manually adding new tags.'));
//            }
//
//            // Make sure that the tag name is not already in use.
//            if ($TagModel->getWhere(array('Name' => $TagName))->numRows() > 0) {
//                $this->Form->addError('The specified tag name is already in use.');
//            }
//
//            $Saved = $this->Form->save();
//            if ($Saved) {
//                $this->informMessage(t('Your changes have been saved.'));
//            }
//        }
//
//        $this->render('addedit', '', 'plugins/Tagging');
//    }
//}
