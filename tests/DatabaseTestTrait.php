<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Framework\TestCase;

/**
 * Test trait with database utilities.
 */
trait DatabaseTestTrait
{
    /**
     * @return \Gdn_Database
     */
    protected function getDb(): \Gdn_Database
    {
        return \Gdn::database();
    }

    /**
     * Assert that no database records were found.
     *
     * @param string $table
     * @param array $where
     */
    protected function assertNoRecordsFound(string $table, array $where)
    {
        $count = $this->getDb()
            ->createSql()
            ->getCount($table, $where);
        TestCase::assertEquals(0, $count);
    }

    /**
     * Assert that some database records were found.
     *
     * @param string $table
     * @param array $where
     * @param int|null $expectedCount The expected record count to be found or null.
     *
     * @return array The found records.
     */
    protected function assertRecordsFound(string $table, array $where, ?int $expectedCount = null): array
    {
        $records = $this->getDb()
            ->createSql()
            ->getWhere($table, $where)
            ->resultArray();
        if ($expectedCount !== null) {
            TestCase::assertCount($expectedCount, $records);
        } else {
            TestCase::assertTrue(count($records) > 0, "No records were found.");
        }
        return $records;
    }
}
