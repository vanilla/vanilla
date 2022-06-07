<?php
/**
 * @author David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Reactions\Events;

use Garden\Events\ResourceEvent;

/**
 * Represent a reaction resource event.
 */
class ReactionEvent extends ResourceEvent
{
    /**
     * ReactionEvent constructor.
     *
     * @param string $action
     * @param array $payload
     * @param array|null $sender
     */
    public function __construct(string $action, array $payload, ?array $sender = null)
    {
        parent::__construct($action, $payload, $sender);
    }

    /**
     * Get the user that made the reaction.
     */
    public function getReactionUserID(): ?int
    {
        return $this->payload["reaction"]["user"]["userID"] ?? null;
    }

    /**
     * @return string
     */
    public function getRecordType(): string
    {
        return strtolower($this->payload["reaction"]["recordType"]);
    }

    /**
     * @return int
     */
    public function getRecordID(): int
    {
        return $this->payload["reaction"]["recordID"];
    }

    /**
     * @return string
     */
    public function getReactionName(): string
    {
        return $this->payload["reaction"]["reactionType"]["name"];
    }
}
