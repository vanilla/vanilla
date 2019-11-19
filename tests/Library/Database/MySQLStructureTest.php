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
    public function setUp() {
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
     * A forced engine should override the default.
     *
     * @param string $expectedEngine
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
}
