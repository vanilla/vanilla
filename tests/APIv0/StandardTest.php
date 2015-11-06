<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv0;


class StandardTest extends BaseTest {

    /**
     * @var array
     */
    protected $testUser;

    /**
     * Test registering a user with the basic method.
     */
    public function testRegisterBasic() {
        $this->api()->saveToConfig([
            'Garden.Registration.Method' => 'Basic',
            'Garden.Registration.ConfirmEmail' => false
        ]);

        $user = [
            'Name' => 'frank',
            'Email' => 'frank@example.com',
            'Password' => 'frankwantsin',
            'PasswordMatch' => 'frankwantsin',
            'Gender' => 'm',
            'TermsOfService' => true
        ];

        // Register the user.
        $r = $this->api()->post('/entry/register.json', $user);

        // Look up the user for confirmation.
        $siteUser = $this->api()->get('/profile.json', ['username' => 'frank']);
        $siteUser = $siteUser['Profile'];

        $this->assertEquals($user['Name'], $siteUser['Name']);
//        $this->assertEquals($user['Email'], $siteUser['Email']);
//        $this->assertEquals($user['Gender'], $siteUser['Gender']);

        $this->setTestUser($siteUser);

        $r = $this->api()->signInUser($user['Name'], $user['Password']);
    }

    /**
     * Get the testUser.
     *
     * @return array Returns the testUser.
     */
    public function getTestUser() {
        return $this->testUser;
    }

    /**
     * Set the testUser.
     *
     * @param array $testUser
     * @return StandardTest Returns `$this` for fluent calls.
     */
    public function setTestUser($testUser) {
        $this->testUser = $testUser;
        return $this;
    }
}
