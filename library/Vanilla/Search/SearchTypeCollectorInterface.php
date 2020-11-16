<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 * A collector of search types.
 */
interface SearchTypeCollectorInterface {

    /**
     * Register a search type.
     *
     * @param AbstractSearchType $searchType
     *
     * @return mixed
     */
    public function registerSearchType(AbstractSearchType $searchType);

    /**
     * Register a new search index template.
     *
     * @param AbstractSearchIndexTemplate $searchIndexTemplate
     * @return mixed
     */
    public function registerSearchIndexTemplate(AbstractSearchIndexTemplate $searchIndexTemplate);

    /**
     * Get a search type by an assosciate dType.
     *
     * @param string $type
     * @return AbstractSearchType|null
     */
    public function getSearchTypeByType(string $type): ?AbstractSearchType;

    /**
     * Get a search type by an assosciate dType.
     *
     * @param int $dType
     * @return AbstractSearchType|null
     */
    public function getSearchTypeByDType(int $dType): ?AbstractSearchType;

    /**
     * Get a search types with an assosciate dType.
     *
     * @return AbstractSearchType[]
     */
    public function getAllWithDType(): array;
}
