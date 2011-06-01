<?php
define('APPLICATION', 'Vanilla');
define('APPLICATION_VERSION', '2.0.18b2');
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Report and track all errors.
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);

ob_start();

// 0. Start profiling if requested in the querystring
if (isset($_GET['xhprof']) && $_GET['xhprof'] == 'yes')
   define('PROFILER', TRUE);

if (defined('PROFILER') && PROFILER) {
   $ProfileWhat = 0;
   
   if (isset($_GET['memory']) && $_GET['memory'] == 'yes')
      $ProfileWhat += XHPROF_FLAGS_MEMORY;
   
   if (isset($_GET['cpu']) && $_GET['cpu'] == 'yes')
      $ProfileWhat += XHPROF_FLAGS_CPU;
   
   xhprof_enable($ProfileWhat);
}

// 1. Define the constants we need to get going.
define('DS', '/');
define('PATH_ROOT', dirname(__FILE__));

// 2. Include the bootstrap to configure the framework.
require_once(PATH_ROOT.'/bootstrap.php');

// 3. Create and configure the dispatcher.
$Dispatcher = Gdn::Dispatcher();

$EnabledApplications = Gdn::ApplicationManager()->EnabledApplicationFolders();
$Dispatcher->EnabledApplicationFolders($EnabledApplications);
$Dispatcher->PassProperty('EnabledApplications', $EnabledApplications);

// 4. Process the request.
$Dispatcher->Dispatch();
$Dispatcher->Cleanup();

// 5. Finish profiling and save results to disk, if requested
if (defined('PROFILER') && PROFILER) {
   $xhprof_data = xhprof_disable();
   
   if (is_null($XHPROF_ROOT))
      die("Unable to save XHProf data. \$XHPROF_ROOT not defined in index.php");

   if (is_null($XHPROF_SERVER_NAME))
      die("Unable to save XHProf data. \$XHPROF_SERVER_NAME not defined in index.php");
   
   //
   // Saving the XHProf run
   // using the default implementation of iXHProfRuns.
   //
   include_once("{$XHPROF_ROOT}/xhprof_lib/utils/xhprof_lib.php");
   include_once("{$XHPROF_ROOT}/xhprof_lib/utils/xhprof_runs.php");

   $xhprof_runs = new XHProfRuns_Default();
   $xhprof_namespace = 'vanilla';

   // Save the run under a namespace              
   //
   // **NOTE**:
   // By default save_run() will automatically generate a unique
   // run id for you. [You can override that behavior by passing
   // a run id (optional arg) to the save_run() method instead.]
   //
   $run_id = $xhprof_runs->save_run($xhprof_data, $xhprof_namespace);

   echo "http://{$XHPROF_SERVER_NAME}/index.php?run={$run_id}&source={$xhprof_namespace}\n";

}
