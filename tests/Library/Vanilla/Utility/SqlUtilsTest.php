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
class SqlUtilsTest extends BootstrapTestCase {
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
    public function setUp(): void {
        parent::setUp();

        $this->container()->call(function (
            \Gdn_MySQLStructure $structure,
            \Gdn_MySQLDriver $sql
        ) {
            $this->structure = $structure;
            $this->sql = $sql;
        });
    }

    /**
     * Larger text fields should be kept when altering a table.
     */
    public function testKeepTextFields(): void {
        $this->doKeepTextFieldsTest(__FUNCTION__, function () {
            SqlUtils::keepTextFieldLengths($this->structure);
        });
    }

    /**
     * The `SqlUtils::keepTextFields()` helper should work as an event handler.
     */
    public function testKeepTextFieldsEvent(): void {
        $this->doKeepTextFieldsTest(__FUNCTION__, function () {
            $this->container()->call(function (EventManager $events) {
                $events->bind(\Gdn_MySQLStructure::class.'_beforeSet', [SqlUtils::class, 'keepTextFieldLengths']);
            });
        });
    }

    /**
     * Run the actual test for `SqlUtils::keepTextFields()`.
     *
     * @param string $tableName
     * @param callable $keep
     */
    protected function doKeepTextFieldsTest(string $tableName, callable $keep): void {
        $this->structure->table($tableName);

        // Create a basic table first.
        $this->structure->table($tableName)
            ->primaryKey('id')
            ->column('Body', 'mediumtext')
            ->set();

        // Now do an alter and make sure it works.
        $this->structure->table($tableName)
            ->column('body', 'text');

        $keep();

        $this->structure->set();

        // Now see if the table was altered.
        $columns = $this->structure->table($tableName)->existingColumns();
        $this->assertSame('mediumtext', $columns['body']->Type);
    }
}
