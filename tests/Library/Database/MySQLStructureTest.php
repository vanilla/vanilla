<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Database;

use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\TestMySQLStructure;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `Gdn_MySQLStructure` class.
 */
class MySQLStructureTest extends TestCase {
    use SiteTestTrait;

    /**
     * @var TestMySQLStructure
     */
    private $st;

    /**
     * Set up a fixture for use in tests.
     */
    public function setUp(): void {
        parent::setUp();

        $st = new TestMySQLStructure($this->container()->get(\Gdn_Database::class));
        $this->st = $st;
        $this->st->reset();
    }

    /**
     * The default storage engine config should be applied to table creates.
     *
     * @param string $engine
     * @dataProvider provideEngines
     */
    public function testDefaultCollation(string $engine): void {
        $this->doCollationTest(
            [
                'Database.DefaultStorageEngine' => $engine,
            ],
            '',
            $engine
        );
    }

    /**
     * The default storage engine should be innodb.
     */
    public function testDefaultCollationInnodb(): void {
        $this->doCollationTest(
            [],
            '',
            'innodb'
        );
    }

    /**
     * A forced engine should override the default.
     *
     * @param string $engine
     * @dataProvider provideEngines
     */
    public function testForceCollation(string $engine): void {
        $this->doCollationTest(
            [
                'Database.DefaultStorageEngine' => 'foo',
                'Database.ForceStorageEngine' => $engine,
            ],
            '',
            $engine
        );
    }

    /**
     * An explicitly set collation should override all configs.
     *
     * @param string $engine
     * @dataProvider provideEngines
     */
    public function testExplicitCollation(string $engine): void {
        $this->doCollationTest(
            [
                'Database.DefaultStorageEngine' => 'foo',
                'Database.ForceStorageEngine' => 'foo',
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
    public function testAddInplaceIndex(): void {
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
     * Test adding an index requring a lock on a table under the modify row threshold.
     *
     * @return void
     */
    public function testIndexRequiringLockUnderThreshold(): void {
        // Make sure we're starting with a blank slate.
        $this->st->table(__FUNCTION__)->drop();

        // Setup the table, without the index.
        $this->st
            ->table(__FUNCTION__)
            ->primaryKey("id")
            ->column("value", "text")
            ->set();

        // Add the index.
        $this->st
            ->table(__FUNCTION__)
            ->column("value", "text", false, "fulltext.test")
            ->set();

        $this->assertColumnHasIndex(__FUNCTION__, "value");
    }

    /**
     * Test adding an index requring a lock on a table under the modify row threshold.
     *
     * @return void
     */
    public function testIndexRequiringLockOverThreshold(): void {
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
        for ($i = 1; $i <= ($threshold + 1); $i++) {
            $sql->insert(__FUNCTION__, ["value" => "Row {$i}"]);
        }

        // Add the index.
        $this->st
            ->table(__FUNCTION__)
            ->column("value", "text", false, "fulltext.test")
            ->set();

        // Verify the issue was detected.
        $issues = $this->st->getIssues();
        $this->assertEquals(
            "The table was past its threshold. Run the alter manually.",
            $issues[0]["message"]
        );

        // Verify the index wasn't actually created.
        $columns = $this->tableColumns(__FUNCTION__);
        $this->assertEmpty($columns["value"]["Key"]);
    }

    /**
     * Assert the specified index exists for the column.
     *
     * @param string $table
     * @param string $column
     * @return boolean
     */
    private function assertColumnHasIndex(string $table, string $column) {
        // Verify the index was set.
        $columns = $this->tableColumns($table);
        $this->assertNotEmpty($columns[$column]["Key"]);
    }

    /**
     * A forced engine should override the default.
     *
     * @param array $config Config changes to run the test with.
     * @param string $explicitEngine The engine to set on the `Gdn_MySQLStructure` class.
     * @param string $expectedEngine The expected engine in the `create table` statement.
     * @dataProvider provideEngines
     */
    final private function doCollationTest(array $config, string $explicitEngine, string $expectedEngine): void {
        $this->runWithConfig($config, function () use ($explicitEngine, $expectedEngine) {
            $this->st
                ->table('testDefaultCollationISAM')
                ->primaryKey('id')
                ->column('name', 'varchar(50)');

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
    public function provideEngines(): array {
        return [
            'innodb' => ['innodb'],
            'myisam' => ['myisam'],
        ];
    }

    /**
     * Get current structure of the database.
     *
     * @param string $table
     * @return array
     */
    private function tableColumns(string $table): array {
        $sql = $this->st->Database->sql();
        $columnsRaw = $sql
            ->query($sql->fetchColumnSql($table))
            ->resultArray();
        $columns = array_column($columnsRaw, null, "Field");
        return $columns;
    }
}
