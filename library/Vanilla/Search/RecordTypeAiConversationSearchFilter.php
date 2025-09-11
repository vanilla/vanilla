<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Web\Exception\ServerException;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Filter to include only specific recordTypes in Ai Conversation search results.
 */
class RecordTypeAiConversationSearchFilter implements AiConversatonFilterInterface
{
    const CONFIG_ACCEPTED_RECORD_TYPES = "Search.AiConversation.AcceptedRecordTypes";
    private AbstractSearchDriver $searchDriver;

    /**
     * @param ConfigurationInterface $config
     * @param SearchService $searchService
     * @throws ServerException
     */
    public function __construct(private ConfigurationInterface $config, SearchService $searchService)
    {
        $this->searchDriver = $searchService->getActiveDriver();
    }

    /**
     * Apply filter logic to include or exclude the recordTypes in the search query. By default, non-listed recordTypes are included.
     *
     * @param array &$query The search query to modify
     * @return void
     */
    public function applyFilter(array &$query): void
    {
        // Get accepted record types from configuration
        $aiConversationRecordType = $this->config->get(self::CONFIG_ACCEPTED_RECORD_TYPES);
        if (!$aiConversationRecordType) {
            return;
        }

        $recordTypes = [];
        $searchTypes = $this->searchDriver->getAllWithDType();

        foreach ($searchTypes as $searchType) {
            $type = $searchType->getType();
            if ($aiConversationRecordType[$type] ?? false) {
                $recordTypes[] = $type;
            }
        }

        if (isset($query["recordTypes"])) {
            $recordTypes = array_intersect($recordTypes, $query["recordTypes"]);
        }

        if (empty($recordTypes)) {
            throw new ServerException("No valid record types found for AI conversation.");
        }

        $query["recordTypes"] = $recordTypes;
    }
}
