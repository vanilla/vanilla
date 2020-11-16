<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Search;

use Garden\Schema\Schema;
use Vanilla\Search\SearchResultItem;

/**
 * UserSearchResultItem.
 */
class UserSearchResultItem extends SearchResultItem {

    /** @var Schema $userSchema*/
    private $userSchema;

    /**
     * Construcotr.
     *
     * @param Schema $userSchema
     * @param array $data
     */
    public function __construct(Schema $userSchema, array $data) {
        $this->userSchema = $userSchema;
        parent::__construct($data);
    }


    /**
     * Extra schema for user type search results
     */
    protected function extraSchema(): ?Schema {
        return Schema::parse([
            'userInfo' => $this->userSchema
        ])->merge($this->userSchema);
    }
}
