<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Database\CallbackWhereExpression;
use Vanilla\DateFilterSchema;
use Vanilla\Logging\AuditLogModel;
use Vanilla\Logging\AuditLogService;
use Vanilla\Models\Model;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Utility\StringUtils;

/**
 * /api/v2/audit-log
 */
class AuditLogsApiController extends \AbstractApiController
{
    /**
     * DI.
     */
    public function __construct(private AuditLogModel $auditLogModel)
    {
    }

    /**
     * GET /api/v2/audit-log
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query): Data
    {
        $this->permission("site.manage");
        $in = Schema::parse([
            "insertUserID?" => RangeExpression::createSchema([":int"])->setField("x-filter", true),
            "insertIPAddress:s?" => [
                "x-filter" => true,
            ],
            "eventType:a?" => [
                "style" => "form",
                "items" => [
                    "type" => "string",
                ],
                "x-filter" => true,
            ],
            "dateInserted?" => new DateFilterSchema([
                "x-filter" => [
                    "field" => "dateInserted",
                    "processor" => [DateFilterSchema::class, "dateFilterField"],
                ],
            ]),
            "page:i?" => [
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "default" => 30,
                "minimum" => 1,
                "maximum" => ApiUtils::getMaxLimit(),
            ],
            "onlySpoofedActions:b?" => [
                "default" => false,
            ],
        ]);
        $in->addValidator("insertIPAddress", function (string $ipAddress, \Garden\Schema\ValidationField $field) {
            if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
                $field->addError("$ipAddress is not a valid IP address");
            }
        });
        $query = $in->validate($query);

        $where = ApiUtils::queryToFilters($in, $query);
        if (isset($where["insertIPAddress"])) {
            $where["insertIPAddress"] = inet_pton($where["insertIPAddress"]);
        }
        if ($query["onlySpoofedActions"]) {
            $where[] = new CallbackWhereExpression(function (\Gdn_SQLDriver $sql) {
                $sql->where("spoofUserID IS NOT NULL AND spoofUserID <> insertUserID", null, false, false)->orWhere(
                    "orcUserEmail IS NOT NULL"
                );
            });
        }
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $rows = $this->auditLogModel->select($where, [
            Model::OPT_ORDER => "-dateInserted",
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
        ]);
        $pagingCount = $this->auditLogModel->selectPagingCount($where);

        $this->auditLogModel->normalizeRows($rows);

        $out = $this->auditLogModel->auditLogSchema();
        SchemaUtils::validateArray($rows, $out);

        $paging = ApiUtils::numberedPagerInfo($pagingCount, "/api/v2/audit-logs", $query, $in);

        return new Data($rows, ["paging" => $paging, "api-allow" => ["email"]]);
    }

    /**
     * GET /api/v2/audit-logs/:auditLogID
     *
     * @param string $id
     *
     * @return Data
     */
    public function get(string $id): Data
    {
        $this->permission("site.manage");
        $auditLog = $this->auditLogModel->selectSingle([
            "auditLogID" => $id,
        ]);

        $rows = [&$auditLog];
        $this->auditLogModel->normalizeRows($rows);
        $out = $this->auditLogModel->auditLogSchema();
        $auditLog = $out->validate($auditLog);
        return new Data($auditLog);
    }

    /**
     * GET /api/v2/audit-logs/event-types
     *
     * @return Data
     */
    public function index_eventTypes(): Data
    {
        $this->permission("site.manage");
        $eventTypes = $this->auditLogModel->selectEventTypes();

        $out = Schema::parse([
            ":a" => Schema::parse(["eventType:s", "name:s"]),
        ]);

        $results = [];
        foreach ($eventTypes as $eventType) {
            $results[] = ["eventType" => $eventType, "name" => StringUtils::labelize($eventType)];
        }

        $results = $out->validate($results);

        return new Data($results);
    }
}
