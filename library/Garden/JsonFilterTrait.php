<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

use Vanilla\ApiUtils;

/**
 * Filters output before being JSON-encoded.
 */
trait JsonFilterTrait
{
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
