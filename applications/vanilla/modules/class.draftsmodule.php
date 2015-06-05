<?php
/**
 * Drafts module
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders user drafts. If rendered within a discussion, it only shows drafts related to that discussion.
 */
class DraftsModule extends Gdn_Module {

    /** @var  Gdn_Form */
    public $Form;

    public function GetData($Limit = 20, $DiscussionID = '') {
        $Session = Gdn::Session();
        if ($Session->IsValid()) {
            $DraftModel = new DraftModel();
            $this->Data = $DraftModel->Get($Session->UserID, 0, $Limit, $DiscussionID);
        }
        $this->Form = $this->_Sender->Form;
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        if (is_object($this->Data) && $this->Data->NumRows() > 0)
            return parent::ToString();

        return '';
    }
}
