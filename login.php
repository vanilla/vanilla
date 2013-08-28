<?php

define('APPLICATION','test');
define('DS', '/');
define('PATH_ROOT', getcwd());

require('conf/constants.php');
require('library/core/functions.general.php');
require('library/core/class.proxyrequest.php');

$Request = new ProxyRequest();

// GET NOTIFICATION PREFS

$Response = $Request->Request(array(
   'URL'             => 'https://kxl-dev.vanillaforums.com/api/v1/users/notifications.json',
   'SSLNoVerify'     => TRUE
), array(
   'User.ForeignID'  => '1076154544:50e73e6f34cf36087e000000',
   'access_token'    => 'f3ad4c4c448ea5967b043dd7011fb3dd'
));

echo "Get Notification Prefs:\n";
echo " - response: ".$Request->ResponseStatus."\n";

$Response = json_decode($Response, TRUE);
$Prefs = GetValue('Preferences', $Response);
echo " - preferences: ".sizeof($Prefs)."\n";
print_r($Prefs);

// SET NOTIFICATION PREFS



exit();