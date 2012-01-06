<?php if (!defined('APPLICATION')) exit();

/**
 * Wrapper for the Portable PHP password hashing framework.
 *
 * @author Damien Lebrun
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
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

   function CheckDjango($Password, $StoredHash) {
      if (strpos($StoredHash, '$') === FALSE) {
         return md5($Password) == $StoredHash;
      } else {
         list($Method, $Salt, $Hash) = explode('$', $StoredHash);
         switch (strtolower($Method)) {
            case 'crypt':
               return crypt($Password, $Salt) == $Hash;
            case 'md5':
               return md5($Salt.$Password) == $Hash;
            case 'sha1':
            default:
               return sha1($Salt.$Password) == $Hash;
         }
      }
   }

   /**
    * Check a password against a stored password.
    *
    * The stored password can be plain, a md5 hash or a phpass hash.
    * If the password wasn't a phppass hash, the Weak property is set to True.
    *
    * @param string $Password
    * @param string $StoredHash
    * @param string $Method
    * @param string $Username
    * @return boolean
    */
   function CheckPassword($Password, $StoredHash, $Method = FALSE, $Username = NULL) {
      $Result = FALSE;
      $ResetUrl = Url('entry/passwordrequest'.(Gdn::Request()->Get('display') ? '?display='.urlencode(Gdn::Request()->Get('display')) : ''));
      switch(strtolower($Method)) {
         case 'django':
            $Result = $this->CheckDjango($Password, $StoredHash);
            break;
         case 'phpbb':
            require_once(PATH_LIBRARY.'/vendors/phpbb/phpbbhash.php');
            $Result = phpbb_check_hash($Password, $StoredHash);
            break;
         case 'punbb':
            $Parts = explode('$', $StoredHash);
            $StoredHash = GetValue(0, $Parts);
            $StoredSalt = GetValue(1, $Parts);
            
            if (md5($Password) == $StoredHash)
               $Result = TRUE;
            elseif (sha1($StoredSalt.sha1($Password)) == $StoredHash)
               $Result = TRUE;
            else
               $Result = FALSE;
            
            break;
         case 'reset':
            throw new Gdn_UserException(sprintf(T('You need to reset your password.', 'You need to reset your password. This is most likely because an administrator recently changed your account information. Click <a href="%s">here</a> to reset your password.'), $ResetUrl));
            break;
         case 'random':
            throw new Gdn_UserException(sprintf(T('You don\'t have a password.', 'Your account does not have a password assigned to it yet. Click <a href="%s">here</a> to reset your password.'), $ResetUrl));
            break;
         case 'smf':
            $Result = (sha1(strtolower($Username).$Password) == $StoredHash);
            break;
         case 'vbulletin':
            // assume vbulletin's password hash has a fixed length of 32, the salt length will vary between version 3 and 4
            $SaltLength = strlen($StoredHash) - 32;
            $Salt = trim(substr($StoredHash, -$SaltLength, $SaltLength));
            $VbStoredHash = substr($StoredHash, 0, strlen($StoredHash) - $SaltLength);
            
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