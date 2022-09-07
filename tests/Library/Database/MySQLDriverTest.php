<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Database;

use PHPUnit\Framework\TestCase;
use Gdn_Database;
use Vanilla\CurrentTimeStamp;
use Vanilla\Database\SetLiterals\Increment;
use Vanilla\Database\SetLiterals\MinMax;
use Vanilla\Schema\RangeExpression;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the **Gdn_MySQLDriver** class.
 */
class MySQLDriverTest extends SiteTestCase
{
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
    public function setUp(): void
    {
        parent::setUp();
        $db = static::container()->get(Gdn_Database::class);
        $this->sql = $db->createSql();
        static::container()->setInstance(\Gdn_MySQLDriver::class, $this->sql);
        static::container()->setInstance(\Gdn_DatabaseStructure::class, null);

        $dump = function () {
            $r = [
                "where" => $this->_Wheres,
            ];

            return $r;
        };
        $this->dump = \Closure::bind($dump, $this->sql, $this->sql);
    }

    /**
     * Make sure the SQL object isn't polluted.
     */
    public function tearDown(): void
    {
        $this->sql->reset();
        parent::tearDown();
    }

    /**
     * Dump protected/private members of the SQL class.
     *
     * @return array
     */
    protected function dumpSql(): array
    {
        return call_user_func($this->dump);
    }

    /**
     * Make sure the table is escaped in **fetchTableSchema()**.
     */
    public function testFetchTableSchemeInjection()
    {
        $this->expectException(\Gdn_UserException::class);
        $schema = $this->sql->fetchTableSchema("User/**/where/**/1=(select/**/1/**/from(select/**/sleep(/**/1/**/))a)");
    }

    /**
     * Field names in where clauses should be escaped.
     */
    public function testFieldEscape()
    {
        $sql = $this->sql->where(["1=sleep(1) and 1" => "world"])->getDelete("Foo", $this->dumpSql()["where"]);

        $this->assertStringContainsString("`1=sleep(1) and 1`", $sql);
    }

    /**
     * Provide table aliases and tables.
     *
     * @return array
     */
    public function provideAliasData()
    {
        return [
            ["Test t", "`GDN_Test` `t`"],
            ["Test as t", "`GDN_Test` `t`"],
            ["Test", "`GDN_Test` `Test`"],
            ["Test t", "GDN_Test t", false],
            ["Test as t", "GDN_Test t", false],
            ["Test", "GDN_Test Test", false],
        ];
    }

    /**
     * Test the alias mapping in the SQL driver.
     *
     * @param string $input
     * @param string $expected
     * @param bool $escape
     * @dataProvider provideAliasData
     */
    public function testMapAliases($input, $expected, $escape = true)
    {
        $aliases = $this->sql->mapAliases($input, $escape);
        $this->assertSame($aliases, $expected);
    }

    /**
     * Testing a basic where in expression.
     */
    public function testWhereIn()
    {
        $sql = $this->sql
            ->select()
            ->from("foo")
            ->whereIn("bar", ["a"])
            ->getSelect();

        $expected = <<<EOT
select *
from `GDN_foo` `foo`
where `bar` in ('a')
EOT;

        $this->assertEquals($expected, $sql);
    }

    /**
     * Testing a where in expression with an empty list.
     */
    public function testWhereInEmpty()
    {
        $sql = $this->sql
            ->select()
            ->from("foo")
            ->whereIn("bar", [])
            ->getSelect();

        $expected = <<<EOT
select *
from `GDN_foo` `foo`
where 1 = 0
EOT;

        $this->assertEquals($expected, $sql);
    }

    /**
     * Testing a where not in expression with an empty list.
     */
    public function testWhereNotInEmpty()
    {
        $sql = $this->sql
            ->select()
            ->from("foo")
            ->whereNotIn("bar", [])
            ->getSelect();

        $expected = <<<EOT
select *
from `GDN_foo` `foo`
where 1 = 1
EOT;

        $this->assertEquals($expected, $sql);
    }

    /**
     * Test a `DateTimeInterface` being passed to `where()`.
     */
    public function testDateWhere()
    {
        $where = [
            "d.DateLastComment <" => \DateTimeImmutable::__set_state([
                "date" => "2019-10-27 23:32:35.000000",
                "timezone_type" => 1,
                "timezone" => "+00:00",
            ]),
        ];

        $this->sql->from("foo")->where($where);

        $sql = $this->sql->getSelect();

        $expected = <<<EOT
select *
from `GDN_foo` `foo`
where `d`.`DateLastComment` < :dDateLastComment
EOT;

        $this->assertEquals($expected, $sql);
    }

    /**
     * The `Gdn_Model::delete()` method had a documented return type of `Gdn_Dataset` forever, so I wanted to confirm its return type.
     */
    public function testDeleteReturn()
    {
        $st = $this->sql->Database->structure();
        $st->table("testDelete")
            ->primaryKey("testDeleteID")
            ->column("name", "varchar(50)")
            ->set();

        $id = $this->sql->insert("testDelete", ["name" => "foo"]);
        $id2 = $this->sql->insert("testDelete", ["name" => "foo"]);

        $r = $this->sql->delete("testDelete", ["name" => $id]);
        $this->assertEquals(2, $r);
    }

    /**
     * Test a basic where in clause.
     */
    public function testWhereInField()
    {
        $actual = $this->sql
            ->select()
            ->from("foo")
            ->where("bar", [1, 2, "three"])
            ->getSelect();
        $expected = <<<EOT
select *
from `GDN_foo` `foo`
where `bar` in ('1', '2', 'three')
EOT;

        $this->assertSame($expected, $actual);
    }

    /**
     * Adding "-" before an order field orders it in descending order.
     */
    public function testNegativeOrderBy()
    {
        $actual = $this->sql
            ->select()
            ->from("foo")
            ->orderBy("-foo, bar", "asc")
            ->getSelect();
        $expected = <<<EOT
select *
from `GDN_foo` `foo`
order by `foo` desc, `bar` asc
EOT;
        $this->assertSame($expected, $actual);
    }

    /**
     * The `Gdn_SQLDriver::where()` method can take `RangeExpression` objects.
     *
     * @param RangeExpression $range
     * @param string $expectedWhere
     * @dataProvider provideRangeExpressionTests
     */
    public function testRangeExpressionWhere(RangeExpression $range, string $expectedWhere)
    {
        $actual = $this->sql
            ->select()
            ->from("foo")
            ->where("b", $range)
            ->getSelect();
        $actual = preg_replace("`\s+`", " ", $actual);
        $this->assertStringContainsString($expectedWhere, $actual);
    }

    /**
     * Provide some sample range expressions and expected where clauses.
     *
     * @return array
     */
    public function provideRangeExpressionTests(): array
    {
        $r = [
            "basic" => [new RangeExpression(">", 1), "where `b` > :b"],
            "two values" => [new RangeExpression(">=", 1, "<=", 2), "where `b` >= :b and `b` <= :b0"],
            "in clause" => [new RangeExpression("=", [1, 2]), "where `b` in ('1', '2')"],
            "empty equals" => [new RangeExpression("=", []), "where 1 = 0"],
        ];
        return $r;
    }

    /**
     * Test the various legacy order Bys.
     *
     * @param string $expected
     * @param mixed $params
     * @dataProvider provideOrderBys
     */
    public function testLegacyOrderBys($expected, ...$params): void
    {
        $actual = $this->sql
            ->from("t")
            ->orderBy(...$params)
            ->getSelect();

        if (!empty($expected)) {
            $expected = "\norder by $expected";
        }

        $sql = <<<EOT
select *
from `GDN_t` `t`$expected
EOT;
        $this->assertSame($sql, $actual);
    }

    /**
     * Provide order by tests.
     *
     * @return array
     */
    public function provideOrderBys(): array
    {
        $r = [
            "column" => ["`a` asc", "a"],
            "-column" => ["`a` desc", "-a"],
            "csv" => ["`a` asc, `b` desc", ["a", "-b"]],

            "column dir" => ["`a` desc", "a", "desc"],
            "a, b" => ["`a` asc, `b` desc", "a, b", "desc"],
            "a => desc" => ["`a` desc", ["a" => "desc"]],

            "''" => ["", ""],
            "[]" => ["", []],
        ];
        return $r;
    }

    /**
     * Test the `Increment` value.
     *
     * @param int $increment
     * @param string $expected
     * @dataProvider incrementTests
     */
    public function testIncrement(int $increment, string $expected): void
    {
        $sql = $this->sql
            ->update("test")
            ->set("a", new Increment($increment))
            ->getUpdateSql();
        $expected = <<<SQL
update `GDN_test` `test`
set `a` = `a` $expected
SQL;
        $this->assertSame($expected, $sql);
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function incrementTests(): array
    {
        $r = [[1, "+1"], [-1, "-1"]];

        return array_column($r, null, 0);
    }

    /**
     * An increment of zero shouldn't add a SET clause.
     */
    public function testIncrementEmpty(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Cannot generate UPDATE statement with missing clauses.");
        $sql = $this->sql->update("test", ["a" => new Increment(0)])->getUpdateSql();
    }

    /**
     * Test min/max literals.
     *
     * @param string $op
     * @param string $expected
     * @dataProvider minMaxTests
     */
    public function testMinMax(string $op = MinMax::OP_MIN, string $expected = "<")
    {
        $dt = new \DateTime("2020-06-20", new \DateTimeZone("UTC"));

        $sql = $this->sql
            ->update("test")
            ->set("a", new MinMax($op, $dt))
            ->getUpdateSql();
        $expected = <<<SQL
update `GDN_test` `test`
set `a` = case when `a` is null or '2020-06-20 00:00:00' $expected `a` then '2020-06-20 00:00:00' else `a` end
SQL;
        $this->assertSame($expected, $sql);
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function minMaxTests(): array
    {
        return [
            MinMax::OP_MIN => [MinMax::OP_MIN, "<"],
            MinMax::OP_MAX => [MinMax::OP_MAX, ">"],
        ];
    }

    /**
     * The driver should convert an array `<>` to a not in clause.
     */
    public function testWhereNotInShorthand()
    {
        $sql = $this->sql
            ->from("test")
            ->where(["foo <>" => ["a", "b"]])
            ->getSelect();

        $expected = <<<SQL
select *
from `GDN_test` `test`
where `foo` not in ('a', 'b')
SQL;

        $this->assertSame($expected, $sql);
    }

    /**
     * Test the history method
     */
    public function testHistory()
    {
        $ts = strtotime("January 1, 2020");
        $dateString = date("Y-m-d H:i:s", $ts);
        CurrentTimeStamp::mockTime($ts);
        $userId = (int) \Gdn::session()->UserID;

        $bothParams = $this->sql->history(true, true)->namedParameters();
        $insertParams = $this->sql->history(false, true)->namedParameters();
        $updateParams = $this->sql->history(true, false)->namedParameters();

        $this->assertArrayHasKey(":DateInserted", $bothParams);
        $this->assertArrayHasKey(":DateUpdated", $bothParams);
        $this->assertArrayHasKey(":InsertUserID", $bothParams);
        $this->assertArrayHasKey(":UpdateUserID", $bothParams);
        $this->assertArrayHasKey(":DateInserted", $insertParams);
        $this->assertArrayHasKey(":InsertUserID", $insertParams);
        $this->assertArrayHasKey(":DateUpdated", $updateParams);
        $this->assertArrayHasKey(":UpdateUserID", $updateParams);
        $this->assertSame($dateString, $bothParams[":DateInserted"]);
        $this->assertSame($dateString, $bothParams[":DateUpdated"]);
        $this->assertSame($userId, $bothParams[":InsertUserID"]);
        $this->assertSame($userId, $bothParams[":UpdateUserID"]);
        $this->assertSame($dateString, $insertParams[":DateInserted"]);
        $this->assertSame($userId, $insertParams[":InsertUserID"]);
        $this->assertSame($dateString, $updateParams[":DateUpdated"]);
        $this->assertSame($userId, $updateParams[":UpdateUserID"]);
        CurrentTimeStamp::clearMockTime();
    }

    /**
     * Tests that query custom options are passed down to {@link Gdn_Database::query} properly
     */
    public function testCustomQueryOptions()
    {
        $sql = "SELECT :param1 AS `value1`";
        $options = [
            "testOption" => "testValue",
        ];

        $mock = $this->createMock(Gdn_Database::class);
        $mock
            ->expects($this->exactly(1))
            ->method("query")
            ->with(
                $sql,
                [":param1" => 42],
                $this->callback(function ($options) {
                    $this->assertArrayHasKey("Type", $options);
                    $this->assertArrayHasKey("Slave", $options);
                    $this->assertEquals("testValue", $options["testOption"]);
                    return true;
                })
            );

        $this->sql->Database = $mock;
        $this->sql->namedParameter("param1", false, 42);
        $this->sql->options($options)->query($sql);
    }

    /**
     * Test the parameterizeGroup value method.
     *
     * @param string $expected
     * @param array $in
     *
     * @dataProvider provideParameterizeGroupValue
     */
    public function testParameterizeGroupValue(string $expected, array $in)
    {
        $result = $this->sql->parameterizeGroupValue($in);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array[]
     */
    public function provideParameterizeGroupValue(): array
    {
        return [
            "empty" => ["()", []],
            "one" => ["(?)", [1]],
            "many" => ["(?,?,?,?)", [true, "other", 4, null]],
            "assosc" => ["(?,?,?)", ["test" => 5, "hello", 2 => "other"]],
        ];
    }

    /**
     * Test that our schema cache is working.
     */
    public function testSchemaCache()
    {
        $cache = self::enableCaching();
        $this->sql->setCache($cache);
        $tableName = __FUNCTION__;
        $runStructure = function (bool $withChange = false) use ($tableName) {
            $st = $this->sql->Database->structure();
            $st->table($tableName)
                ->primaryKey("schemaCacheID")
                ->column("name", "varchar(50)");

            if ($withChange) {
                $st->column("new", "varchar(10)");
            }

            $st->set();
        };

        $runStructure();
        $cache->flush();
        $this->sql->fetchTableSchema($tableName);
        $cache->assertSetCount("*mysql*", 1);
        $this->sql->fetchTableSchema($tableName);
        $cache->assertSetCount("*mysql*", 1);
        $cache->assertNotEmpty();

        $runStructure(true);
        $cache->assertSetCount("*mysql*", 2);
        $this->assertTableSchemaHasColumn($tableName, "new");
    }

    /**
     * Assert that a table schema has a column.
     *
     * @param string $tableName
     * @param string $columnName
     */
    private function assertTableSchemaHasColumn(string $tableName, string $columnName)
    {
        $schema = $this->sql->fetchTableSchema($tableName);
        $this->assertArrayHasKey($columnName, $schema);
    }
}
