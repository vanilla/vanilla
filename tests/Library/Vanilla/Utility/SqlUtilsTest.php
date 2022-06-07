<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Garden\EventManager;
use Vanilla\Utility\SqlUtils;
use VanillaTests\BootstrapTestCase;

/**
 * Tests for the `SqlUtils` helpers.
 */
class SqlUtilsTest extends BootstrapTestCase
{
    /**
     * @var \Gdn_MySQLStructure
     */
    private $structure;

    /**
     * @var \Gdn_MySQLDriver
     */
    private $sql;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container()->call(function (\Gdn_MySQLStructure $structure, \Gdn_MySQLDriver $sql) {
            $this->structure = $structure;
            $this->sql = $sql;
        });
    }

    /**
     * Larger text fields should be kept when altering a table.
     */
    public function testKeepTextFields(): void
    {
        $this->doKeepTextFieldsTest(__FUNCTION__, function () {
            SqlUtils::keepTextFieldLengths($this->structure);
        });
    }

    /**
     * The `SqlUtils::keepTextFields()` helper should work as an event handler.
     */
    public function testKeepTextFieldsEvent(): void
    {
        /** @var EventManager $events */
        $events = $this->container()->get(EventManager::class);
        $handler = function (\Gdn_DatabaseStructure $structure) {
            SqlUtils::keepTextFieldLengths($structure);
        };
        $events->bind(\Gdn_MySQLStructure::class . "_beforeSet", $handler);

        $this->doKeepTextFieldsTest(__FUNCTION__, function () {});

        $events->unbind(\Gdn_MySQLStructure::class . "_beforeSet", $handler);
    }

    /**
     * Run the actual test for `SqlUtils::keepTextFields()`.
     *
     * @param string $tableName
     * @param callable $keep
     */
    protected function doKeepTextFieldsTest(string $tableName, callable $keep): void
    {
        $this->structure->table($tableName);
        $this->structure->drop();

        // Create a basic table first.
        $this->structure
            ->table($tableName)
            ->primaryKey("id")
            ->column("body", "mediumtext")
            ->column("name", "varchar(50)")
            ->column("password", "varbinary(20)")
            ->column("toText", "varchar(30)")
            ->column("makeBigger", "varchar(30)")
            ->column("makeBinBigger", "varbinary(30)")
            ->column("textToVarchar", "text")
            ->set();

        // Now do an alter and make sure it works.
        $this->structure
            ->table($tableName)
            ->column("body", "text")
            ->column("name", "varchar(20)")
            ->column("password", "varbinary(10)")
            ->column("toText", "text")
            ->column("makeBigger", "varchar(31)")
            ->column("makeBinBigger", "varbinary(31)")
            ->column("textToVarchar", "varchar(5)")
            ->column("newText", "text")
            ->column("newVarchar", "varchar(20)");

        $keep();

        $this->structure->set();

        // Now see if the table was altered.
        $columns = $this->structure->table($tableName)->existingColumns();
        $this->assertSame("mediumtext", $columns["body"]->Type);
        $this->assertSame("varchar", $columns["name"]->Type);
        $this->assertSame(50, (int) $columns["name"]->Length);
        $this->assertSame(20, (int) $columns["password"]->Length);
        $this->assertSame("text", $columns["toText"]->Type);
        $this->assertSame(31, (int) $columns["makeBigger"]->Length);
        $this->assertSame(31, (int) $columns["makeBinBigger"]->Length);
        $this->assertSame("text", $columns["textToVarchar"]->Type, "textToVarchar");
        $this->assertSame("text", $columns["newText"]->Type);
        $this->assertSame("varchar", $columns["newVarchar"]->Type);
    }
}
