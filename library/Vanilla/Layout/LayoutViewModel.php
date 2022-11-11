<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Exception;
use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;
use Vanilla\Models\FullRecordCacheModel;
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

    /** @var LayoutHydrator $layoutHydrator */
    private $layoutHydrator;

    /** @var array A mapping of recordType to breadcrumb provider. */
    private $providers = [];

    //region Constructor
    /**
     * DI Constructor.
     *
     * @param CurrentUserFieldProcessor $userFields
     * @param CurrentDateFieldProcessor $dateFields
     * @param LayoutHydrator $layoutHydrator,
     * @param \GDN_Cache $cache
     */
    public function __construct(
        CurrentUserFieldProcessor $userFields,
        CurrentDateFieldProcessor $dateFields,
        LayoutHydrator $layoutHydrator,
        \GDN_Cache $cache
    ) {
        parent::__construct(self::TABLE_NAME, $cache);
        $userFields->camelCase();
        $this->addPipelineProcessor($userFields);
        $dateFields->camelCase();
        $this->addPipelineProcessor($dateFields);
        $this->layoutHydrator = $layoutHydrator;
    }

    //endregion

    //region Public Methods
    /**
     * @param LayoutViewRecordProviderInterface $provider
     */
    public function addProvider(LayoutViewRecordProviderInterface $provider)
    {
        $types = $provider::getValidRecordTypes();
        foreach ($types as $type) {
            $this->providers[$type] = $provider;
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
     * @return int
     */
    public function saveLayoutViews(array $body, string $layoutViewType, string $layoutID): array
    {
        $layoutViewIDs = [];
        try {
            $this->database->beginTransaction();

            $globalBody = GlobalRecordProvider::getRecordTypeAndID();
            $rootBody = RootRecordProvider::getRecordTypeAndID();

            // A layout can't be applied to both "All Homepages & "Site Homepage" at once.
            if (in_array($globalBody, $body) && in_array($rootBody, $body)) {
                throw new Exception(t('A layout can\'t be applied to both "All Homepages" & "Site Homepage".'), 403);
            }

            $this->delete([
                "layoutID" => $layoutID,
            ]);

            foreach ($body as $record) {
                $record["layoutViewType"] = $layoutViewType;
                $record["layoutID"] = $layoutID;
                // If we want to assign a layoutView to a category, we verify if that category exists.
                if (!$this->validateRecordExists($record["recordType"], [$record["recordID"]])) {
                    throw new NotFoundException($record["recordType"]);
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
     * Get a Layout ID by a provided viewType, recordType and record ID
     *
     * @param string $layoutViewType View type
     * @param string $recordType Record Type
     * @param string $recordID ID of the record
     *
     * @return string
     * @throws NotFoundException In case layout ID is not found based on provided parameters.
     */
    public function getLayoutIdLookup(string $layoutViewType, string $recordType, string $recordID): string
    {
        // Get a specific page's `WHERE` statement for GDN_layoutView.
        $where = $this->getProviderLayoutIdLookupParams($layoutViewType, $recordType, $recordID);

        try {
            // See if a layout is assigned to a specific page.
            $row = $this->selectSingle($where);
        } catch (NoResultsException $e) {
            [$recordType, $recordID] = $this->getParentRecordTypeAndID($where["recordType"], $where["recordID"]);
            //Reached the top, only option left is file based layout.
            if ($recordType == self::FILE_RECORD_TYPE) {
                $fileLayoutView = $this->layoutHydrator->getLayoutViewType($layoutViewType);
                return $fileLayoutView->getLayoutID();
            }

            return $this->getLayoutIdLookup($layoutViewType, $recordType, $recordID);
        }

        return $row["layoutID"];
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
     */
    public function getLayoutViews(
        bool $allowNull,
        string $layoutViewType,
        string $recordType = null,
        int $recordID = null
    ): array {
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
                $recordViews[$recordType] = $this->getRecords($recordType, $idList);
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
        $provider = $this->providers[$recordType] ?? null;
        if (!$provider) {
            throw new NotFoundException($recordType . " provider could not be found");
        }
        return $provider->getRecords($recordIDs);
    }

    /**
     * Adapt parameters prior to calling layoutViewModel::getLayoutIdLookup(), if needed.
     *
     * @param string $layoutViewType layoutViewType.
     * @param string $recordType recordType.
     * @param string $recordID recordID.
     * @return array
     * @throws \Garden\Container\ContainerException Container Exception.
     * @throws \Garden\Container\NotFoundException Container not found exception.
     */
    public function getProviderLayoutIdLookupParams(string $layoutViewType, string $recordType, string $recordID): array
    {
        // If the recordID is non-numeric(a slug), we see if we can rely on the SiteSectionModel to provide
        // alternative parameters for getLayOutIdLookup().
        if (!is_numeric($recordID) || $recordType == "siteSection") {
            $model = \Gdn::getContainer()->get(SiteSectionModel::class);
            $section = $model->getByID($recordID);

            if ($section && method_exists($section, "getLayoutIdLookupParams")) {
                return $section->getLayoutIdLookupParams($layoutViewType, $recordType, $recordID);
            }
        }
        return ["layoutViewType" => $layoutViewType, "recordType" => $recordType, "recordID" => $recordID];
    }

    /**
     * Get parent element for the layout hierarchy lookup.
     *
     * @param string $recordType Record Type.
     * @param string $recordID Record ID.
     * @return array
     * @throws NotFoundException
     */
    public function getParentRecordTypeAndID(string $recordType, string $recordID): array
    {
        $provider = $this->providers[$recordType] ?? null;
        if (!$provider) {
            throw new NotFoundException($recordType . " provider could not be found");
        }
        return $provider->getParentRecordTypeAndID($recordType, $recordID);
    }

    /**
     * We verify if that category exists.
     *
     * @param string $recordType Record Type
     * @param array $recordIDs Record IDs
     * @throws NotFoundException Throws exception if there is no provider for the requested recordType.
     */
    public function validateRecordExists(string $recordType, array $recordIDs): bool
    {
        $provider = $this->providers[$recordType] ?? null;
        if (!$provider) {
            throw new NotFoundException($recordType . " provider could not be found");
        }
        return $provider->validateRecords($recordIDs);
    }

    /**
     * Structure the table schema.
     *
     * @param \Gdn_Database $database Database handle
     * @param bool $explicit Optional, true to remove any columns that are not specified here,
     * false to retain those columns. Default false.
     * @param bool $drop Optional, true to drop table if it already exists,
     * false to retain table if it already exists. Default false.
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
        $default = [
            "layoutViewID" => 1,
            "layoutID" => "home",
            "recordID" => 0,
            "recordType" => "global",
            "layoutViewType" => "global",
            "insertUserID" => 2,
            "dateInserted" => date("Y-m-d H:i:s"),
            "updateUserID" => 2,
            "dateUpdated" => date("Y-m-d H:i:s"),
        ];

        $insertDefault =
            !$database->structure()->tableExists(self::TABLE_NAME) ||
            $sql->getWhere(self::TABLE_NAME, ["layoutViewID" => 1])->numRows() == 0;

        if ($insertDefault) {
            $sql->insert(self::TABLE_NAME, $default);
        }

        $fixDefault =
            $database->structure()->tableExists(self::TABLE_NAME) &&
            $sql->getWhere(self::TABLE_NAME, ["layoutViewID" => 1, "layoutID" => 1, "recordID" => 0])->numRows() == 1;

        if ($fixDefault) {
            $sql->update(self::TABLE_NAME, ["layoutID" => "home"], ["layoutViewID" => 1])->put();
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
