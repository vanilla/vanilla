<?php
/**
 * Gdn_PasswordHash
 *
 * @author Damien Lebrun
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

include_once PATH_LIBRARY.'/vendors/phpass/PasswordHash.php';

/**
 * Wrapper for the Portable PHP password hashing framework.
 */
class Gdn_PasswordHash extends PasswordHash {

    /** @var bool  */
    public $Weak = false;

    /**
     * Constructor.
     */
    function __construct() {
        // 8 iteration to create a Portable hash
        parent::passwordHash(8, false);
    }

    /**
     * Check a Django hash.
     *
     * @param $Password
     * @param $StoredHash
     * @return bool
     */
    function checkDjango($Password, $StoredHash) {
        if (strpos($StoredHash, '$') === false) {
            return md5($Password) == $StoredHash;
        } else {
            list($Method, $Salt, $Hash) = explode('$', $StoredHash);
            switch (strtolower($Method)) {
                case 'crypt':
                    return crypt($Password, $Salt) == $Hash;
                case 'md5':
                    return md5($Salt.$Password) == $Hash;
                case 'sha256':
                    return hash('sha256', $Salt.$Password) == $Hash;
                case 'sha1':
                default:
                    return sha1($Salt.$Password) == $Hash;
            }
        }
    }

    /**
     * Check an IPB hash.
     *
     * @param $Password
     * @param $StoredHash
     * @return bool
     */
    function checkIPB($Password, $StoredHash) {
        $Parts = explode('$', $StoredHash, 2);
        if (count($Parts) == 2) {
            $Hash = $Parts[0];
            $Salt = $Parts[1];

            $CalcHash = md5(md5($Salt).md5($Password));
            return $CalcHash == $Hash;
        }
        return false;
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
    function checkPassword($Password, $StoredHash, $Method = false, $Username = null) {
        $Result = false;
        $ResetUrl = Url('entry/passwordrequest'.(Gdn::request()->get('display') ? '?display='.urlencode(Gdn::request()->get('display')) : ''));
        switch (strtolower($Method)) {
            case 'crypt':
                $Result = (crypt($Password, $StoredHash) === $StoredHash);
                break;
            case 'django':
                $Result = $this->checkDjango($Password, $StoredHash);
                break;
            case 'drupal':
                require_once PATH_LIBRARY.'/vendors/drupal/password.inc.php';
                $Result = Drupal\user_check_password($Password, $StoredHash);
                break;
            case 'ipb':
                $Result = $this->checkIPB($Password, $StoredHash);
                break;
            case 'joomla':
                $Parts = explode(':', $StoredHash, 2);
                $Hash = val(0, $Parts);
                $Salt = val(1, $Parts);
                $ComputedHash = md5($Password.$Salt);
                $Result = $ComputedHash == $Hash;
                break;
            case 'mybb':
                // Hash has a fixed length of 32, and we concat the salt to it.
                $SaltLength = strlen($StoredHash) - 32;
                $Salt = trim(substr($StoredHash, -$SaltLength, $SaltLength));
                $MyStoredHash = substr($StoredHash, 0, strlen($StoredHash) - $SaltLength);
                $MyHash = md5(md5($Salt).md5($Password));
                $Result = $MyHash == $MyStoredHash;
                break;
            case 'phpbb':
                require_once(PATH_LIBRARY.'/vendors/phpbb/phpbbhash.php');
                $Result = phpbb_check_hash($Password, $StoredHash);
                break;
            case 'punbb':
                $Parts = explode('$', $StoredHash);
                $StoredHash = val(0, $Parts);
                $StoredSalt = val(1, $Parts);

                if (md5($Password) == $StoredHash) {
                    $Result = true;
                } elseif (sha1($Password) == $StoredHash)
                    $Result = true;
                elseif (sha1($StoredSalt.sha1($Password)) == $StoredHash)
                    $Result = true;
                else {
                    $Result = false;
                }

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
            case 'vbulletin5': // Since 5.1
                // md5 sum the raw password before crypt. Nice work as usual vb.
                $Result = $StoredHash === crypt(md5($Password), $StoredHash);
                break;
            case 'xenforo':
                $Data = @unserialize($StoredHash);
                if (!is_array($Data)) {
                    $Result = false;
                } else {
                    $Hash = val('hash', $Data);
                    $Function = val('hashFunc', $Data);
                    if (!$Function) {
                        $Function = strlen($Hash) == 32 ? 'md5' : 'sha1';
                    }
                    $Salt = val('salt', $Data);
                    $ComputedHash = hash($Function, hash($Function, $Password).$Salt);

                    $Result = $ComputedHash == $Hash;
                }
                break;
            case 'yaf':
                $Result = $this->checkYAF($Password, $StoredHash);
                break;
            case 'webwiz':
                require_once PATH_LIBRARY.'/vendors/misc/functions.webwizhash.php';
                $Result = ww_CheckPassword($Password, $StoredHash);
                break;
            case 'vanilla':
            default:
                $Result = $this->checkVanilla($Password, $StoredHash);
        }
        return $Result;
    }

    /**
     * Check a Vanilla hash.
     *
     * @param $Password
     * @param $StoredHash
     * @return bool
     */
    function checkVanilla($Password, $StoredHash) {
        $this->Weak = false;
        if (!isset($StoredHash[0])) {
            return false;
        }

        if ($StoredHash[0] === '_' || $StoredHash[0] === '$') {
            $Result = parent::checkPassword($Password, $StoredHash);

            // Check to see if this password should be rehashed to crypt-blowfish.
            if (!$this->portable_hashes && CRYPT_BLOWFISH == 1 && substr($StoredHash, 0, 3) === '$P$') {
                $this->Weak = true;
            }

            return $Result;
        } elseif ($Password && $StoredHash !== '*'
            && ($Password === $StoredHash || md5($Password) === $StoredHash)
        ) {
            $this->Weak = true;
            return true;
        }
        return false;
    }

    /**
     * Check a YAF hash.
     *
     * @param $Password
     * @param $StoredHash
     * @return bool
     */
    function checkYAF($Password, $StoredHash) {
        if (strpos($StoredHash, '$') === false) {
            return md5($Password) == $StoredHash;
        } else {
            ini_set('mbstring.func_overload', "0");
            list($Method, $Salt, $Hash, $Compare) = explode('$', $StoredHash);

            $Salt = base64_decode($Salt);
            $Hash = bin2hex(base64_decode($Hash));
            $Password = mb_convert_encoding($Password, 'UTF-16LE');

            // There are two ways of building the hash string in yaf.
            if ($Compare == 's') {
                // Compliant with ASP.NET Membership method of hash/salt
                $HashString = $Salt.$Password;
            } else {
                // The yaf algorithm has a quirk where they knock a
                $HashString = substr($Password, 0, -1).$Salt.chr(0);
            }

            $CalcHash = hash($Method, $HashString);
            return $Hash == $CalcHash;
        }
    }
}
