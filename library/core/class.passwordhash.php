<?php
/**
 * Gdn_PasswordHash
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param string $password The plaintext password to check.
     * @param string $storedHash The password hash stored in the database.
     * @param bool|string $method The password hashing method.
     * @return bool Returns **true** if the password matches the hash or **false** if it doesn't.
     * @throws Gdn_UserException if the password needs to be reset.
     * @throws Gdn_UserException if the password has a method of "random".
     */
    public function checkPassword($password, $storedHash, $method = false) {
        $result = false;

        if (empty($password) || empty($storedHash)) {
            // We don't care if there is a strong password hash. Empty passwords are not cool
            return false;
        }

        switch (strtolower($method)) {
            case 'crypt':
                $result = (crypt($password, $storedHash) === $storedHash);
                break;
            case 'django':
                $result = $this->getAlgorithm('Django')->verify($password, $storedHash);
                break;
            case 'drupal':
                $result = $this->getAlgorithm('Drupal')->verify($password, $storedHash);
                break;
            case 'ipb':
                $result = $this->getAlgorithm('Ipb')->verify($password, $storedHash);
                break;
            case 'joomla':
                $result = $this->getAlgorithm('Joomla')->verify($password, $storedHash);
                break;
            case 'mybb':
                $result = $this->getAlgorithm('Mybb')->verify($password, $storedHash);
                break;
            case 'phpass':
                $result = $this->getAlgorithm('Phpass')->verify($password, $storedHash);
                break;
            case 'phpbb':
                $result = $this->getAlgorithm('Phpbb')->verify($password, $storedHash);
                break;
            case 'punbb':
                $result = $this->getAlgorithm('Punbb')->verify($password, $storedHash);
                break;
            case 'reset':
                $resetUrl = url('entry/passwordrequest'.(Gdn::request()->get('display') ? '?display='.urlencode(Gdn::request()->get('display')) : ''));
                throw new Gdn_UserException(sprintf(t('You need to reset your password.', 'You need to reset your password. This is most likely because an administrator recently changed your account information. Click <a href="%s">here</a> to reset your password.'), $resetUrl));
                break;
            case 'random':
                $resetUrl = url('entry/passwordrequest'.(Gdn::request()->get('display') ? '?display='.urlencode(Gdn::request()->get('display')) : ''));
                throw new Gdn_UserException(sprintf(t('You don\'t have a password.', 'Your account does not have a password assigned to it yet. Click <a href="%s">here</a> to reset your password.'), $resetUrl));
                break;
            case 'smf':
                $result = $this->getAlgorithm('Smf')->verify($password, $storedHash);
                break;
            case 'vbulletin':
                $result = $this->getAlgorithm('Vbulletin')->verify($password, $storedHash);
                break;
            case 'vbulletin5': // Since 5.1
                // md5 sum the raw password before crypt. Nice work as usual vb.
                $result = $storedHash === crypt(md5($password), $storedHash);
                break;
            case 'xenforo':
                $result = $this->getAlgorithm('Xenforo')->verify($password, $storedHash);
                break;
            case 'yaf':
                $result = $this->checkYAF($password, $storedHash);
                break;
            case 'webwiz':
                $result = $this->getAlgorithm('WebWiz')->verify($password, $storedHash);
                break;
            case 'vanilla':
            default:
                $this->Weak = $this->getAlgorithm('Vanilla')->needsRehash($storedHash);
                $result = $this->getAlgorithm('Vanilla')->verify($password, $storedHash);
        }

        return $result;
    }

    /**
     * Check a YAF hash.
     *
     * @param string $password The plaintext password to check.
     * @param string $storedHash The password hash stored in the database.
     * @return bool Returns **true** if the password matches the hash or **false** if it doesn't.
     */
    protected function checkYAF($password, $storedHash) {
        if (strpos($storedHash, '$') === false) {
            return md5($password) == $storedHash;
        } else {
            ini_set('mbstring.func_overload', "0");
            list($method, $salt, $hash, $compare) = explode('$', $storedHash);

            $salt = base64_decode($salt);
            $hash = bin2hex(base64_decode($hash));
            $password = mb_convert_encoding($password, 'UTF-16LE');

            // There are two ways of building the hash string in yaf.
            if ($compare == 's') {
                // Compliant with ASP.NET Membership method of hash/salt
                $hashString = $salt.$password;
            } else {
                // The yaf algorithm has a quirk where they knock a
                $hashString = substr($password, 0, -1).$salt.chr(0);
            }

            $calcHash = hash($method, $hashString);
            return $hash == $calcHash;
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
