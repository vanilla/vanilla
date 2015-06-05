<?php
/**
 * Authentication Module: Local User/Password auth tokens.
 *
 * Validating, Setting, and Retrieving session data in cookies. The HMAC
 * Hashing method used here was inspired by Wordpress 2.5 and this document in
 * particular: http://www.cse.msu.edu/~alexliu/publications/Cookie/cookie.pdf
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Handles authentication with a username and password combo.
 */
class Gdn_PasswordAuthenticator extends Gdn_Authenticator {

    /**
     *
     */
    public function __construct() {
        $this->_DataSourceType = Gdn_Authenticator::DATA_FORM;

        $this->hookDataField('Email', 'Email');
        $this->hookDataField('Password', 'Password');
        $this->hookDataField('RememberMe', 'RememberMe', false);
        $this->hookDataField('ClientHour', 'ClientHour', false);

        // Initialize built-in authenticator functionality
        parent::__construct();
    }

    /**
     * Return the unique id assigned to the user in the database.
     *
     * This method returns 0 if the username/password combination were not found, or -1 if the user does not
     * have permission to sign in.
     *
     * @param string $Email The email address (or unique username) assigned to the user in the database.
     * @param string $Password The password assigned to the user in the database.
     * @return int The UserID of the authenticated user or 0 if one isn't found.
     */
    public function authenticate($Email = '', $Password = '') {
        if (!$Email || !$Password) {
            // We werent given parameters, check if they exist in our DataSource
            if ($this->currentStep() != Gdn_Authenticator::MODE_VALIDATE) {
                return Gdn_Authenticator::AUTH_INSUFFICIENT;
            }

            // Get the values from the DataSource
            $Email = $this->GetValue('Email');
            $Password = $this->GetValue('Password');
            $PersistentSession = $this->GetValue('RememberMe');
            $ClientHour = $this->GetValue('ClientHour');
        } else {
            $PersistentSession = false;
            $ClientHour = 0;
        }

        $UserID = 0;

        // Retrieve matching username/password values
        $UserModel = Gdn::Authenticator()->GetUserModel();
        $UserData = $UserModel->ValidateCredentials($Email, 0, $Password);
        if ($UserData !== false) {
            // Get ID
            $UserID = $UserData->UserID;

            // Get Sign-in permission
            $SignInPermission = $UserData->Admin ? true : false;
            if ($SignInPermission === false && !$UserData->Banned) {
                $PermissionModel = Gdn::Authenticator()->GetPermissionModel();
                foreach ($PermissionModel->GetUserPermissions($UserID) as $Permissions) {
                    $SignInPermission |= ArrayValue('Garden.SignIn.Allow', $Permissions, false);
                }
            }

            // Update users Information
            $UserID = $SignInPermission ? $UserID : -1;
            if ($UserID > 0) {
                // Create the session cookie
                $this->setIdentity($UserID, $PersistentSession);

                // Update some information about the user...
                $UserModel->UpdateVisit($UserID, $ClientHour);

                Gdn::Authenticator()->Trigger(Gdn_Authenticator::AUTH_SUCCESS);
                $this->FireEvent('Authenticated');
            } else {
                Gdn::Authenticator()->Trigger(Gdn_Authenticator::AUTH_DENIED);
            }
        }
        return $UserID;
    }

    /**
     * Return the current authentication step.
     *
     * @return string
     */
    public function currentStep() {
        // Was data submitted through the form already?
        if (is_object($this->_DataSource) && ($this->_DataSource == $this || $this->_DataSource->IsPostBack() === true)) {
            return $this->_checkHookedFields();
        }

        return Gdn_Authenticator::MODE_GATHER;
    }

    /**
     * Destroys the user's session cookie - essentially de-authenticating them.
     */
    public function deAuthenticate() {
        $this->setIdentity(null);

        return Gdn_Authenticator::AUTH_SUCCESS;
    }

    public function loginResponse() {
        return Gdn_Authenticator::REACT_RENDER;
    }

    public function partialResponse() {
        return Gdn_Authenticator::REACT_REDIRECT;
    }

    public function successResponse() {
        return Gdn_Authenticator::REACT_REDIRECT;
    }

    public function logoutResponse() {
        return Gdn_Authenticator::REACT_REDIRECT;
    }

    public function repeatResponse() {
        return Gdn_Authenticator::REACT_RENDER;
    }

    // What to do if the entry/auth/* page is triggered but login is denied or fails
    public function failedResponse() {
        return Gdn_Authenticator::REACT_RENDER;
    }

    public function wakeUp() {
        // Do nothing.
    }

    public function getURL($URLType) {
        // We aren't overriding anything
        return false;
    }
}
