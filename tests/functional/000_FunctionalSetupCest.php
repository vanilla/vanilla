<?php
use \FunctionalTester;

class FunctionalSetup {

    public function _before() {
    }

    public function _after() {
    }


    public function CheckOrSetupVanilla(FunctionalTester $I) {
        $I->wantToTest('Check http://codeception.local');
        $I->amOnPage('/');
        $bodyClass = $I->grabAttributeFrom('body', 'class');
        if (stristr($bodyClass, 'Setup') !== false) {
            $this->setupTest($I);
        } else {
            $I->see('Howdy, Stranger!');
        }


    }

    protected function setupTest(FunctionalTester $I) {

        $I->wantTo('setup');
        $I->amOnPage('/');
        $I->seeElement('#dashboard_setup_index');

        $I->fillField('Database-dot-Host', MYSQL_HOST);
        $I->fillField('Database-dot-Name', MYSQL_DATABASE);
        $I->fillField('Database-dot-User', MYSQL_USER);
        $I->fillField('Database-dot-Password', MYSQL_PASSWORD);
        $I->fillField('Garden-dot-Title', VANILLA_APP_TITLE);
        $I->fillField('Email', VANILLA_ADMIN_EMAIL);
        $I->fillField('Name', VANILLA_ADMIN_USER);
        $I->fillField('Password', VANILLA_ADMIN_PASSWORD);
        $I->fillField('PasswordMatch', VANILLA_ADMIN_PASSWORD);

        $I->click('Continue');
        $I->dontSee('Access denied');
        $I->see('Getting Started with Vanilla');

    }


}