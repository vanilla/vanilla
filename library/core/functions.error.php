<?php
/**
 * Catch and render errors.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Class Gdn_ErrorException
 */
class Gdn_ErrorException extends ErrorException {

    /** @var string */
    protected $_Context;

    /**
     *
     *
     * @param string $Message
     * @param int $ErrorNumber
     * @param int $File
     * @param string $Line
     * @param int $Context
     */
    public function __construct($Message, $ErrorNumber, $File, $Line, $Context) {
        parent::__construct($Message, $ErrorNumber, 0, $File, $Line);
        $this->_Context = $Context;
    }

    /**
     *
     *
     * @return int|string
     */
    public function getContext() {
        return $this->_Context;
    }
}

/**
 *
 *
 * @param $errorNumber
 * @param $message
 * @param $file
 * @param $line
 * @param $arguments
 * @return bool|void
 * @throws Gdn_ErrorException
 */
function Gdn_ErrorHandler($errorNumber, $message, $file, $line, $arguments) {
    $errorReporting = error_reporting();

    // Don't do anything for @supressed errors.
    if ($errorReporting === 0) {
        return;
    }

    if (($errorReporting & $errorNumber) !== $errorNumber) {
        if (function_exists('trace')) {
            trace(new \ErrorException($message, $errorNumber, $errorNumber, $file, $line), TRACE_NOTICE);
        }

        // Ignore errors that are below the current error reporting level.
        return false;
    }

    $fatalErrorBitmask = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
    if ($errorNumber & $fatalErrorBitmask) {
        // Convert all fatal errors to an exception
        throw new Gdn_ErrorException($message, $errorNumber, $file, $line, $arguments);
    }

    // All other unprocessed non-fatal PHP errors are possibly Traced and logged to the PHP error log file
    $nonFatalErrorException = new \ErrorException($message, $errorNumber, $errorNumber, $file, $line);
    if (function_exists('trace')) {
        trace($nonFatalErrorException, TRACE_NOTICE);
    }

    errorLog(formatErrorException($nonFatalErrorException));
}

/**
 * A custom error handler that displays much more, very useful information when
 * errors are encountered in Garden.
 *
 * @param Exception $Exception The exception that was thrown.
 */
function Gdn_ExceptionHandler($Exception) {
    try {
        // Attempt to log the exception as early as possible
        if ($Exception instanceof \ErrorException) {
            errorLog(formatErrorException($Exception));
        } else {
            errorLog(formatException($Exception, true));
        }

        $ErrorNumber = $Exception->getCode();
        $Message = $Exception->getMessage();
        $File = $Exception->getFile();
        $Line = $Exception->getLine();
        if (method_exists($Exception, 'getContext')) {
            $Arguments = $Exception->getContext();
        } else {
            $Arguments = '';
        }
        $Backtrace = $Exception->getTrace();

        // Clean the output buffer in case an error was encountered in-page.
        @ob_end_clean();
        // prevent headers already sent error
        if (!headers_sent()) {
            if ($ErrorNumber >= 100 && $ErrorNumber < 600) {
                $Code = $ErrorNumber;
            } else {
                $Code = 500;
            }

            if (class_exists('Gdn_Controller', false)) {
                $msg = Gdn_Controller::getStatusMessage($Code);
            } else {
                $msg = 'Error';
            }

            safeHeader("HTTP/1.0 $Code $msg", true, $ErrorNumber);
            safeHeader('Content-Type: text/html; charset=utf-8');
        }

        $SenderMessage = $Message;
        $SenderObject = 'PHP';
        $SenderMethod = 'Gdn_ErrorHandler';
        $SenderCode = false;
        $SenderTrace = $Backtrace;
        $MessageInfo = explode('|', $Message);
        $MessageCount = count($MessageInfo);
        if ($MessageCount == 4) {
            list($SenderMessage, $SenderObject, $SenderMethod, $SenderCode) = $MessageInfo;
        } elseif ($MessageCount == 3) {
            list($SenderMessage, $SenderObject, $SenderMethod) = $MessageInfo;
        } elseif (function_exists('GetValueR')) {
            $IsError = (GetValueR('0.function', $SenderTrace) == 'Gdn_ErrorHandler'); // not exception
            $N = ($IsError) ? '1' : '0';
            $SenderMethod = GetValueR($N.'.function', $SenderTrace, $SenderMethod);
            $SenderObject = GetValueR($N.'.class', $SenderTrace, $SenderObject);
        }

        $SenderMessage = htmlspecialchars($SenderMessage);

        $Master = false;  // The parsed master view
        $CssPath = false; // The web-path to the css file
        $ErrorLines = false; // The lines near the error's line #
        $DeliveryType = defined('DELIVERY_TYPE_ALL') ? DELIVERY_TYPE_ALL : 'ALL';
        if (array_key_exists('DeliveryType', $_POST)) {
            $DeliveryType = $_POST['DeliveryType'];
        } elseif (array_key_exists('DeliveryType', $_GET)) {
            $DeliveryType = $_GET['DeliveryType'];
        }

        if (function_exists('debug') && debug()) {
            $Debug = true;
        } else {
            $Debug = false;
        }

        // Make sure all of the required custom functions and variables are defined.
        $PanicError = false; // Should we just dump a message and forget about the master view?
        if (!defined('DS')) {
            $PanicError = true;
        }
        if (!defined('PATH_ROOT')) {
            $PanicError = true;
        }
        if (!defined('APPLICATION')) {
            define('APPLICATION', 'Garden');
        }
        if (!defined('APPLICATION_VERSION')) {
            define('APPLICATION_VERSION', 'Unknown');
        }
        $WebRoot = '';

        // Try and rollback a database transaction.
        if (class_exists('Gdn', false)) {
            $Database = Gdn::database();
            if (is_object($Database)) {
                $Database->rollbackTransaction();
            }
        }

        if ($PanicError === false) {
            // See if we can get the file that caused the error
            if (is_string($File) && is_numeric($ErrorNumber)) {
                $ErrorLines = @file($File);
            }

            // If this error was encountered during an ajax request, don't bother gettting the css or theme files
            if ($DeliveryType == DELIVERY_TYPE_ALL) {
                $CssPaths = array(); // Potential places where the css can be found in the filesystem.
                $MasterViewPaths = array();
                $MasterViewName = 'error.master.php';
                $MasterViewCss = 'error.css';

                if ($Debug) {
                    $MasterViewName = 'deverror.master.php';
                }

                if (class_exists('Gdn', false)) {
                    $CurrentTheme = ''; // The currently selected theme
                    $CurrentTheme = c('Garden.Theme', '');
                    $MasterViewName = c('Garden.Errors.MasterView', $MasterViewName);
                    $MasterViewCss = substr($MasterViewName, 0, strpos($MasterViewName, '.'));
                    if ($MasterViewCss == '') {
                        $MasterViewCss = 'error';
                    }

                    $MasterViewCss .= '.css';

                    if ($CurrentTheme != '') {
                        // Look for CSS in the theme folder:
                        $CssPaths[] = PATH_THEMES.DS.$CurrentTheme.DS.'design'.DS.$MasterViewCss;

                        // Look for Master View in the theme folder:
                        $MasterViewPaths[] = PATH_THEMES.DS.$CurrentTheme.DS.'views'.DS.$MasterViewName;
                    }
                }

                // Look for CSS in the dashboard design folder.
                $CssPaths[] = PATH_APPLICATIONS.DS.'dashboard'.DS.'design'.DS.$MasterViewCss;
                // Look for Master View in the dashboard view folder.
                $MasterViewPaths[] = PATH_APPLICATIONS.DS.'dashboard'.DS.'views'.DS.$MasterViewName;

                $CssPath = false;
                $Count = count($CssPaths);
                for ($i = 0; $i < $Count; ++$i) {
                    if (file_exists($CssPaths[$i])) {
                        $CssPath = $CssPaths[$i];
                        break;
                    }
                }
                if ($CssPath !== false) {
                    $CssPath = str_replace(
                        array(PATH_ROOT, DS),
                        array('', '/'),
                        $CssPath
                    );
                    $CssPath = ($WebRoot == '' ? '' : '/'.$WebRoot).$CssPath;
                }

                $MasterViewPath = false;
                $Count = count($MasterViewPaths);
                for ($i = 0; $i < $Count; ++$i) {
                    if (file_exists($MasterViewPaths[$i])) {
                        $MasterViewPath = $MasterViewPaths[$i];
                        break;
                    }
                }

                if ($MasterViewPath !== false) {
                    include($MasterViewPath);
                    $Master = true;
                }
            }
        }

        if ($DeliveryType != DELIVERY_TYPE_ALL) {
            if (!$Debug) {
                echo '<b class="Bonk">Whoops! There was an error.</b>';
                echo '<div class="BonkError Hidden">';
            }

            // This is an ajax request, so dump an error that is more eye-friendly in the debugger
            echo '<h1>FATAL ERROR IN: ', $SenderObject, '.', $SenderMethod, "();</h1>\n<pre class=\"AjaxError\">\"".$SenderMessage."\"\n";
            if ($SenderCode != '') {
                echo htmlspecialchars($SenderCode, ENT_COMPAT, 'UTF-8')."\n";
            }

            if (is_array($ErrorLines) && $Line > -1) {
                echo "\nLOCATION: ", $File, "\n";
            }

            $LineCount = count($ErrorLines);
            $Padding = strlen($Line + 5);
            for ($i = 0; $i < $LineCount; ++$i) {
                if ($i > $Line - 6 && $i < $Line + 4) {
                    if ($i == $Line - 1) {
                        echo '>>';
                    }

                    echo '> '.str_pad($i + 1, $Padding, " ", STR_PAD_LEFT), ': ',
                        str_replace(array("\n", "\r"), array('', ''), htmlspecialchars($ErrorLines[$i])), "\n";
                }
            }

            if (is_array($Backtrace)) {
                echo "\nBACKTRACE:\n";
                $BacktraceCount = count($Backtrace);
                for ($i = 0; $i < $BacktraceCount; ++$i) {
                    if (array_key_exists('file', $Backtrace[$i])) {
                        $File = $Backtrace[$i]['file'].' '
                            .$Backtrace[$i]['line'];
                    }
                    echo '['.$File.']', ' '
                    , array_key_exists('class', $Backtrace[$i]) ? $Backtrace[$i]['class'] : 'PHP'
                    , array_key_exists('type', $Backtrace[$i]) ? $Backtrace[$i]['type'] : '::'
                    , $Backtrace[$i]['function'], '();'
                    , "\n";
                }
            }
            echo '</pre>';

            if (!$Debug) {
                echo '</div>';
            }
        } else {
            // If the master view wasn't found, assume a panic state and dump the error.
            if ($Master === false) {
                echo '<!DOCTYPE html>
   <html>
   <head>
      <title>Fatal Error</title>
   </head>
   <body>
      <h1>Fatal Error in   ', $SenderObject, '.', $SenderMethod, '();</h1>
      <h2>', $SenderMessage, "</h2>\n";

                if ($SenderCode != '') {
                    echo '<code>', htmlentities($SenderCode, ENT_COMPAT, 'UTF-8'), "</code>\n";
                }

                if (is_array($ErrorLines) && $Line > -1) {
                    echo '<h3><strong>The error occurred on or near:</strong> ', $File, '</h3>
         <pre>';
                    $LineCount = count($ErrorLines);
                    $Padding = strlen($Line + 4);
                    for ($i = 0; $i < $LineCount; ++$i) {
                        if ($i > $Line - 6 && $i < $Line + 4) {
                            echo str_pad($i, $Padding, " ", STR_PAD_LEFT), ': ', htmlentities($ErrorLines[$i], ENT_COMPAT, 'UTF-8');
                        }
                    }
                    echo "</pre>\n";
                }

                echo '<h2>Need Help?</h2>
      <p>If you are a user of this website, you can report this message to a website administrator.</p>
      <p>If you are an administrator of this website, you can get help at the <a href="http://vanillaforums.org/discussions/" target="_blank">Vanilla Community Forums</a>.</p>
      <h2>Additional information for support personnel:</h2>
      <ul>
         <li><strong>Application:</strong> ', APPLICATION, '</li>
         <li><strong>Application Version:</strong> ', APPLICATION_VERSION, '</li>
         <li><strong>PHP Version:</strong> ', PHP_VERSION, '</li>
         <li><strong>Operating System:</strong> ', PHP_OS, "</li>\n";

                if (array_key_exists('SERVER_SOFTWARE', $_SERVER)) {
                    echo '<li><strong>Server Software:</strong> ', htmlspecialchars($_SERVER['SERVER_SOFTWARE']), "</li>\n";
                }

                if (array_key_exists('HTTP_REFERER', $_SERVER)) {
                    echo '<li><strong>Referer:</strong> ', htmlspecialchars($_SERVER['HTTP_REFERER']), "</li>\n";
                }

                if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
                    echo '<li><strong>User Agent:</strong> ', htmlspecialchars($_SERVER['HTTP_USER_AGENT']), "</li>\n";
                }

                if (array_key_exists('REQUEST_URI', $_SERVER)) {
                    echo '<li><strong>Request Uri:</strong> ', htmlspecialchars($_SERVER['REQUEST_URI']), "</li>\n";
                }
                echo '</ul>
   </body>
   </html>';
            }
        }
    } catch (Exception $e) {
        print get_class($e)." thrown within the exception handler.<br/>Message: ".$e->getMessage()." in ".$e->getFile()." on line ".$e->getLine();
        exit();
    }
}

if (!function_exists('errorMessage')) {
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
    function errorMessage($Message, $SenderObject, $SenderMethod, $Code = '') {
        return $Message.'|'.$SenderObject.'|'.$SenderMethod.'|'.$Code;
    }
}

if (!function_exists('errorLog')) {
    /**
     * Attempt to log an error message to the PHP error log.
     *
     * @access private
     * @param string|\Exception $message
     */
    function errorLog($message) {
        $errorLogFile = class_exists('Gdn', false) ? Gdn::config('Garden.Errors.LogFile', '') : '';

        // Log only if the PHP setting "log_errors" is enabled
        // OR if the Garden config "Garden.Errors.LogFile" is provided
        if (!$errorLogFile && !ini_get('log_errors')) {
            return;
        }

        // Make sure the message can be converted to a string otherwise bail out
        if (!is_string($message) && !method_exists($message, '__toString')) {
            return;
        }

        if (!is_string($message)) {
            // Cast the $message to a string
            $message = (string) $message;
        }

        $destination = null;
        if (!$errorLogFile) {
            // sends to PHP's system logger
            $messageType = 0;
        } else {
            // appends to a file
            $messageType = 3;
            $destination = $errorLogFile;

            // Need to prepend the date when appending to an error log file
            // and also add a newline manually
            $date = date('d-M-Y H:i:s e');
            $message = sprintf('[%s] %s', $date, $message) . PHP_EOL;
        }

        @error_log($message, $messageType, $destination);
    }
}

if (!function_exists('formatErrorException')) {
    /**
     * Format an \ErrorException into a string destined for PHP error_log()
     *
     * @access private
     * @param \ErrorException $exception The error exception to format
     * @return string The formatted error message
     */
    function formatErrorException($exception) {
        if (!($exception instanceof \ErrorException)) {
            return '';
        }

        $errorType = '';
        $errorCode = $exception->getCode();

        // Find an error type based on the error code
        switch ($errorCode) {
            case $errorCode & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR):
                $errorType = 'PHP Fatal error';
                break;
            case $errorCode & (E_NOTICE | E_USER_NOTICE):
                $errorType = 'PHP Notice';
                break;
            case $errorCode & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING):
                $errorType = 'PHP Warning';
                break;
            case $errorCode & (E_DEPRECATED | E_USER_DEPRECATED):
                $errorType = 'PHP Deprecated';
                break;
            case $errorCode & (E_PARSE):
                $errorType = 'PHP Parse error';
                break;
            case $errorCode & (E_STRICT):
                $errorType = 'PHP Strict standards';
                break;
        }

        return formatPHPErrorLog($exception->getMessage(), $errorType, $exception->getFile(), $exception->getLine());
    }
}

if (!function_exists('formatException')) {
    /**
     * Format an \Exception or any object implementing \Throwable
     * into a string destined for PHP error_log()
     *
     * @access private
     * @param mixed $exception The Exception to format
     * @param boolean $uncaught Whether the exception was uncaught or not
     * @return string The formatted error message
     */
    function formatException($exception, $uncaught = false) {
        if (!($exception instanceof \Exception) && !($exception instanceof \Throwable)) {
            // Not an Exception or a Throwable type (PHP7)
            return '';
        }

        $errorMessage = (string) $exception;

        if ($uncaught) {
            $errorType = 'PHP Fatal error';
            $errorMessage = 'Uncaught ' . $errorMessage;
        } else {
            $errorType = 'APP Log';
            $errorMessage = 'Caught ' . $errorMessage;
        }
        $errorMessage .= PHP_EOL . '  thrown';

        return formatPHPErrorLog($errorMessage, $errorType, $exception->getFile(), $exception->getLine());
    }
}

if (!function_exists('formatPHPErrorLog')) {
    /**
     * Format an error message to be sent to PHP error_log()
     *
     * @access private
     * @param string $errorMsg The error message
     * @param string $errorType Optional error type such as "PHP Fatal error" or "PHP Notice".  It will be prefixed to the $errorMsg
     * @param string $file Optional file path where the error occured
     * @param string $line Optional line number where the error occured
     * @return string The formatted error message
     */
    function formatPHPErrorLog($errorMsg, $errorType = null, $file = null, $line = null) {
        $formattedMessage = $errorMsg;
        if ($errorType) {
            $formattedMessage = sprintf('%s:  %s', $errorType, $errorMsg);
        }

        if ($file && is_numeric($line)) {
            $formattedMessage = sprintf('%s in %s on line %s', $formattedMessage, $file, $line);
        } elseif ($file) {
            $formattedMessage = sprintf('%s in %s', $formattedMessage, $file, $line);
        }

        return $formattedMessage;
    }
}

if (!function_exists('logException')) {
    /**
     * Log an exception.
     *
     * @param Exception $Ex
     */
    function logException($Ex) {
        if (!class_exists('Gdn', false)) {
            return;
        }

        if ($Ex instanceof Gdn_UserException) {
            return;
        }

        // Attempt to log the exception in the PHP logs
        errorLog(formatException($Ex));
    }
}

if (!function_exists('logMessage')) {
    /**
     * Logs errors to a file. This function does not throw errors because it is
     * a last-ditch effort after errors have already been rendered.
     *
     * @param string The file to save the error log in.
     * @param int The line number that encountered the error.
     * @param string The name of the object that encountered the error.
     * @param string The name of the method that encountered the error.
     * @param string The error message.
     * @param string Any additional information that could be useful to debuggers.
     */
    function logMessage($File, $Line, $Object, $Method, $Message, $Code = '') {
        if (!class_exists('Gdn', false)) {
            return;
        }

        // Prepare the log message
        $Log = "[Garden] $File, $Line, $Object.$Method()";
        if ($Message <> '') {
            $Log .= ", $Message";
        }
        if ($Code <> '') {
            $Log .= ", $Code";
        }

        // Attempt to log the message in the PHP logs
        errorLog($Log);
    }
}

if (!function_exists('boop')) {
    /**
     * Logs a message or print_r()'s an array to the screen.
     *
     * @param mixed $Message The object or string to log to the screen
     * @param optional $Arguments A list of arguments to log to the screen as if from a function call
     */
    function boop($Message, $Arguments = array(), $Vardump = false) {
        if (!defined('BOOP') || !BOOP) {
            return;
        }

        if (is_array($Message) || is_object($Message) || $Vardump === true) {
            if ($Vardump) {
                var_dump($Message);
            } else {
                print_r($Message);
            }
        } else {
            echo $Message;
        }

        if (!is_null($Arguments) && sizeof($Arguments)) {
            echo " (".implode(', ', $Arguments).")";
        }

        echo "\n";
    }
}

if (!function_exists('cleanErrorArguments')) {
    /**
     *
     *
     * @param $Var
     * @param array $BlackList
     */
    function cleanErrorArguments(&$Var, $BlackList = array('configuration', 'config', 'database', 'password')) {
        if (is_array($Var)) {
            foreach ($Var as $Key => $Value) {
                if (in_array(strtolower($Key), $BlackList)) {
                    $Var[$Key] = 'SECURITY';
                } else {
                    if (is_object($Value)) {
                        $Value = Gdn_Format::objectAsArray($Value);
                        $Var[$Key] = $Value;
                    }

                    if (is_array($Value)) {
                        cleanErrorArguments($Var[$Key], $BlackList);
                    }
                }
            }
        }
    }
}

/**
 * Set up Garden to handle php errors.
 *
 * You can remove the "& ~E_STRICT" from time to time to clean up some easy strict errors.
 */
function setHandlers() {
    set_error_handler('Gdn_ErrorHandler', E_ALL & ~E_STRICT);
    set_exception_handler('Gdn_ExceptionHandler');
}

/**
 * Create a new not found exception. This is a convenience function that will create an exception with a standard message.
 *
 * @param string $Code The translation code of the type of object that wasn't found.
 * @return Exception
 */
function notFoundException($RecordType = 'Page') {
    Gdn::dispatcher()
        ->passData('RecordType', $RecordType)
        ->passData('Description', sprintf(T('The %s you were looking for could not be found.'), strtolower($RecordType)));
    return new Gdn_UserException(sprintf(T('%s not found.'), T($RecordType)), 404);
}

/**
 * Create a new permission exception. This is a convenience function that will create an exception with a standard message.
 *
 * @param string|null $Permission The name of the permission that was required.
 * @return Exception
 */
function permissionException($Permission = null) {
    if (!$Permission) {
        $Message = t('PermissionErrorMessage', "You don't have permission to do that.");
    } elseif ($Permission == 'Banned')
        $Message = t("You've been banned.");
    elseif (stringBeginsWith($Permission, '@'))
        $Message = stringBeginsWith($Permission, '@', true, true);
    else {
        $Message = t(
            "PermissionRequired.$Permission",
            sprintf(t('You need the %s permission to do that.'), $Permission)
        );
    }
    return new Gdn_UserException($Message, 403);
}

/**
 * Create a new permission exception. This is a convenience function that will create an exception with a standard message.
 *
 * @param string|null $Permission The name of the permission that was required.
 * @return Exception
 */
function forbiddenException($Resource = null) {
    if (!$Resource) {
        $Message = t('ForbiddenErrorMessage', "You are not allowed to do that.");
    } elseif (stringBeginsWith($Resource, '@'))
        $Message = stringBeginsWith($Resource, '@', true, true);
    else {
        $Message = sprintf(t('You are not allowed to %s.'), $Resource);
    }
    return new Gdn_UserException($Message, 403);
}
