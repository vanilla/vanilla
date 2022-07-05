<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

/**
 * Represent a recordID that has failed to complete successfully.
 *
 * Meant to be yielded from a long-running function,
 *
 * @example
 * yield new LongRunnerFailedID($someRecordID)
 * yield new LongRunnerFailedID($someRecordID, new Exception('Why it failed'));
 */
final class LongRunnerFailedID implements LongRunnerItemResultInterface
{
    /** @var int|string */
    private $recordID;

    /** @var \Exception|null */
    private $exception;

    /**
     * Constructor.
     *
     * @param int|string $recordID
     * @param \Exception|null $exception
     */
    public function __construct($recordID, ?\Exception $exception = null)
    {
        $this->recordID = $recordID;
        $this->exception = $exception;
    }

    /**
     * @inheritdoc
     */
    public function getRecordID()
    {
        return $this->recordID;
    }

    /**
     * @return \Exception|null
     */
    public function getException(): ?\Exception
    {
        return $this->exception;
    }
}
