<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Database;

use PHPUnit\Framework\TestCase;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\JsonFieldProcessor;
use VanillaTests\Fixtures\BasicPipelineModel;

/**
 * Class for testing automatically packing and unpacking a JSON field.
 */
class JsonFieldProcessorTest extends TestCase {

    /**
     * Generate data for testing unpacking JSON fields for read operations.
     *
     * @return array
     */
    public function provideReadOperations() {
        $complexType = ["foo" => "bar"];
        $json = json_encode($complexType);
        return [
            [Operation::TYPE_SELECT, ["attributes" => $json], ["attributes" => $complexType]],
            [Operation::TYPE_SELECT, ["notAttributes" => $json], ["notAttributes" => $json]],
        ];
    }

    /**
     * Generate data for testing packing as JSON for write operations.
     *
     * @return array
     */
    public function provideWriteOperations() {
        $complexType = ["foo" => "bar"];
        $json = json_encode($complexType);
        return [
            [Operation::TYPE_INSERT, ["attributes" => $complexType], ["attributes" => $json]],
            [Operation::TYPE_UPDATE, ["attributes" => $complexType], ["attributes" => $json]],
            [Operation::TYPE_INSERT, ["notAttributes" => $complexType], ["notAttributes" => $complexType]],
        ];
    }

    /**
     * Verify processor packs fields for write operations.
     *
     * @param string $type
     * @param array $set
     * @param array $expected
     * @dataProvider provideWriteOperations
     */
    public function testPackFields(string $type, array $set, array $expected) {
        $model = new BasicPipelineModel("Example");
        $processor = new JsonFieldProcessor();
        $processor->setFields(["attributes"]);
        $model->addPipelineProcessor($processor);

        $operation = new Operation();
        $operation->setType($type);
        $operation->setCaller($model);
        $operation->setSet($set);
        $model->doOperation($operation);
        $this->assertEquals($expected, $operation->getSet());
    }

    /**
     * Verify processor unpacks fields for read operations.
     *
     * @param string $type
     * @param array $row
     * @param array $expected
     * @dataProvider provideReadOperations
     */
    public function testUnpackFields(string $type, array $row, array $expected) {
        $model = new BasicPipelineModel("Example");
        $processor = new JsonFieldProcessor();
        $processor->setFields(["attributes"]);
        $model->addPipelineProcessor($processor);

        $operation = new Operation();
        $operation->setType(Operation::TYPE_SELECT);
        $operation->setCaller($model);
        $result = $model->doSelectOperation($operation, [
            $row
        ]);
        $this->assertEquals([$expected], $result);
    }
}
