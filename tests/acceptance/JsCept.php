<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('check jQuery is loaded');
$I->waitForJS("return $.active == 0;", 60);
$jQueryVersion = $I->executeJS('return jQuery.fn.jquery;');
$I->comment('Jquery Version: ' . $jQueryVersion);