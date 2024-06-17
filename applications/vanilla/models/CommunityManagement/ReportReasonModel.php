<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\CommunityManagement;

use Garden\Schema\Schema;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\FullRecordCacheModel;

/**
 * Model for GDN_reportReason table.
 */
class ReportReasonModel extends FullRecordCacheModel
{
    public const INITIAL_REASON_SPAM = "spam";
    public const INITIAL_REASON_ABUSE = "abuse";
    public const INITIAL_REASON_SEXUAL = "sexual-content";
    public const INITIAL_REASON_FRAUD = "fraud-or-scam";

    /**
     * Constructor.
     */
    public function __construct(\Gdn_Cache $cache, \Gdn_Session $session)
    {
        parent::__construct("reportReason", $cache);
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->camelCase();
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($session);
        $userProcessor->camelCase();
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Create an initial reason if it doesn't exist.
     *
     * @param array $row
     *
     * @return void
     */
    public function createInitialReason(array $row)
    {
        $hasExisting =
            $this->createSql()->getCount($this->getTable(), ["reportReasonID" => $row["reportReasonID"]]) > 0;
        if ($hasExisting) {
            return;
        }
        $this->insert($row);
    }

    /**
     * Get all reasonIDs.
     *
     * @return array
     */
    public function getAvailableReasonIDs(): array
    {
        return array_column($this->select([], [self::OPT_SELECT => ["reportReasonID"]]), "reportReasonID");
    }

    /**
     * Structure our database table.
     *
     * @param \Gdn_DatabaseStructure $structure
     *
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure): void
    {
        $structure
            ->table("reportReason")
            ->primaryKey("reportReasonID", "varchar(255)", false)
            ->column("name", "text")
            ->column("description", "text", true)
            ->column("sort", "int")
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->column("permission", "varchar(700)", true)
            ->set();

        // Add default reasons
        if (!$structure->CaptureOnly) {
            self::createInitialReasons();
        }
    }

    /**
     * Create initial report reasons.
     *
     * @return void
     */
    private static function createInitialReasons()
    {
        $reportReasonModel = \Gdn::getContainer()->get(ReportReasonModel::class);
        $reportReasonModel->createInitialReason([
            "reportReasonID" => self::INITIAL_REASON_SPAM,
            "name" => "Spam",
            "description" => "This content is spam.",
            "sort" => 0,
        ]);
        $reportReasonModel->createInitialReason([
            "reportReasonID" => self::INITIAL_REASON_ABUSE,
            "name" => "Abuse",
            "description" => "This content is abusive.",
            "sort" => 0,
        ]);
        $reportReasonModel->createInitialReason([
            "reportReasonID" => self::INITIAL_REASON_SEXUAL,
            "name" => "Sexual Content",
            "description" => "The content contains describes or contains photos of sexual acts.",
            "sort" => 0,
        ]);
        $reportReasonModel->createInitialReason([
            "reportReasonID" => self::INITIAL_REASON_FRAUD,
            "name" => "Fraud or Scam",
            "description" => "Illegal activity, malware, or promotion of illegal products or services.",
            "sort" => 0,
        ]);
    }

    /**
     * Get a schema representing a fragment of a report.
     *
     * @return Schema
     */
    public static function reasonFragmentSchema(): Schema
    {
        return Schema::parse([
            "reportReasonJunctionID:i?",
            "reportReasonID:string",
            "reportID:i",
            "name:s",
            "description:s",
            "sort:i",
            "visibility:s?",
        ]);
    }

    /**
     * Given a set of rows, expand the reportReasonIDs into reportReasons.
     *
     * @param array{array{reportReasonIDs: string[]}} $rows
     *
     * @return array
     */
    public function expandReportReasonArrays(array &$rows): array
    {
        $allReportReasonIDs = [];
        foreach ($rows as $row) {
            $allReportReasonIDs = array_merge($allReportReasonIDs, $row["reportReasonIDs"]);
        }

        $reasonFragments = $this->getReportReasonsFragments(["rrj.reportReasonID" => $allReportReasonIDs]);
        $reasonFragmentsByID = array_column($reasonFragments, null, "reportReasonID");
        foreach ($rows as &$row) {
            $row["reportReasons"] = [];
            foreach ($row["reportReasonIDs"] as $reportReasonID) {
                $row["reportReasons"][] = $reasonFragmentsByID[$reportReasonID];
            }
        }

        return $rows;
    }

    /**
     * Get a set of report reasons fragments.
     *
     * @param array $where
     *
     * @return array
     */
    public function getReportReasonsFragments(array $where): array
    {
        $reasons = $this->createSql()
            ->select([
                "rrj.reportReasonJunctionID",
                "rrj.reportReasonID",
                "rrj.reportID",
                "rr.name",
                "rr.description",
                "rr.sort",
            ])
            ->from("reportReasonJunction rrj")
            ->join("reportReason rr", "rrj.reportReasonID = rr.reportReasonID")
            ->where($where)
            ->get()
            ->resultArray();
        return $reasons;
    }
}
