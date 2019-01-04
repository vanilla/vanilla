<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Garden\Schema\Schema;
use Vanilla\Database\Operation;
use Vanilla\Models\PipelineModel;

/**
 * Class for basic PipelineModel testing.
 */
class BasicPipelineModel extends PipelineModel {

    /**
     * Perform a dummy database operation using the configured pipeline.
     *
     * @param Operation $databaseOperation
     * @return Operation
     */
    public function doOperation(Operation $databaseOperation): Operation {
        $this->pipeline->process($databaseOperation, function () {
            return;
        });
        return $databaseOperation;
    }

    /**
     * Perform a dummy "select" database operation using the configured pipeline.
     *
     * @param Operation $databaseOperation
     * @param array $dummyResult
     * @return Operation
     */
    public function doSelectOperation(Operation $databaseOperation, array $dummyResult): array {
        $result = $this->pipeline->process($databaseOperation, function () use ($dummyResult) {
            return $dummyResult;
        });
        return $result;
    }

    /**
     * Make sure we have configured schemas available to the instance.
     */
    protected function ensureSchemas() {
        $this->readSchema = $this->writeSchema = Schema::parse([
            "UniqueID" => ["type" => "integer"],
            "InsertUserID" => ["type" => "integer"],
            "DateInserted" => ["type" => "datetime"],
            "UpdateUserID" => ["type" => "integer"],
            "DateUpdated" => ["type" => "datetime"],
        ]);
    }
}
