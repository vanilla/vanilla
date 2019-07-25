<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;
use Gdn_Session;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;

/**
 * Handle reactions types (owner (app, addon) + record type (model, table) + reaction type).
 * ex: knowledgebase + article + helpful
 */
class ReactionOwnerModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /**
     * ReactionOwnerModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("reactionOwner");
        $this->session = $session;

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Get unique reactionOwnerID for the combination of filters: ownerType, reactionType, recordType
     *
     * @param array $filters Structure of 3 elements: ownerType, reactionType, recordType
     * @return int Result reactionOwnerID
     */
    public function getReactionOwnerID(array $filters): int {
        $schema = Schema::parse([
            'ownerType:s',
            'reactionType:s',
            'recordType:s'
        ]);
        $filters = $schema->validate($filters);
        $res = $this->get($filters);
        if (empty($res)) {
            $id = $this->insert($filters);
        } else {
            $id = $res[0]['reactionOwnerID'];
        }
        return $id;
    }
}
