<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Search;

use Vanilla\Search\MysqlSearchDriver;

/**
 * Community search tests for MySQL.
 */
class MysqlCommunitySearchTest extends AbstractCommunitySearchTests {

    /**
     * @inheritdoc
     */
    protected static function getSearchDriverClass(): string {
        return MysqlSearchDriver::class;
    }

    /**
     * Not implemented.
     */
    public function testSearchDiscussionTags() {
        $this->markTestSkipped('MySQL driver does not support tag search.');
    }
}
