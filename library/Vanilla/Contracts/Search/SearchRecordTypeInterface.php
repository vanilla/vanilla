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
     * Get search record type key. Ex: discussion, comment, article
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Get search api type key. Ex: discussion, poll, question, etc...
     *
     * @return string
     */
    public function getApiTypeKey(): string;

    /**
     * Get sphinx index filter dtype value
     *
     * @return string
     */
    public function getDType(): int;

    /**
     * Get sphinx index name
     *
     * @return string
     */
    public function getIndexName(): string;

    /**
     * Get search record linked data model type
     *
     * @return mixed
     */
    public function getModel();

    /**
     * Get records data by their IDs
     *
     * @param array $IDs
     * @param \SearchModel $searchModel
     *
     * @return array
     */
    public function getDocuments(array $IDs, \SearchModel $searchModel): array;

    /**
     * Get advanced search form checkbox input id
     */
    public function getCheckBoxId(): string;

    /**
     * Get advanced search form checkbox input label
     */
    public function getCheckBoxLabel(): string;

    /**
     * Get specific model record id from sphinx guid
     *
     * @param int $guid Global sphinx document id
     * @return int|null
     */
    public function getRecordID(int $guid): ?int;

    /**
     * Get search provider group. Ex: advanced, sphinx, etc...
     *
     * @return string
     */
    public function getProviderGroup(): string;
}
