<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use CategoryModel;
use Exception;
use Garden\Container\ContainerException;
use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\ApiUtils;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Models\ModelCache;
use Vanilla\SchemaFactory;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\ModelUtils;

/**
 * Model for managing the layout view table.
 */
class LayoutViewModel extends FullRecordCacheModel
{
    //region Properties
    private const TABLE_NAME = "layoutView";

    public const FILE_RECORD_TYPE = "file";
    //endregion

    /** @var array<LayoutViewRecordProviderInterface> */
    private array $recordProviders = [];

    private \Gdn_Cache $cache;

    //region Constructor
    /**
     * DI Constructor.
     *
     * @param CurrentUserFieldProcessor $userFields
     * @param CurrentDateFieldProcessor $dateFields
     * @param \Gdn_Cache $cache
     */
    public function __construct(
        CurrentUserFieldProcessor $userFields,
        CurrentDateFieldProcessor $dateFields,
        \Gdn_Cache $cache
    ) {
        $this->cache = $cache;
        parent::__construct(self::TABLE_NAME, $cache);
        $userFields->camelCase();
        $this->addPipelineProcessor($userFields);
        $dateFields->camelCase();
        $this->addPipelineProcessor($dateFields);
    }

    /**
     * @return LayoutHydrator
     */
    private function layoutHydrator(): LayoutHydrator
    {
        return \Gdn::getContainer()->get(LayoutHydrator::class);
    }

    //endregion

    //region Public Methods
    /**
     * @param LayoutViewRecordProviderInterface $provider
     */
    public function addLayoutRecordProvider(LayoutViewRecordProviderInterface $provider)
    {
        $types = $provider::getValidRecordTypes();
        foreach ($types as $type) {
            $this->recordProviders[$type] = $provider;
        }
    }

    /**
     * Get a list of Layout Views by a provided Layout ID
     *
     * @param int|string $layoutID ID of the layout
     * @return array
     */
    public function getViewsByLayoutID($layoutID): array
    {
        $rows = $this->select(["layoutID" => $layoutID]);
        return $rows;
    }

    /**
     * Get a list of Layout Views by a provided Layout IDs
     *
     * @param array $layoutIDs IDs of the layout
     * @return array
     */
    public function getViewsByLayoutIDs(array $layoutIDs): array
    {
        $rows = $this->select(["layoutID" => $layoutIDs]);
        return $rows;
    }

    /**
     * Save Layout Views, and process tests.
     *
     * @param array $body Layout view data to save.
     * @param string $layoutViewType Layout type.
     * @param string $layoutID Layout ID.
     *
     * @return array
     * @throws NotFoundException
     */
    public function saveLayoutViews(array $body, string $layoutViewType, string $layoutID): array
    {
        $layoutViewIDs = [];
        try {
            $this->database->beginTransaction();

            $this->delete([
                "layoutID" => $layoutID,
            ]);
            /** @var EventManager $eventManager */
            $eventManager = Gdn::getContainer()->get(EventManager::class);
            $eventManager->fire("saveLayoutViews", $body);

            foreach ($body as $record) {
                $record["layoutViewType"] = $layoutViewType;
                $record["layoutID"] = $layoutID;
                // If we want to assign a layoutView to a category, we verify if that category exists.
                if (!$this->validateRecordExists($record["recordType"], [$record["recordID"]])) {
                    throw new NotFoundException($record["recordType"]);
                }

                if ($record["recordType"] === GlobalLayoutRecordProvider::RECORD_TYPE) {
                    // Clear out "root" and global record types as well. This is a holdover from the legacy system.
                    $this->delete([
                        "layoutViewType" => $record["layoutViewType"],
                        "recordType" => ["root", GlobalLayoutRecordProvider::RECORD_TYPE],
                    ]);
                }

                // only 1 layout can be assigned to layoutViewType/recordType/recordID
                $this->delete([
                    "layoutViewType" => $record["layoutViewType"],
                    "recordType" => $record["recordType"],
                    "recordID" => $record["recordID"],
                ]);
                $layoutViewIDs[] = $this->insert($record);
            }
            $this->database->commitTransaction();
        } catch (Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
        return $layoutViewIDs;
    }

    /**
     * Get a list of Layout Views by a provided viewType, recordType and or record ID
     *
     * @param bool $allowNull Are we iterating with values allowed to be null
     * @param string $layoutViewType view type
     * @param string|null $recordType record Type
     * @param int|null $recordID ID of the record
     *
     * @return array
     * @throws ValidationException
     */
    public function getLayoutViews(
        bool $allowNull,
        string $layoutViewType,
        string $recordType = null,
        int $recordID = null
    ): array {
        $where = [];
        if ($layoutViewType != null) {
            $where["layoutViewType"] = $layoutViewType;
        }
        if ($recordType != null) {
            $where["recordType"] = $recordType;
        }
        if ($recordID != null) {
            $where["recordID"] = $recordID;
        }
        try {
            $row = $this->selectSingle($where);
        } catch (NoResultsException $e) {
            $row = [];
        }

        if (count($row) == 0 && $allowNull) {
            if ($recordID != null) {
                $row = $this->getLayoutViews($allowNull, $layoutViewType, $recordType);
            } elseif ($recordType != null) {
                $row = $this->getLayoutViews($allowNull, $layoutViewType);
            }
        }
        return $row;
    }

    /**
     * Normalize a layout View record from the database into API output.
     *
     * @param array $row Layout View record from database row
     * @param array|string|bool $expand Additional parameters used to expand output based on row property values
     * @return array Normalized row
     * @throws NotFoundException
     */
    public function normalizeRow(array $row, $expand = false): array
    {
        if (ModelUtils::isExpandOption("record", $expand)) {
            $row["record"] = $this->getRecords($row["recordType"], [$row["recordID"]]);
        }
        return $row;
    }

    /**
     * Normalize a layout View records from the database into API output.
     *
     * @param array $rows Layout View records from database rows
     * @param array|string|bool $expand Additional parameters used to expand output based on row property values
     * @return array Normalized row
     * @throws NotFoundException
     */
    public function normalizeRows(array $rows, $expand): array
    {
        $recordViews = [];
        if (ModelUtils::isExpandOption("record", $expand)) {
            $ids = [];
            //Extract record ID/type and combine them
            array_map(function (array $row) use (&$ids) {
                if (!array_key_exists($row["recordType"], $ids)) {
                    $ids[$row["recordType"]] = [];
                }
                array_push($ids[$row["recordType"]], $row["recordID"]);
            }, $rows);
            // Query recordIDs for each record type.
            foreach ($ids as $recordType => $idList) {
                try {
                    $recordViews[$recordType] = $this->getRecords($recordType, $idList);
                } catch (Exception $e) {
                }
            }
        }

        $rows = array_map(function (array $row) use ($recordViews, $expand) {
            if (ModelUtils::isExpandOption("record", $expand)) {
                // Add expand record types to the request row.
                if (array_key_exists($row["recordType"], $recordViews)) {
                    $typeRecords = $recordViews[$row["recordType"]];
                    if (array_key_exists($row["recordID"], $typeRecords)) {
                        $row["record"] = $typeRecords[$row["recordID"]];
                    }
                }
            }
            return $row;
        }, $rows);

        return $rows;
    }

    public function getRecordProvider(string $recordType): LayoutViewRecordProviderInterface
    {
        $provider = $this->recordProviders[$recordType] ?? null;
        if (!$provider) {
            throw new NotFoundException("Could not find layout view recordProvider for recordType: " . $recordType);
        }

        return $provider;
    }

    /**
     * Translating recordIDs to record information
     *
     * @param string $recordType Record Type
     * @param array $recordIDs Record IDs
     * @return array formatted [id => ['Name'=> , 'URL' => ]]
     * @throws NotFoundException Throws exception when provider not found.
     */
    public function getRecords(string $recordType, array $recordIDs): array
    {
        return $this->getRecordProvider($recordType)->getRecords($recordIDs);
    }

    /**
     * Get a Layout ID and an updated query by a provided viewType, recordType and record ID.
     *
     * This more than a direct lookup and will attempt to resolve dynamic parts of the query.
     * Additionally, it will attempt to search parent records if the query cannot be found.
     *
     * Actually resolution is delegated to implementations of {@link LayoutViewRecordProviderInterface}
     *
     * For example, if looking up LayoutQuery("discussion", "discussion", 56) this will perform the following resolutions:
     * - Ensure the discussion exists.
     * - Resolve the type of discussionThread Eg. (discussionThread, questionThread, or ideaThread)
     * - Look for layoutView assignements on the discussions category.
     * - Look for layoutView assignements on the siteSection/subcommunity
     * - Look for a default layout assignemnt (global)
     * - Fallback to the layout template.
     *
     * @param LayoutQuery $query
     *
     * @return array{string, LayoutQuery} LayoutID and updated query
     *
     * @throws NotFoundException In case layout ID is not found based on provided parameters.
     */
    public function queryLayout(LayoutQuery $query): array
    {
        $initialQuery = $query;
        $currentDepth = 0;
        while (true) {
            $currentDepth++;
            if ($currentDepth > 10) {
                // Sanity check to prevent infinite loop.
                // Something is clearly wrong so lets log that.
                ErrorLogger::error("Infinite loop detected while querying layout ID", [
                    "initialQuery" => $initialQuery,
                ]);
                return ["layoutID" => $this->queryDefaultTemplateID($query), "query" => (array) $query];
            }

            // Perform initial resolution/normalization of the query.
            $query = $this->resolveLayoutQuery($query);

            try {
                // See if a layout is assigned to a specific page.
                $row = $this->selectSingle([
                    "layoutViewType" => $query->layoutViewType,
                    "recordType" => $query->recordType,
                    "recordID" => $query->recordID,
                ]);

                return [$row["layoutID"], $query];
            } catch (NoResultsException $e) {
                // Not a big deal necessarily. Try and get the parent.
                $query = $this->resolveParentLayoutQuery($query);

                if ($query->recordType == self::FILE_RECORD_TYPE) {
                    // If we've reached the "file" record type, we've made it to the top level.
                    // At this point no matching layout was assigned in the database
                    // So we should just fall back to the layout template.
                    $fileLayoutView = $this->layoutHydrator()->getLayoutViewType($query->layoutViewType);
                    return [$fileLayoutView->getTemplateID(), $query];
                }

                // Loop again with the new parent query.
                continue;
            }
        }
    }

    /**
     * Get the default templateID for a layoutViewType.
     *
     * @param LayoutQuery $query
     * @return string
     */
    private function queryDefaultTemplateID(LayoutQuery $query): string
    {
        // If we've reached the "file" record type, we've made it to the top level.
        // At this point no matching layout was assigned in the database
        // So we should just fall back to the layout template.
        $fileLayoutView = $this->layoutHydrator()->getLayoutViewType($query->layoutViewType);
        return $fileLayoutView->getTemplateID();
    }

    /**
     * Given a layout query, perform any resolutions that should be done before looking up the layout is looked up.
     *
     * @param LayoutQuery $query A layout query
     *
     * @return LayoutQuery
     * @throws NotFoundException
     */
    private function resolveLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        return $this->getRecordProvider($query->recordType)->resolveLayoutQuery($query);
    }

    /**
     * Given a layout query that we couldn't find a layout for, generate a parent layout query.
     *
     * @param LayoutQuery $query A layout query
     *
     * @return LayoutQuery
     * @throws NotFoundException
     */
    private function resolveParentLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        return $this->getRecordProvider($query->recordType)->resolveParentLayoutQuery($query);
    }

    /**
     * We verify if that category exists.
     *
     * @param string $recordType
     * @param array $recordIDs
     *
     * @return bool
     */
    public function validateRecordExists(string $recordType, array $recordIDs): bool
    {
        return $this->getRecordProvider($recordType)->validateRecords($recordIDs);
    }

    /**
     * Structure the table schema.
     *
     * @param \Gdn_Database $database Database handle
     * @param bool $explicit Optional, true to remove any columns that are not specified here,
     * false to retain those columns. Default false.
     * @param bool $drop Optional, true to drop table if it already exists,
     * false to retain table if it already exists. Default false.
     * @throws Exception
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $construct = $database->structure();
        $sql = $database->sql();
        $construct
            ->table(self::TABLE_NAME)
            ->primaryKey("layoutViewID")
            ->column("layoutID", "varchar(100)", false, ["index.layoutIDIndex"])
            ->column("recordID", "int", 0, ["unique.record"])
            ->column("layoutViewType", "varchar(100)", false, ["unique.record"])
            ->column("recordType", "varchar(100)", false, ["unique.record"])
            ->column("insertUserID", "int", false)
            ->column("dateInserted", "datetime", false)
            ->column("updateUserID", "int", null)
            ->column("dateUpdated", "datetime", null)
            ->set($explicit, $drop);

        // Older version inject a "global" layoutViewType. It didn't do anything but screws up the UI a bit in the admin section.
        // We remove it here.
        // The fallback to the file-based layouts is dynamic and doesn't need layoutView records.
        $oldDefaultView = $sql
            ->getWhere(self::TABLE_NAME, [
                "recordID" => 0,
                "recordType" => "global",
                "layoutViewType" => "global",
            ])
            ->firstRow(DATASET_TYPE_ARRAY);

        if ($oldDefaultView) {
            $viewModel = \Gdn::getContainer()->get(LayoutViewModel::class);
            $viewModel->delete(["layoutViewID" => $oldDefaultView["layoutViewID"]]);
        }
    }

    /**
     * Migrate configs for the 2023.019 release.
     *
     * @param \Gdn_Configuration $config
     * @param \Gdn_Database $database Database handle
     *
     */
    public static function clearCategoryLayouts_2023_019(\Gdn_Configuration $config, \Gdn_Database $database): void
    {
        $sql = $database->sql();
        $hasAny = $sql
            ->select(self::TABLE_NAME, [
                "layoutViewType" => "categoryList",
                "recordType" => "category",
            ])
            ->whereCount();
        if (
            ($config->get("Feature.LayoutEditor.nestedCategoryList.Enabled", true) ||
                $config->get("Feature.LayoutEditor.discussionCategoryPage.Enabled", true)) &&
            $hasAny
        ) {
            $sql->delete(self::TABLE_NAME, [
                "layoutViewType" => "categoryList",
                "recordType" => "category",
            ]);
        }
    }

    /**
     * @return Schema
     */
    public static function getInputSchema(): Schema
    {
        return SchemaFactory::parse(
            ["layoutID:i|s", "expand?" => ApiUtils::getExpandDefinition(["record"])],
            "LayoutView"
        );
    }

    /**
     * @return Schema
     */
    public static function getSchema(): Schema
    {
        return SchemaFactory::parse(
            [
                "layoutViewID:i",
                "layoutID:i|s",
                "recordID:i",
                "recordType:s",
                "layoutViewType:s",
                "insertUserID:i",
                "dateInserted:dt",
                "record:o?",
            ],
            "LayoutView"
        );
    }
    //endregion
}
