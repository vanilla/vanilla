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
 * An interface for authenticator classes.
 *
 *
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

/**
 * An interface for authenticator classes.
 *
 * @package Garden
 */
interface Gdn_IAuthenticator {


   /**
    * Returns the unique id assigned to the user in the database, 0 if the
    * username/password combination weren't found, or -1 if the user does not
    * have permission to sign in.
    *
    * @param array $Data The data that has to be used to authenticate the data.
    */
   public function Authenticate($Data);


   /**
    * Destroys the user's session information.
    */
   public function DeAuthenticate();


   /**
    * Returns the unique id assigned to the user in the database (retrieved
    * from the session cookie if the cookie authenticates) or FALSE if not
    * found or authentication fails.
    */
   public function GetIdentity();
   
   public function SetIdentity($UserID, $Persist = FALSE);
   
   /**
    * Sets the protocol for authentication (http or https).
    */
   public function Protocol($Value);
   
   /**
    * Returns the url used to register for an account in the application.
    */
   public function RegisterUrl($Redirect = '/');
   
   /**
    * Returns the url used to sign in to the application.
    *
    * @return string
    */
   public function SignInUrl($Redirect = '/');
}
