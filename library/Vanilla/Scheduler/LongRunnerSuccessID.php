<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

/**
 * Represent a recordID that has completed successfully.
 *
 * Meant to be yielded from a long-running function,
 *
 * @example
 * yield new LongRunnerCompleteID($someRecordID)
 */
final class LongRunnerSuccessID implements LongRunnerItemResultInterface
{
    /** @var int|string */
    private $recordID;

    /**
     * Constructor.
     *
     * @param int|string $recordID The completed ID.
     */
    public function __construct($recordID)
    {
        $this->recordID = $recordID;
    }

    /**
     * @inheritdoc
     */
    public function getRecordID()
    {
        return $this->recordID;
    }
}
