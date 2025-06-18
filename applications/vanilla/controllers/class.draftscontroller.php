<?php
/**
 * Drafts controller
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

use Vanilla\Models\ContentDraftModel;
use Vanilla\Models\Model;
use Vanilla\Web\TwigStaticRenderer;
use Vanilla\FeatureFlagHelper;

/**
 * Handles displaying saved drafts of unposted comments via /drafts endpoint.
 */
class DraftsController extends VanillaController
{
    public int $offset = 0;
    public Gdn_DataSet|null $DraftData = null;
    public PagerModule|null $Pager = null;

    public function __construct(
        private Gdn_Database $database,
        private DraftModel $DraftModel,
        private ContentDraftModel $contentDraftModel
    ) {
        parent::__construct();
    }

    /**
     * Default all drafts view: chronological by time saved.
     *
     * @param int $offset Number of drafts to skip.
     */
    public function index($offset = 0)
    {
        $this->offset = $offset;
        Gdn_Theme::section("DiscussionList");

        // Setup head
        $this->permission("session.valid");
        $this->addJsFile("jquery.gardenmorepager.js");

        $this->addJsFile("discussions.js");
        $this->title(t("My Drafts"));

        // Validate $Offset
        if (!is_numeric($offset) || $offset < 0) {
            $offset = 0;
        }

        // Set criteria & get drafts data
        $limit = Gdn::config("Vanilla.Discussions.PerPage", 30);
        $session = Gdn::session();
        $countDrafts = $this->DraftModel->getCountByUser($session->UserID);
        $offsetCalculated = (int) (($countDrafts - 2) / $limit) * $limit;
        if ($offset >= $offsetCalculated) {
            $this->offset = $offsetCalculated;
        }

        if (ContentDraftModel::enabled()) {
            $rows = $this->contentDraftModel->select(
                where: [
                    "insertUserID" => $session->UserID,
                    "recordType" => ["discussion", "comment"],
                ],
                options: [
                    Model::OPT_LIMIT => $limit,
                    Model::OPT_OFFSET => $offset,
                    Model::OPT_ORDER => "dateUpdated",
                    Model::OPT_DIRECTION => "desc",
                ]
            );

            // Now we need to know if the discussions themselves are live.
            foreach ($rows as &$row) {
                $recordType = $row["recordType"] ?? null;
                $row = $this->contentDraftModel->convertToLegacyDraft($row, $recordType);
            }

            $discussionIDs = array_filter(array_column($rows, "DiscussionID"));
            $liveDiscussions = $this->database
                ->createSql()
                ->select(["DiscussionID", "Name"])
                ->from("Discussion")
                ->where("DiscussionID", $discussionIDs)
                ->get()
                ->resultArray();

            $liveDiscussionIDs = array_column($liveDiscussions, "DiscussionID");

            $discussionNamesByDiscussionID = array_column($liveDiscussions, "Name", "DiscussionID");
            foreach ($rows as &$row) {
                $discussionID = $row["DiscussionID"] ?? null;
                $row["DiscussionExists"] = in_array($discussionID, $liveDiscussionIDs);
                if (
                    $discussionID !== null &&
                    ($discussionName = $discussionNamesByDiscussionID[$discussionID] ?? null)
                ) {
                    $row["Name"] = sprintft("Re: %s", $discussionName);
                }
            }

            // Let's convert the rows into the legacy format.

            $data = new Gdn_DataSet($rows, DATASET_TYPE_ARRAY);
        } else {
            $data = $this->DraftModel->getByUser($session->UserID, $offset, $limit);
            $rows = $data->resultArray();
            foreach ($rows as &$row) {
                if (isset($row["DiscussionID"]) && ($rowName = $row["Name"] ?? null)) {
                    $row["Name"] = sprintft("Re: %s", $rowName);
                }
            }
            $data = new Gdn_DataSet($rows, DATASET_TYPE_ARRAY);
        }

        $this->DraftData = $data;
        $this->setData("Drafts", $data);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->Pager = $pagerFactory->getPager("MorePager", $this);
        $this->Pager->MoreCode = "More drafts";
        $this->Pager->LessCode = "Newer drafts";
        $this->Pager->ClientID = "Pager";
        $this->Pager->configure($offset, $limit, $countDrafts, 'drafts/%1$s');

        // Deliver JSON data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson("LessRow", $this->Pager->toString("less"));
            $this->setJson("MoreRow", $this->Pager->toString("more"));
            $this->View = "drafts";
        }
        // Add modules
        $this->addModule("DiscussionFilterModule");
        $this->addModule("NewDiscussionModule");
        $this->addModule("CategoriesModule");
        $this->addModule("BookmarkedModule");

        if (!FeatureFlagHelper::featureEnabled("DraftScheduling")) {
            // Render default view (drafts/index.php)
            $this->render();
        }
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
    public function delete($draftID = 0, $transientKey = "")
    {
        $form = Gdn::factory("Form");
        $session = Gdn::session();
        if (is_numeric($draftID) && $draftID > 0) {
            $draft = ContentDraftModel::enabled()
                ? $this->contentDraftModel->convertToLegacyDraft(
                    $this->contentDraftModel->selectSingle(["draftID" => $draftID])
                )
                : $this->DraftModel->getID($draftID);
        }
        if (!empty($draft)) {
            if (
                $session->validateTransientKey($transientKey) &&
                (val("InsertUserID", $draft) == $session->UserID || checkPermission("Garden.Community.Manage"))
            ) {
                // Delete the draft
                if (ContentDraftModel::enabled()) {
                    $this->contentDraftModel->delete(where: ["draftID" => $draftID]);
                } else {
                    if (!$this->DraftModel->deleteID($draftID)) {
                        $form->addError("Failed to delete draft");
                    }
                }
            } else {
                throw permissionException("Garden.Community.Manage");
            }
        } else {
            throw notFoundException("Draft");
        }

        // Redirect
        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $target = getIncomingValue("Target", "/drafts");
            redirectTo($target);
        }

        // Return any errors
        if ($form->errorCount() > 0) {
            $this->setJson("ErrorMessage", $form->errors());
        }

        // Render default view.
        $this->render("blank", "utility", "dashboard");
    }
}
