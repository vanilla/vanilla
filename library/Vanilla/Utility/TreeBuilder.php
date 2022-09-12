<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * Utility for constructing a tree from an array.
 */
final class TreeBuilder
{
    /** @var string */
    private $recordIDFieldName;

    /** @var string */
    private $parentIDFieldName;

    /** @var string */
    private $childrenFieldName = "children";

    /** @var callable */
    private $sorter = null;

    /** @var string|int */
    private $rootID = null;

    /** @var bool */
    private $allowUnreachableNodes = false;

    /**
     * Public Constructor.
     *
     * @param string $recordIDFieldName The field to use for the recordID. This should be present in all records.
     * @param string $parentIDFieldName The field to use as the parentID in the tree.
     * If this isn't present on a record it will be placed at the top level.
     */
    public static function create(string $recordIDFieldName, string $parentIDFieldName): self
    {
        return new TreeBuilder($recordIDFieldName, $parentIDFieldName);
    }

    /**
     * Constructor.
     *
     * @param string $recordIDFieldName
     * @param string $parentIDFieldName
     */
    private function __construct(string $recordIDFieldName, string $parentIDFieldName)
    {
        $this->recordIDFieldName = $recordIDFieldName;
        $this->parentIDFieldName = $parentIDFieldName;
    }

    /**
     * Set the name to
     *
     * @param string $childrenFieldName
     *
     * @return $this
     */
    public function setChildrenFieldName(string $childrenFieldName): self
    {
        $this->childrenFieldName = $childrenFieldName;
        return $this;
    }

    /**
     * Apply a sorting function for sorting between children.
     *
     * @param callable|null $sorter A callback function for uasort().
     *
     * @return $this
     */
    public function setSorter(?callable $sorter): self
    {
        $this->sorter = $sorter;
        return $this;
    }

    /**
     * Set a rootID field.
     * If the rootID is set the following will occur:
     * - Items with rootID as their parentID will be considered the top level items.
     * - An item with the rootID as their recordID will be sorted first and made the first top level item.
     * - If allowUnreachableNodes is true, then anything without a direct path to the rootID will be sorted to the end.
     * - If allowUnreachableNodes is false, anything that doesn't have a direct path to the rootID will be excluded.
     *
     * @param int|string $rootID
     *
     * @return $this
     */
    public function setRootID($rootID): self
    {
        $this->rootID = $rootID;
        return $this;
    }

    /**
     * If true, unreachable nodes will be sorted separately and applied at the end of the tree.
     * If false, unreachable nodes will be excluded.
     *
     * Best used in conjunction with a rootID.
     *
     * @param bool $allowUnreachableNodes
     *
     * @return $this
     */
    public function setAllowUnreachableNodes(bool $allowUnreachableNodes): self
    {
        $this->allowUnreachableNodes = $allowUnreachableNodes;
        return $this;
    }

    /**
     * Build a tree of records.
     *
     * @param array $source
     * @return array
     */
    public function buildTree(array $source): array
    {
        $sourcesByID = array_column($source, null, $this->recordIDFieldName);
        $rootItemIDs = [];
        $rootItemIDsWrongRoot = [];
        $childIDsByParentID = [];

        // First pass to collect some IDs.
        foreach ($sourcesByID as $sourceID => $sourceRow) {
            $parentID = $sourceRow[$this->parentIDFieldName] ?? null;
            if ($sourceID === $this->rootID) {
                // Do nothing, this will be prepended later.
            } elseif ($parentID === $this->rootID) {
                $rootItemIDs[] = $sourceID;
            } elseif ($this->allowUnreachableNodes) {
                $rootItemIDsWrongRoot[] = $sourceID;
            } elseif ($this->rootID === null && !isset($sourcesByID[$parentID])) {
                $rootItemIDs[] = $sourceID;
            }

            $childIDsByParentID[$parentID][] = $sourceID;
        }

        $seenChildIDs = [];
        $getChildren = function (array $childIDs) use (
            &$childIDsByParentID,
            &$sourcesByID,
            &$seenChildIDs,
            &$getChildren
        ): array {
            $result = [];
            foreach ($childIDs as $childID) {
                if (in_array($childID, $seenChildIDs)) {
                    // Protect against recursion in multiple parts of the tree.
                    continue;
                }
                $seenChildIDs[] = $childID;

                $row = $sourcesByID[$childID] ?? null;
                if ($row === null) {
                    // The row doesn't exist.
                    continue;
                }

                // Attach the children.
                $childIDs = $childIDsByParentID[$childID] ?? [];
                if (!empty($childIDs)) {
                    // We have some children.
                    $row[$this->childrenFieldName] = $getChildren($childIDs);
                } else {
                    $row[$this->childrenFieldName] = [];
                }
                $result[] = $row;
            }
            $this->sortRecords($result);
            return $result;
        };

        $result = $getChildren($rootItemIDs);

        if ($this->allowUnreachableNodes) {
            $wrongRoots = $getChildren($rootItemIDsWrongRoot);
            $rootItems = [];

            // Collect any missing records.
            // This could happen if there are any recursive items.
            $sourceIDs = array_keys($sourcesByID);
            $missingIDs = array_diff($sourceIDs, $seenChildIDs);
            $missing = [];
            foreach ($missingIDs as $missingID) {
                $missingItem = &$sourcesByID[$missingID];
                $missingItem[$this->childrenFieldName] = [];

                if ($this->rootID !== null && $missingID === $this->rootID) {
                    $rootItems[] = $missingItem;
                } else {
                    $missing[] = $missingItem;
                }
            }

            $this->sortRecords($missing);
            $result = array_merge($rootItems, $result, $wrongRoots, $missing);
        }

        return $result;
    }

    /**
     * Flatten a tree structure.
     *
     * @param array $items The items to flatten.
     *
     * @return array The flattened structure.
     */
    public function flattenTree(array $items): array
    {
        $result = [];

        $flattenInternal = function ($item) use (&$result, &$flattenInternal) {
            $children = $item[$this->childrenFieldName] ?? [];
            unset($item[$this->childrenFieldName]);
            $result[] = $item;

            foreach ($children as $child) {
                $flattenInternal($child);
            }
        };

        foreach ($items as $item) {
            $flattenInternal($item);
        }
        return $result;
    }

    /**
     * Sort a flat array into flattened tree.
     *
     * @param array $source The source items.
     *
     * @return array The sorted items.
     */
    public function sort(array $source): array
    {
        $tree = $this->buildTree($source);
        $flattened = $this->flattenTree($tree);
        return $flattened;
    }

    ///
    /// Private utility
    ///

    /**
     * @param array $records
     */
    private function sortRecords(array &$records): void
    {
        if ($this->sorter !== null) {
            // Sort the children
            uasort($records, $this->sorter);
        }
    }
}
