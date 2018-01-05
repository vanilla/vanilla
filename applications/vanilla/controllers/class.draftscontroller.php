<?php
/**
 * Drafts controller
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles displaying saved drafts of unposted comments via /drafts endpoint.
 */
class DraftsController extends VanillaController {

    /** @var array Models to include. */
    public $Uses = ['Database', 'DraftModel'];

    /**
     * Default all drafts view: chronological by time saved.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $offset Number of drafts to skip.
     */
    public function index($offset = '0') {
        Gdn_Theme::section('DiscussionList');

        // Setup head
        $this->permission('Garden.SignIn.Allow');
        $this->addJsFile('jquery.gardenmorepager.js');
        $this->addJsFile('discussions.js');
        $this->title(t('My Drafts'));

        // Validate $Offset
        if (!is_numeric($offset) || $offset < 0) {
            $offset = 0;
        }

        // Set criteria & get drafts data
        $limit = Gdn::config('Vanilla.Discussions.PerPage', 30);
        $session = Gdn::session();
        $wheres = ['d.InsertUserID' => $session->UserID];
        $this->DraftData = $this->DraftModel->getByUser($session->UserID, $offset, $limit);
        $countDrafts = $this->DraftModel->getCountByUser($session->UserID);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->Pager = $pagerFactory->getPager('MorePager', $this);
        $this->Pager->MoreCode = 'More drafts';
        $this->Pager->LessCode = 'Newer drafts';
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $offset,
            $limit,
            $countDrafts,
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
     * @param int $draftID Unique ID of draft to be deleted.
     * @param string $transientKey Single-use hash to prove intent.
     */
    public function delete($draftID = '', $transientKey = '') {
        $form = Gdn::factory('Form');
        $session = Gdn::session();
        if (is_numeric($draftID) && $draftID > 0) {
            $draft = $this->DraftModel->getID($draftID);
        }
        if ($draft) {
            if ($session->validateTransientKey($transientKey)
                && ((val('InsertUserID', $draft) == $session->UserID) || checkPermission('Garden.Community.Manage'))
            ) {
                // Delete the draft
                if (!$this->DraftModel->deleteID($draftID)) {
                    $form->addError('Failed to delete draft');
                }
            } else {
                throw permissionException('Garden.Community.Manage');
            }
        } else {
            throw notFoundException('Draft');
        }

        // Redirect
        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $target = getIncomingValue('Target', '/drafts');
            redirectTo($target);
        }

        // Return any errors
        if ($form->errorCount() > 0) {
            $this->setJson('ErrorMessage', $form->errors());
        }

        // Render default view
        $this->render();
    }
}
