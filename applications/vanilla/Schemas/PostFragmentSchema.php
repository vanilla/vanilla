<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Schemas;

use Garden\Schema\Schema;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\SchemaFactory;

/**
 * Schema for minimal post fields.
 */
class PostFragmentSchema extends Schema {

    /**
     * Setup new schema.
     */
    public function __construct() {
        parent::__construct($this->parseInternal([
            'discussionID:i?' => 'The discussion ID of the post.',
            'commentID:i?' => 'The comment ID of the post, if any.',
            'name:s' => 'The title of the post.',
            'body:s?' => 'The HTML body of the post.',
            'url:s' => 'The URL of the post.',
            'dateInserted:dt' => 'The date of the post.',
            'insertUserID:i' => 'The author of the post.',
            'insertUser?' => SchemaFactory::parse(new UserFragmentSchema(), "UserFragment"),
        ]));
    }
}
