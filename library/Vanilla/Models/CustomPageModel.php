<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Models;

use Garden\EventManager;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\RequestInterface;
use Garden\Web\Exception\ClientException;
use Vanilla\Contracts\Models\CrawlableInterface;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\ResourceEventProcessor;
use Vanilla\Events\CustomPageEvent;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Layout\CustomPageLayoutRecordProvider;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Layout\View\CustomPageLayoutView;
use Vanilla\Logging\ErrorLogger;
use Vanilla\SchemaFactory;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionModel;

class CustomPageModel extends FullRecordCacheModel implements CrawlableInterface
{
    use PrimaryKeyCrawlInfoTrait;

    const STATUS_UNPUBLISHED = "unpublished";

    const STATUS_PUBLISHED = "published";

    const ALL_STATUSES = [self::STATUS_UNPUBLISHED, self::STATUS_PUBLISHED];

    const RESERVED_URL_PATHS = ["/api", "/entry", "/sso", "/utility", "/dashboard", "/settings", "/appearance"];

    const URL_LENGTH = 100;

    const CAN_VIEW_NO_RESTRICTIONS = 2;

    const MAX_CUSTOM_PAGE_COUNT = 500;

    public function __construct(
        \Gdn_Cache $cache,
        private \RoleModel $roleModel,
        private EventManager $eventManager,
        private LayoutModel $layoutModel,
        private LayoutViewModel $layoutViewModel,
        private SiteSectionModel $siteSectionModel,
        private \UserModel $userModel,
        private \Gdn_Session $session,
        ResourceEventProcessor $resourceEventProcessor
    ) {
        parent::__construct("customPage", $cache, [
            \Gdn_Cache::FEATURE_EXPIRY => 60 * 60,
        ]);
        $this->addPipelineProcessor(new JsonFieldProcessor(["roleIDs"], 0));
        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]));
        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
        $resourceEventProcessor->setResourceEventClass(CustomPageEvent::class);
        $this->addPipelineProcessor($resourceEventProcessor);
    }

    /**
     * Get standard schema for custom page response.
     *
     * @return Schema
     */
    public function schema(): Schema
    {
        return SchemaFactory::parse(
            ["customPageID", "seoTitle", "seoDescription", "urlcode", "status", "siteSectionID", "roleIDs", "layoutID"],
            "customPage"
        );
    }

    /**
     * Return schema for validating post/patch.
     *
     * AIDEV-NOTE: Unique URL validator uses validation->addError() to ensure proper "urlcode" field attribution in API responses
     *
     * @param int|null $id Custom page ID if this is a patch.
     * @return Schema
     */
    public function commonPostPatchSchema(?int $id = null): Schema
    {
        return SchemaFactory::parse(
            [
                "seoTitle:s",
                "seoDescription:s",
                "urlcode:s" => ["minLength" => 1, "maxLength" => self::URL_LENGTH],
                "status:s?" => ["enum" => CustomPageModel::ALL_STATUSES],
                "siteSectionID:s?" => ["default" => DefaultSiteSection::DEFAULT_ID],
                "roleIDs:a?" => "i",
                "layoutData:o" => $id
                    ? $this->layoutModel->getPatchSchema()
                    : $this->layoutModel->getCreateSchema([CustomPageLayoutView::VIEW_TYPE]),
            ],
            "customPageCommonPostPatch"
        )
            ->addFilter("layoutData", function ($layoutData) {
                $layoutData["layoutViewType"] = CustomPageLayoutView::VIEW_TYPE;
                return $layoutData;
            })
            ->addFilter("urlcode", $this->createNormalizedUrlFilter())
            ->addValidator("", $this->createUniqueUrlValidator($id))
            ->addValidator("urlcode", $this->createRestrictedUrlValidator())
            ->addValidator("roleIDs", [$this->roleModel, "roleIDsValidator"])
            ->addValidator("siteSectionID", function ($siteSectionID, ValidationField $field) {
                if (null === $this->siteSectionModel->getByID($siteSectionID)) {
                    $field->addError("Site section does not exist");
                    return Invalid::value();
                }
                return true;
            });
    }

    /**
     * Returns true if the current user has permission to view the given custom page record.
     *
     * @param array $customPage
     * @return bool
     */
    public function canViewCustomPage(array $customPage): bool
    {
        if (\Gdn::session()->checkPermission("site.manage")) {
            // Users with this permission can always view custom pages.
            return true;
        }

        if ($customPage["status"] !== CustomPageModel::STATUS_PUBLISHED) {
            return false;
        }

        // Fire an event to check if addons (i.e. ranks) allow the current user to view the custom page.
        // Addons can return either CustomPageModel::CAN_VIEW_NO_RESTRICTIONS if there are no restrictions,
        // or true/false if there are restrictions and if the current user satisfies them.
        $flags = $this->eventManager->fire("customPageModel_canViewCustomPage", $customPage);

        // Check by role and add the result to the array.
        $flags[] = $this->canViewByRole($customPage);

        return $this->checkCanViewFlags($flags);
    }

    /**
     * Check result of customPageModel_canViewCustomPage event dispatch.
     *
     * @param array $flags
     * @return bool
     */
    private function checkCanViewFlags(array $flags): bool
    {
        // Filter flags by only unrestricted flags
        $unrestrictedFlags = array_filter($flags, function ($flag) {
            return $flag === self::CAN_VIEW_NO_RESTRICTIONS;
        });

        // If all addons return unrestricted, the user can view the page.
        if (count($unrestrictedFlags) === count($flags)) {
            return true;
        }

        // Otherwise, only one addon needs to allow with explicit true value.
        if (in_array(true, $flags, true)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the current user has the proper role to view the given page.
     *
     * @param array $customPage
     * @return bool|int
     */
    private function canViewByRole(array $customPage): bool|int
    {
        $currentUserRoleIDs = $this->userModel->getRoleIDs($this->session->UserID);
        if (empty($customPage["roleIDs"])) {
            return self::CAN_VIEW_NO_RESTRICTIONS;
        }
        return !empty(array_intersect($currentUserRoleIDs, $customPage["roleIDs"]));
    }

    /**
     * Filter to normalize URLs.
     *
     * @return callable
     */
    private function createNormalizedUrlFilter(): callable
    {
        return function ($url) {
            // Make sure we are saving only the path and ignore protocol, domain, etc.
            $url = parse_url(trim($url), PHP_URL_PATH);

            // Replace repeating slashes with single slash.
            $url = preg_replace("~/{2,}~", "/", $url);

            // Make sure the URL has one leading slash and no trailing slash.
            return "/" . trim($url, "/");
        };
    }

    /**
     * Validator to validate that the chosen urlcode/siteSection is unique.
     *
     * @param int|null $id Existing custom page ID if this is a patch.
     * @return callable
     */
    private function createUniqueUrlValidator(?int $id = null): callable
    {
        return function ($data, ValidationField $field) use ($id) {
            $validation = $field->getValidation();

            $where = [];

            // For updates, exclude the current page from uniqueness check
            if (isset($id)) {
                $where["customPageID <>"] = $id;

                try {
                    $existingPage = $this->getCustomPage($id);
                    if ($existingPage === null) {
                        // Add error to urlcode field specifically
                        $validation->addError("urlcode", "Custom page not found for update.");
                        return Invalid::value();
                    }

                    // Use new values if provided, otherwise keep existing values
                    $checkUrlcode = $data["urlcode"] ?? $existingPage["urlcode"];
                    $checkSiteSectionID = $data["siteSectionID"] ?? $existingPage["siteSectionID"];
                } catch (\Exception $e) {
                    $validation->addError("urlcode", "Error retrieving existing custom page for validation.");
                    return Invalid::value();
                }
            } else {
                // For new pages
                if (!isset($data["urlcode"])) {
                    $validation->addError("urlcode", "URL code is required.");
                    return Invalid::value();
                }
                $checkUrlcode = $data["urlcode"];
                $checkSiteSectionID = $data["siteSectionID"] ?? DefaultSiteSection::DEFAULT_ID;
            }

            $where["urlcode"] = $checkUrlcode;
            $where["siteSectionID"] = $checkSiteSectionID;

            $result = $this->select($where, [Model::OPT_LIMIT => 1]);
            if (!empty($result)) {
                $siteSection = $this->siteSectionModel->getByID($checkSiteSectionID);
                $fullUrl = $siteSection ? $siteSection->getBasePath() . $checkUrlcode : $checkUrlcode;
                // Add error to urlcode field specifically
                $validation->addError(
                    "urlcode",
                    "A custom page with the URL '{$fullUrl}' already exists in this site section."
                );
                return Invalid::value();
            }
            return true;
        };
    }

    /**
     * Validator to check that the URL does not start with a restricted path.
     *
     * @return callable
     */
    private function createRestrictedUrlValidator(): callable
    {
        return function ($url, ValidationField $field) {
            if ($url === "/") {
                $field->addError("URL code cannot be the root path '/'.");
                return Invalid::value();
            }

            $reservedPaths = self::RESERVED_URL_PATHS;
            foreach ($reservedPaths as $path) {
                if (str_starts_with("$url/", "$path/")) {
                    $field->addError(
                        "URL '{$url}' cannot start with the reserved path '{$path}'. Reserved paths are: " .
                            implode(", ", $reservedPaths)
                    );
                    return Invalid::value();
                }
            }
            return true;
        };
    }

    /**
     * Retrieve custom pages with layout IDs included.
     *
     * @param array $where
     * @param array $options
     * @return array
     */
    public function selectWithLayoutID(array $where = [], array $options = []): array
    {
        $options = $options + [
            self::OPT_SELECT => $this->schema(),
            self::OPT_JOINS => [
                [
                    "tableName" => "layoutView",
                    "on" =>
                        "layoutView.recordID = customPage.customPageID AND layoutView.recordType='customPage' AND layoutView.layoutViewType='customPage'",
                    "joinType" => "left",
                ],
            ],
        ];

        $rows = $this->select($where, $options);
        return $this->normalizeRows($rows);
    }

    /**
     * Apply normalization to record set.
     *
     * @param array $rows
     * @return array
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(function ($row) {
            $row["layoutID"] = isset($row["layoutID"]) ? (int) $row["layoutID"] : null;

            $siteSection = $this->siteSectionModel->getByID($row["siteSectionID"]);
            // AIDEV-NOTE: Use urlcode directly if site section doesn't exist (no base path)
            $url = ($siteSection !== null ? $siteSection->getBasePath() : "") . $row["urlcode"];
            $row["url"] = \Gdn::request()->getSimpleUrl($url);
            return $row;
        }, $rows);
    }

    /**
     * Retrieve single custom page with layout data included.
     *
     * @param int $customPageID
     * @return array|null
     */
    public function getCustomPage(int $customPageID): ?array
    {
        $rows = $this->selectWithLayoutID(["customPageID" => $customPageID], options: [self::OPT_LIMIT => 1]);
        $customPage = $rows[0] ?? null;

        if (isset($customPage)) {
            try {
                $customPage["layoutData"] = !empty($customPage["layoutID"])
                    ? $this->layoutModel->selectSingle(["layoutID" => $customPage["layoutID"]])
                    : null;
            } catch (NoResultsException $e) {
                $customPage["layoutData"] = null;
            }
        }

        return $customPage;
    }

    /**
     * Override to create corresponding layout.
     *
     * {@inheritdoc}
     */
    public function insert(array $set, array $options = []): int
    {
        return $this->database->runWithTransaction(function () use ($set, $options) {
            $layoutData = $set["layoutData"] ?? [];
            unset($set["layoutData"]);

            $customPageID = parent::insert($set, $options);

            $layoutID = $this->layoutModel->insert($layoutData);
            $this->layoutViewModel->saveLayoutViews(
                [["recordID" => $customPageID, "recordType" => "customPage"]],
                layoutViewType: "customPage",
                layoutID: $layoutID
            );

            return $customPageID;
        });
    }

    /**
     * Update custom page by its customPageID.
     *
     * @param int $customPageID
     * @param array $set
     * @return bool
     * @throws \Throwable
     */
    public function updateByID(int $customPageID, array $set = []): bool
    {
        return $this->database->runWithTransaction(function () use ($customPageID, $set) {
            $layoutData = $set["layoutData"] ?? null;
            unset($set["layoutData"]);

            $return = $this->update($set, ["customPageID" => $customPageID]);

            if (isset($layoutData)) {
                try {
                    $layoutView = $this->layoutViewModel->selectSingle([
                        "recordID" => $customPageID,
                        "recordType" => "customPage",
                        "layoutViewType" => "customPage",
                    ]);

                    $this->layoutModel->updateLayout($layoutView["layoutID"], $layoutData);
                } catch (NoResultsException $e) {
                    // This is if we can't find the layout view. It's ok but let's log it.
                    ErrorLogger::warning($e, ["customPages"]);
                }
            }

            return $return;
        });
    }

    /**
     * Delete custom page by its customPageID.
     *
     * @param int $customPageID
     * @return mixed
     * @throws \Throwable
     */
    public function deleteByID(int $customPageID)
    {
        return $this->database->runWithTransaction(function () use ($customPageID) {
            $return = $this->delete(["customPageID" => $customPageID]);

            $this->layoutViewModel->delete([
                "recordID" => $customPageID,
                "recordType" => CustomPageLayoutRecordProvider::RECORD_TYPE,
                "layoutViewType" => CustomPageLayoutView::VIEW_TYPE,
            ]);

            return $return;
        });
    }

    /**
     * Structure the custom page table.
     *
     * @param \Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop
     * @return void
     * @throws \Exception
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("customPage")
            ->primaryKey("customPageID")
            ->column("seoTitle", "varchar(100)")
            ->column("seoDescription", "mediumtext")
            ->column("urlcode", "varchar(" . self::URL_LENGTH . ")", keyType: "unique.urlcode_siteSectionID")
            ->column("status", self::ALL_STATUSES, nullDefault: self::STATUS_UNPUBLISHED, keyType: "index")
            ->column("siteSectionID", "varchar(64)", DefaultSiteSection::DEFAULT_ID, "unique.urlcode_siteSectionID")
            ->column("roleIDs", "json", true)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->set($explicit, $drop);
    }

    /**
     * Look up custom page by matching with the request.
     *
     * @param RequestInterface $request
     * @return array|null
     */
    public function lookupByRequest(RequestInterface $request): ?array
    {
        if (!\Gdn::structure()->tableExists("customPage")) {
            // Bypass if table doesn't exist yet such as for utility/update.
            return null;
        }
        try {
            return $this->selectSingle(
                [
                    "urlcode" => $request->getPath(),
                    "siteSectionID" => $this->siteSectionModel->getCurrentSiteSection()->getSectionID(),
                ],
                [self::OPT_LIMIT => 1]
            );
        } catch (NoResultsException $e) {
            return null;
        }
    }

    /**
     * Get a list of active custom pages with URL paths and IDs for pages that the current user can view.
     *
     * @return array Array of objects containing pageID and url for accessible custom pages
     */
    public function getActiveUrlPathsForUser(): array
    {
        if (!\Gdn::structure()->tableExists("customPage")) {
            // Bypass if table doesn't exist yet such as for utility/update.
            return [];
        }
        $hasManagePermission = \Gdn::session()->checkPermission("settings.manage");
        // Get custom pages based on user permissions
        $where = $hasManagePermission ? [] : ["status" => self::STATUS_PUBLISHED];
        $customPages = $this->selectWithLayoutID($where, [self::OPT_ORDER => ["siteSectionID", "urlcode"]]);

        // Filter by user permissions and build result array
        $result = [];
        foreach ($customPages as $customPage) {
            if ($this->canViewCustomPage($customPage)) {
                $result[] = [
                    "recordID" => $customPage["customPageID"],
                    "urlcode" => $customPage["urlcode"],
                ];
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCrawlInfo(): array
    {
        $r = $this->getCrawlInfoFromPrimaryKey("/api/v2/custom-pages?expand=crawl", "customPageID");

        return $r;
    }

    /**
     * @inheritdoc
     */
    public function getTableName(): string
    {
        return "customPage";
    }

    /**
     * Override to use queryTotalCount instead of table status.
     *
     * @return int
     */
    protected function getTotalRowCount(): int
    {
        return $this->queryTotalCount();
    }

    /**
     * @return int
     */
    public function queryTotalCount(): int
    {
        return $this->modelCache->getCachedOrHydrate(
            [__FUNCTION__],
            fn() => $this->createSql()
                ->from("customPage")
                ->getPagingCount("CustomPageID")
        );
    }
}
