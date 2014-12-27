<?php
use \FunctionalTester;

class SigninCest
{
    public function _before()
    {
    }

    public function _after()
    {
    }

    // tests
    public function signinTest(FunctionalTester $I) {
        $I->wantTo('sign in as admin user');
        $I->amOnPage('/entry/signin');
        $I->fillField('Email', VANILLA_ADMIN_USER);
        $I->fillField('Password', VANILLA_ADMIN_PASSWORD);
        $I->click('Sign_In');
        $I->see('admin');
        $I->haveInDatabase('GDN_User', array('Name' => VANILLA_ADMIN_USER, 'Admin' => 1));


    }

    public function signinWrongPasswordTest(FunctionalTester $I) {
        $I->wantTo('fail to signin in as admin user');
        $I->amOnPage('/entry/signin');
        $I->fillField('Email', VANILLA_ADMIN_USER);
        $I->fillField('Password', 'abc123');
        $I->click('Sign_In');

        $I->see('The password you entered was incorrect. Remember that passwords are case-sensitive.');
    }
}