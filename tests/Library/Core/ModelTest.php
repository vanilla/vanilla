<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Vanilla\Utility\ModelUtils;
use VanillaTests\BootstrapTestCase;

/**
 * Tests for the `Gdn_Model` class.
 */
class ModelTest extends BootstrapTestCase {

    /**
     * @var \Gdn_Model
     */
    private $model;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->container()->call(function (
            \Gdn_DatabaseStructure $st,
            \Gdn_SQLDriver $sql
        ) {
            $st->table('model')
                ->primaryKey('modelID')
                ->column('name', 'varchar(50)', true)
                ->column('tinytext', 'tinytext', true)
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

    /**
     * Test that model validations ensures byte length.
     *
     * @param array $record
     * @param string|null $expectedError
     *
     * @dataProvider provideByteLengths
     */
    public function testByteLengthValidation(array $record, string $expectedError = null) {
        $id = $this->model->insert($record);

        if ($expectedError) {
            $this->expectExceptionMessage($expectedError);
        }
        ModelUtils::validationResultToValidationException($this->model);
        $this->assertIsNumeric($id);
    }

    /**
     * Provide text for model validation.
     *
     * @return array
     */
    public function provideByteLengths() {
        return [
            'max length' => [
                [ 'tinytext' => str_repeat('a', 255) ],
            ],
            'too long plaintext' => [
                ['tinytext' => str_repeat('a', 256) ],
                'tinytext is 1 bytes too long.'
            ],
            'too long mutlibyte' => [
                ['tinytext' => str_repeat('😱', 70) ],
                'tinytext is 25 bytes too long', // Each emoji is 4 bytes. 70 * 4 = 280 - 255 = 25
            ],
            'short multibyte' => [
                ['tinytext' => str_repeat('😱', 10) ]
            ],
            'too long varchar' => [
                ['name' => str_repeat('😱', 20) ],
                'name is 30 bytes too long',
            ]
        ];
    }
}
