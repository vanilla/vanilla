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

   public function __construct() {
      parent::__construct('UserAuthenticationProvider');
   }
   
   public function GetProviderByKey($AuthenticationProviderKey) {
      $ProviderData = Gdn::SQL()
         ->Select('uap.*')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.AuthenticationKey', $AuthenticationProviderKey)
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
      return $ProviderData;
   }
   
   public function GetProviderByURL($AuthenticationProviderURL) {
      $ProviderData = Gdn::SQL()
         ->Select('uap.*')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.URL', "%{$AuthenticationProviderURL}%")
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
      return $ProviderData;
   }
   
   public function GetProviderByScheme($AuthenticationSchemeAlias, $UserID = NULL) {
      $ProviderQuery = Gdn::SQL()
         ->Select('uap.*')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.AuthenticationSchemeAlias', $AuthenticationSchemeAlias);
      
      if (!is_null($UserID) && $UserID)
         $ProviderQuery->Join('UserAuthentication ua', 'ua.ProviderKey = uap.AuthenticationKey', 'left')->Where('ua.UserID', $UserID);
      
      $ProviderData = $ProviderQuery->Get();
      if ($ProviderData->NumRows())
         return $ProviderData->FirstRow(DATASET_TYPE_ARRAY);
         
      return FALSE;
   }
   
}