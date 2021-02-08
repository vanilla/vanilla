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

use Garden\JSON\Transformer;
use Vanilla\Attributes;
use Vanilla\Utility\CamelCaseScheme;

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
    const OPT_RETURN_KEY = 'returnKey';

    /**
     *
     */
    public function __construct() {
        parent::__construct('UserAuthenticationProvider');
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
     * Delete a row by the ID or the authentication key.
     *
     * @param mixed $id
     * @param false $datasetType
     * @param array $options
     * @return array|false|Gdn_DataSet|object
     */
    public function getID($id, $datasetType = false, $options = []) {
        $result = false;
        if (is_numeric($id)) {
            $result = parent::getID($id, $datasetType, $options);
        }
        if ($result === false) {
            parent::options($options);
            $result = parent::getWhere([self::COLUMN_KEY => $id])->firstRow($datasetType);
        }

        if (is_array($result) || is_object($result)) {
            $this->calculate($result);
        }
        return $result;
    }

    /**
     * Delete a row by the ID or the authentication key.
     *
     * @param mixed $id
     * @param array $options
     * @return bool|int
     */
    public function deleteID($id, $options = []) {
        $result = 0;
        if (is_numeric($id)) {
            $result = parent::deleteID($id, $options);
        }
        if ($result === 0) {
            $result = parent::delete([self::COLUMN_KEY => $id], $options);
        }
        return $result;
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

        if (is_array($row)) {
            $attributes = dbdecode($row['Attributes']);
            if (is_array($attributes)) {
                $row = array_merge($attributes, $row);
            }
            unset($row['Attributes']);
        } elseif (is_object($row)) {
            $attributes = dbdecode($row->Attributes ?? null);
            if (is_array($attributes)) {
                $row = (object) array_merge($attributes, (array) $row);
            }
            unset($row->Attributes);
        }
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
            $data = $this->getAllVisible();
        }

        $data = Gdn_DataSet::index($data, ['AuthenticationKey']);
        foreach ($data as &$row) {
            self::calculate($row);
        }
        return $data;
    }

    /**
     * Get all authenticators that are visible (i.e. should have a sign-in button).
     *
     * @return array
     */
    public function getAllVisible(): array {
        $cache = Gdn::cache();

        $data = $cache->get(self::ALL_CACHE_KEY);
        if ($data === Gdn_Cache::CACHEOP_FAILURE) {
            $data = $this->SQL
                ->select('uap.*')
                ->from('UserAuthenticationProvider uap')
                ->where('Visible', true)
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
     * Alias for getting all authenticators.
     *
     * @return array
     * @deprecated
     */
    public function getAll(): array {
        return $this->getAllVisible();
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
     * Given a database row, massage the data into a more externally-useful format.
     *
     * @param array $row
     * @param array $additionalTransformations
     * @return array $row
     */
    public function normalizeRow(array $row, array $additionalTransformations = []): array {
        $spec = array_merge_recursive([
            "active" => "Active",
            "authenticatorID" => "UserAuthenticationProviderID",
            "clientID" => "AuthenticationKey",
            "default" => "IsDefault",
            "name" => "Name",
            "type" => "AuthenticationSchemeAlias",
            "urls" => [
                "authenticateUrl" => "AuthenticateUrl",
                "passwordUrl" => "PasswordUrl",
                "profileUrl" => "ProfileUrl",
                "registerUrl" => "RegisterUrl",
                "signInUrl" => "SignInUrl",
                "signOutUrl" => "SignOutUrl",
            ],
            "visible" => "Visible",
        ], $additionalTransformations);
        $transformer = new Transformer($spec);

        $result = $transformer->transform($row);
        $result["urls"] = new Attributes($result["urls"] ?? []);
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function save($formPostValues, $settings = false) {
        $settings = (array)$settings + [
            self::OPT_RETURN_KEY => true,
        ];

        // Grab the current record.
        $row = false;
        if ($id = $formPostValues[$this->PrimaryKey] ?? null) {
            $row = $this->getID($id, DATASET_TYPE_ARRAY);
        } elseif ($id = val('ID', $settings)) {
            $row = $this->getWhere([self::COLUMN_KEY => $id])->firstRow(DATASET_TYPE_ARRAY);
        } elseif (isset($formPostValues[self::COLUMN_KEY])) {
            $row = $this->getWhere([self::COLUMN_KEY => $formPostValues[self::COLUMN_KEY]])->firstRow(DATASET_TYPE_ARRAY);
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
                    $this->update($fields, [$this->PrimaryKey => $row[$this->PrimaryKey]]);
                    $primaryKeyVal = $settings[self::OPT_RETURN_KEY] ?
                        ($fields[self::COLUMN_KEY] ?? $row[self::COLUMN_KEY]) :
                        $row[$this->PrimaryKey];
                }
            } else {
                $primaryKeyVal = $this->insert($fields);
                if ($settings[self::OPT_RETURN_KEY]) {
                    $primaryKeyVal = $fields[self::COLUMN_KEY];
                }
            }
        } else {
            $primaryKeyVal = false;
        }
        return $primaryKeyVal ?? null;
    }
}
