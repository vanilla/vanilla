<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

use Garden\Web\Data;
use Vanilla\ApiUtils;

/**
 * Filters output before being JSON-encoded.
 */
trait JsonFilterTrait
{
    /** @var string[] */
    private array $jsObjectFields = [];

    /**
     * Ensure that empty array values of these fields are serialized as javascript objects.
     *
     * @param string[] $jsObjectFields
     * @return Data
     */
    public function withJsObjectFields(array $jsObjectFields): Data
    {
        $this->jsObjectFields = $jsObjectFields;
        return $this;
    }

    /**
     * Prepare data for json_encode
     *
     * @param mixed $value
     * @return mixed
     *
     * @deprecated ApiUtils::jsonFilter()
     */
    private function jsonFilter($value)
    {
        return ApiUtils::jsonFilter($value);
    }
}
