<?php
use \AcceptanceTester;

class InstallCest {

    public function _before() {

//        exec(
//            'mysql -u' . MYSQL_USER . ' -p' . MYSQL_PASSWORD . ' -e "drop database if exists ' . MYSQL_DATABASE . ' "'
//        );
//        exec(
//            'mysql -u' . MYSQL_USER . ' -p' . MYSQL_PASSWORD . ' -e "create database if not exists ' . MYSQL_DATABASE . ' "'
//        );
//        @unlink(__DIR__ . '/../../conf/config.php');

    }

    public function _after() {
    }


    public function isWebServerConfigured(AcceptanceTester $I) {
        $I->wantTo('Check http://codeception.local');
        $I->amOnPage('/');
        $I->seeElement('#dashboard_setup_index');
    }

    /**
     * @depends isWebServerConfigured
     * @param AcceptanceTester $I
     */
    public function setupTest(AcceptanceTester $I) {

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

    }


}