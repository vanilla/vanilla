<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

/**
 * Defines an event related to defining a record status
 */
class RecordStatusDefinitionEvent {

    //region Properties
    /** @var array $payload Event payload, typically API output corresponding to a database row */
    protected $payload;

    /** @var int $id ID of the status */
    protected $id;

    /** @var string $action Action associated with the event */
    protected $action;

    /** @var int $userID ID of the user performing the action on the status item */
    protected $userID;

    /** @var int|null Optional foreign ID of the status related to this event */
    private $foreignID;

    //endregion

    //region Constructor
    /**
     * Constructor
     *
     * @param string $action Action associated with the event
     * @param int $id ID assigned to the status
     * @param array $payload Payload for the event, typically a database row
     * @param int $userID ID of the user performing the action on the status item
     * @param int|null $foreignID Optional foreign ID of the status related to this event
     */
    public function __construct(string $action, int $id, array $payload, int $userID, ?int $foreignID = null) {
        $this->action = $action;
        $this->id = $id;
        $this->payload = $payload;
        $this->userID = $userID;
        $this->foreignID = $foreignID;
    }
    //endregion

    //region Accessor Methods

    /**
     * Get the event action
     *
     * @return string
     */
    public function getAction(): string {
        return $this->action;
    }

    /**
     * Get the ID of the affected status
     *
     * @return int
     */
    public function getID(): int {
        return $this->id;
    }

    /**
     * Get the payload of the affected status
     *
     * @return array
     */
    public function getPayload(): array {
        return $this->payload;
    }

    /**
     * Get the ID of the user performing the action on the status item.
     *
     * @return int
     */
    public function getUserID(): int {
        return $this->userID;
    }

    /**
     * Get foreign ID of the status related to this event
     *
     * @return int|null
     */
    public function getForeignID(): ?int {
        return $this->foreignID;
    }
    //endregion
}
