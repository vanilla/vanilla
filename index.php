<?php
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Report and track all errors.
error_reporting(E_ALL);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);

ob_start();

// 1. Define the constants we need to get going.
define('APPLICATION', 'Garden');
define('APPLICATION_VERSION', '1.0');

define('DS', DIRECTORY_SEPARATOR);
define('PATH_ROOT', dirname(__FILE__));

// 2. Include the header.
require_once(PATH_ROOT.DS.'bootstrap.php');

// 3. Start the application.
if(strpos(Gdn_Url::Request(), 'gardensetup') === FALSE)
Gdn::Session()->Start(Gdn::Authenticator());

$Dispatcher = Gdn::Dispatcher();

$EnabledApplications = Gdn::Config('EnabledApplications');
$Dispatcher->EnabledApplicationFolders($EnabledApplications);

$Dispatcher->PassProperty('EnabledApplications', $EnabledApplications);
$Dispatcher->Routes = Gdn::Config('Routes');

// Process the request.
$Dispatcher->Dispatch();