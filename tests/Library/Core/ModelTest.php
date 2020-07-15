<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use VanillaTests\BootstrapTrait;
use VanillaTests\SetupTraitsTrait;

/**
 * Tests for the `Gdn_Model` class.
 */
class ModelTest extends TestCase {
    use BootstrapTrait, SetupTraitsTrait;

    /**
     * @var \Gdn_Model
     */
    private $model;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->initializeDatabase();
        $this->setupTestTraits();

        $this->container()->call(function (
            \Gdn_DatabaseStructure $st,
            \Gdn_SQLDriver $sql
        ) {
            $st->table('model')
                ->primaryKey('modelID')
                ->column('name', 'varchar(50)')
                ->set();

            $sql->truncate('model');
        });

        $this->model = new \Gdn_Model('model');
    }

    /**
     * Test `Gdn_Model::delete()` with an option of `reset = false`.
     */
    public function testDelete(): void {
        $id = $this->model->insert(['name' => 'toDelete']);
        $this->assertNotFalse($id);
        $this->assertNotFalse($this->model->getID($id));

        $r = $this->model->delete(['modelID' => $id]);
        $this->assertFalse($this->model->getID($id));
    }
}
