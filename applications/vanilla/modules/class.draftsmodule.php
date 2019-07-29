<?php
/**
 * Drafts module
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders user drafts. If rendered within a discussion, it only shows drafts related to that discussion.
 */
class DraftsModule extends Gdn_Module {

    /** @var  Gdn_Form */
    public $Form;

    public function getData($limit = 20, $discussionID = '') {
        $session = Gdn::session();
        if ($session->isValid()) {
            $draftModel = new DraftModel();
            $this->Data = $draftModel->getByUser($session->UserID, 0, $limit, $discussionID);
        }
        $this->Form = $this->_Sender->Form;
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        $this->getData();
        if (is_object($this->Data) && $this->Data->numRows() > 0) {
            return parent::toString();
        }

        return '';
    }
}
