<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use DateTime;

/**
 * Represent a AfterUserAnonymize event.
 */
class AfterUserAnonymizeEvent
{
    /** @var int */
    private $userID;

    /** @var DateTime */
    private $dateTime;
    /**
     * Constructor for AfterUserAnonymizeEvent class.
     *
     * @param int $userID user ID.
     * @param DateTime $dateTime start of the anonymizing.
     */
    public function __construct(int $userID, DateTime $dateTime)
    {
        $this->userID = $userID;
        $this->dateTime = $dateTime;
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

    /**
     * Return a start dataTime userAnonymize event.
     *
     * @return DateTime
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    /**
     * Set start date time.
     *
     * @param string $dateTime
     * @return void
     */
    public function setDateTime(string $dateTime)
    {
        $this->dateTime = $dateTime;
    }
}
