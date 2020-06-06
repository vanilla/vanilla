<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Database;

use PHPUnit\Framework\TestCase;
use Vanilla\Schema\RangeExpression;
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
    public function setUp(): void {
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
    public function tearDown(): void {
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
     */
    public function testFetchTableSchemeInjection() {
        $this->expectError();
        $schema = $this->sql->fetchTableSchema("User/**/where/**/1=(select/**/1/**/from(select/**/sleep(/**/1/**/))a)");
    }

    /**
     * Field names in where clauses should be escaped.
     */
    public function testFieldEscape() {
        $sql = $this->sql
            ->where(["1=sleep(1) and 1" => "world"])
            ->getDelete('Foo', $this->dumpSql()['where']);

        $this->assertStringContainsString('`1=sleep(1) and 1`', $sql);
    }

    /**
     * Provide table aliases and tables.
     *
     * @return array
     */
    public function provideAliasData() {
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
    public function testMapAliases($input, $expected, $escape = true) {
        $aliases = $this->sql->mapAliases($input, $escape);
        $this->assertSame($aliases, $expected);
    }

    /**
     * Testing a basic where in expression.
     */
    public function testWhereIn() {
        $sql = $this->sql
            ->select()
            ->from('foo')
            ->whereIn('bar', ['a'])
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
    public function testWhereInEmpty() {
        $sql = $this->sql
            ->select()
            ->from('foo')
            ->whereIn('bar', [])
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
    public function testWhereNotInEmpty() {
        $sql = $this->sql
            ->select()
            ->from('foo')
            ->whereNotIn('bar', [])
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
    public function testDateWhere() {
        $where = [
            'd.DateLastComment <' =>
                \DateTimeImmutable::__set_state([
                    'date' => '2019-10-27 23:32:35.000000',
                    'timezone_type' => 1,
                    'timezone' => '+00:00',
                ]),
        ];

        $this->sql
            ->from('foo')
            ->where($where);

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
    public function testDeleteReturn() {
        $st = $this->sql->Database->structure();
        $st
            ->table('testDelete')
            ->primaryKey('testDeleteID')
            ->column('name', 'varchar(50)')
            ->set();

        $id = $this->sql->insert('testDelete', ['name' => 'foo']);
        $id2 = $this->sql->insert('testDelete', ['name' => 'foo']);

        $r = $this->sql->delete('testDelete', ['name' => $id]);
        $this->assertEquals(2, $r);
    }

    /**
     * Test a basic where in clause.
     */
    public function testWhereInField() {
        $actual = $this->sql->select()->from('foo')->where('bar', [1, 2, 'three'])->getSelect();
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
    public function testNegativeOrderBy() {
        $actual = $this->sql->select()->from('foo')->orderBy('-foo, bar', 'asc')->getSelect();
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
    public function testRangeExpressionWhere(RangeExpression $range, string $expectedWhere) {
        $actual = $this->sql->select()->from('foo')->where('b', $range)->getSelect();
        $actual = preg_replace('`\s+`', ' ', $actual);
        $this->assertStringContainsString($expectedWhere, $actual);
    }

    /**
     * Provide some sample range expressions and expected where clauses.
     *
     * @return array
     */
    public function provideRangeExpressionTests(): array {
        $r = [
            'basic' => [new RangeExpression('>', 1), 'where `b` > :b'],
            'two values' => [new RangeExpression('>=', 1, '<=', 2), 'where `b` >= :b and `b` <= :b0'],
            'in clause' => [new RangeExpression('=', [1, 2]), "where `b` in ('1', '2')"],
            'empty equals' => [new RangeExpression('=', []), "where 1 = 0"]
        ];
        return $r;
    }
}
