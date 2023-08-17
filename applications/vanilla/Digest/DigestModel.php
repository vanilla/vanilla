<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Digest;

use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Models\PipelineModel;

class DigestModel extends PipelineModel
{
    public const DIGEST_TYPE_WEEKLY = "weekly";
    public const DIGEST_TYPE_TEST_WEEKLY = "test-weekly";
    public const DIGEST_TYPE_IMMEDIATE = "immediate";

    public const DIGEST_TYPES = [self::DIGEST_TYPE_WEEKLY, self::DIGEST_TYPE_TEST_WEEKLY, self::DIGEST_TYPE_IMMEDIATE];

    public function __construct()
    {
        parent::__construct("digest");
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"])->setUpdateFields([]);
        $this->addPipelineProcessor($dateProcessor);
    }

    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        // Table for getting storing digest data
        $structure
            ->table("digest")
            ->primaryKey("digestID")
            ->column("digestType", self::DIGEST_TYPES, null, "index")
            ->column("dateInserted", "datetime")
            ->column("dateScheduled", "datetime", null, "index")
            ->set();
    }

    /**
     * Get the scheduled digest dates based on a limit
     *
     * @param int $limit
     * @return array
     */
    public function getRecentWeeklyDigestScheduledDates(int $limit = 10): array
    {
        $sql = $this->database->createSql();
        return $sql
            ->select("dateScheduled")
            ->from($this->getTableName())
            ->where("digestType", self::DIGEST_TYPE_WEEKLY)
            ->orderBy("dateScheduled", "desc")
            ->limit($limit)
            ->get()
            ->column("dateScheduled");
    }
}
