<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Garden\Schema\Schema;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\Pipeline;
use Vanilla\Models\PipelineModel;

/**
 * Class for basic PipelineModel testing.
 */
class BasicPipelineModel extends PipelineModel {
    /**
     * BasicPipelineModel constructor.
     *
     * @param string $table
     */
    public function __construct(string $table) {
        parent::__construct($table);

        $this->readSchema = $this->writeSchema = Schema::parse([
            "UniqueID" => ["type" => "integer"],
            "InsertUserID" => ["type" => "integer"],
            "DateInserted" => ["type" => "datetime"],
            "UpdateUserID" => ["type" => "integer"],
            "DateUpdated" => ["type" => "datetime"],
        ]);
    }

    /**
     * Perform a dummy database operation using the configured pipeline.
     *
     * @param Operation $operation
     */
    public function doOperation(Operation $operation) {
        $result = $this->pipeline->processOperation($operation);
        return $result;
    }

    /**
     * No-op for database operations..
     *
     * @param Operation $op
     */
    protected function handleInnerOperation(Operation $op) {
        return;
    }

    /**
     * Directly set a configured Pipeline instance.
     *
     * @param Pipeline $pipeline
     */
    public function setPipeline(Pipeline $pipeline): void {
        $this->pipeline = $pipeline;
    }
}
