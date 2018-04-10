<?php
/**
 * UserMeta model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0.18 (?)
 */

/**
 * Handles usermeta data.
 */
class UserMetaModel extends Gdn_Model {

    /** @var array Store in-memory copies of everything retrieved from and set to the DB. */
    protected static $MemoryCache;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        self::$MemoryCache = [];
        parent::__construct('UserMeta');
        $this->SQL = clone Gdn::sql();
        $this->SQL->reset();
    }

    /**
     * Retrieves UserMeta information for a UserID / Key pair.
     *
     * This method takes a $userID or array of $userIDs, and a $key. It converts the
     * $key to fully qualified format and then queries for the associated value(s). $key
     * can contain SQL wildcards, in which case multiple results can be returned.
     *
     * If $userID is an array, the return value will be a multi dimensional array with the first
     * axis containing UserIDs and the second containing fully qualified UserMetaKeys, associated with
     * their values.
     *
     * If $userID is a scalar, the return value will be a single dimensional array of $userMetaKey => $value
     * pairs.
     *
     * @param $userID integer UserID or array of UserIDs
     * @param $key string relative user meta key
     * @param $default optional default return value if key is not found
     * @return array results or $default
     */
    public function getUserMeta($userID, $key = null, $default = null) {
        if (Gdn::cache()->activeEnabled()) {
            if (is_array($userID)) {
                $result = [];
                foreach ($userID as $iD) {
                    $meta = $this->getUserMeta($iD, $key, $default);
                    $result[$iD] = $meta;
                }
                return $result;
            }

            // Try and grab the user meta from the cache.
            $cacheKey = 'UserMeta_'.$userID;
            $userMeta = Gdn::cache()->get($cacheKey);

            if ($userMeta === Gdn_Cache::CACHEOP_FAILURE) {
                $userMeta = $this->getWhere(['UserID' => $userID], 'Name')->resultArray();
                $userMeta = array_column($userMeta, 'Value', 'Name');
                Gdn::cache()->store($cacheKey, $userMeta);
            }

            if ($key === null) {
                return $userMeta;
            }

            if (strpos($key, '%') === false) {
                $result = val($key, $userMeta, $default);
                return [$key => $result];
            }

            $regex = '`'.str_replace('%', '.*', preg_quote($key)).'`i';

            $result = [];
            foreach ($userMeta as $name => $value) {
                if (preg_match($regex, $name)) {
                    $result[$name] = $value;
                }
            }
            return $result;
        }

        $sql = clone Gdn::sql();
        $sql->reset();
        $userMetaQuery = $sql
            ->select('*')
            ->from('UserMeta u');

        if (is_array($userID)) {
            $userMetaQuery->whereIn('u.UserID', $userID);
        } else {
            $userMetaQuery->where('u.UserID', $userID);
        }

        if (stristr($key, '%')) {
            $userMetaQuery->where('u.Name like', $key);
        } else {
            $userMetaQuery->where('u.Name', $key);
        }

        $userMetaData = $userMetaQuery->get();

        $userMeta = [];
        if ($userMetaData->numRows()) {
            if (is_array($userID)) {
                while ($metaRow = $userMetaData->nextRow()) {
                    $userMeta[$metaRow->UserID][$metaRow->Name] = $metaRow->Value;
                }
            } else {
                while ($metaRow = $userMetaData->nextRow()) {
                    $userMeta[$metaRow->Name] = $metaRow->Value;
                }
            }
        } else {
            self::$MemoryCache[$key] = $default;
            $userMeta[$key] = $default;
        }

        unset($userMetaData);
        return $userMeta;
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
     * @param $userID int UserID or array of UserIDs
     * @param $key string relative user key
     * @param $value mixed optional value to set, null to delete
     * @return void
     */
    public function setUserMeta($userID, $key, $value = null) {
        if (Gdn::cache()->activeEnabled()) {
            if (is_array($userID)) {
                foreach ($userID as $iD) {
                    $this->setUserMeta($iD, $key, $value);
                }
                return;
            }

            $userMeta = $this->getUserMeta($userID);
            if (!stristr($key, '%')) {
                if ($value === null) {
                    unset($userMeta[$key]);
                } else {
                    $userMeta[$key] = $value;
                }
            } else {
                $matchKey = str_replace('%', '*', $key);
                foreach ($userMeta as $userMetaKey => $userMetaValue) {
                    if (fnmatch($matchKey, $userMetaKey)) {
                        if ($value === null) {
                            unset($userMeta[$userMetaKey]);
                        } else {
                            $userMeta[$userMetaKey] = $value;
                        }
                    }
                }
            }

            $cacheKey = 'UserMeta_'.$userID;
            Gdn::cache()->store($cacheKey, $userMeta, [
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ]);

            // Update the DB.
            $this->SQL->reset();
            if ($value === null) {
                $q = $this->SQL->where('UserID', $userID);
                if (stristr($key, '%')) {
                    $q->like('Name', $key);
                } else {
                    $q->where('Name', $key);
                }

                $q->delete('UserMeta');
            } else {
                $px = $this->SQL->Database->DatabasePrefix;
                $sql = "insert {$px}UserMeta (UserID, Name, Value) values(:UserID, :Name, :Value) on duplicate key update Value = :Value1";
                $params = [':UserID' => $userID, ':Name' => $key, ':Value' => $value, ':Value1' => $value];
                $this->Database->query($sql, $params);
            }

            return;
        }


        if (is_null($value)) {  // Delete
            $userMetaQuery = Gdn::sql();

            if (is_array($userID)) {
                $userMetaQuery->whereIn('UserID', $userID);
            } else {
                $userMetaQuery->where('UserID', $userID);
            }

            if (stristr($key, '%')) {
                $userMetaQuery->like('Name', $key);
            } else {
                $userMetaQuery->where('Name', $key);
            }

            $userMetaQuery->delete('UserMeta');
        } else {                // Set
            if (!is_array($userID)) {
                $userID = [$userID];
            }

            foreach ($userID as $uID) {
                try {
                    Gdn::sql()->insert('UserMeta', [
                        'UserID' => $uID,
                        'Name' => $key,
                        'Value' => $value
                    ]);
                } catch (Exception $e) {
                    Gdn::sql()->update('UserMeta', [
                        'Value' => $value
                    ], [
                        'UserID' => $uID,
                        'Name' => $key
                    ])->put();
                }
            }
        }
        return;
    }
}
