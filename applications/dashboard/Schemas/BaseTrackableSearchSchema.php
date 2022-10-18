<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Vanilla\Search\SearchService;
use Vanilla\Site\SiteSectionSchema;

/**
 * The schema for a trackable search event.
 */
class BaseTrackableSearchSchema extends Schema
{
    /** @var null|SearchService */
    private $searchService;

    /**
     * Create the trackable search schema.
     */
    public function __construct()
    {
        $this->searchService = Gdn::getContainer()->get(SearchService::class);
        $searchTypes = $this->searchService->getActiveDriver()->getSearchTypes();
        $searchTypesEnum = [];
        foreach ($searchTypes as $type) {
            $searchTypesEnum[] = $type->getType();
        }
        parent::__construct(
            $this->parseInternal([
                "type:s",
                "domain:s",
                "searchResults:i",
                "searchQuery:o" => Schema::parse([
                    "terms:a" => ["items" => "string"],
                    "negativeTerms:a?" => ["items" => "string"],
                    "originalQuery:s" => [
                        "minLength" => 0,
                    ],
                ]),
                "searchTypes:a?" => [
                    "items" => ["type" => "string", "enum" => $searchTypesEnum],
                ],
                "page:i",
                "siteSection:o?" => SiteSectionSchema::getSchema(),
                "source:o?" => Schema::parse(["key:s", "label:s"]),
            ])
        );
    }
}
