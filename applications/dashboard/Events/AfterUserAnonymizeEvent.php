<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

/**
 * Represent a AfterUserAnonymize event.
 */
class AfterUserAnonymizeEvent
{
    /** @var int */
    private $userID;

    /**
     * Constructor for AfterUserAnonymizeEvent class.
     *
     * @param int $userID
     */
    public function __construct(int $userID)
    {
        $this->userID = $userID;
    }

    /**
     * Return an userID userAnonymize event.
     *
     * @return int
     */
    public function getUserID(): int
    {
        return $this->userID;
    }

    /**
     * Set userID.
     *
     * @param int $userID
     * @return void
     */
    public function setUserID(int $userID)
    {
        $this->userID = $userID;
    }
}
