<?php
/**
 * Authentication Helper: Authentication Provider Model
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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

    const ALL_CACHE_KEY = 'AuthenticationProviders-All';
    const DEFAULT_CACHE_KEY = 'AuthenticationProviders-Default';
    const CACHE_TTL = 60 * 30; // 30 minutes.

    /**
     *
     */
    public function __construct() {
        parent::__construct('UserAuthenticationProvider');
        $this->PrimaryKey = self::COLUMN_KEY;
    }

    /**
     * Cache invalidation.
     */
    protected function onUpdate() {
        parent::onUpdate();
        $cache = Gdn::cache();
        $cache->remove(self::ALL_CACHE_KEY);
        $cache->remove(self::DEFAULT_CACHE_KEY);
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
        $cache = Gdn::cache();
        // GDN cache has local caching by default.
        $result = $cache->get(self::DEFAULT_CACHE_KEY);
        if ($result === Gdn_Cache::CACHEOP_FAILURE) {
            $rows = self::getWhereStatic(['IsDefault' => 1]);
            if (empty($rows)) {
                $result = false;
            } else {
                $result = array_pop($rows);
            }
            $cache->store(self::DEFAULT_CACHE_KEY, $result, [
                Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL,
            ]);
        }
        return $result;
    }

    /**
     *
     *
     * @return array|null
     */
    public function getProviders() {
        if (Gdn::session()->isValid()) {
            $userID = Gdn::session()->UserID;
            $this->SQL
                ->select('uap.*')
                ->from('UserAuthenticationProvider uap')
                ->select('ua.ForeignUserKey', '', 'UniqueID')
                ->join('UserAuthentication ua', "uap.AuthenticationKey = ua.ProviderKey and ua.UserID = $userID", 'left');

            $data = $this->SQL->get()->resultArray();
        } else {
            $data = $this->getAll();
        }

        $data = Gdn_DataSet::index($data, ['AuthenticationKey']);
        foreach ($data as &$row) {
            self::calculate($row);
        }
        return $data;
    }

    /**
     * @return array
     */
    public function getAll(): array {
        $cache = Gdn::cache();

        $data = $cache->get(self::ALL_CACHE_KEY);
        if ($data === Gdn_Cache::CACHEOP_FAILURE) {
            $data = $this->SQL
                ->select('uap.*')
                ->from('UserAuthenticationProvider uap')
                ->get()
                ->resultArray()
            ;
            $cache->store(self::ALL_CACHE_KEY, $data, [
                Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL,
            ]);
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
     * {@inheritDoc}
     */
    public function save($formPostValues, $settings = false) {
        // Grab the current record.
        $row = false;
        if ($id = val('ID', $settings)) {
            $row = $this->getWhere([$this->PrimaryKey => $id])->firstRow(DATASET_TYPE_ARRAY);
        } elseif (isset($formPostValues[$this->PrimaryKey])) {
            $row = $this->getWhere([$this->PrimaryKey => $formPostValues[$this->PrimaryKey]])->firstRow(DATASET_TYPE_ARRAY);
        } elseif ($pK = val('PK', $settings)) {
            $row = $this->getWhere([$pK => $formPostValues[$pK]])->firstRow(DATASET_TYPE_ARRAY);
        }

        // Get the columns and put the extended data in the attributes.
        $this->defineSchema();
        $columns = $this->Schema->fields();
        $remove = ['TransientKey' => 1, 'hpt' => 1, 'Save' => 1, 'Checkboxes' => 1];
        $formPostValues = array_diff_key($formPostValues, $remove);
        $attributes = array_diff_key($formPostValues, $columns);

        if (!empty($attributes)) {
            $formPostValues = array_diff_key($formPostValues, $attributes);
            $formPostValues['Attributes'] = dbencode($attributes);
        }

        $insert = !$row;
        if ($insert) {
            $this->addInsertFields($formPostValues);
        } else {
            $this->addUpdateFields($formPostValues);
        }

        // Validate the form posted values
        if ($this->validate($formPostValues, $insert) === true) {
            // Clear the default from other authentication providers.
            $default = val('IsDefault', $formPostValues);
            if ($default) {
                $this->SQL->put(
                    $this->Name,
                    ['IsDefault' => 0],
                    ['AuthenticationKey <>' => val('AuthenticationKey', $formPostValues)]
                );
            }

            $fields = $this->Validation->validationFields();
            if ($insert === false) {
                if ($settings['checkExisting'] ?? false) {
                    $fields = array_diff_assoc($fields, $row);
                }

                if (!empty($fields)) {
                    $primaryKeyVal = $row[$this->PrimaryKey];
                    $this->update($fields, [$this->PrimaryKey => $primaryKeyVal]);
                }
            } else {
                $primaryKeyVal = $this->insert($fields);
            }
        } else {
            $primaryKeyVal = false;
        }
        return $primaryKeyVal ?? null;
    }
}
