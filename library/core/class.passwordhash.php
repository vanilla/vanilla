<?php
/**
 * Gdn_PasswordHash
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

use Garden\Password\DjangoPassword;
use Garden\Password\IpbPassword;
use Garden\Password\JoomlaPassword;
use Garden\Password\MybbPassword;
use Garden\Password\PhpassPassword;
use Garden\Password\PhpbbPassword;
use Garden\Password\PasswordInterface;
use Garden\Password\PhpPassword;
use Garden\Password\PunbbPassword;
use Garden\Password\SmfPassword;
use Garden\Password\VbulletinPassword;
use Garden\Password\VanillaPassword;
use Garden\Password\XenforoPassword;

/**
 * Wrapper for Garden Password.
 */
class Gdn_PasswordHash {

    /** @var array */
    protected $algorithms = [];

    /** @var bool */
    public $portable_hashes = false;

    /** @var bool */
    public $Weak = false;

    /**
     * Check a password against a stored password.
     *
     * The stored password can be plain, a md5 hash or a phpass hash.
     * If the password wasn't a phppass hash, the Weak property is set to **true**.
     *
     * @param string $Password The plaintext password to check.
     * @param string $StoredHash The password hash stored in the database.
     * @param bool|string $Method The password hashing method.
     * @return bool Returns **true** if the password matches the hash or **false** if it doesn't.
     * @throws Gdn_UserException if the password needs to be reset.
     * @throws Gdn_UserException if the password has a method of "random".
     */
    public function checkPassword($Password, $StoredHash, $Method = false) {
        $Result = false;

        if (empty($Password) || empty($StoredHash)) {
            // We don't care if there is a strong password hash. Empty passwords are not cool
            return false;
        }

        switch (strtolower($Method)) {
            case 'crypt':
                $Result = (crypt($Password, $StoredHash) === $StoredHash);
                break;
            case 'django':
                $Result = $this->getAlgorithm('Django')->verify($Password, $StoredHash);
                break;
            case 'drupal':
                require_once PATH_LIBRARY.'/vendors/drupal/password.inc.php';
                $Result = Drupal\user_check_password($Password, $StoredHash);
                break;
            case 'ipb':
                $Result = $this->getAlgorithm('Ipb')->verify($Password, $StoredHash);
                break;
            case 'joomla':
                $Result = $this->getAlgorithm('Joomla')->verify($Password, $StoredHash);
                break;
            case 'mybb':
                $Result = $this->getAlgorithm('Mybb')->verify($Password, $StoredHash);
                break;
            case 'phpass':
                $Result = $this->getAlgorithm('Phpass')->verify($Password, $StoredHash);
                break;
            case 'phpbb':
                $Result = $this->getAlgorithm('Phpbb')->verify($Password, $StoredHash);
                break;
            case 'punbb':
                $Result = $this->getAlgorithm('Punbb')->verify($Password, $StoredHash);
                break;
            case 'reset':
                $ResetUrl = url('entry/passwordrequest'.(Gdn::request()->get('display') ? '?display='.urlencode(Gdn::request()->get('display')) : ''));
                throw new Gdn_UserException(sprintf(T('You need to reset your password.', 'You need to reset your password. This is most likely because an administrator recently changed your account information. Click <a href="%s">here</a> to reset your password.'), $ResetUrl));
                break;
            case 'random':
                $ResetUrl = url('entry/passwordrequest'.(Gdn::request()->get('display') ? '?display='.urlencode(Gdn::request()->get('display')) : ''));
                throw new Gdn_UserException(sprintf(T('You don\'t have a password.', 'Your account does not have a password assigned to it yet. Click <a href="%s">here</a> to reset your password.'), $ResetUrl));
                break;
            case 'smf':
                $Result = $this->getAlgorithm('Smf')->verify($Password, $StoredHash);
                break;
            case 'vbulletin':
                $Result = $this->getAlgorithm('Vbulletin')->verify($Password, $StoredHash);
                break;
            case 'vbulletin5': // Since 5.1
                // md5 sum the raw password before crypt. Nice work as usual vb.
                $Result = $StoredHash === crypt(md5($Password), $StoredHash);
                break;
            case 'xenforo':
                $Result = $this->getAlgorithm('Xenforo')->verify($Password, $StoredHash);
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
                $this->Weak = $this->getAlgorithm('Vanilla')->needsRehash($StoredHash);
                $Result = $this->getAlgorithm('Vanilla')->verify($Password, $StoredHash);
        }

        return $Result;
    }

    /**
     * Check a YAF hash.
     *
     * @param string $Password The plaintext password to check.
     * @param string $StoredHash The password hash stored in the database.
     * @return bool Returns **true** if the password matches the hash or **false** if it doesn't.
     */
    protected function checkYAF($Password, $StoredHash) {
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

    /**
     * Grab an instance of a valid password algorithm.
     *
     * @param string $algorithm
     * @return object
     * @throws Exception if a matching class cannot be found
     * @throws Exception if the found class is not an instance of PasswordInterface
     */
    protected function getAlgorithm($algorithm) {
        if (!stringEndsWith($algorithm, 'Password')) {
            $algorithm .= 'Password';
        }

        if (!array_key_exists($algorithm, $this->algorithms)) {
            $class = "\\Garden\\Password\\{$algorithm}";

            if (!class_exists($class)) {
                throw new Exception(sprintf(t('Password hashing algorithm does not exist: %s'), $algorithm));
            }

            $instance = new $class();
            if ($instance instanceof PasswordInterface) {
                $this->algorithms[$algorithm] = $instance;
            } else {
                throw new Exception(sprintf(t('Invalid password hashing algorithm: %s')), $algorithm);
            }
        }

        return $this->algorithms[$algorithm];
    }

    /**
     * Create a password hash.
     *
     * This method tries to use PHP's built in {@link password_hash()} function and falls back to the default
     * implementation if that's not possible.
     *
     * @param string $password The plaintext password to hash.
     * @return string Returns a secure hash of {@link $password}.
     */
    public function hashPassword($password) {
        if (!$this->portable_hashes && function_exists('password_hash')) {
            // Use PHP's native password hashing function.
            $result = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $result = $this->hashPasswordPhpass($password);
        }
        return $result;
    }

    /**
     * Create a password hash using Phpass's algorithm.
     *
     * @param string $password The plaintext password to hash.
     * @return string Returns a password hash.
     */
    public function hashPasswordPhpass($password) {
        $phpass = $this->getAlgorithm('Phpass');

        if ($this->portable_hashes) {
            $phpass->setHashMethod(PhpassPassword::HASH_PHPASS);
        } else {
            $phpass->setHashMethod(PhpassPassword::HASH_BLOWFISH);
        }

        return $phpass->hash($password);
    }
}
