<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Database;

use PHPUnit\Framework\TestCase;
use Vanilla\Database\Operation;
use VanillaTests\Fixtures\BasicPipelineModel;
use VanillaTests\Fixtures\Database\ProcessorFixture;

/**
 * Test post processing in the pipeline model.
 */
class PostProcessorTest extends TestCase {

    /**
     * Processor.
     */
    public function testPostProcessor() {
        $model = new BasicPipelineModel("Example");

        $wasNormalProcessorCalled = false;
        $isInsertCalled = false;

        $processor = new ProcessorFixture(function (Operation $operation) use (&$isInsertCalled, &$wasNormalProcessorCalled) {
            if (!$wasNormalProcessorCalled) {
                throw new \Exception('Normal processor must be called before post processors');
            }
            if ($operation->getType() === Operation::TYPE_INSERT) {
                $isInsertCalled = true;
            }
        });
        $model->addPipelinePostProcessor($processor);
        $preprocessor = new ProcessorFixture(function (Operation $operation) use (&$wasNormalProcessorCalled) {
            $wasNormalProcessorCalled = true;
        });
        $model->addPipelineProcessor($preprocessor);


        $operation = new Operation();
        $operation->setType(Operation::TYPE_DELETE);
        $operation->setCaller($model);
        $model->doOperation($operation);
        $this->assertFalse($isInsertCalled);

        $operation = new Operation();
        $operation->setType(Operation::TYPE_INSERT);
        $operation->setCaller($model);
        $model->doOperation($operation);
        $this->assertTrue($isInsertCalled);
    }
}
