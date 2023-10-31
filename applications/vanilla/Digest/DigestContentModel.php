<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Digest;

use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\PruneProcessor;
use Vanilla\Models\PipelineModel;

class DigestContentModel extends PipelineModel
{
    public function __construct()
    {
        parent::__construct("digestContent");
        $this->addPipelineProcessor(new JsonFieldProcessor(["attributes", "digestContent"]));
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"])->setUpdateFields([]);
        $this->addPipelineProcessor($dateProcessor);
        $this->addPipelineProcessor(new PruneProcessor("dateInserted", "2 weeks"));
    }

    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        $structure
            ->table("digestContent")
            ->primaryKey("digestContentID")
            ->column("digestContentHash", "varchar(40)", false, "unique")
            ->column("digestID", "int", false, "unique")
            ->column("attributes", "mediumtext")
            ->column("digestContent", "mediumtext")
            ->column("dateInserted", "datetime", false, "index")
            ->set();
    }
}
