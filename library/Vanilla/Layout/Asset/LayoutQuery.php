<?php
/**
 * @author Adam Charron <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Asset;

use stdClass;
use Vanilla\Utility\ArrayUtils;

/**
 * Asset class for hydration parameters.
 */
class LayoutQuery implements \JsonSerializable
{
    public string $layoutViewType;
    public string $recordType;
    public $recordID;
    public array $params;

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
     * Create a new layout query with a layout view type applied.
     *
     * @param string $layoutViewType
     *
     * @return $this
     */
    public function withLayoutViewType(string $layoutViewType): LayoutQuery
    {
        $query = clone $this;
        $query->layoutViewType = $layoutViewType;
        return $query;
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
     * Create a new layout query with a recordType applied.
     *
     * @param string $recordType
     *
     * @return $this
     */
    public function withRecordType(string $recordType): LayoutQuery
    {
        $query = clone $this;
        $query->recordType = $recordType;
        return $query;
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
     * Create a new layout query with a recordID applied.
     *
     * @param string|int $recordID
     *
     * @return $this
     */
    public function withRecordID($recordID): LayoutQuery
    {
        $query = clone $this;
        $query->recordID = $recordID;
        return $query;
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
     * Get Parameters as associative array for front end.
     *
     * @return \ArrayObject
     */
    public function getParams()
    {
        $params = $this->params;
        array_walk_recursive($params, function (&$val) {
            if ($val === true) {
                $val = "true";
            } elseif ($val === false) {
                $val = "false";
            } elseif (is_integer($val)) {
                $val = (string) $val;
            }
        });
        return new \ArrayObject($params);
    }

    /**
     * Set Parameters.
     *
     * @param array|\ArrayObject $params
     */
    public function setParams($params): void
    {
        $this->params = (array) $params;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->getArgs();
    }
}
