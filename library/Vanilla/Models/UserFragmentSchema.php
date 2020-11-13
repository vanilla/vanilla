<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\SchemaFactory;

/**
 * Schema to validate shape of some media upload metadata.
 */
class UserFragmentSchema extends Schema {

    /**
     * Override constructor to initialize schema.
     */
    public function __construct() {
        parent::__construct($this->parseInternal([
            'userID:i', // The ID of the user.
            'name:s', // The username of the user.
            'url:s?', // Full URL to the user profile page.
            'photoUrl:s', // The URL of the user's avatar picture.
            'dateLastActive:dt|n', // Time the user was last active.
            'label:s?'
        ]));
    }

    /** @var UserFragmentSchema */
    private static $cache = null;

    /**
     * @return UserFragmentSchema
     */
    public static function instance(): UserFragmentSchema {
        if (self::$cache === null) {
            self::$cache = SchemaFactory::get(self::class, 'UserFragment');
        }

        return self::$cache;
    }

    /**
     * Normalize a user from the DB into a user fragment.
     *
     * @param array $dbRecord
     * @return array
     */
    public static function normalizeUserFragment(array $dbRecord) {
        $photo = $dbRecord['Photo'] ?? '';
        if ($photo) {
            $photo = userPhotoUrl($dbRecord);
            $dbRecord['PhotoUrl'] = $photo;
        } else {
            $url = \UserModel::getDefaultAvatarUrl($dbRecord);
            $dbRecord['PhotoUrl'] = ($url) ? $url : \UserModel::getDefaultAvatarUrl();
        }

        $dbRecord['url'] = url(userUrl($dbRecord), true);

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        $schemaRecord = self::instance()->validate($schemaRecord);
        return $schemaRecord;
    }
}
