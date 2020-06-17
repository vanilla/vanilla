<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Tests for the `Model` class.
 */
class PipelineModelTest extends ModelTest {

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $this->container()->call(function (
            \Gdn_SQLDriver $sql
        ) {
            $sql->truncate('model');
        });

        /** @var PipelineModel $model */
        $model = $this->container()->getArgs(PipelineModel::class, ['model']);
        $model->addPipelineProcessor(new JsonFieldProcessor(['attributes']));
        $this->model = $model;
    }
}
