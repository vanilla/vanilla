<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Models;

use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\PruneProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;

class UserDigestModel extends PipelineModel
{
    public function __construct()
    {
        parent::__construct("userDigest");
        $this->addPipelineProcessor(new JsonFieldProcessor(["userMeta", "attributes", "digestContent"]));
        $this->addPipelineProcessor(new PruneProcessor("dateInserted", "2 days"));
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"])->setUpdateFields([]);
        $this->addPipelineProcessor($dateProcessor);
    }
}
