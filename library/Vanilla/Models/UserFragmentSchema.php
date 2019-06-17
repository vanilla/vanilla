<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;

/**
 * Schema to validate shape of some media upload metadata.
 */
class UserFragmentSchema extends Schema {

    /**
     * Override constructor to initaliaze schema.
     */
    public function __construct() {
        parent::__construct([
            'userID:i', // The ID of the user.
            'name:s', // The username of the user.
            'photoUrl:s', // The URL of the user\'s avatar picture.
            'dateLastActive:dt|n', // Time the user was last active.
        ]);
    }
}
