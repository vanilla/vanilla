<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Search;

/**
 * Interface SearchRecordTypeInterface
 * @package Vanilla\Contracts\Search
 */
interface SearchRecordTypeInterface {

    /**
     * Get search record type unique key
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Get search record features structure
     *
     * @return string
     */
    public function getFeatures(): array;

    /**
     * Get search record linked data model type
     *
     * @return mixed
     */
    public function getModel();

    /**
     * Get search provider group. Ex: advanced, sphinx, etc...
     *
     * @return string
     */
    public function getProviderGroup(): string;
}
