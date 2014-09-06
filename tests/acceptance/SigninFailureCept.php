<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('fail to sign in as admin user');
$I->amOnPage('/entry/signin');
$I->fillField('Email', VANILLA_ADMIN_USER);
$I->fillField('Password', 'abc123');
$I->click('Sign_In');

$I->see('The password you entered was incorrect. Remember that passwords are case-sensitive.');
