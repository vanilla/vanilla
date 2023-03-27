<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Database;

use PHPUnit\Framework\TestCase;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\TestMySQLStructure;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `Gdn_MySQLStructure` class.
 */
class MySQLStructureTest extends SiteTestCase
{
    /**
     * @var TestMySQLStructure
     */
    private $st;

    /** @var \Gdn_Database */
    private $db;

    /**
     * Set up a fixture for use in tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->db = $this->container()->get(\Gdn_Database::class);
        $sql = $this->db->createSql();
        $st = new TestMySQLStructure($sql, $this->db);
        $this->st = $st;
        $this->st->reset();
        $this->st->CaptureOnly = false;
        $this->st->Database->CapturedSql = [];
        \Gdn::sql()->CaptureModifications = false;
    }

    /**
     * The default storage engine config should be applied to table creates.
     *
     * @param string $engine
     * @dataProvider provideEngines
     */
    public function testDefaultCollation(string $engine): void
    {
        $this->doCollationTest(
            [
                "Database.DefaultStorageEngine" => $engine,
            ],
            "",
            $engine
        );
    }

    /**
     * The default storage engine should be innodb.
     */
    public function testDefaultCollationInnodb(): void
    {
        $this->doCollationTest([], "", "innodb");
    }

    /**
     * A forced engine should override the default.
     *
     * @param string $engine
     * @dataProvider provideEngines
     */
    public function testForceCollation(string $engine): void
    {
        $this->doCollationTest(
            [
                "Database.DefaultStorageEngine" => "foo",
                "Database.ForceStorageEngine" => $engine,
            ],
            "",
            $engine
        );
    }

    /**
     * An explicitly set collation should override all configs.
     *
     * @param string $engine
     * @dataProvider provideEngines
     */
    public function testExplicitCollation(string $engine): void
    {
        $this->doCollationTest(
            [
                "Database.DefaultStorageEngine" => "foo",
                "Database.ForceStorageEngine" => "foo",
            ],
            $engine,
            $engine
        );
    }

    /**
     * Test adding a simple index.
     *
     * @return void
     */
    public function testAddInplaceIndex(): void
    {
        // Make sure we're starting with a blank slate.
        $this->st->table(__FUNCTION__)->drop();

        // Setup the table, without the index.
        $this->st
            ->table(__FUNCTION__)
            ->primaryKey("id")
            ->column("value", "int")
            ->set();

        // Add the index.
        $this->st
            ->table(__FUNCTION__)
            ->column("value", "int", false, "index.test")
            ->set();

        $this->assertColumnHasIndex(__FUNCTION__, "value");
    }

    /**
     * Test conditionally dropping a column if it exists.
     */
    public function testConditionColumnDrop()
    {
        $this->st
            ->table(__FUNCTION__)
            ->primaryKey("id")
            ->column("value", "int", false, "index.test")
            ->column("value2", "int", false)
            ->set();

        // we have an index.
        $this->assertColumnHasIndex(__FUNCTION__, "value");

        // We can drop it.
        $this->st->table(__FUNCTION__)->dropIndexIfExists("IX_" . __FUNCTION__ . "_test");
        $this->assertColumnNotHasIndex(__FUNCTION__, "value");

        // We can call this even on other columns without error.
        $this->assertColumnNotHasIndex(__FUNCTION__, "value");
        $this->assertColumnNotHasIndex(__FUNCTION__, "value2");
    }

    /**
     * Database indexes should be case-insensitive.
     */
    public function testIndexCaseInsensitive(): void
    {
        $px = $this->st->Database->DatabasePrefix;
        $tbl = __FUNCTION__;

        $this->st->table($tbl)->drop();

        $this->st
            ->table($tbl)
            ->primaryKey("id")
            ->column("status", "int")
            ->set();

        $this->st->Database->query("alter table `$px{$tbl}` add index IX_{$tbl}_Status (`status`)");
        $this->assertColumnHasIndex($tbl, "status");

        try {
            $this->st->CaptureOnly = true;
            $this->assertEmpty($this->db->CapturedSql, "Something went wrong with the test.");

            $this->st
                ->table($tbl)
                ->column("status", "int", false, "index.status")
                ->set();

            $this->assertEmpty($this->db->CapturedSql, $this->db->CapturedSql[0] ?? "");
        } finally {
            $this->st->CaptureOnly = false;
        }
    }

    /**
     * Test adding an index requring a lock on a table under the modify row threshold.
     *
     * @return void
     */
    public function testIndexRequiringLockUnderThreshold(): void
    {
        // Make sure we're starting with a blank slate.
        $this->st->table(__FUNCTION__)->drop();

        // Setup the table, without the index.
        $this->st
            ->table(__FUNCTION__)
            ->primaryKey("id")
            ->column("value", "text")
            ->set();

        // Add the index.
        $this->st->setFullTextIndexingEnabled(true);
        $this->st
            ->table(__FUNCTION__)
            ->column("value", "text", false, "fulltext.test")
            ->set();

        $this->assertColumnHasIndex(__FUNCTION__, "value");
    }

    /**
     * Test adding an index requring a lock on a table *over* the modify row threshold.
     *
     * @return void
     */
    public function testIndexRequiringLockOverThreshold(): void
    {
        // Make sure we're starting with a blank slate.
        $this->st->table(__FUNCTION__)->drop();

        // Setup the table, without the index.
        $this->st
            ->table(__FUNCTION__)
            ->primaryKey("id")
            ->column("value", "text")
            ->set();

        // Exceed the threshold.
        $sql = $this->st->Database->sql();
        $threshold = 5;
        $this->st->setAlterTableThreshold($threshold);
        for ($i = 1; $i <= $threshold + 1; $i++) {
            $sql->insert(__FUNCTION__, ["value" => "Row {$i}"]);
        }

        // Add the index.
        $this->st->setFullTextIndexingEnabled(true);
        $this->st
            ->table(__FUNCTION__)
            ->column("value", "text", false, "fulltext.test")
            ->set();

        // Verify the issue was detected.
        $issues = $this->st->getIssues();
        $this->assertEquals("The table was past its threshold. Run the alter manually.", $issues[0]["message"]);

        // Verify the index wasn't actually created.
        $columns = $this->tableColumns(__FUNCTION__);
        $this->assertEmpty($columns["value"]["Key"]);
    }

    /**
     * Assert the specified index exists for the column.
     *
     * @param string $table
     * @param string $column
     */
    private function assertColumnHasIndex(string $table, string $column)
    {
        // Verify the index was set.
        $columns = $this->tableColumns($table);
        $this->assertNotEmpty($columns[$column]["Key"]);
    }

    /**
     * Assert that a column has no indexes.
     *
     * @param string $table
     * @param string $column
     */
    private function assertColumnNotHasIndex(string $table, string $column)
    {
        // Verify the index was set.
        $columns = $this->tableColumns($table);
        $this->assertEmpty($columns[$column]["Key"]);
    }

    /**
     * A forced engine should override the default.
     *
     * @param array $config Config changes to run the test with.
     * @param string $explicitEngine The engine to set on the `Gdn_MySQLStructure` class.
     * @param string $expectedEngine The expected engine in the `create table` statement.
     * @dataProvider provideEngines
     */
    final function doCollationTest(array $config, string $explicitEngine, string $expectedEngine): void
    {
        $this->runWithConfig($config, function () use ($explicitEngine, $expectedEngine) {
            $this->st
                ->table("testDefaultCollationISAM")
                ->primaryKey("id")
                ->column("name", "varchar(50)");

            if (!empty($explicitEngine)) {
                $this->st->engine($explicitEngine, false);
            }

            $dml = $this->st->dumpCreateTable();
            $expected = <<<EOT
create table `GDN_testDefaultCollationISAM` (
`id` int not null auto_increment,
`name` varchar(50) not null,
primary key (`id`)
) engine=$expectedEngine default character set utf8mb4 collate utf8mb4_unicode_ci;
EOT;

            $this->assertEquals($expected, $dml);
        });
    }

    /**
     * Provide the database engines that we support.
     *
     * @return array
     */
    public function provideEngines(): array
    {
        return [
            "innodb" => ["innodb"],
            "myisam" => ["myisam"],
        ];
    }

    /**
     * Get current structure of the database.
     *
     * @param string $table
     * @return array
     */
    private function tableColumns(string $table): array
    {
        $sql = $this->st->Database->sql();
        $columnsRaw = $sql->query($sql->fetchColumnSql($table))->resultArray();
        $columns = array_column($columnsRaw, null, "Field");
        return $columns;
    }

    /**
     * Text types should not alter when being re-defined.
     */
    public function testNoAlterTextColumns(): void
    {
        $this->st
            ->table(__FUNCTION__)
            ->column("foo", "text")
            ->set();

        $this->st->CaptureOnly = true;
        $this->st->table(__FUNCTION__)->column("foo", "text");
        $this->st->set();

        $sql = $this->st->Database->CapturedSql ?? [];
        $this->assertEmpty($sql, "The table should not have altered.");
    }

    /**
     * Test the creation of multi-column unique indexes works correctly.
     *
     * We had a bug previously where the initial indexes on update were incorrect, but were correct on update.
     */
    public function testCreateUniqueIndexes()
    {
        $createStructure = function () {
            $this->st
                ->table("uniqueIndexes")
                ->column("part1", "int", false, ["index", "unique.combined"])
                ->column("part2", "int", false, "unique.combined")
                ->set();
        };

        // run twice to make sure indexes are stable.
        $createStructure();
        $createStructure();

        $this->assertIndexes(
            ["UX_uniqueIndexes_combined[part1]", "UX_uniqueIndexes_combined[part2]", "IX_uniqueIndexes_part1[part1]"],
            "uniqueIndexes"
        );
    }

    /**
     * Assert that we have indexes in the following format.
     *
     * INDEX_NAME[columnName]
     *
     * @param string[] $expected
     * @param string $table
     */
    private function assertIndexes(array $expected, string $table)
    {
        $actualIndexRows = $this->db
            ->sql()
            ->query("SHOW INDEXES FROM GDN_$table")
            ->resultArray();

        $actual = "";
        foreach ($actualIndexRows as $actualIndexRow) {
            $actual .= $actualIndexRow["Key_name"] . "[" . $actualIndexRow["Column_name"] . "]" . "\n";
        }
        $actual = trim($actual);

        $expected = implode("\n", $expected);
        $this->assertEquals($expected, $actual, "Incorrect indexes were created for table '$table'");
    }

    /**
     * Test the index exists function.
     */
    public function testIndexExists()
    {
        $this->st
            ->table("indexExists")
            ->column("col1", "int", false, ["index"])
            ->set();

        $this->assertTrue($this->st->indexExists("indexExists", "IX_indexExists_col1"));
        $this->assertFalse($this->st->indexExists("nonExistantTable", "IX_indexExists_col1"));
        $this->assertFalse($this->st->indexExists("nonExistantTable", "IX_nonexistent_index"));
    }
}
