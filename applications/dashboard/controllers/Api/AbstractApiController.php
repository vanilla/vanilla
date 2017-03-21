<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;

abstract class AbstractApiController extends \Vanilla\Web\Controller {
    /**
     * @var Schema
     */
    private $userFragmentSchema;

    /**
     * Get the schema for users joined to records.
     *
     * @return Schema Returns a schema.
     */
    public function getUserFragmentSchema() {
        if ($this->userFragmentSchema === null) {
            $this->userFragmentSchema = $this->schema([
                'userID:i' => 'The ID of the user.',
                'name:s' => 'The username of the user.',
                'avatarUrl:s' => 'The URL of the user\'s avatar picture.'
            ], 'UserFragment');
        }
        return $this->userFragmentSchema;
    }

    public function options($path) {
        return '';
    }
}
