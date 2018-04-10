<?php
/**
 * Manage user authentication tokens.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

class UserAuthenticationTokenModel extends Gdn_Model {
    use \Vanilla\PrunableTrait;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('UserAuthenticationToken');
        $this->setPruneField('Timestamp');
    }

    /**
     * @inheritdoc
     */
    public function insert($fields) {
        $this->prune();

        if (!isset($fields['Timestamp'])) {
            $fields['Timestamp'] = date(MYSQL_DATE_FORMAT);
        }

        return parent::insert($fields) !== false;
    }

    /**
     * Lookup a token, based on a user ID and a provider authentication key.
     *
     * @param int $userID
     * @param string $authKey
     * @return bool|array A UserAuthenticationToken row on success, false on failure.
     */
    public function getByAuth($userID, $authKey) {
        $result = false;

        $row = $this->SQL->select('uat.*')
            ->from('UserAuthenticationToken uat')
            ->join('UserAuthentication ua', 'ua.ForeignUserKey = uat.ForeignUserKey')
            ->where('ua.UserID', $userID)
            ->where('ua.ProviderKey', $authKey)
            ->limit(1)
            ->get();
        if ($row->numRows()) {
            $result = $row->firstRow(DATASET_TYPE_ARRAY);
        }

        return $result;
    }

    /**
     * Lookup a valid token row, accounting for lifespans.
     *
     * @param string $providerKey
     * @param string $userKey
     * @param string|null $tokenType
     * @return array|bool|stdClass
     */
    public function lookup($providerKey, $userKey, $tokenType = null) {
        $row = Gdn::database()->sql()
            ->select('uat.*')
            ->from('UserAuthenticationToken uat')
            ->where('uat.ForeignUserKey', $userKey)
            ->where('uat.ProviderKey', $providerKey)
            ->beginWhereGroup()
            ->where('(uat.Timestamp + uat.Lifetime) >=', 'NOW()', true, false)
            ->orWhere('uat.Lifetime', 0)
            ->endWhereGroup()
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        $result = false;

        if ($row && ($tokenType === null || strtolower($tokenType) == strtolower($row['TokenType']))) {
            $result = $row;
        }

        return $result;
    }
}
