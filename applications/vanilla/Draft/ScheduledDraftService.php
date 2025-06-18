<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Draft;

use Vanilla\Models\ContentDraftModel;
use Vanilla\Models\ScheduledDraftModel;

/**
 * Service class to process scheduled drafts
 */
class ScheduledDraftService
{
    public function __construct(
        private ScheduledDraftModel $draftScheduledModel,
        private ContentDraftModel $draftModel,
        private ScheduledDraftJob $scheduledDraftJob
    ) {
    }

    /**
     * check for any scheduled drafts that need to be processed nad initiate a job.
     *
     * @return void
     * @throws \Garden\Schema\ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function processScheduledDrafts(): void
    {
        $key = "scheduleTheDraft";
        // If the user is not allowed to sign in then do nothing as they dont have permission to run a long runner job
        if (!\Gdn::session()->checkPermission("Garden.SignIn.Allow")) {
            return;
        }
        // If we have made a check in the last 60 seconds then do nothing
        if (!\Gdn::cache()->get($key)) {
            if (!ContentDraftModel::draftSchedulingEnabled()) {
                return;
            }
            // Schedule drafts
            if ($this->draftScheduledModel->isCurrentlyScheduled()) {
                //if there is a job already in progress then do nothing
                return;
            }
            if (!$this->draftModel->getCurrentScheduledDraftsCount()) {
                // There is nothing to process at the moment
                return;
            }
            \Gdn::cache()->store($key, true, [\Gdn_Cache::FEATURE_EXPIRY => 120]);
            // process the drafts that are ready to be processed
            $this->scheduledDraftJob->run();
        }
    }
}
