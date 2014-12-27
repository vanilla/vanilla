<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('sign in and sign out as admin user');
$I->amOnPage('/entry/signin');
$I->fillField('Email', VANILLA_ADMIN_USER);
$I->fillField('Password', VANILLA_ADMIN_PASSWORD);
$I->click('Sign_In');
$I->see('admin');
$I->seeLink('admin', 'index.php?p=/profile/1/admin');

$I->wantTo('log out');
$I->executeJS("$('#Panel > div.MeBox > div > div > span:nth-child(4)').click();");
$signoutUrl = $I->executeJS("return $('#Panel > div.MeBox > div > div > span.ToggleFlyout.Open > div > ul > li.SignInOutWrap.SignOutWrap > a').attr('href')");
$I->amOnPage($signoutUrl);
$I->see('Howdy, Stranger!');