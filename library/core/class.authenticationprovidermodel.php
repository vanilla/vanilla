<?php
/**
 * Authentication Helper: Authentication Provider Model
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.10
 */

/**
 * Used to access and manipulate the UserAuthenticationProvider table.
 */
class Gdn_AuthenticationProviderModel extends Gdn_Model {

    /** Database mapping. */
    const COLUMN_KEY = 'AuthenticationKey';

    /** Database mapping. */
    const COLUMN_ALIAS = 'AuthenticationSchemeAlias';

    /** Database mapping. */
    const COLUMN_NAME = 'Name';

    /**
     * @var array The default authentication provider.
     */
    private static $default = null;

    /**
     *
     */
    public function __construct() {
        parent::__construct('UserAuthenticationProvider');
        $this->PrimaryKey = self::COLUMN_KEY;
    }

    /**
     *
     *
     * @param $row
     */
    protected static function calculate(&$row) {
        if (!$row) {
            return;
        }

        $attributes = dbdecode($row['Attributes']);
        if (is_array($attributes)) {
            $row = array_merge($attributes, $row);
        }
        unset($row['Attributes']);
    }

    /**
     * Return the default provider.
     *
     * @return array
     */
    public static function getDefault() {
        if (self::$default === null) {
            $rows = self::getWhereStatic(['IsDefault' => 1]);
            if (empty($rows)) {
                self::$default = false;
            } else {
                self::$default = array_pop($rows);
            }
        }
        return self::$default;
    }

    /**
     *
     *
     * @return array|null|type
     */
    public function getProviders() {
        $this->SQL
            ->select('uap.*')
            ->from('UserAuthenticationProvider uap');

        if (Gdn::session()->isValid()) {
            $userID = Gdn::session()->UserID;

            $this->SQL
                ->select('ua.ForeignUserKey', '', 'UniqueID')
                ->join('UserAuthentication ua', "uap.AuthenticationKey = ua.ProviderKey and ua.UserID = $userID", 'left');
        }

        $data = $this->SQL->get()->resultArray();
        $data = Gdn_DataSet::index($data, ['AuthenticationKey']);
        foreach ($data as &$row) {
            self::calculate($row);
        }
        return $data;
    }

    /**
     *
     *
     * @param $authenticationProviderKey
     * @return array|bool|stdClass
     */
    public static function getProviderByKey($authenticationProviderKey) {
        $providerData = Gdn::sql()
            ->select('uap.*')
            ->from('UserAuthenticationProvider uap')
            ->where('uap.AuthenticationKey', $authenticationProviderKey)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        self::calculate($providerData);

        return $providerData;
    }

    /**
     *
     *
     * @param $authenticationProviderURL
     * @return array|bool|stdClass
     */
    public static function getProviderByURL($authenticationProviderURL) {
        $providerData = Gdn::sql()
            ->select('uap.*')
            ->from('UserAuthenticationProvider uap')
            ->where('uap.URL', "%{$authenticationProviderURL}%")
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        self::calculate($providerData);

        return $providerData;
    }

    /**
     *
     *
     * @param $authenticationSchemeAlias
     * @param null $userID
     * @return array|bool|stdClass
     */
    public static function getProviderByScheme($authenticationSchemeAlias, $userID = null) {
        $providerQuery = Gdn::sql()
            ->select('uap.*')
            ->from('UserAuthenticationProvider uap')
            ->where('uap.AuthenticationSchemeAlias', $authenticationSchemeAlias);

        if (!is_null($userID) && $userID) {
            $providerQuery
                ->join('UserAuthentication ua', 'ua.ProviderKey = uap.AuthenticationKey', 'left')
                ->where('ua.UserID', $userID);
        }

        $providerData = $providerQuery->get();
        if ($providerData->numRows()) {
            $result = $providerData->firstRow(DATASET_TYPE_ARRAY);
            self::calculate($result);
            return $result;
        }

        return false;
    }


    /**
     * Get a list of providers by (read type) scheme.
     *
     * @param $authenticationSchemeAlias
     * @return array
     */
    public function getProvidersByScheme($authenticationSchemeAlias) {
        $providers = $this->SQL
            ->select('uap.*')
            ->from('UserAuthenticationProvider uap')
            ->where('uap.AuthenticationSchemeAlias', $authenticationSchemeAlias)
            ->get()
            ->resultArray();

        foreach($providers as &$provider) {
            self::calculate($provider);
        }

        return $providers;
    }

    /**
     *
     *
     * @param bool $where
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool $limit
     * @param bool $offset
     * @return array|null
     */
    public static function getWhereStatic($where = false, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        $data = Gdn::sql()->getWhere('UserAuthenticationProvider', $where, $orderFields, $orderDirection, $limit, $offset)->resultArray();
        foreach ($data as &$row) {
            self::calculate($row);
        }
        return $data;
    }

    /**
     *
     *
     * @param array $data
     * @param bool $settings
     * @return bool
     */
    public function save($data, $settings = false) {
        // Grab the current record.
        $row = false;
        if ($id = val('ID', $settings)) {
            $row = $this->getWhere([$this->PrimaryKey => $id])->firstRow(DATASET_TYPE_ARRAY);
        } elseif (isset($data[$this->PrimaryKey])) {
            $row = $this->getWhere([$this->PrimaryKey => $data[$this->PrimaryKey]])->firstRow(DATASET_TYPE_ARRAY);
        } elseif ($pK = val('PK', $settings)) {
            $row = $this->getWhere([$pK => $data[$pK]])->firstRow(DATASET_TYPE_ARRAY);
        }

        // Get the columns and put the extended data in the attributes.
        $this->defineSchema();
        $columns = $this->Schema->fields();
        $remove = ['TransientKey' => 1, 'hpt' => 1, 'Save' => 1, 'Checkboxes' => 1];
        $data = array_diff_key($data, $remove);
        $attributes = array_diff_key($data, $columns);

        if (!empty($attributes)) {
            $data = array_diff_key($data, $attributes);
            $data['Attributes'] = dbencode($attributes);
        }

        $insert = !$row;
        if ($insert) {
            $this->addInsertFields($data);
        } else {
            $this->addUpdateFields($data);
        }

        // Validate the form posted values
        if ($this->validate($data, $insert) === true) {
            // Clear the default from other authentication providers.
            $default = val('IsDefault', $data);
            if ($default) {
                $this->SQL->put(
                    $this->Name,
                    ['IsDefault' => 0],
                    ['AuthenticationKey <>' => val('AuthenticationKey', $data)]
                );
            }

            $fields = $this->Validation->validationFields();
            if ($insert === false) {
                $primaryKeyVal = $row[$this->PrimaryKey];
                $this->update($fields, [$this->PrimaryKey => $primaryKeyVal]);

            } else {
                $primaryKeyVal = $this->insert($fields);
            }
        } else {
            $primaryKeyVal = false;
        }
        return $primaryKeyVal;
    }
}
