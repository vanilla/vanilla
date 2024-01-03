<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\PruneProcessor;

/**
 * Model for storing developer profiles.
 */
class DeveloperProfileModel extends PipelineModel
{
    const TABLE_NAME = "developerProfile";

    private ConfigurationInterface $config;

    /**
     * Constructor.
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->config = $config;
        parent::__construct(self::TABLE_NAME);
        $this->addPipelineProcessor(
            new PruneProcessor("dateRecorded", "7 days", 100, [
                "isTracked" => false,
            ])
        );
        $this->addPipelineProcessor(
            new JsonFieldProcessor(["profile", "timers", "requestQuery"], JSON_INVALID_UTF8_IGNORE)
        );
        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateRecorded"], []));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["isTracked"]));
    }

    /**
     * Configure the database structure.
     *
     * @param \Gdn_DatabaseStructure $structure
     *
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure): void
    {
        $structure
            ->table(self::TABLE_NAME)
            ->primaryKey("developerProfileID")
            ->column("profile", "mediumtext")
            ->column("isTracked", "tinyint(1)")
            ->column("timers", "mediumtext")
            ->column("name", "varchar(255)")
            ->column("requestID", "varchar(50)")
            ->column("requestMethod", "varchar(10)")
            ->column("requestPath", "text")
            ->column("requestQuery", "text")
            ->column("requestElapsedMs", "float")
            ->column("dateRecorded", "datetime")
            ->set();

        $structure
            ->table(self::TABLE_NAME)
            ->createIndexIfNotExists("IX_developerProfile_requestID", ["requestID"])
            ->createIndexIfNotExists("IX_developerProfile_requestElapsedMs_name", ["requestElapsedMs", "name"])
            // For pruning
            ->createIndexIfNotExists("IX_developerProfile_dateRecorded_name", ["dateRecorded", "name"])
            ->createIndexIfNotExists("IX_developerProfile_dateRecorded_isTracked", ["dateRecorded", "isTracked"]);
    }

    /**
     * Get a limited paging count for an API controller.
     *
     * @param array $where
     *
     * @return int
     */
    public function getPagingCount(array $where): int
    {
        $limit = $this->config->get("Vanilla.APIv2.MaxCount", 10000);
        $innerQuery = $this->createSql()
            ->select("developerProfileID")
            ->from(self::TABLE_NAME)
            ->where($where)
            ->limit($limit)
            ->getSelect(true);
        $countQuery = <<<SQL
SELECT COUNT(*) as count FROM ({$innerQuery}) cq
SQL;

        $result = $this->createSql()->query($countQuery);

        return $result->firstRow(DATASET_TYPE_ARRAY)["count"];
    }
}
