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
trait DatabaseTestTrait {

    /**
     * @return \Gdn_Database
     */
    protected function getDb(): \Gdn_Database {
        return \Gdn::database();
    }

    /**
     * Assert that no database records were found.
     *
     * @param string $table
     * @param array $where
     */
    protected function assertNoRecordsFound(string $table, array $where) {
        $count = $this->getDb()
            ->createSql()
            ->getCount($table, $where)
        ;
        TestCase::assertEquals(0, $count);
    }

    /**
     * Assert that some database records were found.
     *
     * @param string $table
     * @param array $where
     */
    protected function assertRecordsFound(string $table, array $where) {
        $count = $this->getDb()
            ->createSql()
            ->getCount($table, $where)
        ;
        TestCase::assertGreaterThan(0, $count);
    }
}
