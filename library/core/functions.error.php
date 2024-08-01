<?php
/**
 * Catch and render errors.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

use Garden\Utils\ContextException;
use Vanilla\Exception\PermissionException;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Utility\DebugUtils;
use Whoops\Handler\PrettyPageHandler;

/**
 * A custom error handler that displays much more, very useful information when
 * errors are encountered in Garden.
 *
 * @param Throwable $Exception The exception that was thrown.
 */
function gdnExceptionHandler(Throwable $Exception)
{
    try {
        ErrorLogger::handleException($Exception);

        $ErrorNumber = $Exception->getCode();
        $Message = $Exception->getMessage();
        $File = $Exception->getFile();
        $Line = $Exception->getLine();
        if (method_exists($Exception, "getContext")) {
            $Arguments = $Exception->getContext();
        } else {
            $Arguments = "";
        }
        $Backtrace = $Exception->getTrace();

        // Clean the output buffer in case an error was encountered in-page.
        @ob_end_clean();
        // prevent headers already sent error
        if (!headers_sent()) {
            if ($ErrorNumber >= 400 && $ErrorNumber < 600) {
                $Code = $ErrorNumber;
            } else {
                $Code = 500;
            }

            if (class_exists("Gdn_Controller", false)) {
                $msg = Gdn_Controller::getStatusMessage($Code);
            } else {
                $msg = "Error";
            }

            safeHeader("HTTP/1.0 $Code $msg", true, $ErrorNumber);
            safeHeader("Content-Type: text/html; charset=utf-8");
        }

        $SenderMessage = $Message;
        $SenderObject = "PHP";
        $SenderMethod = "Gdn_ErrorHandler";
        $SenderCode = false;
        $SenderTrace = $Backtrace;
        $MessageInfo = explode("|", $Message);
        $MessageCount = count($MessageInfo);
        if ($MessageCount == 4) {
            [$SenderMessage, $SenderObject, $SenderMethod, $SenderCode] = $MessageInfo;
        } elseif ($MessageCount == 3) {
            [$SenderMessage, $SenderObject, $SenderMethod] = $MessageInfo;
        } elseif (function_exists("GetValueR")) {
            $IsError = getValueR("0.function", $SenderTrace) == "Gdn_ErrorHandler"; // not exception
            $N = $IsError ? "1" : "0";
            $SenderMethod = getValueR($N . ".function", $SenderTrace, $SenderMethod);
            $SenderObject = getValueR($N . ".class", $SenderTrace, $SenderObject);
        }

        $SenderMessage = htmlspecialchars($SenderMessage);

        $Master = false; // The parsed master view
        $CssPath = false; // The web-path to the css file
        $ErrorLines = false; // The lines near the error's line #
        $DeliveryType = defined("DELIVERY_TYPE_ALL") ? DELIVERY_TYPE_ALL : "ALL";
        if (array_key_exists("DeliveryType", $_POST)) {
            $DeliveryType = $_POST["DeliveryType"];
        } elseif (array_key_exists("DeliveryType", $_GET)) {
            $DeliveryType = $_GET["DeliveryType"];
        }

        if (function_exists("debug") && debug()) {
            $Debug = true;
        } else {
            $Debug = false;
        }

        // Make sure all of the required custom functions and variables are defined.
        $PanicError = false; // Should we just dump a message and forget about the master view?
        if (!defined("DS")) {
            $PanicError = true;
        }
        if (!defined("PATH_ROOT")) {
            $PanicError = true;
        }
        if (!defined("APPLICATION")) {
            define("APPLICATION", "Garden");
        }
        if (!defined("APPLICATION_VERSION")) {
            define("APPLICATION_VERSION", "Unknown");
        }
        $WebRoot = "";

        // Try and rollback a database transaction.
        if (class_exists("Gdn", false)) {
            $Database = Gdn::database();
            if (is_object($Database)) {
                $Database->rollbackTransaction();
            }
        }

        if ($Debug) {
            $handler = new PrettyPageHandler();
            $handler->setApplicationRootPath(PATH_ROOT);
            if (!$PanicError) {
                $handler->setApplicationPaths([PATH_APPLICATIONS, PATH_PLUGINS, PATH_THEMES, PATH_LIBRARY]);
            }

            $exStack = $Exception;
            while (true) {
                if ($exStack instanceof ContextException) {
                    $handler->addDataTable(get_class($exStack) . " Context", $exStack->getContext());
                }
                if ($exStack->getPrevious() === null) {
                    break;
                } else {
                    $exStack = $exStack->getPrevious();
                }
            }

            $whoops = new \Whoops\Run();

            $whoops->allowQuit(false);
            $whoops->writeToOutput(false);
            $whoops->pushHandler($handler);
            $html = $whoops->handleException($Exception);
            echo $html;
            exit();
        }

        if ($PanicError === false) {
            // See if we can get the file that caused the error
            if (is_string($File) && is_numeric($ErrorNumber)) {
                $ErrorLines = @file($File);
            }

            // If this error was encountered during an ajax request, don't bother gettting the css or theme files
            if ($DeliveryType == DELIVERY_TYPE_ALL) {
                $CssPaths = []; // Potential places where the css can be found in the filesystem.
                $MasterViewPaths = [];
                $MasterViewName = "error.master.php";
                $MasterViewCss = "error.css";

                if (class_exists("Gdn", false)) {
                    $CurrentTheme = ""; // The currently selected theme
                    $CurrentTheme = c("Garden.Theme", "");
                    $MasterViewName = c("Garden.Errors.MasterView", $MasterViewName);
                    $MasterViewCss = substr($MasterViewName, 0, strpos($MasterViewName, "."));
                    if ($MasterViewCss == "") {
                        $MasterViewCss = "error";
                    }

                    $MasterViewCss .= ".css";

                    if ($CurrentTheme != "") {
                        // Look for CSS in the theme folder:
                        $CssPaths[] = PATH_THEMES . DS . $CurrentTheme . DS . "design" . DS . $MasterViewCss;
                        $CssPaths[] = PATH_ADDONS_THEMES . DS . $CurrentTheme . DS . "design" . DS . $MasterViewCss;

                        // Look for Master View in the theme folder:
                        $MasterViewPaths[] = PATH_THEMES . DS . $CurrentTheme . DS . "views" . DS . $MasterViewName;
                        $MasterViewPaths[] =
                            PATH_ADDONS_THEMES . DS . $CurrentTheme . DS . "views" . DS . $MasterViewName;
                    }
                }

                // Look for CSS in the dashboard design folder.
                $CssPaths[] = PATH_APPLICATIONS . DS . "dashboard" . DS . "design" . DS . $MasterViewCss;
                // Look for Master View in the dashboard view folder.
                $MasterViewPaths[] = PATH_APPLICATIONS . DS . "dashboard" . DS . "views" . DS . $MasterViewName;

                $CssPath = false;
                $Count = count($CssPaths);
                for ($i = 0; $i < $Count; ++$i) {
                    if (file_exists($CssPaths[$i])) {
                        $CssPath = $CssPaths[$i];
                        break;
                    }
                }
                if ($CssPath !== false) {
                    $CssPath = str_replace([PATH_ROOT, DS], ["", "/"], $CssPath);
                    $CssPath = ($WebRoot == "" ? "" : "/" . $WebRoot) . $CssPath;
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
                    include $MasterViewPath;
                    $Master = true;
                }
            }
        }

        if ($DeliveryType != DELIVERY_TYPE_ALL) {
            if (!$Debug) {
                die('<b class="Bonk">' . t("Whoops! There was an error.") . "</b>");
            }

            // This is an ajax request, so dump an error that is more eye-friendly in the debugger
            echo "<h1>FATAL ERROR IN: ",
                $SenderObject,
                ".",
                $SenderMethod,
                "();</h1>\n<pre class=\"AjaxError\">\"" . $SenderMessage . "\"\n";
            if ($SenderCode != "") {
                echo htmlspecialchars($SenderCode, ENT_COMPAT, "UTF-8") . "\n";
            }

            if (is_array($ErrorLines) && $Line > -1) {
                echo "\nLOCATION: ", $File, "\n";
            }

            $LineCount = count($ErrorLines);
            $Padding = strlen($Line + 5);
            for ($i = 0; $i < $LineCount; ++$i) {
                if ($i > $Line - 6 && $i < $Line + 4) {
                    if ($i == $Line - 1) {
                        echo ">>";
                    }

                    echo "> " . str_pad($i + 1, $Padding, " ", STR_PAD_LEFT),
                        ": ",
                        str_replace(["\n", "\r"], ["", ""], htmlspecialchars($ErrorLines[$i])),
                        "\n";
                }
            }

            if (is_array($Backtrace)) {
                echo "\nBACKTRACE:\n";
                $BacktraceCount = count($Backtrace);
                for ($i = 0; $i < $BacktraceCount; ++$i) {
                    if (array_key_exists("file", $Backtrace[$i])) {
                        $File = $Backtrace[$i]["file"] . " " . $Backtrace[$i]["line"];
                    }
                    echo "[" . $File . "]",
                        " ",
                        array_key_exists("class", $Backtrace[$i]) ? $Backtrace[$i]["class"] : "PHP",
                        array_key_exists("type", $Backtrace[$i]) ? $Backtrace[$i]["type"] : "::",
                        $Backtrace[$i]["function"],
                        "();",
                        "\n";
                }
            }
            echo "</pre>";
        } else {
            // If the master view wasn't found, assume a panic state and dump the error.
            if ($Master === false) {
                echo '<!DOCTYPE html>
   <html>
   <head>
      <title>Fatal Error</title>
   </head>
   <body>
      <h1>Fatal Error in   ',
                    $SenderObject,
                    ".",
                    $SenderMethod,
                    '();</h1>
      <h2>',
                    $SenderMessage,
                    "</h2>\n";

                if ($SenderCode != "") {
                    echo "<code>", htmlentities($SenderCode, ENT_COMPAT, "UTF-8"), "</code>\n";
                }

                if (is_array($ErrorLines) && $Line > -1) {
                    echo "<h3><strong>The error occurred on or near:</strong> ",
                        $File,
                        '</h3>
         <pre>';
                    $LineCount = count($ErrorLines);
                    $Padding = strlen($Line + 4);
                    for ($i = 0; $i < $LineCount; ++$i) {
                        if ($i > $Line - 6 && $i < $Line + 4) {
                            echo str_pad($i, $Padding, " ", STR_PAD_LEFT),
                                ": ",
                                htmlentities($ErrorLines[$i], ENT_COMPAT, "UTF-8");
                        }
                    }
                    echo "</pre>\n";
                }

                echo '<h2>Need Help?</h2>
      <p>If you are a user of this website, you can report this message to a website administrator.</p>
      <p>If you are an administrator of this website, you can get help at the <a href="https://open.vanillaforums.com/discussions/" target="_blank">Vanilla Community Forums</a>.</p>
      <h2>Additional information for support personnel:</h2>
      <ul>
         <li><strong>Application:</strong> ',
                    APPLICATION,
                    '</li>
         <li><strong>Application Version:</strong> ',
                    APPLICATION_VERSION,
                    '</li>
         <li><strong>PHP Version:</strong> ',
                    PHP_VERSION,
                    '</li>
         <li><strong>Operating System:</strong> ',
                    PHP_OS,
                    "</li>\n";

                if (array_key_exists("SERVER_SOFTWARE", $_SERVER)) {
                    echo "<li><strong>Server Software:</strong> ",
                        htmlspecialchars($_SERVER["SERVER_SOFTWARE"]),
                        "</li>\n";
                }

                if (array_key_exists("HTTP_REFERER", $_SERVER)) {
                    echo "<li><strong>Referer:</strong> ", htmlspecialchars($_SERVER["HTTP_REFERER"]), "</li>\n";
                }

                if (array_key_exists("HTTP_USER_AGENT", $_SERVER)) {
                    echo "<li><strong>User Agent:</strong> ", htmlspecialchars($_SERVER["HTTP_USER_AGENT"]), "</li>\n";
                }

                if (array_key_exists("REQUEST_URI", $_SERVER)) {
                    echo "<li><strong>Request Uri:</strong> ", htmlspecialchars($_SERVER["REQUEST_URI"]), "</li>\n";
                }
                echo '</ul>
   </body>
   </html>';
            }
        }
    } catch (Exception $e) {
        print get_class($e) .
            " thrown within the exception handler.<br/>Message: " .
            $e->getMessage() .
            " in " .
            $e->getFile() .
            " on line " .
            $e->getLine();
        exit();
    }
}

if (!function_exists("errorMessage")) {
    /**
     * Returns an error message formatted in a way that the custom ErrorHandler
     * function can understand (allows a little more information to be displayed
     * on errors).
     *
     * @param string $message The actual error message.
     * @param string $senderObject The name of the object that encountered the error.
     * @param string $senderMethod The name of the method that encountered the error.
     * @param string $code Any additional information that could be useful to debuggers.
     * @return string
     * @deprecated This function should just be replaced with a human-readable error message.
     */
    function errorMessage($message, $senderObject, $senderMethod, $code = "")
    {
        return $message . "|" . $senderObject . "|" . $senderMethod . "|" . $code;
    }
}

if (!function_exists("errorLog")) {
    /**
     * Attempt to log an error message to the PHP error log.
     *
     * @param string|\Exception $message
     * @deprecated ErrorLogger::writeErrorLog
     */
    function errorLog($message)
    {
        // Make sure the message can be converted to a string otherwise bail out
        if (!is_string($message) && !method_exists($message, "__toString")) {
            return;
        }

        if (!is_string($message)) {
            // Cast the $message to a string
            $message = (string) $message;
        }

        ErrorLogger::writeErrorLog($message);
    }
}

if (!function_exists("formatErrorException")) {
    /**
     * Format an \ErrorException into a string destined for PHP error_log()
     *
     * @param \ErrorException $exception The error exception to format
     * @return string The formatted error message
     * @access private
     */
    function formatErrorException($exception)
    {
        if (!($exception instanceof \ErrorException)) {
            return "";
        }

        $errorType = "";
        $errorCode = $exception->getCode();

        // Find an error type based on the error code
        switch ($errorCode) {
            case $errorCode & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR):
                $errorType = "PHP Fatal error";
                break;
            case $errorCode & (E_NOTICE | E_USER_NOTICE):
                $errorType = "PHP Notice";
                break;
            case $errorCode & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING):
                $errorType = "PHP Warning";
                break;
            case $errorCode & (E_DEPRECATED | E_USER_DEPRECATED):
                $errorType = "PHP Deprecated";
                break;
            case $errorCode & E_PARSE:
                $errorType = "PHP Parse error";
                break;
            case $errorCode & E_STRICT:
                $errorType = "PHP Strict standards";
                break;
        }

        $stackTrace = c("Garden.Errors.StackTrace") ? $exception->getTrace() : null;

        return formatPHPErrorLog(
            $exception->getMessage(),
            $errorType,
            $exception->getFile(),
            $exception->getLine(),
            $stackTrace
        );
    }
}

if (!function_exists("formatException")) {
    /**
     * Format an \Exception or any object implementing \Throwable
     * into a string destined for PHP error_log()
     *
     * @param mixed $exception The Exception to format
     * @param boolean $uncaught Whether the exception was uncaught or not
     * @return string The formatted error message
     * @access private
     */
    function formatException($exception, $uncaught = false)
    {
        if (!($exception instanceof \Exception) && !($exception instanceof \Throwable)) {
            // Not an Exception or a Throwable type (PHP7)
            return "";
        }

        $errorMessage = (string) $exception;

        if ($uncaught) {
            $errorType = "PHP Fatal error";
            $errorMessage = "Uncaught " . $errorMessage;
        } else {
            $errorType = "APP Log";
            $errorMessage = "Caught " . $errorMessage;
        }
        $errorMessage .= PHP_EOL . "  thrown";

        $stackTrace = c("Garden.Errors.StackTrace") ? $exception->getTrace() : null;

        return formatPHPErrorLog($errorMessage, $errorType, $exception->getFile(), $exception->getLine(), $stackTrace);
    }
}

if (!function_exists("formatPHPErrorLog")) {
    /**
     * Format an error message to be sent to PHP error_log()
     *
     * @param string $errorMsg The error message
     * @param string $errorType Optional error type such as "PHP Fatal error" or "PHP Notice".  It will be prefixed to the $errorMsg
     * @param string $file Optional file path where the error occured
     * @param string $line Optional line number where the error occured
     * @param array $stackTrace Optional stack trace of the error
     * @return string The formatted error message
     * @access private
     */
    function formatPHPErrorLog($errorMsg, $errorType = null, $file = null, $line = null, $stackTrace = null)
    {
        $formattedMessage = $errorMsg;
        if ($errorType) {
            $formattedMessage = sprintf("%s:  %s", $errorType, $errorMsg);
        }

        if ($file && is_numeric($line)) {
            $formattedMessage = sprintf("%s in %s on line %s", $formattedMessage, $file, $line);
        } elseif ($file) {
            $formattedMessage = sprintf("%s in %s", $formattedMessage, $file, $line);
        }

        if ($stackTrace) {
            $formattedMessage .= formatStackTrace($stackTrace) . "\n";
        }

        return $formattedMessage;
    }
}

if (!function_exists("formatStackTrace")) {
    /**
     * Format a stack trace.
     *
     * @param array $stackTrace
     * @return string The formatted stack trace
     * @deprecated Use DebugUtils::stackTraceString
     */
    function formatStackTrace($stackTrace)
    {
        return DebugUtils::stackTraceString($stackTrace);
    }
}

if (!function_exists("logException")) {
    /**
     * Log an exception.
     *
     * @param Exception $ex
     */
    function logException($ex)
    {
        if (!class_exists("Gdn", false)) {
            return;
        }

        if ($ex instanceof Gdn_UserException || $ex instanceof \Garden\Web\Exception\ClientException) {
            return;
        }

        ErrorLogger::error($ex, ["logException"]);
    }
}

if (!function_exists("logMessage")) {
    /**
     * Logs errors to a file. This function does not throw errors because it is
     * a last-ditch effort after errors have already been rendered.
     *
     * @param string $file The file to save the error log in.
     * @param int $line The line number that encountered the error.
     * @param string $object The name of the object that encountered the error.
     * @param string $method The name of the method that encountered the error.
     * @param string $message The error message.
     * @param string $code Any additional information that could be useful to debuggers.
     */
    function logMessage($file, $line, $object, $method, $message, $code = "")
    {
        if (!class_exists("Gdn", false)) {
            return;
        }

        // Prepare the log message
        $log = "[Garden] $file, $line, $object.$method()";
        if ($message != "") {
            $log .= ", $message";
        }
        if ($code != "") {
            $log .= ", $code";
        }

        // Attempt to log the message in the PHP logs
        errorLog($log);
    }
}

if (!function_exists("cleanErrorArguments")) {
    /**
     * Deprecated.
     *
     * @param mixed $var
     * @param array $blackList
     * @deprecated
     */
    function cleanErrorArguments(&$var, $blackList = ["configuration", "config", "database", "password"])
    {
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                if (in_array(strtolower($key), $blackList)) {
                    $var[$key] = "SECURITY";
                } else {
                    if (is_object($value)) {
                        $value = Gdn_Format::objectAsArray($value);
                        $var[$key] = $value;
                    }

                    if (is_array($value)) {
                        cleanErrorArguments($var[$key], $blackList);
                    }
                }
            }
        }
    }
}

if (!function_exists("__cleanErrorArguments")) {
    /**
     * This is an internal function not to be used outside of error printing.
     *
     * @param mixed $var
     * @param array $blackList
     * @return mixed
     */
    function __cleanErrorArguments($var, $blackList = ["configuration", "config", "database", "password"])
    {
        $seen = [];

        $fn = function ($var, int $nest = 0) use (&$fn, $blackList, &$seen) {
            if (is_array($var)) {
                $result = [];
                foreach ($var as $key => $value) {
                    if (in_array(strtolower($key), $blackList)) {
                        $value = "**SECURITY**";
                    } else {
                        if (is_object($value) && !in_array($value, $seen, true)) {
                            $seen[] = $value;
                            $value = Gdn_Format::objectAsArray($value);
                        }

                        if (is_array($value)) {
                            if ($nest < 10) {
                                $value = $fn($value, $nest + 1);
                            } else {
                                $value = "**MAX NESTING**";
                            }
                        }
                    }
                    $result[$key] = $value;
                }
            } else {
                $result = $var;
            }
            return $result;
        };

        $result = $fn($var);
        return $result;
    }
}

/**
 * Create a new not found exception. This is a convenience function that will create an exception with a standard message.
 *
 * @param string $recordType The translation code of the type of object that wasn't found.
 * @return Exception
 */
function notFoundException($recordType = "Page")
{
    Gdn::dispatcher()
        ->passData("RecordType", $recordType)
        ->passData(
            "Description",
            t(
                sprintf("The %s you were looking for could not be found.", strtolower($recordType)),
                t("The page you were looking for could not be found.")
            )
        );
    return new Gdn_UserException(
        t(sprintf("%s Not Found", $recordType), sprintf(t("%s Not Found"), t($recordType))),
        404
    );
}

/**
 * Create a new permission exception. This is a convenience function that will create an exception with a standard message.
 *
 * @param string|null $permission The name of the permission that was required.
 * @return Exception
 */
function permissionException($permission = null)
{
    if (!$permission) {
        $message = t("PermissionErrorMessage", "You don't have permission to do that.");
    } elseif ($permission == "Banned") {
        $message = t("You've been banned.");
    } elseif (stringBeginsWith($permission, "@")) {
        $message = stringBeginsWith($permission, "@", true, true);
    } else {
        $message = t(
            "PermissionRequired.$permission",
            sprintf(t("You need the %s permission to do that."), $permission)
        );
    }
    $prev = new PermissionException($permission);
    return new Gdn_UserException($message, 403, $prev);
}

/**
 * Create a new permission exception. This is a convenience function that will create an exception with a standard message.
 *
 * @param string|null $resource The name of the permission that was required.
 * @return Exception
 */
function forbiddenException($resource = null)
{
    if (!$resource) {
        $message = t("ForbiddenErrorMessage", "You are not allowed to do that.");
    } elseif (stringBeginsWith($resource, "@")) {
        $message = stringBeginsWith($resource, "@", true, true);
    } else {
        $message = sprintf(t("You are not allowed to %s."), $resource);
    }
    return new Gdn_UserException($message, 403);
}
