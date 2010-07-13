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
 * Wrapper for the Portable PHP password hashing framework.
 *
 * @author Damien Lebrun
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */


include PATH_LIBRARY . '/vendors/phpass/PasswordHash.php';


/**
 * Wrapper for the Portable PHP password hashing framework.
 *
 * @namespace Garden.Core
 */
class Gdn_PasswordHash extends PasswordHash {

   public $Weak = FALSE;

   /**
    * Constructor
    *
    * @todo use configuration settings here.
    */
   function __construct() {
      // 8 iteration to create a Portable hash
      parent::PasswordHash(8, TRUE);
   }

   /**
    * Chech a password against a stored password
    *
    * The stored password can be plain, a md5 hash or a phpass hash.
    *
    * If the password wasn't a phppass hash,
    * the Weak property is set to True.
    *
    * @param string $Password
    * @param string $StoredHash
    * @return boolean
    */
   function CheckPassword($Password, $StoredHash, $Method = FALSE) {
      $Result = FALSE;
		switch(strtolower($Method)) {
         case 'phpbb':
            require_once(PATH_LIBRARY.'/vendors/phpbb/phpbbhash.php');
            $Result = phpbb_check_hash($Password, $StoredHash);
            break;
         case 'reset':
            throw new Gdn_UserException(sprintf(T('You need to reset your password.', 'You need to reset your password. This is most likely because an administrator recently changed your account information. Click <a href="%s">here</a> to reset your password.'), Url('entry/passwordrequest')));
            break;
			case 'vbulletin':
				$Salt = trim(substr($StoredHash, -3, 3));
				$VbStoredHash = substr($StoredHash, 0, strlen($StoredHash) - 3);
				$VbHash = md5(md5($Password).$Salt);
				$Result = $VbHash == $VbStoredHash;
				break;
			case 'vanilla':
			default:
				$Result = $this->CheckVanilla($Password, $StoredHash);
		}
		
		return $Result;
   }
	
	function CheckVanilla($Password, $StoredHash) {
		$this->Weak = FALSE;
      if (!isset($StoredHash[0]))
         return FALSE;
      
      if ($StoredHash[0] === '_' || $StoredHash[0] === '$') {
         return parent::CheckPassword($Password, $StoredHash);
      } else if ($Password && $StoredHash !== '*'
         && ($Password === $StoredHash || md5($Password) === $StoredHash)
      ) {
         $this->Weak = TRUE;
         return TRUE;
      }
      return FALSE;
	}
}