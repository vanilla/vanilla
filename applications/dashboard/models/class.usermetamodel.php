<?php
/**
 * UserMeta model.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0.18 (?)
 */

use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\StringUtils;

/**
 * Handles usermeta data.
 */
class UserMetaModel extends Gdn_Model
{
    const SOFT_LIMIT = 100;
    const HARD_LIMIT = 500;
    const QUERY_VALUE_LENGTH = 500;
    const NAME_LENGTH = 100;

    /** @var Gdn_Cache */
    private $cache;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct()
    {
        parent::__construct("UserMeta");
        $this->cache = \Gdn::cache();
    }

    /**
     * Retrieves UserMeta information for a UserID / Key pair.
     *
     * This method takes a $userID or array of $userIDs, and a $key. It converts the
     * $key to fully qualified format and then queries for the associated value(s). $key
     * can contain SQL wildcards, in which case multiple results can be returned.
     *
     * If $userID is an array, the return value will be a multidimensional array with the first
     * axis containing UserIDs and the second containing fully qualified UserMetaKeys, associated with
     * their values.
     *
     * If $userID is a scalar, the return value will be a single dimensional array of $userMetaKey => $value
     * pairs.
     *
     * @param int|int[] $userID UserID or array of UserIDs
     * @param string|null $key string relative user meta key. If null is passed, all keys will be fetched.
     * @param mixed $default default return value if key is not found
     * @param string $prefix A prefix to trim off all the resulting keys.
     *
     * @return mixed A list of results or a single value.
     */
    public function getUserMeta($userID, ?string $key = null, $default = null, string $prefix = "")
    {
        if (is_array($userID)) {
            $multiple = $this->getMultipleUserMeta($userID, $key);
            $result = [];
            foreach ($userID as $singleUserID) {
                $result[$singleUserID] = $this->normalizeUserMeta(
                    $multiple[$singleUserID] ?? [],
                    $key,
                    $default,
                    $prefix
                );
            }
            return $result;
        }

        $userMeta = $this->fetchSingleUserMeta($userID, $key);
        $userMeta = $userMeta ?? [];
        $userMeta = $this->normalizeUserMeta($userMeta, $key, $default, $prefix);
        return $userMeta;
    }

    /**
     * Normalize a single users set of meta for it's key, with defaults and prefix trimming.
     *
     * @param array $userMeta
     * @param string|null $key
     * @param $default
     * @param string $prefix
     * @return array
     */
    private function normalizeUserMeta(array $userMeta, ?string $key = null, $default = null, string $prefix = "")
    {
        if (empty($userMeta) && $key !== null && !str_contains($key, "%")) {
            // We have an explicitly requested single key.
            $userMeta = [$key => $default];
        }

        $result = ArrayUtils::trimKeyPrefix($userMeta, $prefix);
        return $result;
    }

    /**
     * Get user meta data for multiple users at once.
     *
     * @param array $userIDs
     * @param string|null $key
     *
     * @return array
     */
    private function getMultipleUserMeta(array $userIDs, ?string $key = null): array
    {
        $where = ["UserID" => $userIDs];
        if ($key !== null) {
            $where["Name like"] = $key;
        }
        $rows = $this->getWhere($where, "Name", "asc")->resultArray();
        // Condense these by userID.
        $userMetasByUserID = ArrayUtils::arrayColumnArrays($rows, null, "UserID");
        foreach ($userMetasByUserID as $userID => $userMeta) {
            $userMetasByUserID[$userID] = $this->condenseUserMeta($userMeta);
        }
        return $userMetasByUserID;
    }

    /**
     * Create a cache key for user meta values of a single user and some meta key.
     *
     * @param int $userID
     * @param string|null $key
     * @return string
     */
    private function getSingleUserCacheKey(int $userID, ?string $key): string
    {
        $key = $key ?? "";
        return "UserMeta_{$userID}_{$key}";
    }

    /**
     * Invalidate single user caches as needed.
     *
     * If the key was: part1.part2.part3, we will invalidate the following keys.
     * - part1.part2.part3
     * - part1.part2.%
     * - part1.%
     *
     * @param int $userID
     * @param array $changedKeys
     * @return void
     */
    private function invalidateUserCaches(int $userID, array $changedKeys): void
    {
        foreach ($changedKeys as $changedKey) {
            $directKey = $this->getSingleUserCacheKey($userID, $changedKey);
            $this->cache->remove($directKey);
            $keyPieces = explode(".", $directKey);
            // We don't need the last piece for this.
            array_pop($keyPieces);
            $wildcardBuilder = "";

            foreach ($keyPieces as $keyPiece) {
                $wildcardBuilder .= $keyPiece . ".";
                $this->cache->remove($wildcardBuilder . "%");
            }
        }
    }

    /**
     * Clear UserMeta cache
     *
     * @param int $userID
     *
     */
    public function clearUserMetaCache(int $userID)
    {
        $this->invalidateUserCaches($userID, ["Profile.%"]);
    }

    /**
     * Fetch some usermeta data from cache or from the database.
     *
     * @param int $userID
     * @param string|null $key
     * @param string $prefix
     *
     * @return ?array
     */
    private function fetchSingleUserMeta(int $userID, ?string $key): ?array
    {
        $cacheKey = $this->getSingleUserCacheKey($userID, $key);
        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult !== Gdn_Cache::CACHEOP_FAILURE) {
            return $cachedResult;
        }

        // Go get it from the database.
        $result = $this->getSingleUserMetasFromDatabase($userID, $key);
        // If we got something from the database, store it in cache.
        if (!is_null($result)) {
            $this->cache->store($cacheKey, $result, [
                Gdn_Cache::FEATURE_EXPIRY => 60 * 15, // 15 minutes.
            ]);
        }

        return $result;
    }

    /**
     * Get all user meta values from the database.
     *
     * @param int $userID The userID to fetch for.
     * @param string|null $key An explicit usermeta key to grab or a wildcard: "profile.%".
     *
     * @return array|null
     */
    private function getSingleUserMetasFromDatabase(int $userID, ?string $key): ?array
    {
        $where = ["UserID" => $userID];
        if ($key !== null) {
            $where["Name like"] = $key;
        }
        $userMeta = $this->getWhere($where, "Name", "asc", self::HARD_LIMIT)->resultArray();
        $countMetas = count($userMeta);
        if (count($userMeta) > self::SOFT_LIMIT) {
            trigger_error(
                "User meta for user $userID is at over over the limit with $countMetas meta rows",
                E_USER_NOTICE
            );
        }

        $userMetaCondensed = $this->condenseUserMeta($userMeta);

        if (is_string($key) && !strstr($key, "%")) {
            // Someone is requesting a single value.
            return $userMetaCondensed ?? null;
        }

        return $userMetaCondensed;
    }

    /**
     * For user meta records with multiple entries for the same name, condense them into 1 array.
     *
     * @param array $userMeta A single users meta values.
     *
     * @return array
     */
    private function condenseUserMeta(array $userMeta): array
    {
        $userMetaCondensed = [];
        foreach ($userMeta as $metaItem) {
            $metaName = $metaItem["Name"];
            $metaValue = $metaItem["Value"];

            if (isset($userMetaCondensed[$metaName])) {
                if (is_array($userMetaCondensed[$metaName])) {
                    $userMetaCondensed[$metaName][] = $metaValue;
                } else {
                    $userMetaCondensed[$metaName] = [$userMetaCondensed[$metaName], $metaValue];
                }
            } else {
                $userMetaCondensed[$metaName] = $metaValue;
            }
        }
        return $userMetaCondensed;
    }

    /**
     * Sets UserMeta data to the UserMeta table.
     *
     * This method takes a UserID, Key, and Value, and attempts to set $key = $value for $userID.
     * $key can be an SQL wildcard, thereby allowing multiple variations of a $key to be set. $userID
     * can be an array, thereby allowing multiple users' $keys to be set to the same $value.
     *
     * ++ Before any queries are run, $key is converted to its fully qualified format (Plugin.<PluginName> prepended)
     * ++ to prevent collisions in the meta table when multiple plugins have similar key names.
     *
     * If $value == null, the matching row(s) are deleted instead of updated.
     *
     * @param int[]|int $userID UserID or array of UserIDs
     * @param string $key string relative user key
     * @param mixed $value optional value to set, null to delete
     * @return void
     */
    public function setUserMeta($userID, string $key, $value = null)
    {
        // Cast boolean values so they get concatted properly.
        if ($value === false) {
            $value = "0";
        } elseif ($value === true) {
            $value = "1";
        }
        if (is_array($userID)) {
            foreach ($userID as $iD) {
                $this->setUserMeta($iD, $key, $value);
            }
            return;
        }

        // Update the DB.
        $this->SQL->reset();
        if ($value === null) {
            $q = $this->SQL->where("UserID", $userID);
            if (stristr($key, "%")) {
                $q->like("Name", $key);
            } else {
                $q->where("Name", $key);
            }

            $q->delete("UserMeta");
        } else {
            try {
                $this->SQL->Database->beginTransaction();

                $this->delete(["UserID" => $userID, "Name like" => $key]);

                if (ArrayUtils::isArray($value) && !ArrayUtils::isAssociative($value)) {
                    // We will insert as multiple rows.
                    foreach ($value as $valuePart) {
                        $valuePart = $this->normalizeValue($valuePart);
                        $queryValue = $this->createQueryValue($key, $valuePart);
                        $this->insert([
                            "UserID" => $userID,
                            "Name" => $key,
                            "Value" => StringUtils::stripUnicodeWhitespace($valuePart),
                            "QueryValue" => $queryValue,
                        ]);
                    }
                } else {
                    $value = $this->normalizeValue($value);
                    $queryValue = $this->createQueryValue($key, $value);
                    $this->insert([
                        "UserID" => $userID,
                        "Name" => $key,
                        "Value" => StringUtils::stripUnicodeWhitespace($value),
                        "QueryValue" => $queryValue,
                    ]);
                }
                $this->SQL->Database->commitTransaction();
            } catch (Throwable $e) {
                $this->SQL->Database->rollbackTransaction();
                throw $e;
            }
        }
        $this->invalidateUserCaches($userID, [$key]);
    }

    /**
     * Normalize a meta value.
     *
     * @param $value
     */
    private function normalizeValue($value)
    {
        if ($value instanceof DateTimeInterface) {
            $value = $value->format(MYSQL_DATE_FORMAT);
        }
        return $value;
    }

    /**
     * Create a query value out of a profile fields value. This includes some normalization and truncation.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return string
     */
    public function createQueryValue(string $key, $value): string
    {
        $value = $this->normalizeValue($value);
        $valuePart = StringUtils::stripUnicodeWhitespace($value);

        // We will insert as multiple rows.
        $queryValue = $key . "." . "$valuePart";
        $queryValue = substr($queryValue, 0, self::QUERY_VALUE_LENGTH);
        return $queryValue;
    }
}
