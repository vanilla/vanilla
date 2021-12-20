<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

/**
 * Defines the event dispatched when a status is applied to a discussion
 */
class DiscussionStatusEvent {

    //region Properties
    /** @var int $discussionID */
    private $discussionID;

    /** @var int $statusID */
    private $statusID;

    /** @var int $previousStatusID */
    private $previousStatusID;
    //endregion

    //region Constructor
    /**
     * Constructor
     *
     * @param int $discussionID ID of the discussion to which the status update applies
     * @param int $statusID ID of the status assigned to the discussion
     * @param int $previousStatusID ID of the discussion's status that was overwritten by the status update
     */
    public function __construct(int $discussionID, int $statusID, int $previousStatusID) {
        $this->discussionID = $discussionID;
        $this->statusID = $statusID;
        $this->previousStatusID = $previousStatusID;
    }
    //endregion

    //region Methods
    /**
     * Get the ID of the discussion to which the status update applies.
     *
     * @return int
     */
    public function getDiscussionID(): int {
        return $this->discussionID;
    }

    /**
     * Get the ID of the status assigned to the discussion
     *
     * @return int
     */
    public function getStatusID(): int {
        return $this->statusID;
    }

    /**
     * Get the ID of the discussion's status that was overwritten by the status update
     *
     * @return int
     */
    public function getPreviousStatusID(): int {
        return $this->previousStatusID;
    }

    /**
     * Convert to string.
     */
    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
    //endregion
}
