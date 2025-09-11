<?php

/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 */

namespace Vanilla\Dashboard\Scope;

use Vanilla\Dashboard\Scope\Models\ScopeModel;
use Vanilla\Site\SiteSectionModel;

/**
 * Helper class for managing scope operations across different record types.
 */
class ScopeHelper
{
    /**
     * ScopeHelper constructor.
     *
     * @param ScopeModel $scopeModel
     * @param \CategoryModel $categoryModel
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        private ScopeModel $scopeModel,
        private \CategoryModel $categoryModel,
        private SiteSectionModel $siteSectionModel
    ) {
    }

    /**
     * Apply scope settings to a record.
     *
     * @param string $recordType The type of record (e.g., 'tag', 'status')
     * @param int $recordID The record ID
     * @param array|null $scope The scope data
     * @return void
     */
    public function applyScopeToRecord(string $recordType, int $recordID, ?array $scope): void
    {
        // If no scope data is provided, nothing to do
        if ($scope === null) {
            return;
        }

        // Clear existing scope data and apply new scope data
        $this->clearScopeData($recordType, $recordID);
        $this->applyScopeData($recordType, $recordID, $scope);
    }

    /**
     * Apply scope data to a record.
     *
     * @param string $recordType The type of record
     * @param int $recordID The record ID
     * @param array $scope The scope data
     * @return void
     */
    private function applyScopeData(string $recordType, int $recordID, array $scope = []): void
    {
        $records = $this->buildScopeRecords($scope);

        // Add new scope relationships using ScopeModel
        foreach ($records as $record) {
            $this->scopeModel->insert([
                "recordType" => $recordType,
                "recordID" => $recordID,
                "scopeRecordType" => $record["scopeRecordType"],
                "scopeRecordID" => $record["scopeRecordID"],
                "relationType" => ScopeModel::RELATION_TYPE_SCOPE,
            ]);
        }
    }

    /**
     * Clear scope data for a record.
     *
     * @param string $recordType The type of record
     * @param int $recordID The record ID
     * @return void
     */
    public function clearScopeData(string $recordType, int $recordID): void
    {
        $this->scopeModel->delete([
            "recordType" => $recordType,
            "recordID" => $recordID,
            "relationType" => ScopeModel::RELATION_TYPE_SCOPE,
        ]);
    }

    /**
     * Build scope records from scope data.
     *
     * @param array $scope The scope data
     * @return array Array of scope records
     */
    private function buildScopeRecords(array $scope): array
    {
        $records = [];
        if (isset($scope["categoryIDs"]) && is_array($scope["categoryIDs"])) {
            foreach ($scope["categoryIDs"] as $categoryID) {
                $records[] = [
                    "scopeRecordType" => ScopeModel::SCOPE_RECORD_TYPE_CATEGORY,
                    "scopeRecordID" => (string) $categoryID,
                ];
            }
        }
        if (isset($scope["siteSectionIDs"]) && is_array($scope["siteSectionIDs"])) {
            foreach ($scope["siteSectionIDs"] as $siteSectionID) {
                $records[] = [
                    "scopeRecordType" => ScopeModel::SCOPE_RECORD_TYPE_SITE_SECTION,
                    "scopeRecordID" => $siteSectionID,
                ];
            }
        }
        return $records;
    }

    /**
     * Get scope data for multiple records.
     *
     * @param string $recordType The type of record
     * @param array $recordIDs Array of record IDs
     * @return array Array indexed by recordID, containing scope records for each record
     */
    public function getRecordsScope(string $recordType, array $recordIDs): array
    {
        // Get scope data all at once.
        $scopes = $this->scopeModel->select([
            "recordType" => $recordType,
            "recordID" => $recordIDs,
            "relationType" => ScopeModel::RELATION_TYPE_SCOPE,
        ]);

        $result = [];
        foreach ($scopes as $scope) {
            $recordID = $scope["recordID"];
            $recordTypeKey = match ($scope["scopeRecordType"]) {
                "category" => "categoryIDs",
                "siteSection" => "siteSectionIDs",
            };
            $result[$recordID]["scope"][$recordTypeKey][] = $scope["scopeRecordID"];
        }

        // Expand category IDs to include descendant categories that inherit from scoped categories
        foreach ($result as &$record) {
            if (!empty($record["scope"]["categoryIDs"])) {
                $originalCategoryIDs = $record["scope"]["categoryIDs"];
                $expandedCategoryIDs = $this->categoryModel->getCategoriesDescendantIDs($originalCategoryIDs);
                $record["scope"]["allowedCategoryIDs"] = $expandedCategoryIDs;
            }
        }

        return $result;
    }

    /**
     * Join scope information to a set of record rows.
     *
     * @param string $recordType The type of record
     * @param array $rows Array of record rows to attach scope data to
     * @param string $idColumn The ID column name
     * @return array The record rows with scope data attached
     */
    public function joinScopes(string $recordType, array $rows, string $idColumn = "ID"): array
    {
        if (empty($rows)) {
            return $rows;
        }

        // Collect record IDs
        $recordIDs = [];
        foreach ($rows as $row) {
            if (isset($row[$idColumn])) {
                $recordIDs[] = $row[$idColumn];
            }
        }

        if (empty($recordIDs)) {
            return $rows;
        }

        // Get all scope data in one query
        $allRecordsScope = $this->getRecordsScope($recordType, $recordIDs);

        // Attach scope data to each row
        foreach ($rows as &$row) {
            $recordID = $row[$idColumn];

            if (!empty($allRecordsScope[$recordID])) {
                $row += $allRecordsScope[$recordID];
            }

            // If not restricted, don't include scope field at all (indicates global)
        }

        return $rows;
    }

    /**
     * For the given scope filter, return only the categoryIDs and siteSectionIDs that the user has permission to use.
     *
     * @param array $scope
     * @return array A tuple of category IDs and site section IDs i.e. [$categoryIDs, $siteSectionIDs]
     */
    public function resolveScopeRecordIDs(array $scope): array
    {
        // Handle category-based permissions.
        $visibleCategoryIDs = $this->categoryModel->getVisibleCategoryIDs(["filterDiscussionsAdd" => true]);

        // If the user has access to all categories, we only need to return the filters if they were there already.
        if ($visibleCategoryIDs === true) {
            return [$scope["categoryIDs"] ?? null, $scope["siteSectionIDs"] ?? null];
        }

        // If we have a categoryIDs filter, intersect with visible category IDs, use the visible category IDs.
        if (isset($scope["categoryIDs"])) {
            $categoryIDs = array_intersect($scope["categoryIDs"], $visibleCategoryIDs);
        } else {
            $categoryIDs = $visibleCategoryIDs;
        }
        $categoryIDs = array_values($categoryIDs);

        // Get all site sections.
        $allSiteSections = $this->siteSectionModel->getAll();

        // Get all site section IDs belonging to categories that the user has access to.
        $siteSectionIDs = array_map(
            fn($siteSection) => $siteSection->getSectionID(),
            array_filter(
                $allSiteSections,
                fn($siteSection) => in_array($siteSection->getCategoryID(), $visibleCategoryIDs)
            )
        );

        // If we have a siteSectionIDs filter, then intersect with the allowed siteSectionIDs.
        if (isset($scope["siteSectionIDs"])) {
            $siteSectionIDs = array_intersect($scope["siteSectionIDs"], $siteSectionIDs);
        }
        $siteSectionIDs = array_values($siteSectionIDs);

        return [$categoryIDs, $siteSectionIDs];
    }
}
