<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\Schema\Schema;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Database\Operation\InsertUuidProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\PruneProcessor;
use Vanilla\Database\SetLiterals\JsonArrayInsert;
use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\SchemaUtils;

/**
 * Model for working with the GDN_auditLog table.
 */
class AuditLogModel extends PipelineModel
{
    /**
     * Constructor.
     */
    public function __construct(private AuditLogService $auditLogService, ConfigurationInterface $config)
    {
        parent::__construct("auditLog");
        $this->addInsertUpdateProcessors(false, true, true);
        $jsonProcessor = new JsonFieldProcessor(["requestQuery", "context", "meta"], 0);
        $this->addPipelineProcessor($jsonProcessor);
        $this->addPipelineProcessor(
            new PruneProcessor("dateInserted", $config->get(AuditLogger::CONF_RETENTION, "60 days"))
        );
        $this->addPipelineProcessor(new InsertUuidProcessor(["auditLogID"]));
    }

    /**
     * Update the context of an audit log.
     *
     * @param string $auditLogID
     * @param AuditLogEventInterface $childEvent
     *
     * @return void
     */
    public function pushChildEvent(string $auditLogID, AuditLogEventInterface $childEvent): void
    {
        $this->createSql()
            ->update("auditLog")
            ->set(["meta" => new JsonArrayInsert("childEvents", AuditLogger::auditLogEventToArray($childEvent))])
            ->where("auditLogID", $auditLogID)
            ->put();
    }

    /**
     * @return string[]
     */
    public function selectEventTypes(): array
    {
        return $this->createSql()
            ->from($this->getTable())
            ->select("eventType", "distinct")
            ->get()
            ->column("eventType");
    }

    /**
     * Add an audit log event if it doesn't already exist.
     *
     * @param AuditLogEventInterface $auditLogEvent
     *
     * @return string
     */
    public function add(AuditLogEventInterface $auditLogEvent): string
    {
        $sessionAttributes = \Gdn::session()->Session["Attributes"] ?? [];
        $spoofUserID = $sessionAttributes["spoofedByUserID"] ?? null;
        $orcUserEmail = $sessionAttributes["orcUserEmail"] ?? null;

        $insert = AuditLogger::auditLogEventToArray($auditLogEvent) + [
            "context" => array_merge(
                [
                    "childEvents" => [],
                ],
                $auditLogEvent->getAuditContext()
            ),
            "spoofUserID" => $spoofUserID,
            "orcUserEmail" => $orcUserEmail,
            "insertUserID" => $auditLogEvent->getSessionUserID(),
        ];
        // Ignore duplicate entries.
        // This could happen if the same event was logged twice in the same request.
        // This way the caller can explicitly set auditLogID themselves.
        $rowID = $this->insert($insert, [
            Model::OPT_IGNORE => true,
        ]);
        return $rowID;
    }

    /**
     * Create the DB structure for the model.
     *
     * @param \Gdn_DatabaseStructure $structure
     */
    public static function structure(\Gdn_DatabaseStructure $structure): void
    {
        $structure
            ->table("auditLog")
            ->column("auditLogID", "varchar(40)", false, "primary")
            ->column("eventType", "varchar(150)", false, "index")
            ->column("requestMethod", "varchar(10)", false, "index")
            ->column("requestPath", "varchar(200)", false, "index")
            ->column("requestQuery", "json", false)
            ->column("context", "json", false)
            ->column("meta", "json", false)
            ->column("spoofUserID", "int", null, "index")
            ->column("orcUserEmail", "varchar(100)", null, "index")
            ->insertUpdateColumns(false, true, 4)
            ->set();

        $structure
            ->table("auditLog")
            ->createIndexIfNotExists("auditLog_insertUserID", ["dateInserted", "insertUserID"])
            ->createIndexIfNotExists("auditLog_insertUserID_dateInserted", ["insertUserID", "dateInserted"]);

        if (\Gdn::config("Garden.Installed")) {
            \Gdn::config()->touch(AuditLogger::CONF_ENABLED, true);
        }
    }

    /**
     * Schema for a single audit log API event.
     *
     * @return Schema
     */
    public function auditLogSchema(): Schema
    {
        $minimalSchema = Schema::parse([
            "auditLogID:s",
            "eventType:s",
            "message:s",
            "requestMethod:s",
            "requestPath:s",
            "requestQuery:o",
            "context:o|a",
            "meta:o",
        ]);

        $mainSchema = SchemaUtils::composeSchemas(
            $minimalSchema,
            Schema::parse([
                "insertUserID:i",
                "dateInserted:dt",
                "insertIPAddress:s",
                "spoofUserID:i|n",
                "orcUserEmail:s|n",
            ])
        );
        $schemaWithChildren = SchemaUtils::composeSchemas(
            $mainSchema,
            Schema::parse([
                "childEvents:a?" => $minimalSchema,
            ])
        );
        return $schemaWithChildren;
    }

    /**
     * Normalize rows for {@link self::auditLogSchema()}
     *
     * @param array $rows
     * @return void
     */
    public function normalizeRows(array &$rows): void
    {
        foreach ($rows as &$row) {
            // Extract a few fields from the context.
            if (isset($row["context"]["childEvents"])) {
                $row["childEvents"] = $row["context"]["childEvents"];
                $this->normalizeRows($row["childEvents"]);

                unset($row["context"]["childEvents"]);
            }

            if (empty($row["requestQuery"])) {
                $row["requestQuery"] = new \ArrayObject();
            }

            $row["message"] = $this->auditLogService->formatEventMessage(
                $row["eventType"],
                $row["context"],
                $row["meta"] ?? []
            );

            if (empty($row["context"])) {
                $row["context"] = new \ArrayObject();
            }
        }
    }
}
