<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Faker\Core\DateTime;
use Garden\Http\HttpClient;
use Garden\Schema\Schema;
use Garden\Utils\ContextException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Gdn_Cache;
use Gdn_Database;
use Gdn_Session;
use Gdn_SQLDriver;
use HttpResponseException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Database\CallbackWhereExpression;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\PrimaryKeyUuidProcessor;
use Vanilla\Database\SetLiterals\RawExpression;
use Vanilla\Feature\FeatureService;
use Vanilla\Formatting\FormatService;
use Vanilla\Models\Model;
use Vanilla\Models\ModelCache;
use Vanilla\Models\NormalizeRowsTrait;
use Vanilla\Models\PipelineModel;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Site\OwnSite;
use Vanilla\Web\Middleware\CircuitBreakerMiddleware;

/**
 * Model for GDN_productMessage and GDN_userProductMessage.
 */
class ProductMessageModel extends PipelineModel
{
    use NormalizeRowsTrait;

    const TYPE_PERSONAL = "personalMessage";
    const TYPE_ANNOUNCEMENT = "announcement";
    const CONF_BASE_URL = "productMessages.baseUrl";
    const CONF_ACCESS_TOKEN = "productMessages.accessToken";
    const CONF_CATEGORY_ID = "productMessages.categoryID";
    const CONF_FOREIGN_USER_FILTER = "productMessages.foreignUserFilter";

    const ANNOUNCE_NONE = "None";
    const ANNOUNCE_INBOX = "Inbox";
    const ANNOUNCE_DIRECT = "Direct";

    const ANNOUNCEMENT_TYPES = [self::ANNOUNCE_NONE, self::ANNOUNCE_INBOX, self::ANNOUNCE_DIRECT];

    /**
     * DI.
     */
    public function __construct(
        private ConfigurationInterface $config,
        private FeatureService $featureService,
        private Gdn_Session $session,
        private CircuitBreakerMiddleware $circuitBreaker,
        private OwnSite $ownSite,
        private FormatService $formatService,
        Gdn_Cache $cache
    ) {
        parent::__construct("productMessage");
        $this->addPipelineProcessor(new JsonFieldProcessor(["foreignInsertUser"]));
        $this->addInsertUpdateProcessors();
        $this->addPipelineProcessor(new PrimaryKeyUuidProcessor("productMessageID"));
    }

    /**
     * Database structure.
     *
     * @param Gdn_Database $database
     */
    public static function structure(Gdn_Database $database): void
    {
        $structure = $database->structure();
        $structure
            ->table("productMessage")
            ->primaryKey("productMessageID", type: "varchar(40)", autoIncrement: false)
            ->column("productMessageType", [self::TYPE_PERSONAL, self::TYPE_ANNOUNCEMENT])
            ->column("name", "varchar(500)")
            ->column("body", "mediumtext")
            ->column("format", "varchar(10)")
            ->column("foreignInsertUser", "json")
            ->column("foreignUrl", "text", nullDefault: true)
            ->column("ctaLabel", "text", nullDefault: true)
            ->column("ctaUrl", "text", nullDefault: true)
            ->column("announcementType", self::ANNOUNCEMENT_TYPES, nullDefault: self::ANNOUNCE_INBOX)
            ->insertUpdateColumns()
            ->set();

        $structure
            ->table("userProductMessage")
            ->column("userID", "int(11)", nullDefault: false, keyType: "primary")
            ->column("productMessageID", "varchar(40)", nullDefault: false, keyType: "primary")
            ->column("isDismissed", "tinyint(1)", nullDefault: 0)
            ->column("dateDismissed", "datetime", nullDefault: true)
            ->set();
    }

    /**
     * @param string $productMessageID
     * @return void
     */
    public function dismissMessage(string $productMessageID): void
    {
        $this->createSql()
            ->applyModelOptions([
                Model::OPT_REPLACE => true,
            ])
            ->insert("userProductMessage", [
                "userID" => $this->session->UserID,
                "productMessageID" => $productMessageID,
                "isDismissed" => 1,
                "dateDismissed" => CurrentTimeStamp::getMySQL(),
            ]);
    }

    /**
     * @return void
     */
    public function dismissAll(): void
    {
        $this->database->query(
            <<<SQL
INSERT INTO GDN_userProductMessage (userID, productMessageID, isDismissed, dateDismissed)
SELECT
    :userID,
    productMessageID,
    1,
    :currentTime1
FROM
    GDN_productMessage
ON DUPLICATE KEY UPDATE
    isDismissed = 1,
    dateDismissed = :currentTime2
SQL
            ,
            [
                ":userID" => $this->session->UserID,
                ":currentTime1" => CurrentTimeStamp::getMySQL(),
                ":currentTime2" => CurrentTimeStamp::getMySQL(),
            ]
        );
    }

    /**
     * Join the userProductMessage table onto a query.
     *
     * @param Gdn_SQLDriver $sql
     * @return Gdn_SQLDriver
     */
    private function joinUserProductMessage(Gdn_SQLDriver $sql): Gdn_SQLDriver
    {
        $sql->with(
            "viewerCounts",
            $this->createSql()
                ->select("COUNT(userID) AS countViewers")
                ->select("productMessageID")
                ->from("userProductMessage")
                ->groupBy("productMessageID")
        );
        $sql->leftJoin(
            "userProductMessage",
            "userProductMessage.productMessageID = productMessage.productMessageID AND userProductMessage.userID = :userID"
        );
        $sql->namedParameter("userID", true, $this->session->UserID);
        $sql->leftJoin("@viewerCounts vc", "vc.productMessageID = productMessage.productMessageID");

        return $sql;
    }

    /**
     * Get the userIDs that have viewed a specific product message.
     *
     * @param string $productMessageID
     * @return int[]
     */

    public function selectViewerUserIDs(string $productMessageID): array
    {
        $this->joinUserProductMessage($this->createSql());
        $this->createSql()->select("userID");
        return $this->createSql()
            ->select("userID")
            ->from("userProductMessage")
            ->where("productMessageID", $productMessageID)
            ->get()
            ->column("userID");
    }

    /**
     * @inheritDoc
     */
    public function select(array $where = [], array $options = []): array
    {
        if (isset($where["productMessageID"])) {
            $where["productMessage.productMessageID"] = $where["productMessageID"];
            unset($where["productMessageID"]);
        }

        $where[] = [
            new CallbackWhereExpression(function (Gdn_SQLDriver $sql) use ($options) {
                $this->joinUserProductMessage($sql);

                $sql->select("countViewers");
                if (empty($options[Model::OPT_SELECT])) {
                    $sql->select("productMessage.*")->select(
                        "userProductMessage.isDismissed,userProductMessage.dateDismissed"
                    );
                }
            }),
        ];

        $options[Model::OPT_ORDER] =
            $options[Model::OPT_ORDER] ??
            new RawExpression("CASE WHEN productMessageType = 'personalMessage' THEN 0 ELSE 1 END, dateInserted DESC");
        return parent::select($where, $options);
    }

    /**
     * List available users from success to make private messages as.
     *
     * @return array
     */
    public function listForeignUsers(): array
    {
        $response = $this->httpClient()
            ->get(
                "/api/v2/users",
                $this->config->get(self::CONF_FOREIGN_USER_FILTER, [
                    "limit" => 100,
                ])
            )
            ->getBody();

        return $response;
    }

    /**
     * @return Schema
     */
    public function foreignUserFragmentSchema(): Schema
    {
        return Schema::parse(["userID:i", "url:s", "photoUrl:s?", "name:s", "label:s?"]);
    }

    /**
     * @param int $foreignUserID
     *
     * @return array
     */
    public function getForeignUser(int $foreignUserID): array
    {
        try {
            $response = $this->httpClient()
                ->get("/api/v2/users/$foreignUserID")
                ->getBody();
        } catch (\Garden\Http\HttpResponseException $exception) {
            if ($exception->getResponse()->getStatusCode() === 404) {
                throw new NotFoundException("Foreign User");
            }
            throw new ServerException("Something went wrong with the upstream service.", 500, previous: $exception);
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function getReadSchema(): Schema
    {
        return parent::getReadSchema()->merge(
            Schema::parse([
                "isDismissed:b?" => [
                    "default" => false,
                ],
                "dateDismissed:dt?",
                "countViewers:i?" => [
                    "default" => 0,
                ],
            ])
        );
    }

    /**
     * Get an authenticated HTTP client to the foreign vanilla site.
     *
     * @return HttpClient
     * @throws ContextException If the base URL or access token is not set.
     */
    public function httpClient(): HttpClient
    {
        $baseUrl = $this->config->get(self::CONF_BASE_URL);
        $accessToken = $this->config->get(self::CONF_ACCESS_TOKEN);

        if (empty($baseUrl) || empty($accessToken)) {
            throw new ContextException("Product message base URL or access token not set", 500);
        }

        $client = new HttpClient($baseUrl);
        $client->setThrowExceptions(true);
        $client->setDefaultOption("timeout", 5);
        $client->setDefaultHeader("Authorization", "Bearer $accessToken");
        $client->setDefaultHeader("Accept", "application/json");

        $client->addMiddleware($this->circuitBreaker);

        return $client;
    }

    /**
     * Sync announcements from the foreign site, removing all stale ones.
     */
    public function syncAnnouncements(): array
    {
        $params = [
            "sort" => "-dateInserted",
            "limit" => 100,
            "expand" => "insertUser,postMeta",
        ];

        $categoryID = $this->config->get(self::CONF_CATEGORY_ID, null);
        if ($categoryID !== null) {
            $params["categoryID"] = $categoryID;
        }

        $announcements = $this->httpClient()
            ->get("/api/v2/discussions", $params)
            ->getBody();

        $enabledFeatureIDs = $this->featureService->getEnabledFeatureIDs();
        $applicationVersion = $this->ownSite->getApplicationVersion();
        // Trim off snapshot numbers
        $applicationVersion = str_replace("-SNAPSHOT", "", $applicationVersion);

        $finalForeignIDs = [];
        $this->database->runWithTransaction(function () use (
            $announcements,
            $enabledFeatureIDs,
            $applicationVersion,
            &$finalForeignIDs
        ) {
            foreach ($announcements as $announcement) {
                $targetEnabledFeatureIDs = array_filter(
                    $announcement["postMeta"]["enabled-feature"] ?? ["All"],
                    fn($featureID) => $featureID !== "All"
                );
                $targetDisabledFeatureIDs = array_filter(
                    $announcement["postMeta"]["disabled-feature"] ?? ["None"],
                    fn($featureID) => $featureID !== "None"
                );

                // Check if we match all the enabled featureIDs and none of the disabled featureIDs
                $matchesAllEnabledFeatureIDs =
                    count(array_intersect($targetEnabledFeatureIDs, $enabledFeatureIDs)) ===
                    count($targetEnabledFeatureIDs);

                $doesNotMatchAnyDisabledFeatureIDs =
                    count(array_intersect($targetDisabledFeatureIDs, $enabledFeatureIDs)) === 0;
                if (!$matchesAllEnabledFeatureIDs || !$doesNotMatchAnyDisabledFeatureIDs) {
                    // This announcement doesn't matter to this site.
                    continue;
                }

                // Now filter versions if they are present
                $targetVersions = array_filter(
                    $announcement["postMeta"]["version"] ?? ["All"],
                    fn(string $version) => $version !== "All"
                );

                if (!empty($targetVersions)) {
                    $matchesVersion = false;
                    foreach ($targetVersions as $targetVersion) {
                        if ($targetVersion === $applicationVersion) {
                            $matchesVersion = true;
                            break;
                        }
                    }
                    if (!$matchesVersion) {
                        // This announcement doesn't matter to this site.
                        continue;
                    }
                }

                $foreignID = "discussion_{$announcement["discussionID"]}";
                $finalForeignIDs[] = $foreignID;
                $message = [
                    "productMessageID" => $foreignID,
                    "productMessageType" => self::TYPE_ANNOUNCEMENT,
                    "name" => $announcement["name"],
                    "body" => $announcement["body"],
                    "format" => "foreign",
                    "dateInserted" => $announcement["dateInserted"],
                    "foreignInsertUser" => $announcement["insertUser"],
                    "foreignUrl" => $announcement["url"],
                    "ctaLabel" => $announcement["postMeta"]["link-label"] ?? null,
                    "ctaUrl" => $announcement["postMeta"]["link"] ?? null,
                ];

                $this->insert(
                    $message,
                    options: [
                        Model::OPT_REPLACE => true,
                    ]
                );
            }

            // Delete all announcements that are not in the final list
            $this->delete([
                "productMessageType" => self::TYPE_ANNOUNCEMENT,
                "productMessageID <>" => $finalForeignIDs,
            ]);
        });

        return [
            "countSynced" => count($finalForeignIDs),
        ];
    }

    /**
     * @param array $rows
     */
    protected function normalizeRowsImpl(array &$rows): void
    {
        foreach ($rows as &$row) {
            if ($row["format"] !== "foreign") {
                $row["body"] = $this->formatService->renderHTML($row["body"], $row["format"]);
            }
        }
    }
}
