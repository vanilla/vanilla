<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\Dashboard\Models\UserFragment;
use Vanilla\ApiUtils;
use Vanilla\SchemaFactory;

/**
 * Schema to validate shape of some media upload metadata.
 */
class UserFragmentSchema extends Schema
{
    /**
     * Override constructor to initialize schema.
     */
    public function __construct()
    {
        parent::__construct(
            $this->parseInternal([
                "userID:i", // The ID of the user.
                "name:s", // The username of the user.
                "title:s?", // The title of the user.
                "url:s?", // Full URL to the user profile page.
                "photoUrl:s", // The URL of the user's avatar picture.
                "dateLastActive:dt|n?", // Time the user was last active.
                "banned:i?", // The banned status of the user.
                "punished:i?", // The jailed status of the user.
                "private:b?", // The private profile status of the user.
                "label:s?",
            ])
        );
    }

    /** @var UserFragmentSchema */
    private static $cache = null;

    /**
     * @return UserFragmentSchema
     */
    public static function instance(): UserFragmentSchema
    {
        if (self::$cache === null) {
            self::$cache = SchemaFactory::get(self::class, "UserFragment");
        }

        return self::$cache;
    }

    /**
     * Normalize a user from the DB into a user fragment.
     *
     * @param array $dbRecord
     * @return array
     */
    public static function normalizeUserFragment(array $dbRecord)
    {
        $photo = $dbRecord["Photo"] ?? ($dbRecord["photo"] ?? "");
        if ($photo) {
            $photo = userPhotoUrl($dbRecord);
            $photoUrl = $photo;
        } else {
            $url = \UserModel::getDefaultAvatarUrl($dbRecord);
            $photoUrl = $url ?: \UserModel::getDefaultAvatarUrl();
        }
        $privateProfile = \UserModel::getRecordAttribute($dbRecord, "Private", "0");

        $schemaRecord = [
            "userID" => $dbRecord["UserID"] ?? $dbRecord["userID"],
            "photoUrl" => $photoUrl,
            "url" => url(userUrl($dbRecord), true),
            "name" => $dbRecord["Name"] ?? ($dbRecord["name"] ?? "Unknown"),
            "private" => (bool) $privateProfile,
            "banned" => $dbRecord["Banned"] ?? 0,
            "punished" => $dbRecord["Punished"] ?? 0,
            "dateLastActive" => $dbRecord["DateLastActive"] ?? ($dbRecord["dateLastActive"] ?? null),
            "title" => $dbRecord["Title"] ?? null,
            "label" => $dbRecord["Label"] ?? ($dbRecord["label"] ?? null),
        ];
        $schemaRecord = ApiUtils::convertOutputKeys($schemaRecord);
        $schemaRecord = self::instance()->validate($schemaRecord);
        return $schemaRecord;
    }

    /**
     * Validate data against the schema.
     *
     * @param mixed $data The data to validate.
     * @param bool $sparse Whether or not this is a sparse validation.
     * @return mixed Returns a cleaned version of the data.
     * @throws ValidationException Throws an exception when the data does not validate against the schema.
     */
    public function validate($data, $sparse = false)
    {
        if ($data instanceof UserFragment) {
            $result = $data;
        } else {
            $result = parent::validate($data, $sparse);
        }

        return $result;
    }
}
