<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\CommunityManagement;

use Garden\Schema\Schema;
use Vanilla\Database\CallbackWhereExpression;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\InsertProcessor;
use Vanilla\Database\Operation\InvalidateCallbackProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Select;
use Vanilla\Database\SetLiterals\RawExpression;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Utility\ArrayUtils;

/**
 * Model for GDN_reportReason table.
 */
class ReportReasonModel extends FullRecordCacheModel
{
    public const INITIAL_REASON_SPAM = "spam";
    public const INITIAL_REASON_ABUSE = "abuse";
    public const INITIAL_REASON_INAPPROPRIATE = "inappropriate";
    public const INITIAL_REASON_DECEPTIVE = "deceptive-misleading";
    public const INITIAL_REASON_RULE_BREAKING = "rule-breaking";
    public const INITIAL_REASON_SPAM_AUTOMATION = "spam-automation";
    public const INITIAL_REASON_APPROVAL = "approval-required";

    /**
     * Constructor.
     */
    public function __construct(\Gdn_Cache $cache, private \Gdn_Session $session, private \UserModel $userModel)
    {
        parent::__construct("reportReason", $cache);
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->camelCase();
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($session);
        $userProcessor->camelCase();
        $this->addPipelineProcessor($userProcessor);

        $this->addPipelineProcessor(new JsonFieldProcessor(["roleIDs"], JSON_THROW_ON_ERROR));

        $this->addPipelineProcessor(new Operation\BooleanFieldProcessor(["deleted", "isSystem"]));

        // Make sure newly inserted items set sort and go to the end of the list.
        $this->addPipelineProcessor(
            new InsertProcessor(function (Operation $operation, callable $stack) {
                if (!$operation->getSetItem("sort")) {
                    $operation->setSetItem("sort", $this->selectMaxSort() + 1);
                }

                $result = $stack($operation);
                return $result;
            })
        );
    }

    /**
     * Join the count of reports for each reason.
     *
     * @param array $reasonOrReasons Multiple report reasons or a single reason.
     *
     * @return void
     */
    public function joinReasonCounts(array &$reasonOrReasons): void
    {
        if (ArrayUtils::isAssociative($reasonOrReasons)) {
            $reasonRows = [&$reasonOrReasons];
        } else {
            $reasonRows = &$reasonOrReasons;
        }

        $reasonIDs = array_column($reasonRows, "reportReasonID");
        $reasonCounts = $this->createSql()
            ->select("rrj.reportReasonID")
            ->select("rrj.reportReasonID", "COUNT", "countReports")
            ->from("reportReasonJunction rrj")
            ->where(["rrj.reportReasonID" => $reasonIDs])
            ->groupBy("rrj.reportReasonID")
            ->get()
            ->resultArray();
        $reasonCountsByID = array_column($reasonCounts, "countReports", "reportReasonID");
        foreach ($reasonRows as &$reasonRow) {
            $reasonRow["countReports"] = $reasonCountsByID[$reasonRow["reportReasonID"]] ?? 0;
        }
    }

    /**
     * Select our current maximum sort value.
     *
     * @return int
     */
    public function selectMaxSort(): int
    {
        $maxSort =
            $this->createSql()
                ->select("sort", "MAX", "maxSort")
                ->from("reportReason")
                ->get()
                ->firstRow()->maxSort ?? 0;
        return $maxSort;
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
     * @param array $where
     *
     * @return array
     */
    public function selectReasonIDs(array $where = []): array
    {
        return array_column(
            $this->select(where: $where, options: [self::OPT_SELECT => ["reportReasonID"]]),
            "reportReasonID"
        );
    }

    /**
     * Get all reasonIDs.
     *
     * @param array $where
     *
     * @return array
     */
    public function selectReasons(array $where = []): array
    {
        return array_column(
            $this->select(where: $where, options: [self::OPT_SELECT => ["reportReasonID", "name"]]),
            "name",
            "reportReasonID"
        );
    }

    /**
     * Get reasonIDs available to the current sessioned user.
     *
     * @return array
     */
    public function getPermissionAvailableReasonIDs(bool $includeSystem = false): array
    {
        $where = [
            "deleted" => false,
            "isSystem" => false,
        ];
        $reasons = $this->select(where: $where, options: [self::OPT_SELECT => ["reportReasonID", "roleIDs"]]);

        $result = [];
        $isMod = \Gdn::session()->checkPermission("community.moderate");
        $userRoleIDs = $this->userModel->getRoleIDs($this->session->UserID) ?: [];
        foreach ($reasons as $reason) {
            $roleIDs = $reason["roleIDs"] ?? [];
            if ($isMod || empty($roleIDs) || count(array_intersect($roleIDs, $userRoleIDs)) > 0) {
                $result[] = $reason["reportReasonID"];
            }
        }
        return $result;
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
            ->column("roleIDs", "json", null)
            ->column("deleted", "tinyint(1)", "0", "index")
            ->column("isSystem", "tinyint(1)", "0")
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
            "name" => "Spam / Solicitation",
            "description" => "This content is spam.",
            "sort" => 0,
        ]);
        $reportReasonModel->createInitialReason([
            "reportReasonID" => self::INITIAL_REASON_ABUSE,
            "name" => "Abuse",
            "description" => "This content is abusive.",
            "sort" => 1,
        ]);
        $reportReasonModel->createInitialReason([
            "reportReasonID" => self::INITIAL_REASON_INAPPROPRIATE,
            "name" => "Inappropriate",
            "description" => "This content is inappropriate.",
            "sort" => 2,
        ]);
        $reportReasonModel->createInitialReason([
            "reportReasonID" => self::INITIAL_REASON_DECEPTIVE,
            "name" => "Deceptive / Misleading",
            "description" => "Illegal activity, malware, or promotion of illegal products or services.",
            "sort" => 3,
        ]);
        $reportReasonModel->createInitialReason([
            "reportReasonID" => self::INITIAL_REASON_RULE_BREAKING,
            "name" => "Breaks Community Rules",
            "description" => "The content is breaking community rules.",
            "sort" => 4,
        ]);
        $reportReasonModel->createInitialReason([
            "reportReasonID" => self::INITIAL_REASON_SPAM_AUTOMATION,
            "name" => "Spam Automation",
            "description" =>
                "The content was marked as spam by a spam automation system such as Akismet or StopForumSpam.",
            "sort" => 0,
            "isSystem" => true,
        ]);
        $reportReasonModel->createInitialReason([
            "reportReasonID" => self::INITIAL_REASON_APPROVAL,
            "name" => "Approval Required",
            "description" => "The user making the post needs moderator approval for posts in this category.",
            "sort" => 0,
            "isSystem" => true,
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
            "deleted:b",
            "isSystem:b" => [
                "default" => false,
            ],
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
            uasort($row["reportReasons"], function (array $a, array $b) {
                return $a["sort"] <=> $b["sort"];
            });
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
                "rr.deleted",
            ])
            ->from("reportReasonJunction rrj")
            ->join("reportReason rr", "rrj.reportReasonID = rr.reportReasonID")
            ->where($where)
            ->orderBy("rr.sort")
            ->get()
            ->resultArray();

        // Light normalization.
        foreach ($reasons as &$reason) {
            $reason["deleted"] = (bool) $reason["deleted"];
        }
        return $reasons;
    }

    /**
     * Update sort values for records using a reportReasonID => sort mapping.
     *
     * @param array<string,int> $sorts Key-value mapping of reportReasonID => sort
     *
     * @return void
     */
    public function updateSorts(array $sorts)
    {
        try {
            $this->database->beginTransaction();
            foreach ($sorts as $reportReasonID => $sort) {
                $this->update(["sort" => $sort], ["reportReasonID" => $reportReasonID]);
            }
            $this->database->commitTransaction();
        } catch (\Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
    }
}
