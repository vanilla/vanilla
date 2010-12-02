<?php
define('APPLICATION', 'Vanilla');
define('APPLICATION_VERSION', '2.0.16');
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Report and track all errors.
if(defined('DEBUG'))
   error_reporting(E_ALL);
else
   error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);

ob_start();

// 1. Define the constants we need to get going.
define('DS', '/');
define('PATH_ROOT', dirname(__FILE__));

// 2. Include the header.
require_once(PATH_ROOT.DS.'bootstrap.php');

$Dispatcher = Gdn::Dispatcher();

$EnabledApplications = Gdn::Config('EnabledApplications');
$Dispatcher->EnabledApplicationFolders($EnabledApplications);

$Dispatcher->PassProperty('EnabledApplications', $EnabledApplications);

// Process the request.
$Dispatcher->Dispatch();
$Dispatcher->Cleanup();