<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Core;

use Gdn_PasswordHash;

/**
 * Test the the {@link Gdn_PasswordHash} class.
 */
class PasswordTest extends \PHPUnit_Framework_TestCase {

    /**
     * Make sure an empty password fails.
     */
    public function testEmptyHash() {
        $pw = new Gdn_PasswordHash();

        $password = '';
        $this->assertFalse($pw->checkPassword($password, ''));
    }

    /**
     * Make sure improper string passwords don't verify against a hash.
     *
     * Since `0 == 'any string'` we want to make sure our passwords are vulnerable to this kind of thing.
     */
    public function testBadStringPasswords() {
        $pw = new Gdn_PasswordHash();

        $hash = $pw->hashPassword('password');

        $this->assertFalse($pw->checkPassword('', $hash, 'Vanilla'));
        $this->assertFalse($pw->checkPassword(0, $hash, 'Vanilla'));
        $this->assertFalse($pw->checkPassword(false, $hash, 'Vanilla'));
        $this->assertFalse($pw->checkPassword(true, $hash, 'Vanilla'));
        $this->assertFalse($pw->checkPassword(null, $hash, 'Vanilla'));
    }

    /**
     * An empty password should always fail.
     */
    public function testEmptyPassword() {
        $pw = new Gdn_PasswordHash();

        $hash = $pw->hashPassword('');
        $this->assertFalse($pw->checkPassword('', $hash, 'Vanilla'));
    }

    /**
     * A plaintext password will verify, but be marked as weak.
     */
    public function testPlainTextPassword() {
        $pw = new Gdn_PasswordHash();

        $password = 'password123';
        $this->assertTrue($pw->checkPassword($password, $password, 'Vanilla'));
        $this->assertTrue($pw->Weak);
    }

    /**
     * A hashed password should not verify if it looks like a crypt password.
     */
    public function testNoPlaintextHash() {
        $pw = new Gdn_PasswordHash();

        $password = 'letmeinPLEASE!!!!';
        $hash = $pw->hashPassword($password);
        $this->assertTrue($pw->checkPassword($password, $hash, 'Vanilla'));
        $this->assertFalse($pw->checkPassword($hash, $hash, 'Vanilla'));
    }

    /**
     * Vanilla 1 used MD5 so we should support that, but mark them as weak.
     */
    public function testOldMd5Passwords() {
        $pw = new Gdn_PasswordHash();

        $password = 'don\'tkickmeout';
        $hash = md5($password);
        $this->assertTrue($pw->checkPassword($password, $hash, 'Vanilla'));
        $this->assertTrue($pw->Weak);
    }

    /**
     * Make sure that the **password_** functions are backwards compatible.
     */
    public function testDifferentHashVerifys() {
        $pwPortable = new Gdn_PasswordHash();
        $pwPortable->portable_hashes = true;

        $pw = new Gdn_PasswordHash();
        $pw->portable_hashes = false;

        $password = 'letmein';

        // Hash the password various ways.
        $hashPortable = $pwPortable->hashPassword($password);
        $hashPhpass = $pw->hashPasswordPhpass($password);
        $hash = $pw->hashPassword($password);

        // Make sure the password hashes are different.
        $this->assertNotEquals(substr($hashPortable, 0, 3), substr($hash, 0, 3));

        // Make sure all of the passwords verify.
        $this->assertTrue($pwPortable->checkPassword($password, $hashPortable, 'Vanilla'));
        $this->assertTrue($pwPortable->checkPassword($password, $hashPhpass, 'Vanilla'));
        $this->assertTrue($pwPortable->checkPassword($password, $hash, 'Vanilla'));

        $this->assertTrue($pw->checkPassword($password, $hashPortable, 'Vanilla'));
        $this->assertTrue($pw->checkPassword($password, $hashPhpass, 'Vanilla'));
        $this->assertTrue($pw->checkPassword($password, $hash, 'Vanilla'));

        // Make sure the Phpass can verify the passwords.
        $this->assertTrue($pw->checkPassword($password, $hashPortable, 'Phpass'));
        $this->assertTrue($pw->checkPassword($password, $hashPhpass, 'Phpass'));
        $this->assertTrue($pw->checkPassword($password, $hash, 'Phpass')); // mimics back port
    }
}
