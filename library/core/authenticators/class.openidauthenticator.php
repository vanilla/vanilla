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
 * Authentication Module: OpenID
 * 
 * @author Tim Gunter
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

/**
 * Authenticating User Identities via the OpenID Identity Management Framework.
 * Extensive documentation for this method can be found at http://www.openid.net
 * and in particular, here: http://openid.net/specs/openid-authentication-2_0.html
 *
 * @package Garden
 */
class Gdn_OpenidAuthenticator extends Gdn_Authenticator {

   protected $_Consumer;
   
   public function __construct() {
      $this->_DataSourceType = Gdn_Authenticator::DATA_FORM;
      
      $this->HookDataField('Identifier', 'Identifier');
      
      // Initialize built-in authenticator functionality
      parent::__construct();
   }
   
   protected function OpenIDConsumer()
	{
		if (!$this->_Consumer) {
			$StorePath = PATH_CACHE."/openid_store";

			if (!file_exists($StorePath) && !mkdir($StorePath))
			    throw new Exception("The temporary openid authentication cache could not be instantiated at '{$StorePath}'.");

			$FileStore = new Auth_OpenID_FileStore($store_path);
			$this->_Consumer = new Auth_OpenID_Consumer($store);
		}
		return $this->_Consumer;
	}

   /**
    * @param string $Identifier The OP Identifier string from which to extrapolate the discovery URL
    * @param array $ExtensionArguments (optional) Extension arguments to append to the OP URL
    * @param boolean $Immediate (optional) Whether to require that the response be immediate and avoid user interaction
    */
   public function Authenticate() {
   
      if ($this->CurrentStep() != Gdn_Authenticator::MODE_VALIDATE) return Gdn_Authenticator::AUTH_INSUFFICIENT;
   
      $Identifier = $this->GetValue('Identifier');
      $ExtensionArguments = array();
      $Immediate = FALSE;
      
		$OpenIDAuthRequest = $this->OpenIDConsumer()->begin($Identifier);
		
		if (!$OpenIDAuthRequest)
			return false;
		
		// Check if we need to attach any additional parameters to the request
		if (is_array($ExtensionArguments) && sizeof($ExtensionArguments))
			foreach ($ExtensionArguments as $Extension)
				if (sizeof($Extension) == 3)
					call_user_func_array(array($OpenIDAuthRequest,'addExtensionArg'),$Extension);
		
		$AuthenticationRealm = Gdn::Request()->WebPath();
		$AuthenticationResponseHandler = Gdn::Request()->WebPath().'entry/authcallback/'.$this->_AuthenticationSchemeAlias;
		$Redirect = $OpenIDAuthRequest->redirectURL($AuthenticationRealm, $AuthenticationResponseHandler, $Immediate);
		
		if (!$Immediate)
   		header("Location: ".$Redirect);
      else {
         // TODO: Handle/Support 'immediate' auth requests
         return 0;
      }
   }
   
   public function AuthenticateCallback() {
      $UserID = 0;

      // Retrieve matching username/password values
      $UserModel = $this->GetUserModel();
      $UserData = $UserModel->ValidateCredentials($Email, 0, $Password);
      if ($UserData !== False) {
         // Get ID
         $UserID = $UserData->UserID;

         // Get Sign-in permission
         $SignInPermission = $UserData->Admin == '1' ? TRUE : FALSE;
         if ($SignInPermission === FALSE) {
            $PermissionModel = $this->GetPermissionModel();
            foreach($PermissionModel->GetUserPermissions($UserID) as $Permissions) {
               $SignInPermission |= ArrayValue('Garden.SignIn.Allow', $Permissions, FALSE);
            }
         }

         // Update users Information
         $UserID = $SignInPermission ? $UserID : -1;
         if ($UserID > 0) {
            // Create the session cookie
            $this->_Identity->SetIdentity($UserID, $PersistentSession);

            // Update some information about the user...
            $UserModel->UpdateLastVisit($UserID, $UserData->Attributes, $ClientHour);
            
            $this->FireEvent('Authenticated');
         }
      }
      return $UserID;
   }

   public function CurrentStep() {
      return $this->_CheckHookedFields();
   }

   public function DeAuthenticate() {
      Gdn::Authenticator()->SetIdentity(NULL);
   }
   
   public function SuccessResponse() {
      return self::REACT_NONE;
   }
   
   public function WakeUp() {
      // Do nothing.
   }

}