<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * An abstract template for authenticator classes.
 *
 * @author Tim Gunter
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

/**
 * An abstract template for authenticator classes.
 *
 * @package Garden
 */
abstract class Gdn_Authenticator extends Gdn_Pluggable {
   
   const DATA_FORM            = 'data form';
   const DATA_REQUEST         = 'data request';
   
   const MODE_REPEAT          = 'already logged in';
   const MODE_GATHER          = 'gather';
   const MODE_VALIDATE        = 'validate';
   
   const AUTH_DENIED          = 0;
   const AUTH_PERMISSION      = -1;
   const AUTH_INSUFFICIENT    = -2;
   const AUTH_PARTIAL         = -3;
   const AUTH_SUCCESS         = -4;
   const AUTH_ABORTED         = -5;
   
   const REACT_RENDER         = 0;
   const REACT_EXIT           = 1;
   const REACT_REDIRECT       = 2;
   const REACT_REMOTE         = 3;
   
   const URL_REGISTER         = 'RegistrationUrl';
   const URL_SIGNIN           = 'SignInUrl';
   const URL_SIGNOUT          = 'SignOutUrl';
   
   /**
    * Alias of the authentication scheme to use, e.g. "password" or "openid"
    *
    */
   protected $_AuthenticationSchemeAlias = NULL;
   
   /**
    * Contains authenticator configuration information, such as a preshared key or
    * discovery URL.
    *
    */
   protected $_AuthenticationProviderModel = NULL;
   protected $_AuthenticationProviderData = NULL;
   
   protected $_DataSourceType = self::DATA_FORM;
   protected $_DataSource = NULL;
   public $_DataHooks = array();

   /**
    * Returns the unique id assigned to the user in the database, 0 if the
    * username/password combination weren't found, or -1 if the user does not
    * have permission to sign in.
    */
   abstract public function Authenticate();
   abstract public function CurrentStep();
   abstract public function DeAuthenticate();
   abstract public function WakeUp();
   
   // What to do if entry/auth/* is called while the user is logged out. Should normally be REACT_RENDER
   abstract public function LoginResponse();
   
   // What to do after part 1 of a 2 part authentication process. This is used in conjunction with OAauth/OpenID type authentication schemes
   abstract public function PartialResponse();
   
   // What to do after authentication has succeeded. 
   abstract public function SuccessResponse();
   
   // What to do if the entry/auth/* page is triggered for a user that is already logged in
   abstract public function RepeatResponse();
   
   // Get one of the three Forwarding URLs (Registration, SignIn, SignOut)
   abstract public function GetURL($URLType);

   public function __construct() {
      // Figure out what the authenticator alias is
      $this->_AuthenticationSchemeAlias = $this->GetAuthenticationSchemeAlias();
      
      // Initialize gdn_pluggable
      parent::__construct();
   }
   
   public function DataSourceType() {
      return $this->_DataSourceType;
   }
   
   public function FetchData($DataSource) {
      $this->_DataSource = $DataSource;
      
      if (sizeof($this->_DataHooks))
         foreach ($this->_DataHooks as $DataTarget => $DataHook)
            $this->_DataHooks[$DataTarget]['value'] = $this->_DataSource->GetValue($DataHook['lookup'], FALSE);
   }
   
   public function HookDataField($InternalFieldName, $DataFieldName, $DataFieldRequired = TRUE) {
      $this->_DataHooks[$InternalFieldName] = array('lookup' => $DataFieldName, 'required' => $DataFieldRequired);
   }

   public function GetValue($Key, $Default = FALSE) {
      if (array_key_exists($Key, $this->_DataHooks) && array_key_exists('value', $this->_DataHooks[$Key]))
         return $this->_DataHooks[$Key]['value'];
         
      return $Default;
   }
   
   protected function _CheckHookedFields() {
      foreach ($this->_DataHooks as $DataKey => $DataValue) {
         if ($DataValue['required'] == TRUE && (!array_key_exists('value', $DataValue) || $DataValue['value'] == NULL)) return Gdn_Authenticator::MODE_GATHER;
      }
      
      return Gdn_Authenticator::MODE_VALIDATE;
   }
   
   public function LoadProvider($AuthenticationProviderLookupKey) {
      
      $this->_AuthenticationProviderModel = new Gdn_AuthenticationProviderModel();
      $AuthenticatorData = $this->_AuthenticationProviderModel->GetProviderByKey($AuthenticationProviderLookupKey);
      
      if ($AuthenticatorData) {
         $this->_AuthenticationProviderData = $AuthenticatorData;
      }
      else {
         throw new Exception("Tried to load bogus authentication provider via lookup key'{$AuthenticationProviderLookupKey}'. No information stored for this key.");
      }
   }
   
   public function GetAuthenticationSchemeAlias() {
      $StipSuffix = str_replace('Gdn_','',__CLASS__);
      $ClassName = str_replace('Gdn_','',get_class($this));
      $ClassName = substr($ClassName,-strlen($StipSuffix)) == $StipSuffix ? substr($ClassName,0,-strlen($StipSuffix)) : $ClassName;
      return strtolower($ClassName);
   }

   public function GetProviderValue($Key, $Default = FALSE) {
      if (array_key_exists($Key, $this->_AuthenticationProviderData))
         return $this->_AuthenticationProviderData[$Key];
         
      return $Default;
   }
   
   public function SetIdentity($UserID, $Persist = TRUE) {
      $AuthenticationSchemeAlias = $this->GetAuthenticationSchemeAlias();
      Gdn::Authenticator()->SetIdentity($UserID, $Persist);
      Gdn::Session()->Start();
      
      if ($UserID > 0) {
         Gdn::Session()->SetPreference('Authenticator', $AuthenticationSchemeAlias);
      } else {
         Gdn::Session()->SetPreference('Authenticator', '');
      }
   }
   
   public function GetProviderKey() {
      return $this->GetProviderValue('AuthenticationKey');
   }
   
   public function GetProviderSecret() {
      return $this->GetProviderValue('AssociationSecret');
   }
   
   public function GetProviderUrl() {
      return $this->GetProviderValue('URL');
   }

}
