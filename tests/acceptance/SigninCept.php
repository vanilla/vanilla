<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('sign in as admin user');
$I->amOnPage('/entry/signin');
$I->fillField('Email', VANILLA_ADMIN_USER);
$I->fillField('Password', VANILLA_ADMIN_PASSWORD);
$I->click('Sign_In');
$I->see('admin');
$I->seeLink('admin', 'index.php?p=/profile/1/admin');