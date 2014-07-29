<?php
use \AcceptanceTester;

class SigninCest {

    public function _before() {
    }

    public function _after() {
    }

    public function loginTest(AcceptanceTester $I) {
        $I->wantTo('log in as admin user');
//        $I->amOnPage('/entry/signin');
        $I->amOnPage('/index.php?p=/entry/signin');
        $I->fillField('Email', VANILLA_ADMIN_USER);
        $I->fillField('Password', VANILLA_ADMIN_PASSWORD);
        $I->click('Sign_In');
        $I->see('admin');

    }
}
