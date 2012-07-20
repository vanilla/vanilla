<?php

/**
 * Application Gateway
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package Garden
 * @since 2.0
 */

define('APPLICATION', 'Vanilla');
define('APPLICATION_VERSION', '2.1a23');

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
// TIM: Removed this change temporarily for .com hosting
// Gdn::Authenticator()->StartAuthenticator();
$Dispatcher = Gdn::Dispatcher();

$EnabledApplications = Gdn::ApplicationManager()->EnabledApplicationFolders();
$Dispatcher->EnabledApplicationFolders($EnabledApplications);
$Dispatcher->PassProperty('EnabledApplications', $EnabledApplications);

// 4. Process the request.
$Dispatcher->Start();
$Dispatcher->Dispatch();

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

   echo CombinePaths(array("http://{$XHPROF_SERVER_NAME}","/?run={$run_id}&source={$xhprof_namespace}\n"));

}
