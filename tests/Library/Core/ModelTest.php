<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Vanilla\Models\Model;
use Vanilla\Utility\ModelUtils;
use VanillaTests\BootstrapTestCase;
use VanillaTests\ExpectExceptionTrait;

/**
 * Tests for the `Gdn_Model` class.
 */
class ModelTest extends BootstrapTestCase
{
    use ExpectExceptionTrait;

    /**
     * @var \Gdn_Model
     */
    private $oldModel;

    /** @var Model */
    private $newModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container()
            ->rule(Model::class)
            ->setShared(false);
        $this->container()->call(function (\Gdn_DatabaseStructure $st, \Gdn_SQLDriver $sql) {
            $st->table("model")
                ->primaryKey("modelID")
                ->column("name", "varchar(50)", true)
                ->column("tinytext", "tinytext", true)
                ->set();

            $sql->truncate("model");
        });

        $this->oldModel = new \Gdn_Model("model");
        $this->newModel = $this->container()->getArgs(Model::class, ["model"]);
    }

    /**
     * Test `Gdn_Model::delete()` with an option of `reset = false`.
     */
    public function testDelete(): void
    {
        $id = $this->oldModel->insert(["name" => "toDelete"]);
        $this->assertNotFalse($id);
        $this->assertNotFalse($this->oldModel->getID($id));

        $r = $this->oldModel->delete(["modelID" => $id]);
        $this->assertFalse($this->oldModel->getID($id));
    }

    /**
     * Test that model validations ensures byte length on the old models.
     *
     * @param array $record
     * @param string|null $expectedError
     *
     * @dataProvider provideByteLengths
     */
    public function testByteLengthValidationOldModel(array $record, string $expectedError = null)
    {
        $id = $this->oldModel->insert($record);

        if ($expectedError) {
            $this->expectExceptionMessage($expectedError);
        }
        ModelUtils::validationResultToValidationException($this->oldModel);
        $this->assertIsNumeric($id);
    }

    /**
     * Test that model validations ensures byte length using the new models.
     *
     * @param array $record
     * @param string|null $expectedError
     * @param string|null $expectedNewError
     *
     * @dataProvider provideByteLengths
     */
    public function testByteLengthValidationNewModel(
        array $record,
        string $expectedError = null,
        string $expectedNewError = null
    ) {
        $expectedNewError = $expectedNewError ?? $expectedError;
        if ($expectedNewError) {
            $this->expectExceptionMessage($expectedNewError);
        }
        $id = $this->newModel->insert($record);
        $this->assertIsNumeric($id);
    }

    /**
     * Provide text for model validation.
     *
     * @return array
     */
    public function provideByteLengths()
    {
        return [
            "()max length" => [["tinytext" => str_repeat("a", 255)]],
            "too long plaintext" => [
                ["tinytext" => str_repeat("a", 256)],
                "tinytext is 1 characters too long.",
                "tinytext is 1 characters too long. tinytext is 1 byte too long",
            ],
            "too long multibyte" => [
                ["tinytext" => str_repeat("😱", 70)],
                "tinytext is 25 bytes too long", // Each emoji is 4 bytes. 70 * 4 = 280 - 255 = 25
            ],
            "short multibyte" => [["tinytext" => str_repeat("😱", 10)]],
            "max length varchar" => [["name" => str_repeat("😱", 20)]],
            "too long varchar" => [["name" => str_repeat("😱", 51)], "name is 1 characters too long"],
        ];
    }

    /**
     * Test that out byte length validation is dependent on the DB encoding.
     */
    public function testByteLengthMatchesEncoding()
    {
        $this->runWithConfig(
            [
                "Database.CharacterEncoding" => "latin",
            ],
            function () {
                $this->runWithExpectedExceptionMessage("name is 2 bytes too long", function () {
                    $model = $this->container()->getArgs(Model::class, ["model"]);
                    $model->getWriteSchema()->validate([
                        "name" => str_repeat("😱", 13),
                    ]);
                });
            }
        );

        $this->runWithConfig(
            [
                "Database.CharacterEncoding" => "utf8",
            ],
            function () {
                $this->runWithExpectedExceptionMessage("name is 2 bytes too long", function () {
                    $model = $this->container()->getArgs(Model::class, ["model"]);
                    $model->getWriteSchema()->validate([
                        "name" => str_repeat("😱", 38),
                    ]);
                });
            }
        );
    }
}
