<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/


/**
 * A custom error handler that displays much more, very useful information when
 * errors are encountered in Garden.
 *
 * @param int The level of the error raised.
 * @param string The error message.
 * @param string The filename that the error was raised in.
 * @param string The line number the error was raised at.
 * @param string An array of every variable that existed in the scope the error was triggered in.
 */
function ErrorHandler($ErrorNumber, $Message, $File, $Line, $Arguments) {
   // Ignore errors that have a @ before them (ie. @function();)
   if (error_reporting() == 0)
      return FALSE;
   
   // Clean the output buffer in case an error was encountered in-page.
   @ob_end_clean();
   header('Content-Type: text/html; charset=utf-8');
   
   $SenderMessage = $Message;
   $SenderObject = 'PHP';
   $SenderMethod = 'ErrorHandler';
   $SenderCode = FALSE;
   $MessageInfo = explode('|', $Message);
   $MessageCount = count($MessageInfo);
   if ($MessageCount == 4)
      list($SenderMessage, $SenderObject, $SenderMethod, $SenderCode) = $MessageInfo;
   else if ($MessageCount == 3)
      list($SenderMessage, $SenderObject, $SenderMethod) = $MessageInfo;
   
   $SenderMessage = strip_tags($SenderMessage);
   
   $Master = FALSE;  // The parsed master view
   $CssPath = FALSE; // The web-path to the css file
   $ErrorLines = FALSE; // The lines near the error's line #
   $DeliveryType = DELIVERY_TYPE_ALL;
   if (array_key_exists('DeliveryType', $_POST)) {
      $DeliveryType = $_POST['DeliveryType'];
   } else if (array_key_exists('DeliveryType', $_GET)) {
      $DeliveryType = $_GET['DeliveryType'];
   }

   // Make sure all of the required custom functions and variables are defined.
   $PanicError = FALSE; // Should we just dump a message and forget about the master view?
   if (!defined('DS')) $PanicError = TRUE;
   if (!defined('PATH_ROOT')) $PanicError = TRUE;
   if (!defined('APPLICATION')) define('APPLICATION', 'Garden');
   if (!defined('APPLICATION_VERSION')) define('APPLICATION_VERSION', 'Unknown');
   $WebRoot = class_exists('Url', FALSE) ? Gdn_Url::WebRoot() : '';
   
   // Try and rollback a database transaction.
   if(class_exists('Gdn', FALSE)) {
      $Database = Gdn::Database();
      if(is_object($Database))
         $Database->RollbackTransaction();
   }

   if ($PanicError === FALSE) {
      // See if we can get the file that caused the error
      if (is_string($File) && is_numeric($ErrorNumber))
         $ErrorLines = @file($File);
         
      // If this error was encountered during an ajax request, don't bother gettting the css or theme files
      if ($DeliveryType == DELIVERY_TYPE_ALL) {
         $CssPaths = array(); // Potential places where the css can be found in the filesystem.
         $MasterViewPaths = array();
         $MasterViewName = 'error.master.php';
         $MasterViewCss = 'error.css';
            
         if(class_exists('Gdn', FALSE)) {
            $CurrentTheme = ''; // The currently selected theme
            $CurrentTheme = Gdn::Config('Garden.Theme', '');
            $MasterViewName = Gdn::Config('Garden.Errors.MasterView', $MasterViewName);
            $MasterViewCss = substr($MasterViewName, 0, strpos($MasterViewName, '.'));
            if ($MasterViewCss == '')
               $MasterViewCss = 'error';
            
            $MasterViewCss .= '.css';
      
            if ($CurrentTheme != '') {
               // Look for CSS in the theme folder:
               $CssPaths[] = PATH_THEMES . DS . $CurrentTheme . DS . 'design' . DS . $MasterViewCss;
               
               // Look for Master View in the theme folder:
               $MasterViewPaths[] = PATH_THEMES . DS . $CurrentTheme . DS . 'views' . DS . $MasterViewName;
            }
         }
            
         // Look for CSS in the garden design folder.
         $CssPaths[] = PATH_APPLICATIONS . DS . 'garden' . DS . 'design' . DS . $MasterViewCss;
         // Look for Master View in the garden view folder.
         $MasterViewPaths[] = PATH_APPLICATIONS . DS . 'garden' . DS . 'views' . DS . $MasterViewName;
         
         $CssPath = FALSE;
         $Count = count($CssPaths);
         for ($i = 0; $i < $Count; ++$i) {
            if (file_exists($CssPaths[$i])) {
               $CssPath = $CssPaths[$i];
               break;
            }
         }
         if ($CssPath !== FALSE) {
            $CssPath = str_replace(
               array(PATH_ROOT, DS),
               array('', '/'),
               $CssPath
            );
            $CssPath = ($WebRoot == '' ? '' : '/'. $WebRoot) . $CssPath;
         }
   
         $MasterViewPath = FALSE;
         $Count = count($MasterViewPaths);
         for ($i = 0; $i < $Count; ++$i) {
            if (file_exists($MasterViewPaths[$i])) {
               $MasterViewPath = $MasterViewPaths[$i];
               break;
            }
         }
   
         if ($MasterViewPath !== FALSE) {
            include($MasterViewPath);
            $Master = TRUE;
         }
      }
   }
   
   if ($DeliveryType != DELIVERY_TYPE_ALL) {
      // This is an ajax request, so dump an error that is more eye-friendly in the debugger
      echo 'FATAL ERROR IN: ',$SenderObject,'.',$SenderMethod,"();\n\"".$SenderMessage."\"\n";
      if ($SenderCode != '')
         echo htmlentities($SenderCode, ENT_COMPAT, 'UTF-8')."\n";
         
      if (is_array($ErrorLines) && $Line > -1)
         echo "LOCATION: ",$File,"\n";
         
      $LineCount = count($ErrorLines);
      $Padding = strlen($Line+5);
      for ($i = 0; $i < $LineCount; ++$i) {
         if ($i > $Line-6 && $i < $Line+4) {
            if ($i == $Line - 1)
               echo '>>';
               
            echo '> '.str_pad($i+1, $Padding, " ", STR_PAD_LEFT),': ',str_replace(array("\n", "\r"), array('', ''), $ErrorLines[$i]),"\n";
         }
      }
      $Backtrace = debug_backtrace();
      if (is_array($Backtrace)) {
         echo "BACKTRACE:\n";
         $BacktraceCount = count($Backtrace);
         for ($i = 0; $i < $BacktraceCount; ++$i) {
            if (array_key_exists('file', $Backtrace[$i])) {
               $File = $Backtrace[$i]['file'].' '
               .$Backtrace[$i]['line'];
            }
            echo '['.$File.']' , ' '
               ,array_key_exists('class', $Backtrace[$i]) ? $Backtrace[$i]['class'] : 'PHP'
               ,array_key_exists('type', $Backtrace[$i]) ? $Backtrace[$i]['type'] : '::'
               ,$Backtrace[$i]['function'],'();'
            ,"\n";
         }
      }
   } else {
      // If the master view wasn't found, assume a panic state and dump the error.
      if ($Master === FALSE) {
         echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <title>Fatal Error</title>
</head>
<body>
   <h1>Fatal Error in ',$SenderObject,'.',$SenderMethod,'();</h1>
   <h2>',$SenderMessage,"</h2>\n";

   if ($SenderCode != '')
      echo '<code>',htmlentities($SenderCode, ENT_COMPAT, 'UTF-8'),"</code>\n";

   if (is_array($ErrorLines) && $Line > -1) {
      echo '<h3><strong>The error occurred on or near:</strong> ',$File,'</h3>
      <pre>';
         $LineCount = count($ErrorLines);
         $Padding = strlen($Line+4);
         for ($i = 0; $i < $LineCount; ++$i) {
            if ($i > $Line-6 && $i < $Line+4) {
               echo str_pad($i, $Padding, " ", STR_PAD_LEFT),': ',htmlentities($ErrorLines[$i], ENT_COMPAT, 'UTF-8');
            }
         }
      echo "</pre>\n";
   }

   echo '<h2>Need Help?</h2>
   <p>If you are a user of this website, you can report this message to a website administrator.</p>
   <p>If you are an administrator of this website, you can get help at the <a href="http://vanillaforums.org/discussions/" target="_blank">Vanilla Community Forums</a>.</p>
   <h2>Additional information for support personnel:</h2>
   <ul>
      <li><strong>Application:</strong> ',APPLICATION,'</li>
      <li><strong>Application Version:</strong> ',APPLICATION_VERSION,'</li>
      <li><strong>PHP Version:</strong> ',PHP_VERSION,'</li>
      <li><strong>Operating System:</strong> ',PHP_OS,"</li>\n";

      if (array_key_exists('HTTP_REFERER', $_SERVER))
         echo '<li><strong>Referer:</strong> ',$_SERVER['HTTP_REFERER'],"</li>\n";

      if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
         echo '<li><strong>User Agent:</strong> ',$_SERVER['HTTP_USER_AGENT'],"</li>\n";

      if (array_key_exists('REQUEST_URI', $_SERVER))
         echo '<li><strong>Request Uri:</strong> ',$_SERVER['REQUEST_URI'],"</li>\n";
   echo '</ul>
</body>
</html>';
      }
   }
   
   // Attempt to log an error message no matter what.
   LogMessage($File, $Line, $SenderObject, $SenderMethod, $SenderMessage, $SenderCode);
   exit();
}

if (!function_exists('ErrorMessage')) {
   /**
    * Returns an error message formatted in a way that the custom ErrorHandler
    * function can understand (allows a little more information to be displayed
    * on errors).
    *
    * @param string The actual error message.
    * @param string The name of the object that encountered the error.
    * @param string The name of the method that encountered the error.
    * @param string Any additional information that could be useful to debuggers.
    */
   function ErrorMessage($Message, $SenderObject, $SenderMethod, $Code = '') {
      return $Message.'|'.$SenderObject.'|'.$SenderMethod.'|'.$Code;
   }
}

if (!function_exists('LogMessage')) {
   /**
    * Logs errors to a file. This function does not throw errors because it is
    * a last-ditch effort after errors have already
    * been rendered.
    *
    * @param string The file to save the error log in.
    * @param int The line number that encountered the error.
    * @param string The name of the object that encountered the error.
    * @param string The name of the method that encountered the error.
    * @param string The error message.
    * @param string Any additional information that could be useful to debuggers.
    */
   function LogMessage($File, $Line, $Object, $Method, $Message, $Code = '') {
      // Figure out where to save the log
      if(class_exists('Gdn', FALSE)) {
         $LogErrors = Gdn::Config('Garden.Errors.LogEnabled', FALSE);
         if ($LogErrors === TRUE) {
            $Log = "[Garden] $File, $Line, $Object.$Method()";
            if ($Message <> '') $Log .= ", $Message";
            if ($Code <> '') $Log .= ", $Code";
            $Log .= "\n";
             
            // Fail silently (there could be permission issues on badly set up servers).
            $ErrorLogFile = Gdn::Config('Garden.Errors.LogFile');
            if ($ErrorLogFile == '') {
               @error_log($Log);
            } else {
               @error_log($Log, 3, $ErrorLogFile);
            }
         }
      }
   }
}

if (!function_exists('CleanErrorArguments')) {
   function CleanErrorArguments(&$Var, $BlackList = array('configuration', 'config', 'database', 'password')) {
      if (is_array($Var)) {
         foreach ($Var as $Key => $Value) {
            if (in_array(strtolower($Key), $BlackList)) {
               $Var[$Key] = 'SECURITY';
            } else {
               if (is_object($Value)) {
                  $Value = Format::ObjectAsArray($Value);
                  $Var[$Key] = $Value;
               }
                  
               if (is_array($Value))
                  CleanErrorArguments($Var[$Key], $BlackList);
            }
         }
      }
   }
}

// Set up Garden to handle php errors
set_error_handler('ErrorHandler', E_ALL);
