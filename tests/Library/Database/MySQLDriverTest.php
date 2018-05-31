<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Database;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the **Gdn_MySQLDriver** class.
 */
class MySQLDriverTest extends TestCase {
    use SiteTestTrait;

    /**
     * @var \Gdn_MySQLDriver
     */
    protected $sql;

    /**
     * @var \Closure
     */
    protected $dump;

    /**
     * Rest the SQL driver before every test.
     */
    public function setUp() {
        parent::setUp();
        $sql = static::container()->get(\Gdn_MySQLDriver::class);
        $sql->reset();
        $this->sql = $sql;

        $dump = function () {
            $r =  [
                'where' => $this->_Wheres,
            ];

            return $r;
        };
        $this->dump = \Closure::bind($dump, $this->sql, $this->sql);
    }

    /**
     * Make sure the SQL object isn't polluted.
     */
    public function tearDown() {
        $this->sql->reset();
        parent::tearDown();
    }

    /**
     * Dump protected/private members of the SQL class.
     *
     * @return array
     */
    protected function dumpSql(): array {
        return call_user_func($this->dump);
    }

    /**
     * Make sure the table is escaped in **fetchTableSchema()**.
     *
     * @expectedException PHPUnit\Framework\Error\Error
     */
    public function testFetchTableSchemeInjection() {
        $schema = $this->sql->fetchTableSchema("User/**/where/**/1=(select/**/1/**/from(select/**/sleep(/**/1/**/))a)");
    }

    /**
     * Field names in where clauses should be escaped.
     */
    public function testFieldEscape() {
        $sql = $this->sql
            ->where(["1=sleep(1) and 1" => "world"])
            ->getDelete('Foo', $this->dumpSql()['where']);

        $this->assertContains('`1=sleep(1) and 1`', $sql);
    }
}
