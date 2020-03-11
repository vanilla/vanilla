<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;

/**
 * Fragment representing some group of permissions.
 */
class PermissionFragmentSchema extends Schema {

    const TYPE_GLOBAL = "global";

    /**
     * Override to add schema.
     */
    public function __construct() {
        parent::__construct($this->parseInternal([
            'type:s' => [
                'enum' => ['global', 'category', 'knowledgeBase'],
            ],
            'id:i?', // The record ID of the permission (if it has a record type.)
            'permissions:o', // The permissions for the type + id combination.
        ]));
    }
}
