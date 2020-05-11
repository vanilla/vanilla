<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use PHPUnit\Framework\TestCase;
use Vanilla\Models\Model;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `Model` class.
 */
class ModelTest extends TestCase {
    use SiteTestTrait;

    /**
     * @var Model
     */
    private $model;

    /**
     * Install the site and set up a test table.
     */
    public static function setupBeforeClass(): void {
        static::setupSiteTest();

        static::container()->call(function (
            \Gdn_DatabaseStructure $st
        ) {
            $st->table('model')
                ->primaryKey('modelID')
                ->column('name', 'varchar(50)')
                ->set();
        });
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $this->container()->call(function (
            \Gdn_SQLDriver $sql
        ) {
            $sql->truncate('model');
        });

        $this->model = $this->container()->getArgs(Model::class, ['model']);
    }

    /**
     * Test a basic insert get path.
     */
    public function testInsertGet(): void {
        $id = $this->model->insert(['name' => 'foo']);
        $row = $this->model->selectSingle($this->model->primaryWhere($id));

        $this->assertSame('foo', $row['name']);
        $this->assertSame($id, $row['modelID']);
    }

    public function testAllButSelect(): void {
        $id = $this->model->insert(['name' => 'foo']);

        $row = $this->model->selectSingle(
            $this->model->primaryWhere($id),
            [Model::OPT_SELECT => '-modelID']
        );
        $this->assertSame('foo', $row['name']);
        $this->assertArrayNotHasKey('modelID', $row);
    }
}
