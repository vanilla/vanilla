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
        $Rows = self::GetWhereStatic(array('IsDefault' => 1));
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
            ->Select('uap.*')
            ->From('UserAuthenticationProvider uap');

        if (Gdn::Session()->IsValid()) {
            $UserID = Gdn::Session()->UserID;

            $this->SQL
                ->Select('ua.ForeignUserKey', '', 'UniqueID')
                ->Join('UserAuthentication ua', "uap.AuthenticationKey = ua.ProviderKey and ua.UserID = $UserID", 'left');
        }

        $Data = $this->SQL->Get()->ResultArray();
        $Data = Gdn_DataSet::Index($Data, array('AuthenticationKey'));
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
        $ProviderData = Gdn::SQL()
            ->Select('uap.*')
            ->From('UserAuthenticationProvider uap')
            ->Where('uap.AuthenticationKey', $AuthenticationProviderKey)
            ->Get()
            ->FirstRow(DATASET_TYPE_ARRAY);

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
        $ProviderData = Gdn::SQL()
            ->Select('uap.*')
            ->From('UserAuthenticationProvider uap')
            ->Where('uap.URL', "%{$AuthenticationProviderURL}%")
            ->Get()
            ->FirstRow(DATASET_TYPE_ARRAY);

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
        $ProviderQuery = Gdn::SQL()
            ->Select('uap.*')
            ->From('UserAuthenticationProvider uap')
            ->Where('uap.AuthenticationSchemeAlias', $AuthenticationSchemeAlias);

        if (!is_null($UserID) && $UserID) {
            $ProviderQuery
                ->Join('UserAuthentication ua', 'ua.ProviderKey = uap.AuthenticationKey', 'left')
                ->Where('ua.UserID', $UserID);
        }

        $ProviderData = $ProviderQuery->Get();
        if ($ProviderData->NumRows()) {
            $Result = $ProviderData->FirstRow(DATASET_TYPE_ARRAY);
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
        $Data = Gdn::SQL()->GetWhere('UserAuthenticationProvider', $Where, $OrderFields, $OrderDirection, $Limit, $Offset)->ResultArray();
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
        if (isset($Data[$this->PrimaryKey]))
            $Row = $this->GetWhere(array($this->PrimaryKey => $Data[$this->PrimaryKey]))->FirstRow(DATASET_TYPE_ARRAY);
        elseif ($PK = GetValue('PK', $Settings)) {
            $Row = $this->GetWhere(array($PK => $Data[$PK]));
        }

        // Get the columns and put the extended data in the attributes.
        $this->DefineSchema();
        $Columns = $this->Schema->Fields();
        $Remove = array('TransientKey' => 1, 'hpt' => 1, 'Save' => 1, 'Checkboxes' => 1);
        $Data = array_diff_key($Data, $Remove);
        $Attributes = array_diff_key($Data, $Columns);

        if (!empty($Attributes)) {
            $Data = array_diff_key($Data, $Attributes);
            $Data['Attributes'] = serialize($Attributes);
        }

        $Insert = !$Row;
        if ($Insert) {
            $this->AddInsertFields($Data);
        } else {
            $this->AddUpdateFields($Data);
        }

        // Validate the form posted values
        if ($this->Validate($Data, $Insert) === true) {
            // Clear the default from other authentication providers.
            $Default = GetValue('IsDefault', $Data);
            if ($Default) {
                $this->SQL->Put(
                    $this->Name,
                    array('IsDefault' => 0),
                    array('AuthenticationKey <>' => GetValue('AuthenticationKey', $Data))
                );
            }

            $Fields = $this->Validation->ValidationFields();
            if ($Insert === false) {
                $PrimaryKeyVal = $Row[$this->PrimaryKey];
                $this->Update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));

            } else {
                $PrimaryKeyVal = $this->Insert($Fields);
            }
        } else {
            $PrimaryKeyVal = false;
        }
        return $PrimaryKeyVal;
    }
}
