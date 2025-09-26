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
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\RequestInterface;
use Vanilla\Dashboard\Controllers\API\LayoutsApiController;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Select;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Layout\CustomPageLayoutRecordProvider;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Layout\View\CustomPageLayoutView;
use Vanilla\Logging\ErrorLogger;
use Vanilla\SchemaFactory;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

class CustomPageModel extends FullRecordCacheModel
{
    const FEATURE_FLAG = "customPages";

    const STATUS_UNPUBLISHED = "unpublished";

    const STATUS_PUBLISHED = "published";

    const ALL_STATUSES = [self::STATUS_UNPUBLISHED, self::STATUS_PUBLISHED];

    const RESERVED_URL_PATHS = ["/api", "/entry", "/sso", "/utility", "/dashboard", "/settings", "/appearance"];

    public function __construct(
        \Gdn_Cache $cache,
        private \RoleModel $roleModel,
        private EventManager $eventManager,
        private LayoutModel $layoutModel,
        private LayoutViewModel $layoutViewModel,
        private SiteSectionModel $siteSectionModel
    ) {
        parent::__construct("customPage", $cache, [
            \Gdn_Cache::FEATURE_EXPIRY => 60 * 60,
        ]);
        $this->addPipelineProcessor(new JsonFieldProcessor(["roleIDs"], 0));
        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]));
        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
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
     * @param int|null $id Custom page ID if this is a patch.
     * @return Schema
     */
    public function commonPostPatchSchema(?int $id = null): Schema
    {
        return SchemaFactory::parse(
            [
                "seoTitle:s",
                "seoDescription:s",
                "urlcode:s",
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
            ->addValidator("urlcode", $this->createRestrictedUrlValidator())
            ->addValidator("", $this->createUniqueUrlValidator($id))
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
        if (\Gdn::session()->checkPermission("settings.manage")) {
            // Users with this permission can always view custom pages.
            return true;
        }

        if ($customPage["status"] !== CustomPageModel::STATUS_PUBLISHED) {
            return false;
        }

        $currentUserRoles = \Gdn::userModel()->getRoleIDs(\Gdn::session()->UserID);
        if (!empty($customPage["roleIDs"]) && empty(array_intersect($currentUserRoles, $customPage["roleIDs"]))) {
            return false;
        }

        $allowedByAddons = $this->eventManager->fireFilter("customPageModel_canViewCustomPage", true, $customPage);
        if (!$allowedByAddons) {
            return false;
        }

        return true;
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
            $where = [];
            if (isset($id)) {
                $existingPage = $this->getCustomPage($id);
                $where["customPageID <>"] = $id;
                $where["urlcode"] = $existingPage["urlcode"];
                $where["siteSectionID"] = $existingPage["siteSectionID"];
            }
            if (isset($data["urlcode"])) {
                $where["urlcode"] = $data["urlcode"];
            }
            if (isset($data["siteSectionID"])) {
                $where["siteSectionID"] = $data["siteSectionID"];
            }

            $result = $this->select($where, [Model::OPT_LIMIT => 1]);
            if (!empty($result)) {
                $field->addError("A custom page with this URL already exists.");
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
            $reservedPaths = self::RESERVED_URL_PATHS;
            foreach ($reservedPaths as $path) {
                if (str_starts_with("$url/", "$path/")) {
                    $field->addError("URL cannot start with one of the following: " . implode(", ", $reservedPaths));
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
            $url = $siteSection->getBasePath() . $row["urlcode"];
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
     * Throw a client exception if the given layout is a custom page layout.
     * Mostly for preventing direct updates/deletes using layouts api endpoints.
     *
     * @param array $layout
     * @return void
     */
    public static function ensureNonCustomPageLayout(array $layout): void
    {
        if (($layout["layoutViewType"] ?? null) === CustomPageLayoutView::VIEW_TYPE) {
            throw new ClientException("Cannot modify or delete custom page layouts from here.");
        }
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
            ->column("urlcode", "varchar(100)", keyType: "unique.urlcode_siteSectionID")
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
}
