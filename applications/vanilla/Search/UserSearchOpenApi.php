<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Search;

use Vanilla\Search\SearchService;
use Vanilla\Utility\ArrayUtils;

/**
 * Filter Open API to add user-specific filters to the search endpoint.
 */
class UserSearchOpenApi
{
    private UserSearchType $userSearchType;

    private SearchService $searchService;

    /**
     * DI.
     *
     * @param UserSearchType $userSearchType
     * @param SearchService $searchService
     */
    public function __construct(UserSearchType $userSearchType, SearchService $searchService)
    {
        $this->userSearchType = $userSearchType;
        $this->searchService = $searchService;
    }

    /**
     * Filter Open API to add user-specific filters to the search endpoint.
     *
     * @param array $openApi
     * @return void
     */
    public function __invoke(array &$openApi): void
    {
        // Don't add profile field filters if user search type is not registered
        try {
            $driver = $this->searchService->getActiveDriver();
            if (is_null($driver->getSearchTypeByType($this->userSearchType->getType()))) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $schema = $this->userSearchType->buildProfileFieldSchema();
        $properties = $schema->jsonSerialize()["properties"] ?? [];

        // Add the profile fields schema to Open API.
        ArrayUtils::setByPath("components.parameters.ProfileFieldFilters.schema.properties", $openApi, $properties);
    }
}
