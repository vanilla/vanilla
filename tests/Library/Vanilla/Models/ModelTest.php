<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use PHPUnit\Framework\TestCase;
use Vanilla\Exception\Database\NoResultsException;
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
     *
     * @return int
     */
    public function testInsertGet(): int {
        $id = $this->insertOne();
        $row = $this->model->selectSingle($this->model->primaryWhere($id));

        $this->assertSame('foo', $row['name']);
        $this->assertSame($id, $row['modelID']);
        return $id;
    }

    /**
     * You can select all but some columns by prefixng with a "-".
     */
    public function testAllButSelect(): void {
        $id = $this->insertOne();

        $row = $this->model->selectSingle(
            $this->model->primaryWhere($id),
            [Model::OPT_SELECT => '-modelID']
        );
        $this->assertSame('foo', $row['name']);
        $this->assertArrayNotHasKey('modelID', $row);
    }

    /**
     * Test a basic delete.
     *
     * @depends testInsertGet
     */
    public function testDelete(): void {
        $id = $this->insertOne();
        $this->model->delete($this->model->primaryWhere($id));

        $this->expectException(NoResultsException::class);
        $row = $this->model->selectSingle($this->model->primaryWhere($id));
    }

    /**
     * Test a basic record update.
     *
     * @depends testInsertGet
     */
    public function testUpdate(): void {
        $id = $this->insertOne();
        $r = $this->model->update(['name' => 'bar'], $this->model->primaryWhere($id));


        $row = $this->model->selectSingle($this->model->primaryWhere($id));
        $this->assertSame('bar', $row['name']);
    }

    /**
     * Insert a basic test row.
     *
     * @param string $name
     * @return int
     */
    private function insertOne(string $name = 'foo'): int {
        $id = $this->model->insert(['name' => $name]);
        return $id;
    }
}
