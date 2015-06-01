<?php
/**
 * Drafts controller
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles displaying saved drafts of unposted comments via /drafts endpoint.
 */
class DraftsController extends VanillaController {

    /** @var array Models to include. */
    public $Uses = array('Database', 'DraftModel');

    /**
     * Default all drafts view: chronological by time saved.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $Offset Number of drafts to skip.
     */
    public function Index($Offset = '0') {
        Gdn_Theme::Section('DiscussionList');

        // Setup head
        $this->Permission('Garden.SignIn.Allow');
        $this->AddJsFile('jquery.gardenmorepager.js');
        $this->AddJsFile('discussions.js');
        $this->Title(T('My Drafts'));

        // Validate $Offset
        if (!is_numeric($Offset) || $Offset < 0)
            $Offset = 0;

        // Set criteria & get drafts data
        $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
        $Session = Gdn::Session();
        $Wheres = array('d.InsertUserID' => $Session->UserID);
        $this->DraftData = $this->DraftModel->Get($Session->UserID, $Offset, $Limit);
        $CountDrafts = $this->DraftModel->GetCount($Session->UserID);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->Pager = $PagerFactory->GetPager('MorePager', $this);
        $this->Pager->MoreCode = 'More drafts';
        $this->Pager->LessCode = 'Newer drafts';
        $this->Pager->ClientID = 'Pager';
        $this->Pager->Configure(
            $Offset,
            $Limit,
            $CountDrafts,
            'drafts/%1$s'
        );

        // Deliver JSON data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->SetJson('LessRow', $this->Pager->ToString('less'));
            $this->SetJson('MoreRow', $this->Pager->ToString('more'));
            $this->View = 'drafts';
        }

        // Add modules
        $this->AddModule('DiscussionFilterModule');
        $this->AddModule('NewDiscussionModule');
        $this->AddModule('CategoriesModule');
        $this->AddModule('BookmarkedModule');

        // Render default view (drafts/index.php)
        $this->Render();
    }

    /**
     * Delete a single draft.
     *
     * Redirects user back to Index unless DeliveryType is set.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DraftID Unique ID of draft to be deleted.
     * @param string $TransientKey Single-use hash to prove intent.
     */
    public function Delete($DraftID = '', $TransientKey = '') {
        $Form = Gdn::Factory('Form');
        $Session = Gdn::Session();
        if (
            is_numeric($DraftID)
            && $DraftID > 0
            && $Session->UserID > 0
            && $Session->ValidateTransientKey($TransientKey)
        ) {
            // Delete the draft
            $Draft = $this->DraftModel->GetID($DraftID);
            if ($Draft && !$this->DraftModel->Delete($DraftID))
                $Form->AddError('Failed to delete discussion');
        } else {
            // Log an error
            $Form->AddError('ErrPermission');
        }

        // Redirect
        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $Target = GetIncomingValue('Target', '/drafts');
            Redirect($Target);
        }

        // Return any errors
        if ($Form->ErrorCount() > 0)
            $this->SetJson('ErrorMessage', $Form->Errors());

        // Render default view
        $this->Render();
    }
}
