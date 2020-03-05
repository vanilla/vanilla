<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL v2
 */

namespace Vanilla\Utility;

use Garden\StaticCacheConfigTrait;

/**
 * Class Deprecation.
 */
class Deprecation {
    use StaticCacheConfigTrait;

    protected static $calls = [];

    /**
     * Collect data about deprecated global function call.
     *
     * Note 1: We need to extend this method or create another one to track calls from different places to collect all information
     *       Current implementation only outputs 1 time per request
     *       That mean if we have few plugins or classes calling same deprecated function we will only get 1 error message
     *       for only 1st call
     *
     * Note 2: We need to extend this method or create another one for any non global function to deprecate
     *       That one should include class name and method name detection
     *
     * IMPORTANT: any new call/reference to this log() method should be pushed/merged to master branch
     *            only when it does not output any errors on your localhost and/or staging
     *            General idea is to apply this method only to strong function-candidates
     *            which have no visible calls in the project developer can detect.
     *
     * Outputs that data to error log (should be visible on production)
     *
     * This function outputs 1 message per function per request
     *
     * @return void
     */
    public static function log() {
        // Set Garden.Log.Deprecation to false to disable log output
        if (self::c('Garden.Log.Deprecation', true)) {
            $info = debug_backtrace()[1];
            if (!key_exists($info['function'], self::$calls)) {
                $fileName = self::extractFileNameFromFrame($info);
                $message = 'Deprecated function '.$info['function'].' called from '.$fileName.' at line : '.$info['line'];
                self::logErrorMessage($message);
                self::$calls[$info['function']] = true;
            }
        }
    }

    /**
     * Log information about an unsupported parameter value.
     *
     * @param string $paramName The parameter name.
     * @param mixed $value The value of the parameter.
     * @param string $reason An additional explanation of why the value is invalid.
     *
     * @return void
     */
    public static function unsupportedParam(string $paramName, $value, string $reason = "") {
        // Set Garden.Log.Deprecation to false to disable log output
        if (!self::c('Garden.Log.Deprecation', true)) {
            return;
        }

        $stack = debug_backtrace();
        $firstFrame = $stack[1];
        $methodName = self::extractMethodNameFromFrame($firstFrame);
        $lookupKey = $methodName . '-' . $paramName . '-' . $value;
        if (array_key_exists($lookupKey, self::$calls)) {
            // We've already logged this call in this request.
            return;
        } else {
            self::$calls[$lookupKey] = true;
        }


        $message = "Method received unsupported parameter type.\n" . $paramName . ": " . json_encode($value);
        if ($reason) {
            $message .= "\n" . $reason;
        }
        $message .= self::formatBackTrace($stack);
        self::logErrorMessage($message);
    }

    /**
     * Log an error message.
     *
     * @param string $message
     *
     * @throws \Exception If called while debug mode is enabled.
     */
    private static function logErrorMessage(string $message) {
        trigger_error($message, E_USER_DEPRECATED);
        error_log($message, E_USER_ERROR);

        if (self::c('Debug', false)) {
            throw new \Exception($message);
        }
    }

    /**
     * Format a backtrace for logging.
     *
     * @param array $backtrace
     * @return string
     */
    private static function formatBackTrace(array $backtrace): string {
        $errorMessage = "";
        $stackDepth = self::c('Garden.Log.StackDepth', 3);

        for ($i = 0; $i < $stackDepth; $i++) {
            $frame = next($backtrace);
            if (false === $frame) {
                break;
            }

            $methodName = self::extractMethodNameFromFrame($frame);
            $fileName = self::extractFileNameFromFrame($frame);

            $errorMessage .= "\nMETHOD ==> " . $methodName;
            $errorMessage .= "\n  FILE ==> " . $fileName . ':' . $frame['line'];
            $errorMessage .= "\n";
        }

        return $errorMessage;
    }

    /**
     * Extract a nice printable method from a backtrace frame.
     *
     * @param array $frame
     * @return string
     */
    private static function extractMethodNameFromFrame(array $frame): string {
        $methodName = $frame['function'] . '()';
        if ($frame['class']) {
            $methodName = $frame['class'] . $frame['type'] . $methodName;
        }

        return $methodName;
    }

    /**
     * Get a nicely trimmed filename from a stack frame.
     *
     * @param array $frame
     * @return string
     */
    private static function extractFileNameFromFrame(array $frame): string {
        return str_replace(PATH_ROOT, '', $frame['file']);
    }
}
