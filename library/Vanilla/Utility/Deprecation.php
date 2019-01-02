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
                $fileName = str_replace(PATH_ROOT, '', $info['file']);
                $message = 'Deprecated function '.$info['function'].' called from '.$fileName.' at line : '.$info['line'];
                trigger_error($message, E_USER_DEPRECATED);
                error_log($message, E_USER_ERROR);
                self::$calls[$info['function']] = true;
            }
        }
    }
}
