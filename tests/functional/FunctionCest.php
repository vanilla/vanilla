<?php
use \FunctionalTester;

class SigninTest {

    public function _before() {
    }

    public function _after() {
    }

    public function loginTest(FunctionalTester $I) {
        $I->wantTo('log in as admin user');
        $I->amOnPage('/entry/signin');
        $I->fillField('Email', VANILLA_ADMIN_USER);
        $I->fillField('Password', VANILLA_ADMIN_PASSWORD);
        $I->click('Sign_In');
        $I->see('admin');

    }

    public function loginFailTest(FunctionalTester $I) {
        $I->wantTo('fail to log in as admin user');
        $I->amOnPage('/entry/signin');
        $I->fillField('Email', VANILLA_ADMIN_USER);
        $I->fillField('Password', 'abc123');
        $I->click('Sign_In');

        $I->see('The password you entered was incorrect. Remember that passwords are case-sensitive.');

    }

}
