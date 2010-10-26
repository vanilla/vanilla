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
 * A template for handshake-aware authenticator classes.
 *
 * @author Tim Gunter
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

interface Gdn_IHandshake {

   /**
   * Get the handshake data, such as temporary foreign user identity info
   * 
   * In VanillaConnect and ProxyConnect, this function retrieves the temporary handshake data
   * stored in the authenticator's cookie. This information is used as a parameter when calling
   * the Get____FromHandshake() methods decribed below.
   */
   public function GetHandshake();

   /**
   * Fetches the remote user key from the parsed handshake package
   *    
   * @param mixed $Handshake
   */
   public function GetUserKeyFromHandshake($Handshake);
   public function GetUserNameFromHandshake($Handshake);
   public function GetProviderKeyFromHandshake($Handshake);
   public function GetTokenKeyFromHandshake($Handshake);
   public function GetUserEmailFromHandshake($Handshake);
   
   public function Finalize($UserKey, $UserID, $ConsumerKey, $TokenKey, $Payload);
   
   public function GetHandshakeMode();

}