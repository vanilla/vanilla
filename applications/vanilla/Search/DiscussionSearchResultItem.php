<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Search;

use Garden\Schema\Schema;
use Vanilla\Contracts\Site\AbstractSiteProvider;
use Vanilla\Search\SearchQuery;
use Vanilla\Search\SearchResultItem;

/**
 * DiscussionSearchResultItem.
 */
class DiscussionSearchResultItem extends SearchResultItem {

    /**
     * @var string Class used to construct search result items.
     */
    public static $searchItemClass = DiscussionSearchResultItem::class;

    /**
     * Extra schema for user type search results
     */
    protected function extraSchema(): ?Schema {
        return Schema::parse([
            'discussionID:i?',
            'tagIDs:a?',
            'labelCodes:a?',
        ]);
    }

    /**
     * @param int|null $count
     */
    public function setSubqueryMatchCount(?int $count): void {
        $discussionID = $this->data['discussionID'] ?? null;
        if ($count !== null && $discussionID !== null) {
            parent::setSubqueryMatchCount($count);
            $this->data['subqueryExtraParams'] = [
                'scope' => 'site',
                'discussionID' => $discussionID,
            ];
        }
    }
}
