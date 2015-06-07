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
    public function index($Offset = '0') {
        Gdn_Theme::section('DiscussionList');

        // Setup head
        $this->permission('Garden.SignIn.Allow');
        $this->addJsFile('jquery.gardenmorepager.js');
        $this->addJsFile('discussions.js');
        $this->title(t('My Drafts'));

        // Validate $Offset
        if (!is_numeric($Offset) || $Offset < 0) {
            $Offset = 0;
        }

        // Set criteria & get drafts data
        $Limit = Gdn::config('Vanilla.Discussions.PerPage', 30);
        $Session = Gdn::session();
        $Wheres = array('d.InsertUserID' => $Session->UserID);
        $this->DraftData = $this->DraftModel->get($Session->UserID, $Offset, $Limit);
        $CountDrafts = $this->DraftModel->getCount($Session->UserID);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->Pager = $PagerFactory->GetPager('MorePager', $this);
        $this->Pager->MoreCode = 'More drafts';
        $this->Pager->LessCode = 'Newer drafts';
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $Offset,
            $Limit,
            $CountDrafts,
            'drafts/%1$s'
        );

        // Deliver JSON data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = 'drafts';
        }

        // Add modules
        $this->addModule('DiscussionFilterModule');
        $this->addModule('NewDiscussionModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');

        // Render default view (drafts/index.php)
        $this->render();
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
    public function delete($DraftID = '', $TransientKey = '') {
        $Form = Gdn::Factory('Form');
        $Session = Gdn::session();
        if (is_numeric($DraftID)
            && $DraftID > 0
            && $Session->UserID > 0
            && $Session->validateTransientKey($TransientKey)
        ) {
            // Delete the draft
            $Draft = $this->DraftModel->getID($DraftID);
            if ($Draft && !$this->DraftModel->delete($DraftID)) {
                $Form->addError('Failed to delete discussion');
            }
        } else {
            // Log an error
            $Form->addError('ErrPermission');
        }

        // Redirect
        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $Target = GetIncomingValue('Target', '/drafts');
            redirect($Target);
        }

        // Return any errors
        if ($Form->errorCount() > 0) {
            $this->setJson('ErrorMessage', $Form->errors());
        }

        // Render default view
        $this->render();
    }
}
