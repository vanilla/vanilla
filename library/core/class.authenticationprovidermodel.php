<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
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