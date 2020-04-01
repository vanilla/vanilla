<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Database;

use DateTime;
use PHPUnit\Framework\TestCase;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use VanillaTests\Fixtures\BasicPipelineModel;

/**
 * Class for testing the "current date field" database operation processor.
 */
class CurrentDateFieldProcessorTest extends TestCase {

    /**
     * Verify processor adds fields to insert operations.
     *
     * @param string $type
     * @param array|null $dateFields
     * @dataProvider provideOperations
     */
    public function testAddInsertFields(string $type, $dateFields) {
        $model = new BasicPipelineModel("Example");
        $processor = new CurrentDateFieldProcessor();
        $model->addPipelineProcessor($processor);

        $operation = new Operation();
        $operation->setType($type);
        $operation->setCaller($model);
        $model->doOperation($operation);

        $set = $operation->getSet();
        if ($dateFields === null) {
            $this->assertEmpty($set);
        } else {
            foreach ($set as $setField => $setValue) {
                if (!in_array($setField, $dateFields)) {
                    // Throw a failure exception.
                    $this->fail("Unexpected field: {$setField}");
                }

                // Attempt to parse into a DateTime object to validate the value.
                $dateTime = DateTime::createFromFormat("Y-m-d H:i:s", $setValue);
                if ($dateTime instanceof DateTime) {
                    // Verify what went in is what comes back out.
                    $this->assertTrue($dateTime->format("Y-m-d H:i:s") === $setValue);
                } else {
                    $this->fail("Invalid date: {$setValue}");
                }
            }
        }
    }

    /**
     * Generate type and "sets" pairs for testing if the current date fields should be set (or not).
     *
     * @return array
     */
    public function provideOperations() {
        return [
            [Operation::TYPE_DELETE, null],
            [Operation::TYPE_INSERT, ["DateInserted"]],
            [Operation::TYPE_SELECT, null],
            [Operation::TYPE_UPDATE, ["DateUpdated"]],
        ];
    }

    /**
     * Verify processor adds fields to insert operations default mode.
     *
     * @param string $type
     * @param array $dateFields
     * @dataProvider provideOperationsDate
     */
    public function testOperationModeDefault(string $type, $dateFields) {
        $model = new BasicPipelineModel("Example");
        $processor = new CurrentDateFieldProcessor();
        $model->addPipelineProcessor($processor);

        $operation = new Operation();
        $operation->setType($type);
        $operation->setCaller($model);
        $operation->setSet($dateFields);
        $model->doOperation($operation);

        $set = $operation->getSet();

        foreach ($set as $setField => $setValue) {
            if (!array_key_exists($setField, $dateFields)) {
                // Throw a failure exception.
                $this->fail("Unexpected field: {$setField}");
            }

            // Attempt to parse into a DateTime object to validate the value.
            $dateTime = DateTime::createFromFormat("Y-m-d H:i:s", $setValue);
            if ($dateTime instanceof DateTime) {
                // Verify what went in is what comes back out.
                $this->assertTrue($dateTime->format("Y-m-d H:i:s") === $setValue);
                $this->assertNotEquals($dateFields[$setField], $setValue);
            } else {
                $this->fail("Invalid date: {$setValue}");
            }
        }
    }
    /**
     * Verify processor adds fields to insert operations import mode.
     *
     * @param string $type
     * @param array $dateFields
     * @dataProvider provideOperationsDate
     */
    public function testOperationModeImport(string $type, $dateFields) {
        $model = new BasicPipelineModel("Example");
        $processor = new CurrentDateFieldProcessor();
        $model->addPipelineProcessor($processor);

        $operation = new Operation();
        $operation->setType($type);
        $operation->setCaller($model);
        $operation->setMode(Operation::MODE_IMPORT);
        $operation->setSet($dateFields);
        $model->doOperation($operation);

        $set = $operation->getSet();

        foreach ($set as $setField => $setValue) {
            if (!array_key_exists($setField, $dateFields)) {
                // Throw a failure exception.
                $this->fail("Unexpected field: {$setField}");
            }

            // Attempt to parse into a DateTime object to validate the value.
            $dateTime = DateTime::createFromFormat("Y-m-d H:i:s", $setValue);
            if ($dateTime instanceof DateTime) {
                // Verify what went in is what comes back out.
                $this->assertTrue($dateTime->format("Y-m-d H:i:s") === $setValue);
                $this->assertEquals($dateFields[$setField], $setValue);
            } else {
                $this->fail("Invalid date: {$setValue}");
            }
        }
    }

    /**
     * Generate type and "sets" pairs for testing if the current date fields should be set (or not).
     *
     * @return array
     */
    public function provideOperationsDate() {
        $timestamp = (new DateTime())
        ->modify('-1 hour')
        ->format("Y-m-d H:i:s");
        return [
            [Operation::TYPE_INSERT, ["DateInserted" => $timestamp]],
            [Operation::TYPE_UPDATE, ["DateUpdated" => $timestamp]],
        ];
    }
}
