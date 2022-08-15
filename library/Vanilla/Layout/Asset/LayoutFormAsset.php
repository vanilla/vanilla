<?php
/**
 * @author Adam Charron <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Asset;

use stdClass;

/**
 * Asset class for hydration parameters.
 */
class LayoutFormAsset
{
    /** @var string */
    public $layoutViewType;
    /** @var string  */
    public $recordType;
    /** @var int|string */
    public $recordID;
    /** @var array */
    public $params;

    /**
     * Constructor for hydration parameters
     *
     * @param string $layoutViewType Layout View Type
     * @param string $recordType record Type
     * @param int|string $recordID record ID
     * @param array $params parameters[]
     */
    public function __construct(
        string $layoutViewType,
        string $recordType = "global",
        $recordID = -1,
        array $params = []
    ) {
        $this->layoutViewType = $layoutViewType;
        $this->recordType = $recordType;
        $this->recordID = $recordID;
        $this->params = $params;
    }

    /**
     * Build Arguments for redux action
     *
     * @return array
     */
    public function getArgs(): array
    {
        return [
            "layoutViewType" => $this->getLayoutViewType(),
            "recordID" => $this->getRecordID(),
            "recordType" => $this->getRecordType(),
            "params" => $this->getParams(),
        ];
    }

    /**
     * Return layout view type.
     *
     * @return string
     */
    public function getLayoutViewType(): string
    {
        return $this->layoutViewType;
    }

    /**
     * Set layout view type
     *
     * @param string $layoutViewType
     */
    public function setLayoutViewType(string $layoutViewType): void
    {
        $this->layoutViewType = $layoutViewType;
    }

    /**
     * Get record type.
     *
     * @return string
     */
    public function getRecordType(): string
    {
        return $this->recordType;
    }

    /**
     * Set Record View Type.
     *
     * @param string $recordType
     */
    public function setRecordType(string $recordType): void
    {
        $this->recordType = $recordType;
    }

    /**
     * Get Record ID.
     *
     * @return int|string
     */
    public function getRecordID()
    {
        return $this->recordID;
    }

    /**
     * Set Record ID
     *
     * @param int $recordID
     */
    public function setRecordID(int $recordID): void
    {
        $this->recordID = $recordID;
    }

    /**
     * Get Parameters as associative array for front end.
     *
     * @return array|stdClass
     */
    public function getParams()
    {
        return empty($this->params) ? new stdClass() : $this->params;
    }

    /**
     * Set Parameters.
     *
     * @param array $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }
}
