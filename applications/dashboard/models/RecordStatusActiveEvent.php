<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

/**
 * Represent a RecordStatusStructure event.
 */
class RecordStatusActiveEvent
{
    private $activeRecordStatusIDs = [];

    private $isActive = true;

    /**
     * Get isActive for filter.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Set isActive filter.
     *
     * @param bool $isActive
     */
    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * Return an array of recordStatus' statusID that are meant to be enabled.
     *
     * @return array
     */
    public function getActiveRecordsStatusIDs(): array
    {
        return $this->activeRecordStatusIDs;
    }

    /**
     * Add statusIDs to the array of recordStatus that are meant to be enabled.
     *
     * @param array $statusIDs
     * @return void
     */
    public function addActiveRecordStatusIDs(array $statusIDs)
    {
        $this->activeRecordStatusIDs = array_values(
            array_unique(array_merge($this->activeRecordStatusIDs, $statusIDs), SORT_REGULAR)
        );
    }

    /**
     * Remove statusIDs from the array of recordStatus that are meant to be enabled.
     *
     * @param array $statusIDs
     * @return void
     */
    public function removeActiveRecordStatusIds(array $statusIDs)
    {
        $this->activeRecordStatusIDs = array_values(
            array_unique(array_diff($this->activeRecordStatusIDs, $statusIDs), SORT_REGULAR)
        );
    }
}
