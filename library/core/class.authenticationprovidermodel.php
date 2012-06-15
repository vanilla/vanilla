<?php if (!defined('APPLICATION')) exit();

/**
 * Authentication Helper: Authentication Provider Model
 * 
 * Used to access and manipulate the UserAuthenticationProvider table.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0.10
 */

class Gdn_AuthenticationProviderModel extends Gdn_Model {
   const COLUMN_KEY = 'AuthenticationKey';
   const COLUMN_ALIAS = 'AuthenticationSchemeAlias';
   const COLUMN_NAME = 'Name';
   
   public function __construct() {
      parent::__construct('UserAuthenticationProvider');
      $this->PrimaryKey = self::COLUMN_KEY;
   }
   
   protected static function _Calculate(&$Row) {
      if (!$Row)
         return;
      
      $Attributes = @unserialize($Row['Attributes']);
      if (is_array($Attributes))
         $Row = array_merge($Attributes, $Row);
      unset($Row['Attributes']);
   }
   
   public static function GetProviderByKey($AuthenticationProviderKey) {
      $ProviderData = Gdn::SQL()
         ->Select('uap.*')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.AuthenticationKey', $AuthenticationProviderKey)
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
      
      self::_Calculate($ProviderData);
         
      return $ProviderData;
   }
   
   public static function GetProviderByURL($AuthenticationProviderURL) {
      $ProviderData = Gdn::SQL()
         ->Select('uap.*')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.URL', "%{$AuthenticationProviderURL}%")
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
      self::_Calculate($ProviderData);
         
      return $ProviderData;
   }
   
   public static function GetProviderByScheme($AuthenticationSchemeAlias, $UserID = NULL) {
      $ProviderQuery = Gdn::SQL()
         ->Select('uap.*')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.AuthenticationSchemeAlias', $AuthenticationSchemeAlias);
      
      if (!is_null($UserID) && $UserID)
         $ProviderQuery->Join('UserAuthentication ua', 'ua.ProviderKey = uap.AuthenticationKey', 'left')->Where('ua.UserID', $UserID);
      
      $ProviderData = $ProviderQuery->Get();
      if ($ProviderData->NumRows()) {
         $Result = $ProviderData->FirstRow(DATASET_TYPE_ARRAY);
         self::_Calculate($Result);
         return $Result;
      }
         
      return FALSE;
   }
   
   public function Save($Data, $Settings = FALSE) {
      // Grab the current record.
      $Row = FALSE;
      if (isset($Data[$this->PrimaryKey]))
         $Row = $this->GetWhere(array($this->PrimaryKey => $Data[$this->PrimaryKey]))->FirstRow(DATASET_TYPE_ARRAY);
      elseif ($PK = GetValue('PK', $Settings)) {
         $Row = $this->GetWhere(array($PK => $Data[$PK]));
      }
      
      // Get the columns and put the extended data in the attributes.
      $this->DefineSchema();
      $Columns = $this->Schema->Fields();
      $Attributes = array_diff_key($Data, $Columns, array('TransientKey' => 1, 'hpt' => 1, 'Save' => 1));
            
      if (!empty($Attributes)) {
         $Data = array_diff($Data, $Attributes);
         $Data['Attributes'] = serialize($Attributes);
      }
      
      $Insert = !$Row;
      if ($Insert) {
         $this->AddInsertFields($Data);
      } else {
         $this->AddUpdateFields($Data);
      }

      // Validate the form posted values
      if ($this->Validate($Data, $Insert) === TRUE) {
         $Fields = $this->Validation->ValidationFields();
         if ($Insert === FALSE) {
            $PrimaryKeyVal = $Row[$this->PrimaryKey];
            $this->Update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));
            
         } else {
            $PrimaryKeyVal = $this->Insert($Fields);
         }
      } else {
         $PrimaryKeyVal = FALSE;
      }
      return $PrimaryKeyVal;
   }
   
}