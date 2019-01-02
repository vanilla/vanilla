<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Database;

use Gdn_Session;
use PHPUnit\Framework\TestCase;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use VanillaTests\Fixtures\BasicPipelineModel;

/**
 * Class for testing the "current user field" database operation processor.
 */
class CurrentUserFieldProcessorTest extends TestCase {

    const CURRENT_USER_ID = 99;

    /**
     * Verify processor adds fields to insert operations.
     *
     * @param string $type
     * @param array $expectedSet
     * @dataProvider provideOperations
     */
    public function testAddInsertFields(string $type, array $expectedSet) {
        $model = new BasicPipelineModel("Example");
        $session = new Gdn_Session();
        $session->UserID = self::CURRENT_USER_ID;
        $processor = new CurrentUserFieldProcessor($session);
        $model->addPipelineProcessor($processor);

        $operation = new Operation();
        $operation->setType($type);
        $operation->setCaller($model);
        $model->doOperation($operation);
        $this->assertEquals($expectedSet, $operation->getSet());
    }

    /**
     * Generate type and "sets" pairs for testing if the current user fields should be set (or not).
     *
     * @return array
     */
    public function provideOperations() {
        return [
            [Operation::TYPE_DELETE, []],
            [Operation::TYPE_INSERT, ["InsertUserID" => self::CURRENT_USER_ID]],
            [Operation::TYPE_SELECT, []],
            [Operation::TYPE_UPDATE, ["UpdateUserID" => self::CURRENT_USER_ID]],
        ];
    }
}
