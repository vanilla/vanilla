<?php
/**
 * Authentication Helper: Authentication Provider Model
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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
     *
     */
    public function __construct() {
        parent::__construct('UserAuthenticationProvider');
        $this->PrimaryKey = self::COLUMN_KEY;
    }

    /**
     *
     *
     * @param $Row
     */
    protected static function calculate(&$Row) {
        if (!$Row) {
            return;
        }

        $Attributes = @unserialize($Row['Attributes']);
        if (is_array($Attributes)) {
            $Row = array_merge($Attributes, $Row);
        }
        unset($Row['Attributes']);
    }

    /**
     * Return the default provider.
     *
     * @return array
     */
    public static function getDefault() {
        $Rows = self::getWhereStatic(array('IsDefault' => 1));
        if (empty($Rows)) {
            return false;
        }
        return array_pop($Rows);
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
            $UserID = Gdn::session()->UserID;

            $this->SQL
                ->select('ua.ForeignUserKey', '', 'UniqueID')
                ->join('UserAuthentication ua', "uap.AuthenticationKey = ua.ProviderKey and ua.UserID = $UserID", 'left');
        }

        $Data = $this->SQL->get()->resultArray();
        $Data = Gdn_DataSet::index($Data, array('AuthenticationKey'));
        foreach ($Data as &$Row) {
            self::calculate($Row);
        }
        return $Data;
    }

    /**
     *
     *
     * @param $AuthenticationProviderKey
     * @return array|bool|stdClass
     */
    public static function getProviderByKey($AuthenticationProviderKey) {
        $ProviderData = Gdn::sql()
            ->select('uap.*')
            ->from('UserAuthenticationProvider uap')
            ->where('uap.AuthenticationKey', $AuthenticationProviderKey)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        self::calculate($ProviderData);

        return $ProviderData;
    }

    /**
     *
     *
     * @param $AuthenticationProviderURL
     * @return array|bool|stdClass
     */
    public static function getProviderByURL($AuthenticationProviderURL) {
        $ProviderData = Gdn::sql()
            ->select('uap.*')
            ->from('UserAuthenticationProvider uap')
            ->where('uap.URL', "%{$AuthenticationProviderURL}%")
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        self::calculate($ProviderData);

        return $ProviderData;
    }

    /**
     *
     *
     * @param $AuthenticationSchemeAlias
     * @param null $UserID
     * @return array|bool|stdClass
     */
    public static function getProviderByScheme($AuthenticationSchemeAlias, $UserID = null) {
        $ProviderQuery = Gdn::sql()
            ->select('uap.*')
            ->from('UserAuthenticationProvider uap')
            ->where('uap.AuthenticationSchemeAlias', $AuthenticationSchemeAlias);

        if (!is_null($UserID) && $UserID) {
            $ProviderQuery
                ->join('UserAuthentication ua', 'ua.ProviderKey = uap.AuthenticationKey', 'left')
                ->where('ua.UserID', $UserID);
        }

        $ProviderData = $ProviderQuery->get();
        if ($ProviderData->numRows()) {
            $Result = $ProviderData->firstRow(DATASET_TYPE_ARRAY);
            self::calculate($Result);
            return $Result;
        }

        return false;
    }

    /**
     *
     *
     * @param bool $Where
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param bool $Limit
     * @param bool $Offset
     * @return array|null
     */
    public static function getWhereStatic($Where = false, $OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        $Data = Gdn::sql()->getWhere('UserAuthenticationProvider', $Where, $OrderFields, $OrderDirection, $Limit, $Offset)->resultArray();
        foreach ($Data as &$Row) {
            self::calculate($Row);
        }
        return $Data;
    }

    /**
     *
     *
     * @param array $Data
     * @param bool $Settings
     * @return bool
     */
    public function save($Data, $Settings = false) {
        // Grab the current record.
        $Row = false;
        if (isset($Data[$this->PrimaryKey])) {
            $Row = $this->getWhere(array($this->PrimaryKey => $Data[$this->PrimaryKey]))->firstRow(DATASET_TYPE_ARRAY);
        } elseif ($PK = val('PK', $Settings)) {
            $Row = $this->getWhere(array($PK => $Data[$PK]));
        }

        // Get the columns and put the extended data in the attributes.
        $this->defineSchema();
        $Columns = $this->Schema->fields();
        $Remove = array('TransientKey' => 1, 'hpt' => 1, 'Save' => 1, 'Checkboxes' => 1);
        $Data = array_diff_key($Data, $Remove);
        $Attributes = array_diff_key($Data, $Columns);

        if (!empty($Attributes)) {
            $Data = array_diff_key($Data, $Attributes);
            $Data['Attributes'] = serialize($Attributes);
        }

        $Insert = !$Row;
        if ($Insert) {
            $this->addInsertFields($Data);
        } else {
            $this->addUpdateFields($Data);
        }

        // Validate the form posted values
        if ($this->validate($Data, $Insert) === true) {
            // Clear the default from other authentication providers.
            $Default = val('IsDefault', $Data);
            if ($Default) {
                $this->SQL->put(
                    $this->Name,
                    array('IsDefault' => 0),
                    array('AuthenticationKey <>' => val('AuthenticationKey', $Data))
                );
            }

            $Fields = $this->Validation->validationFields();
            if ($Insert === false) {
                $PrimaryKeyVal = $Row[$this->PrimaryKey];
                $this->update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));

            } else {
                $PrimaryKeyVal = $this->insert($Fields);
            }
        } else {
            $PrimaryKeyVal = false;
        }
        return $PrimaryKeyVal;
    }
}
