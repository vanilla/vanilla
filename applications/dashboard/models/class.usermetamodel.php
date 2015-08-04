<?php
/**
 * UserMeta model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
        self::$MemoryCache = array();
        parent::__construct('UserMeta');
        $this->SQL = clone Gdn::sql();
        $this->SQL->reset();
    }

    /**
     * Retrieves UserMeta information for a UserID / Key pair.
     *
     * This method takes a $UserID or array of $UserIDs, and a $Key. It converts the
     * $Key to fully qualified format and then queries for the associated value(s). $Key
     * can contain SQL wildcards, in which case multiple results can be returned.
     *
     * If $UserID is an array, the return value will be a multi dimensional array with the first
     * axis containing UserIDs and the second containing fully qualified UserMetaKeys, associated with
     * their values.
     *
     * If $UserID is a scalar, the return value will be a single dimensional array of $UserMetaKey => $Value
     * pairs.
     *
     * @param $UserID integer UserID or array of UserIDs
     * @param $Key string relative user meta key
     * @param $Default optional default return value if key is not found
     * @return array results or $Default
     */
    public function getUserMeta($UserID, $Key = null, $Default = null) {
        if (Gdn::cache()->activeEnabled()) {
            if (is_array($UserID)) {
                $Result = array();
                foreach ($UserID as $ID) {
                    $Meta = $this->GetUserMeta($ID, $Key, $Default);
                    $Result[$ID] = $Meta;
                }
                return $Result;
            }

            // Try and grab the user meta from the cache.
            $CacheKey = 'UserMeta_'.$UserID;
            $UserMeta = Gdn::cache()->get($CacheKey);

            if ($UserMeta === Gdn_Cache::CACHEOP_FAILURE) {
                $UserMeta = $this->getWhere(array('UserID' => $UserID), 'Name')->resultArray();
                $UserMeta = consolidateArrayValuesByKey($UserMeta, 'Name', 'Value');
                Gdn::cache()->store($CacheKey, $UserMeta);
            }

            if ($Key === null) {
                return $UserMeta;
            }

            if (strpos($Key, '%') === false) {
                $Result = val($Key, $UserMeta, $Default);
                return array($Key => $Result);
            }

            $Regex = '`'.str_replace('%', '.*', preg_quote($Key)).'`i';

            $Result = array();
            foreach ($UserMeta as $Name => $Value) {
                if (preg_match($Regex, $Name)) {
                    $Result[$Name] = $Value;
                }
            }
            return $Result;
        }

        $Sql = clone Gdn::sql();
        $Sql->reset();
        $UserMetaQuery = $Sql
            ->select('*')
            ->from('UserMeta u');

        if (is_array($UserID)) {
            $UserMetaQuery->whereIn('u.UserID', $UserID);
        } else {
            $UserMetaQuery->where('u.UserID', $UserID);
        }

        if (stristr($Key, '%')) {
            $UserMetaQuery->where('u.Name like', $Key);
        } else {
            $UserMetaQuery->where('u.Name', $Key);
        }

        $UserMetaData = $UserMetaQuery->get();

        $UserMeta = array();
        if ($UserMetaData->numRows()) {
            if (is_array($UserID)) {
                while ($MetaRow = $UserMetaData->NextRow()) {
                    $UserMeta[$MetaRow->UserID][$MetaRow->Name] = $MetaRow->Value;
                }
            } else {
                while ($MetaRow = $UserMetaData->NextRow()) {
                    $UserMeta[$MetaRow->Name] = $MetaRow->Value;
                }
            }
        } else {
            self::$MemoryCache[$Key] = $Default;
            $UserMeta[$Key] = $Default;
        }

        unset($UserMetaData);
        return $UserMeta;
    }

    /**
     * Sets UserMeta data to the UserMeta table.
     *
     * This method takes a UserID, Key, and Value, and attempts to set $Key = $Value for $UserID.
     * $Key can be an SQL wildcard, thereby allowing multiple variations of a $Key to be set. $UserID
     * can be an array, thereby allowing multiple users' $Keys to be set to the same $Value.
     *
     * ++ Before any queries are run, $Key is converted to its fully qualified format (Plugin.<PluginName> prepended)
     * ++ to prevent collisions in the meta table when multiple plugins have similar key names.
     *
     * If $Value == null, the matching row(s) are deleted instead of updated.
     *
     * @param $UserID int UserID or array of UserIDs
     * @param $Key string relative user key
     * @param $Value mixed optional value to set, null to delete
     * @return void
     */
    public function setUserMeta($UserID, $Key, $Value = null) {
        if (Gdn::cache()->activeEnabled()) {
            if (is_array($UserID)) {
                foreach ($UserID as $ID) {
                    $this->SetUserMeta($ID, $Key, $Value);
                }
                return;
            }

            $UserMeta = $this->GetUserMeta($UserID);
            if (!stristr($Key, '%')) {
                if ($Value === null) {
                    unset($UserMeta[$Key]);
                } else {
                    $UserMeta[$Key] = $Value;
                }
            } else {
                $MatchKey = str_replace('%', '*', $Key);
                foreach ($UserMeta as $UserMetaKey => $UserMetaValue) {
                    if (fnmatch($MatchKey, $UserMetaKey)) {
                        if ($Value === null) {
                            unset($UserMeta[$UserMetaKey]);
                        } else {
                            $UserMeta[$UserMetaKey] = $Value;
                        }
                    }
                }
            }

            $CacheKey = 'UserMeta_'.$UserID;
            Gdn::cache()->store($CacheKey, $UserMeta);

            // Update the DB.
            $this->SQL->reset();
            if ($Value === null) {
                $Q = $this->SQL->where('UserID', $UserID);
                if (stristr($Key, '%')) {
                    $Q->like('Name', $Key);
                } else {
                    $Q->where('Name', $Key);
                }

                $Q->delete('UserMeta');
            } else {
                $Px = $this->SQL->Database->DatabasePrefix;
                $Sql = "insert {$Px}UserMeta (UserID, Name, Value) values(:UserID, :Name, :Value) on duplicate key update Value = :Value1";
                $Params = array(':UserID' => $UserID, ':Name' => $Key, ':Value' => $Value, ':Value1' => $Value);
                $this->Database->query($Sql, $Params);
            }

            return;
        }


        if (is_null($Value)) {  // Delete
            $UserMetaQuery = Gdn::sql();

            if (is_array($UserID)) {
                $UserMetaQuery->whereIn('UserID', $UserID);
            } else {
                $UserMetaQuery->where('UserID', $UserID);
            }

            if (stristr($Key, '%')) {
                $UserMetaQuery->like('Name', $Key);
            } else {
                $UserMetaQuery->where('Name', $Key);
            }

            $UserMetaQuery->delete('UserMeta');
        } else {                // Set
            if (!is_array($UserID)) {
                $UserID = array($UserID);
            }

            foreach ($UserID as $UID) {
                try {
                    Gdn::sql()->insert('UserMeta', array(
                        'UserID' => $UID,
                        'Name' => $Key,
                        'Value' => $Value
                    ));
                } catch (Exception $e) {
                    Gdn::sql()->update('UserMeta', array(
                        'Value' => $Value
                    ), array(
                        'UserID' => $UID,
                        'Name' => $Key
                    ))->put();
                }
            }
        }
        return;
    }
}
