<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * Trait for testing and asserting items logged with the log model.
 */
trait LogModelTestTrait
{
    /**
     * Assert that some items were logged and return them.
     *
     * @param int $expectedCount The expected count of items.
     * @param array $where A where clause to look for items.
     * @param string $message Message to return on failure.
     *
     * @return array The found items.
     */
    protected function assertCountLoggedRecords(int $expectedCount, array $where, string $message = ""): array
    {
        $logModel = $this->getLogModel();
        $results = $logModel->getWhere($where);
        Assert::assertCount($expectedCount, $results, $message);
        return $results;
    }

    /**
     * @return \LogModel
     */
    protected function getLogModel(): \LogModel
    {
        return \Gdn::getContainer()->get(\LogModel::class);
    }
}
