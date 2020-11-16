<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 * Interface to apply on a query type that supports boosting various fields at query time.
 */
interface CollapsableSerachQueryInterface {

    /**
     * Collapse documents by a field.
     *
     * @param string $fieldName
     *
     * @return mixed
     */
    public function collapseField(string $fieldName);
}
