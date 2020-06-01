<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Gdn_Session;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Handle all-purpose drafts.
 */
class DraftModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /**
     * DraftModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("contentDraft");
        $this->session = $session;

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);

        $jsonProcessor = new JsonFieldProcessor();
        $jsonProcessor->setFields(["attributes"]);
        $this->addPipelineProcessor($jsonProcessor);
    }

    /**
     * Get draft count for particular user
     *
     * @param int $userID
     * @return int
     */
    public function draftsCount(int $userID): int {

        $countRecord = $this->sql()
            ->from($this->getTable())
            ->select('*', 'COUNT', 'draftCount')
            ->where('insertUserID', $userID)
            ->groupBy('insertUserID')
            ->get()->nextRow(DATASET_TYPE_ARRAY);

         return $countRecord['draftCount'];
    }
}
