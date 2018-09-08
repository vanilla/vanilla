<?php
/**
 * General functions
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

if (!function_exists('absoluteSource')) {
    /**
     * Get the full url of a source path relative to a base url.
     *
     * Takes a source path (ie. an image src from an html page), and an
     * associated URL (ie. the page that the image appears on), and returns the
     * absolute source (including url & protocol) path.
     *
     * @param string $srcPath The source path to make absolute (if not absolute already).
     * @param string $url The full url to the page containing the src reference.
     * @return string Absolute source path.
     */
    function absoluteSource($srcPath, $url) {
        // If there is a scheme in the srcpath already, just return it.
        if (!is_null(parse_url($srcPath, PHP_URL_SCHEME))) {
            return $srcPath;
        }

        // Does SrcPath assume root?
        if (in_array(substr($srcPath, 0, 1), ['/', '\\'])) {
            return parse_url($url, PHP_URL_SCHEME)
            .'://'
            .parse_url($url, PHP_URL_HOST)
            .$srcPath;
        }

        // Work with the path in the url & the provided src path to backtrace if necessary
        $urlPathParts = explode('/', str_replace('\\', '/', parse_url($url, PHP_URL_PATH)));
        $srcParts = explode('/', str_replace('\\', '/', $srcPath));
        $result = [];
        foreach ($srcParts as $part) {
            if (!$part || $part == '.') {
                continue;
            }

            if ($part == '..') {
                array_pop($urlPathParts);
            } else {
                $result[] = $part;
            }
        }
        // Put it all together & return
        return parse_url($url, PHP_URL_SCHEME)
        .'://'
        .parse_url($url, PHP_URL_HOST)
        .'/'.implode('/', array_filter(array_merge($urlPathParts, $result)));
    }
}

if (!function_exists('anonymizeIP')) {
    /**
     * Anonymize an IPv4 or IPv6 address.
     *
     * @param string $ip An IPv4 or IPv6 address.
     * @return bool|string Anonymized IP address on success. False on failure.
     */
    function anonymizeIP(string $ip) {
        $result = false;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 & FILTER_FLAG_IPV6)) {
            // Need a packed version for bitwise operations.
            $packed = inet_pton($ip);
            if ($packed !== false) {
                // Remove the last octet of an IPv4 address or the last 80 bits of an IPv6 address.
                // IP v4 addresses are 32 bits (4 bytes). IP v6 addresses are 128 bits (16 bytes).
                $mask = strlen($packed) == 4 ? inet_pton('255.255.255.0') : inet_pton('ffff:ffff:ffff::');
                $result = inet_ntop($packed & $mask);
            }
        }

        return $result;
    }
}

if (!function_exists('arrayCombine')) {
    /**
     * PHP's array_combine has a limitation that doesn't allow array_combine to work if either of the arrays are empty.
     *
     * @param array $array1 Array of keys to be used. Illegal values for key will be converted to string.
     * @param array $array2 Array of values to be used.
     */
    function arrayCombine($array1, $array2) {
        if (!is_array($array1)) {
            $array1 = [];
        }

        if (!is_array($array2)) {
            $array2 = [];
        }

        if (count($array1) > 0 && count($array2) > 0) {
            return array_combine($array1, $array2);
        } elseif (count($array1) == 0) {
            return $array2;
        } else {
            return $array1;
        }
    }
}
/*
 We now support PHP 5.2.0 - Which should make this declaration unnecessary.
if (!function_exists('array_fill_keys')) {
   function array_fill_keys($Keys, $Val) {
      return array_combine($Keys,array_fill(0,count($Keys),$Val));
   }
}
*/
if (!function_exists('arrayHasValue')) {
    /**
     * Search an array (and all arrays it contains) for a value.
     *
     * @param array $array The array to search.
     * @param mixed $value The value to search for.
     */
    function arrayHasValue($array, $value) {
        if (in_array($value, $array)) {
            return true;
        } else {
            foreach ($array as $k => $v) {
                if (is_array($v) && arrayHasValue($v, $value) === true) {
                    return true;
                }
            }
            return false;
        }
    }
}

if (!function_exists('arrayKeyExistsI')) {
    /**
     * A case-insensitive array_key_exists search.
     *
     * @param string|int $key The key to search for.
     * @param array $search The array to search.
     * @return bool Returns true if the array contains the key or false otherwise.
     * @see array_key_exists, arrayHasValue
     */
    function arrayKeyExistsI($key, $search) {
        if (is_array($search)) {
            foreach ($search as $k => $v) {
                if (strtolower($key) == strtolower($k)) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('arrayReplaceConfig')) {
    /**
     * Replaces elements from an override array into a default array recursively, overwriting numeric arrays entirely.
     *
     * This function differs from **array_replace_recursive** in that if an array is numeric it will be completely replaced.
     *
     * @param array $default The array of default values.
     * @param array $override The array of override values.
     * @return array Returns the replaced arrays.
     */
    function arrayReplaceConfig(array $default, array $override) {
        if (isset($override[0]) || empty($default)) {
            return $override;
        }

        $result = array_replace($default, $override);

        foreach ($result as $key => &$value) {
            if (is_array($value) && isset($default[$key]) && isset($override[$key]) && is_array($default[$key]) && !isset($value[0]) && !isset($default[$key][0])) {
                $value = arrayReplaceConfig($default[$key], $override[$key]);
            }
        }

        return $result;
    }
}

if (!function_exists('arraySearchI')) {
    /**
     * Case-insensitive version of array_search.
     *
     * @param array $value The value to find in array.
     * @param array $search The array to search in for $value.
     * @return mixed Key of $value in the $search array.
     */
    function arraySearchI($value, $search) {
        return array_search(strtolower($value), array_map('strtolower', $search));
    }
}

if (!function_exists('arrayTranslate')) {
    /**
     * Take all of the items specified in an array and make a new array with them specified by mappings.
     *
     * @param array $array The input array to translate.
     * @param array $mappings The mappings to translate the array.
     * @param bool $addRemaining Whether or not to add the remaining items to the array.
     * @return array
     */
    function arrayTranslate($array, $mappings, $addRemaining = false) {
        $array = (array)$array;
        $result = [];
        foreach ($mappings as $index => $value) {
            if (is_numeric($index)) {
                $key = $value;
                $newKey = $value;
            } else {
                $key = $index;
                $newKey = $value;
            }
            if ($newKey === null) {
                unset($array[$key]);
                continue;
            }

            if (isset($array[$key])) {
                $result[$newKey] = $array[$key];
                unset($array[$key]);
            } else {
                $result[$newKey] = null;
            }
        }

        if ($addRemaining) {
            foreach ($array as $key => $value) {
                if (!isset($result[$key])) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}

if (!function_exists('arrayValueI')) {
    /**
     * Get the value associated with the {@link $needle} in the {@link $haystack}. This is a CASE-INSENSITIVE search.
     *
     * @param string $needle The key to look for in the $haystack associative array.
     * @param array $haystack The associative array in which to search for the $needle key.
     * @param mixed $default The default value to return if the requested value is not found. Default is false.
     * @return mixed Returns the value at {@link $needle} in {@link $haystack} or {@link $default} if it isn't found.
     */
    function arrayValueI($needle, $haystack, $default = false) {
        $return = $default;
        $needle = strtolower($needle);
        if (is_array($haystack)) {
            foreach ($haystack as $key => $value) {
                if ($needle == strtolower($key)) {
                    $return = $value;
                    break;
                }
            }
        }
        return $return;
    }
}

if (!function_exists('asset')) {
    /**
     * Takes the path to an asset (image, js file, css file, etc) and prepends the web root.
     *
     * @param string $destination The path to the asset.
     * @param boolean $withDomain Whether or not to include the domain.
     * @param boolean $addVersion Whether or not to add a cache-busting querystring parameter to the URL.
     * @param string $version Forced version, skips auto-lookup.
     * @return string Returns the URL to the asset.
     */
    function asset($destination = '', $withDomain = false, $addVersion = false, $version = null) {
        $destination = str_replace('\\', '/', $destination);
        if (isUrl($destination)) {
            $result = $destination;
        } else {
            $result = Gdn::request()->urlDomain($withDomain).Gdn::request()->assetRoot().'/'.ltrim($destination, '/');
        }

        if ($addVersion) {
            $version = assetVersion($destination, $version);
            $result .= (strpos($result, '?') === false ? '?' : '&').'v='.urlencode($version);
        }
        return $result;
    }
}

if (!function_exists('assetVersion')) {
    /**
     * Get a version string for a given asset.
     *
     * @param string $destination The path of the asset.
     * @param string|null $version A known version for the asset or **null** to grab it from the addon's info array.
     * @return string Returns a version string.
     */
    function assetVersion($destination, $version = null) {
        static $gracePeriod = 90;

        // Figure out which version to put after the asset.
        if (is_null($version)) {
            $version = APPLICATION_VERSION;
            if (preg_match('`^/([^/]+)/([^/]+)/`', $destination, $matches)) {
                $type = $matches[1];
                $key = $matches[2];
                static $themeVersion = null;

                switch ($type) {
                    case 'plugins':
                    case 'applications':
                        $addon = Gdn::addonManager()->lookupAddon($key);
                        if ($addon) {
                            $version = $addon->getVersion();
                        }
                        break;
                    case 'themes':
                        if ($themeVersion === null) {
                            $theme = Gdn::addonManager()->lookupTheme(theme());
                            if ($theme) {
                                $themeVersion = $theme->getVersion();
                            }
                        }
                        $version = $themeVersion;
                        break;
                }
            }
        }

        // Add a timestamp component to the version if available.
        if ($timestamp = c('Garden.Deployed')) {
            $graced = $timestamp + $gracePeriod;
            if (time() >= $graced) {
                $timestamp = $graced;
            }
            $version .= '.'.dechex($timestamp);
        }
        return $version;
    }
}

if (!function_exists('attribute')) {
    /**
     * Takes an attribute (or array of attributes) and formats them in attribute="value" format.
     *
     * @param string|array $name The attribute array or the name of the attribute.
     * @param mixed $valueOrExclude The value of the attribute or a prefix of attribute names to exclude.
     * @return string Returns a string in attribute="value" format.
     */
    function attribute($name, $valueOrExclude = '') {
        $return = '';
        if (!is_array($name)) {
            $name = [$name => $valueOrExclude];
            $exclude = '';
        } else {
            $exclude = $valueOrExclude;
        }

        foreach ($name as $attribute => $val) {
            if ((empty($val) && !in_array($val, [0, '0'], true)) || ($exclude && stringBeginsWith($attribute, $exclude))) {
                continue;
            }

            if (is_array($val) && strpos($attribute, 'data-') === 0) {
                $val = json_encode($val);

            }

            if ($val != '' && $attribute != 'Standard') {
                $return .= ' '.$attribute.'="'.htmlspecialchars($val, ENT_COMPAT, 'UTF-8').'"';
            }
        }
        return $return;
    }
}

if (!function_exists('c')) {
    /**
     * Retrieves a configuration setting.
     *
     * @param string|bool $name The name of the configuration setting.
     * Settings in different sections are separated by dots.
     * @param mixed $default The result to return if the configuration setting is not found.
     * @return mixed The configuration setting.
     * @see Gdn::config()
     */
    function c($name = false, $default = false) {
        return Gdn::config($name, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Retrieves a configuration setting.
     *
     * @param string|bool $name The name of the configuration setting.
     * Settings in different sections are separated by dots.
     * @param mixed $default The result to return if the configuration setting is not found.
     * @return mixed The configuration setting.
     * @see Gdn::config()
     */
    function config($name = false, $default = false) {
        return Gdn::config($name, $default);
    }
}

if (!function_exists('calculateNumberOfPages')) {
    /**
     * Calculate the total number of pages based on the total items and items per page.
     *
     * Based on the total number of items and the number of items per page,
     * this function will calculate how many pages there are.
     *
     * @param int $itemCount The total number of items.
     * @param int $itemsPerPage The number of items per page.
     * @return int Returns the number of pages available.
     */
    function calculateNumberOfPages($itemCount, $itemsPerPage) {
        $tmpCount = ($itemCount / $itemsPerPage);
        $roundedCount = intval($tmpCount);

        if ($tmpCount > 1) {
            if ($tmpCount > $roundedCount) {
                $pageCount = $roundedCount + 1;
            } else {
                $pageCount = $roundedCount;
            }
        } else {
            $pageCount = 1;
        }
        return $pageCount;
    }
}

if (!function_exists('changeBasename')) {
    /**
     * Change the basename part of a filename for a given path.
     *
     * @param string $path The path to alter.
     * @param string $newBasename The new basename. A %s will be replaced by the old basename.
     * @return string Return {@link $path} with the basename changed.
     */
    function changeBasename($path, $newBasename) {
        $newBasename = str_replace('%s', '$2', $newBasename);
        $result = preg_replace('/^(.*\/)?(.*?)(\.[^.]+)$/', '$1'.$newBasename.'$3', $path);

        return $result;
    }
}

// Smarty
if (!function_exists('checkPermission')) {
    /**
     * A functional version of {@link Gdn_Session::checkPermission()}.
     *
     * @param string|array[string] $permissionName The permission or permissions to check.
     * @param string $type The type of permission. Either "Category" or empty.
     * @return bool Returns true if the current user has the given permission(s).
     */
    function checkPermission($permissionName, $type = '') {
        $result = Gdn::session()->checkPermission($permissionName, false, $type ? 'Category' : '', $type);
        return $result;
    }
}

// Negative permission check
if (!function_exists('checkRestriction')) {
    /**
     * Check to see if a user **does not** have a permission.
     *
     * @param string|array[string] $permissionName The permission or permissions to check.
     * @return bool Returns true if the current user **does not** have the given permission(s).
     */
    function checkRestriction($permissionName) {
        $result = Gdn::session()->checkPermission($permissionName);
        $unrestricted = Gdn::session()->checkPermission('Garden.Admin.Only');
        return $result && !$unrestricted;
    }
}

// Smarty sux
if (!function_exists('multiCheckPermission')) {
    /**
     * Check to see if a use has any one of a set of permissions.
     *
     * @param string|array[string] $permissionName The permission or permissions to check.
     * @return bool Returns true if the current user has any one of the given permission(s).
     */
    function multiCheckPermission($permissionName) {
        $result = Gdn::session()->checkPermission($permissionName, false);
        return $result;
    }
}

if (!function_exists('check_utf8')) {
    /**
     * Check to see if a string is UTF-8.
     *
     * @param string $str The string to check.
     * @return bool Returns true if the string contains only valid UTF-8 characters or false otherwise.
     */
    function check_utf8($str) {
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c > 247)) {
                    return false;
                } elseif ($c > 239) {
                    $bytes = 4;
                } elseif ($c > 223) {
                    $bytes = 3;
                } elseif ($c > 191) {
                    $bytes = 2;
                } else {
                    return false;
                }
                if (($i + $bytes) > $len) {
                    return false;
                }
                while ($bytes > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bytes--;
                }
            }
        }
        return true;
    }
}

if (!function_exists('combinePaths')) {
    /**
     * Takes an array of path parts and concatenates them using the specified delimiter.
     *
     * Delimiters will not be duplicated. Example: all of the
     * following arrays will generate the path "/path/to/vanilla/applications/dashboard"
     * array('/path/to/vanilla', 'applications/dashboard')
     * array('/path/to/vanilla/', '/applications/dashboard')
     * array('/path', 'to', 'vanilla', 'applications', 'dashboard')
     * array('/path/', '/to/', '/vanilla/', '/applications/', '/dashboard')
     *
     * @param array $paths The array of paths to concatenate.
     * @param string $delimiter The delimiter to use when concatenating. Defaults to system-defined directory separator.
     * @returns string Returns the concatenated path.
     */
    function combinePaths($paths, $delimiter = DS) {
        if (is_array($paths)) {
            $mungedPath = implode($delimiter, $paths);
            $mungedPath = str_replace(
                [$delimiter.$delimiter.$delimiter, $delimiter.$delimiter],
                [$delimiter, $delimiter],
                $mungedPath
            );
            return str_replace(['http:/', 'https:/'], ['http://', 'https://'], $mungedPath);
        } else {
            return $paths;
        }
    }
}

if (!function_exists('concatSep')) {
    /**
     * Concatenate a string to another string with a separator.
     *
     * @param string $sep The separator string to use between the concatenated strings.
     * @param string $str1 The first string in the concatenation chain.
     * @param mixed $str2 The second string in the concatenation chain.
     *  - This parameter can be an array in which case all of its elements will be concatenated.
     *  - If this parameter is a string then the function will look for more arguments to concatenate.
     * @return string
     */
    function concatSep($sep, $str1, $str2) {
        if (is_array($str2)) {
            $strings = array_merge((array)$str1, $str2);
        } else {
            $strings = func_get_args();
            array_shift($strings);
        }

        $result = '';
        foreach ($strings as $string) {
            if (!$string) {
                continue;
            }

            if ($result) {
                $result .= $sep;
            }
            $result .= $string;
        }
        return $result;
    }
}

if (!function_exists('flattenArray')) {

    /**
     * Recursively flatten a nested array into a single-dimension array.
     *
     * @param string $sep The string used to separate keys.
     * @param array $array The array to flatten.
     * @return array Returns the flattened array.
     */
    function flattenArray($sep, $array) {
        $result = [];

        $fn = function ($array, $px = '') use ($sep, &$fn, &$result) {
            foreach ($array as $key => $value) {
                $px = $px ? "{$px}{$sep}{$key}" : $key;

                if (is_array($value)) {
                    $fn($value, $px);
                } else {
                    $result[$px] = $value;
                }
            }
        };

        $fn($array);

        return $result;
    }
}

if (!function_exists('safePrint')) {
    /**
     * Return/print human-readable and non casted information about a variable.
     *
     * @param mixed $mixed The variable to return/echo.
     * @param bool $returnData Whether or not return the data instead of echoing it.
     * @return string|void Returns {@link $mixed} or nothing if {@link $returnData} is false.
     */
    function safePrint($mixed, $returnData = false) {

        $functionName = __FUNCTION__;

        $replaceCastedValues = function (&$value) use (&$replaceCastedValues, $functionName) {
            $isObject = is_object($value);

            // Replace original object by a shallow copy of itself to keep it from being modified.
            if ($isObject) {
                $value = clone $value;
            }

            if ($isObject || is_array($value)) {
                foreach ($value as &$content) {
                    $replaceCastedValues($content);
                }
                unset($content);
                return;
            }

            if ($value === '') {
                $value = $functionName.'{empty string}';
            } elseif ($value === true) {
                $value = $functionName.'{true}';
            } elseif ($value === false) {
                $value = $functionName.'{false}';
            } elseif ($value === null) {
                $value = $functionName.'{null}';
            } elseif ($value === 0) {
                $value = $functionName.'{0}';
            }
        };

        $replaceCastedValues($mixed);

        return print_r($mixed, $returnData);
    }
}

if (!function_exists('dbdecode')) {
    /**
     * Decode a value retrieved from database storage.
     *
     * @param string $value An encoded string representation of a value to be decoded.
     * @return mixed Null if the $value was empty, a decoded value on success or false on failure.
     */
    function dbdecode($value) {
        // Mirror dbencode behaviour.
        if ($value === null || $value === '') {
            return null;
        } elseif (is_array($value)) {
            // This handles a common double decoding scenario.
            return $value;
        }

        $decodedValue = json_decode($value, true);

        // Backward compatibility.
        if ($decodedValue === null) {
            // Suppress errors https://github.com/vanilla/vanilla/pull/3734#issuecomment-210664113
            $decodedValue = @unserialize($value, ['allowed_classes' => false]);
        }

        if (is_array($value) || is_object($value)) {
            // IP addresses are binary packed now. Let's convert them from text to binary
            $decodedValue = ipEncodeRecursive($decodedValue);
        }

        return $decodedValue;
    }
}

if (!function_exists('dbencode')) {
    /**
     * Encode a value in preparation for database storage.
     *
     * @param mixed $value A value to be encoded.
     * @return mixed An encoded string representation of the provided value, null if the value was empty or false on failure.
     */
    function dbencode($value) {
        // Treat an empty value as null so that we insert "nothing" in the database instead of an empty string.
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            // IP addresses are binary packed now.
            // Let's convert them to text so that they can be safely inserted into the text column
            $value = ipDecodeRecursive($value);
        }

        $encodedValue = false;
        try {
            $encodedValue = jsonEncodeChecked($value, JSON_UNESCAPED_SLASHES);
        } catch (Exception $ex) {
            $msg = 'Failed to encode a value in dbencode()';
            $context = ['value' => $value];
            trace(array_merge(['Error' => $msg], $context), TRACE_ERROR);
            Logger::log(Logger::ERROR, 'Failed to encode a value in dbencode()', $context);
        }

        return $encodedValue;
    }
}

if (!function_exists('decho')) {
    /**
     * Echo debug messages and variables.
     *
     * @param mixed $mixed The variable to echo.
     * @param string $prefix The text to be used as a prefix for the output.
     * @param bool $public Whether or not output is visible for everyone.
     */
    function decho($mixed, $prefix = 'DEBUG', $public = false) {
        $prefix = stringEndsWith($prefix, ': ', true, true).': ';

        if ($public || Gdn::session()->checkPermission('Garden.Debug.Allow')) {
            $stack = debug_backtrace();

            $backtrace = 'Line '.$stack[0]['line'].' in '.$stack[0]['file']."\n";
            if (defined('PATH_ROOT')) {
                $backtrace = str_replace(PATH_ROOT, '', $backtrace);
            }

            echo '<pre style="text-align: left; padding: 0 4px;">'.$backtrace.$prefix;
            if (is_string($mixed)) {
                echo $mixed;
            } else {
                echo htmlspecialchars(safePrint($mixed, true));
            }

            echo '</pre>';
        }
    }
}

if (!function_exists('dateCompare')) {
    /**
     * Compare two dates.
     *
     * This function compares two dates in a way that is similar to {@link strcmp()}.
     *
     * @param int|string $date1 A timestamp or string representation of a date.
     * @param int|string $date2 A timestamp or string representation of a date.
     * @return int Returns < 0 if {@link $date1} is less than {@link $date2}; > 0 if {@link $date1} is greater than
     * {@link $date2}, and 0 if they are equal.
     * @since 2.1
     */
    function dateCompare($date1, $date2) {
        if (!is_numeric($date1)) {
            $date1 = strtotime($date1);
        }
        if (!is_numeric($date2)) {
            $date2 = strtotime($date2);
        }

        if ($date1 == $date2) {
            return 0;
        }
        if ($date1 > $date2) {
            return 1;
        }
        return -1;
    }
}

if (!function_exists('debug')) {
    /**
     * Get or set the current debug state of the application.
     *
     * @param bool|null $value The new debug value or null to just return the current value.
     * @return bool Returns the current debug level.
     */
    function debug($value = null) {
        static $debug = false;
        if ($value === null) {
            return $debug;
        }
        $debug = $value;
        return $debug;
    }
}

if (!function_exists('debugMethod')) {
    /**
     * Format a function or method call for debug output.
     *
     * @param string $methodName The name the method.
     * @param array $methodArgs An array of arguments passed to the method.
     * @return string Returns the method formatted for debug output.
     */
    function debugMethod($methodName, $methodArgs = []) {
        echo $methodName."(";
        $sA = [];
        foreach ($methodArgs as $funcArg) {
            if (is_null($funcArg)) {
                $sA[] = 'null';
            } elseif (!is_array($funcArg) && !is_object($funcArg)) {
                $sA[] = "'{$funcArg}'";
            } elseif (is_array($funcArg)) {
                $sA[] = "'Array(".sizeof($funcArg).")'";
            } else {
                $sA[] = gettype($funcArg)."/".get_class($funcArg);
            }
        }
        echo implode(', ', $sA);
        echo ")\n";
    }
}

if (!function_exists('deprecated')) {
    /**
     * Mark a function deprecated.
     *
     * @param string $oldName The name of the deprecated function.
     * @param string $newName The name of the new function that should be used instead.
     * @param string $date Deprecated. Ironic, no?
     */
    function deprecated($oldName, $newName = '', $date = '') {
        $message = "$oldName is deprecated.";
        if ($newName) {
            $message .= " Use $newName instead.";
        }

        trigger_error($message, E_USER_DEPRECATED);
    }
}

if (!function_exists('explodeTrim')) {
    /**
     * Split a string by a string and do some trimming to clean up faulty user input.
     *
     * @param string $delimiter The boundary string.
     * @param string $string The input string.
     * @param bool $implode Whether or not to re-implode the string before returning.
     * @return array|string Returns the exploded string as an array or a string if {@link $implode} is true.
     */
    function explodeTrim($delimiter, $string, $implode = false) {
        $arr = explode($delimiter, $string);
        $arr = array_map('trim', $arr);
        $arr = array_filter($arr);
        if ($implode) {
            return implode($delimiter, $arr);
        } else {
            return $arr;
        }
    }
}

if (!function_exists('externalUrl')) {
    /**
     * Build a URL to an external site linked to this one.
     *
     * This function is used when an external site is configured with Vanilla in an embedding scenario.
     *
     * @param string $path The path within Vanilla.
     * @return string Returns the external URL.
     */
    function externalUrl($path) {
        $urlFormat = c('Garden.ExternalUrlFormat');

        if ($urlFormat && !isUrl($path)) {
            $result = sprintf($urlFormat, ltrim($path, '/'));
        } elseif (stringBeginsWith($path, '//')) {
            $result = Gdn::request()->scheme().':'.$path;
        } else {
            $result = url($path, true);
        }

        return $result;
    }
}


if (!function_exists('fetchPageInfo')) {
    /**
     * Examine a page at {@link $Url} for title, description & images.
     *
     * Be sure to check the resultant array for any Exceptions that occurred while retrieving the page.
     *
     * @param string $url The url to examine.
     * @param integer $timeout How long to allow for this request.
     * Default Garden.SocketTimeout or 1, 0 to never timeout. Default is 0.
     * @param bool $sendCookies Whether or not to send browser cookies with the request.
     * @param bool $includeMedia Include media (e.g. image, video) attributes?
     * @return array Returns an array containing Url, Title, Description, Images (array) and Exception
     * (if there were problems retrieving the page).
     */
    function fetchPageInfo($url, $timeout = 3, $sendCookies = false, $includeMedia = false) {
        $pageInfo = [
            'Url' => $url,
            'Title' => '',
            'Description' => '',
            'Images' => [],
            'Exception' => false
        ];

        try {
            // Make sure the URL is valid.
            $urlParts = parse_url($url);
            if ($urlParts === false || !in_array(val('scheme', $urlParts), ['http', 'https'])) {
                throw new Exception('Invalid URL.', 400);
            }

            $request = new ProxyRequest();
            $pageHtml = $request->request([
                'URL' => $url,
                'Timeout' => $timeout,
                'Cookies' => $sendCookies,
                'Redirects' => true,
                // Make sure that the redirect is on http/https
                'ProtocolMask' => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            ]);

            if (!$request->status()) {
                throw new Exception('Couldn\'t connect to host.', 400);
            }

            $dom = pQuery::parseStr($pageHtml);
            if (!$dom) {
                throw new Exception('Failed to load page for parsing.');
            }

            /**
             * Parse a page for OpenGraph media information.
             *
             * @param array $pageInfo
             */
            $getOpenGraphMedia = function (array &$pageInfo) use ($dom) {
                $pageInfo['Media'] = [];

                // Only target og:image and og:video tags.
                $mediaTypes = ['image', 'video'];
                foreach ($mediaTypes as $mediaType) {
                    $tags = $dom->query('meta[property ^= "og:'.$mediaType.'"]');

                    /** @var pQuery\DomNode $node */
                    foreach ($tags as $node) {
                        $property = $node->attr('property');
                        $content = $node->attr('content');

                        // If this is a root type element, save any existing type row data and start a new row.
                        if ($property == "og:{$mediaType}") {
                            if (isset($media)) {
                                if (!array_key_exists($mediaType, $pageInfo['Media'])) {
                                    $pageInfo['Media'][$mediaType] = [];
                                }
                                $pageInfo['Media'][$mediaType][] = $media;
                            }
                            $media = ['value' => $content];
                        } else {
                            // Shave off the type prefix. Save the content, if it's something we actually want.
                            $subproperty = trim(stringBeginsWith(
                                $property,
                                "og:{$mediaType}",
                                false,
                                true
                            ), ':');
                            if (in_array($subproperty, ['height', 'width'])) {
                                if (isset($media)) {
                                    $media[$subproperty] = $content;
                                }
                            }
                        }
                    }

                    // Save any outstanding information. Clear the row in preparation for the next iteration.
                    if (isset($media)) {
                        if (!array_key_exists($mediaType, $pageInfo['Media'])) {
                            $pageInfo['Media'][$mediaType] = [];
                        }
                        $pageInfo['Media'][$mediaType][] = $media;
                        unset($media);
                    }
                }
            };

            // FIRST PASS: Look for open graph title, desc, images
            $pageInfo['Title'] = domGetContent($dom, 'meta[property="og:title"]');

            trace('Getting og:description');
            $pageInfo['Description'] = domGetContent($dom, 'meta[property="og:description"]');
            foreach ($dom->query('meta[property="og:image"]') as $image) {
                if ($image->attr('content')) {
                    $pageInfo['Images'][] = $image->attr('content');
                }
            }

            // SECOND PASS: Look in the page for title, desc, images
            if ($pageInfo['Title'] == '') {
                $pageInfo['Title'] = $dom->query('title')->text();
            }

            if ($pageInfo['Description'] == '') {
                trace('Getting meta description');
                $pageInfo['Description'] = domGetContent($dom, 'meta[name="description"]');
            }

            // THIRD PASS: Look in the page contents
            if ($pageInfo['Description'] == '') {
                foreach ($dom->query('p') as $element) {
                    trace('Looking at p for description.');

                    if (strlen($element->plaintext) > 150) {
                        $pageInfo['Description'] = $element->text();
                        break;
                    }
                }
                if (strlen($pageInfo['Description']) > 400) {
                    $pageInfo['Description'] = sliceParagraph($pageInfo['Description'], 400);
                }
            }

            // Final: Still nothing? remove limitations
            if ($pageInfo['Description'] == '') {
                foreach ($dom->query('p') as $element) {
                    trace('Looking at p for description (no restrictions)');
                    if (trim($element->text()) != '') {
                        $pageInfo['Description'] = $element->text();
                        break;
                    }
                }
            }

            // Page Images
            if (count($pageInfo['Images']) == 0) {
                $images = domGetImages($dom, $url);
                $pageInfo['Images'] = array_values($images);
            }

            $pageInfo['Title'] = htmlEntityDecode($pageInfo['Title']);
            $pageInfo['Description'] = htmlEntityDecode($pageInfo['Description']);

            /**
             * Add OpenGraph media information?
             */
            if ($includeMedia) {
                $getOpenGraphMedia($pageInfo);
            }
        } catch (Exception $ex) {
            $pageInfo['Exception'] = $ex->getMessage();
        }

        return $pageInfo;
    }
}

if (!function_exists('domGetContent')) {
    /**
     * Search a DOM for a selector and return the contents.
     *
     * @param pQuery $dom The DOM to search.
     * @param string $selector The CSS style selector for the content to find.
     * @param string $default The default content to return if the node isn't found.
     * @return string Returns the content of the found node or {@link $default} otherwise.
     */
    function domGetContent($dom, $selector, $default = '') {
        $element = $dom->query($selector);
        $content = $element->attr('content');
        return $content ? $content : $default;
    }
}

if (!function_exists('domGetImages')) {
    /**
     * Get the images from a DOM.
     *
     * @param pQuery $dom The DOM to search.
     * @param string $url The URL of the document to add to relative URLs.
     * @param int $maxImages The maximum number of images to return.
     * @return array Returns an array in the form: `[['Src' => '', 'Width' => '', 'Height' => ''], ...]`.
     */
    function domGetImages($dom, $url, $maxImages = 4) {
        $images = [];
        foreach ($dom->query('img') as $element) {
            $images[] = [
                'Src' => absoluteSource($element->attr('src'), $url),
                'Width' => $element->attr('width'),
                'Height' => $element->attr('height'),
            ];
        }

//      Gdn::controller()->Data['AllImages'] = $Images;

        // Sort by size, biggest one first
        $imageSort = [];
        // Only look at first 4 images (speed!)
        $i = 0;
        foreach ($images as $imageInfo) {
            $image = $imageInfo['Src'];

            if (strpos($image, 'doubleclick.') != false) {
                continue;
            }

            try {
                if ($imageInfo['Height'] && $imageInfo['Width']) {
                    $height = $imageInfo['Height'];
                    $width = $imageInfo['Width'];
                } else {
                    list($width, $height) = getimagesize($image);
                }

                $diag = (int)floor(sqrt(($width * $width) + ($height * $height)));

//            Gdn::controller()->Data['Foo'][] = array($Image, $Width, $Height, $Diag);

                if (!$width || !$height) {
                    continue;
                }

                // Require min 100x100 dimension image.
                if ($width < 100 && $height < 100) {
                    continue;
                }

                // Don't take a banner-shaped image.
                if ($height * 4 < $width) {
                    continue;
                }

                // Prefer images that are less than 800px wide (banners?)
//            if ($Diag > 141 && $Width < 800) { }

                if (!array_key_exists($diag, $imageSort)) {
                    $imageSort[$diag] = [$image];
                } else {
                    $imageSort[$diag][] = $image;
                }


                $i++;

                if ($i > $maxImages) {
                    break;
                }
            } catch (Exception $ex) {
                // do nothing
            }
        }

        krsort($imageSort);
        $goodImages = [];
        foreach ($imageSort as $diag => $arr) {
            $goodImages = array_merge($goodImages, $arr);
        }
        return $goodImages;
    }
}

if (!function_exists('forceIPv4')) {
    /**
     * Force a string into ipv4 notation.
     *
     * @param string $iP The IP address to force.
     * @return string Returns the IPv4 address version of {@link IP}.
     * @since 2.1
     */
    function forceIPv4($iP) {
        if ($iP === '::1') {
            return '127.0.0.1';
        } elseif (strpos($iP, ':') === true) {
            return '0.0.0.1';
        } elseif (strpos($iP, '.') === false) {
            return '0.0.0.2';
        } else {
            return substr($iP, 0, 15);
        }
    }
}

if (!function_exists('ForeignIDHash')) {
    /**
     * If a ForeignID is longer than 32 characters, use its hash instead.
     *
     * @param string $foreignID The current foreign ID value.
     * @return string Returns a string that is 32 characters or less.
     */
    function foreignIDHash($foreignID) {
        return strlen($foreignID) > 32 ? md5($foreignID) : $foreignID;
    }
}

if (!function_exists('formatString')) {
    /**
     * Formats a string by inserting data from its arguments, similar to sprintf, but with a richer syntax.
     *
     * @param string $string The string to format with fields from its args enclosed in curly braces.
     * The format of fields is in the form {Field,Format,Arg1,Arg2}. The following formats are the following:
     *  - date: Formats the value as a date. Valid arguments are short, medium, long.
     *  - number: Formats the value as a number. Valid arguments are currency, integer, percent.
     *  - time: Formats the value as a time. This format has no additional arguments.
     *  - url: Calls url() function around the value to show a valid url with the site.
     * You can pass a domain to include the domain.
     *  - urlencode, rawurlencode: Calls urlencode/rawurlencode respectively.
     *  - html: Calls htmlspecialchars.
     * @param array $args The array of arguments.
     * If you want to nest arrays then the keys to the nested values can be separated by dots.
     * @return string The formatted string.
     * <code>
     * echo formatString("Hello {Name}, It's {Now,time}.", array('Name' => 'Frank', 'Now' => '1999-12-31 23:59'));
     * // This would output the following string:
     * // Hello Frank, It's 12:59PM.
     * </code>
     */
    function formatString($string, $args = []) {
        _formatStringCallback($args, true);
        $result = preg_replace_callback('/{([^\s][^}]+[^\s]?)}/', '_formatStringCallback', $string);

        return $result;
    }
}

if (!function_exists('_formatStringCallback')) {
    /**
     * The callback helper for {@link formatString()}.
     *
     * @param array $match Either the array of arguments or the regular expression match.
     * @param bool $setArgs Whether this is a call to initialize the arguments or a matching callback.
     * @return mixed Returns the matching string or nothing when setting the arguments.
     * @access private
     */
    function _formatStringCallback($match, $setArgs = false) {
        static $args = [], $contextUserID = null;
        if ($setArgs) {
            $args = $match;

            if (isset($args['_ContextUserID'])) {
                $contextUserID = $args['_ContextUserID'];
            } else {
                $contextUserID = Gdn::session() && Gdn::session()->isValid() ? Gdn::session()->UserID : null;
            }

            return '';
        }

        $match = $match[1];
        if ($match == '{') {
            return $match;
        }

        // Parse out the field and format.
        $parts = explode(',', $match);
        $field = trim($parts[0]);
        $format = trim(val(1, $parts, ''));
        $subFormat = strtolower(trim(val(2, $parts, '')));
        $formatArgs = val(3, $parts, '');

        if (in_array($format, ['currency', 'integer', 'percent'])) {
            $formatArgs = $subFormat;
            $subFormat = $format;
            $format = 'number';
        } elseif (is_numeric($subFormat)) {
            $formatArgs = $subFormat;
            $subFormat = '';
        }

        $value = valr($field, $args, null);
        if ($value === null && !in_array($format, ['url', 'exurl', 'number', 'plural'])) {
            $result = '';
        } else {
            switch (strtolower($format)) {
                case 'date':
                    switch ($subFormat) {
                        case 'short':
                            $result = Gdn_Format::date($value, '%d/%m/%Y');
                            break;
                        case 'medium':
                            $result = Gdn_Format::date($value, '%e %b %Y');
                            break;
                        case 'long':
                            $result = Gdn_Format::date($value, '%e %B %Y');
                            break;
                        default:
                            $result = Gdn_Format::date($value);
                            break;
                    }
                    break;
                case 'html':
                case 'htmlspecialchars':
                    $result = htmlspecialchars($value);
                    break;
                case 'number':
                    if (!is_numeric($value)) {
                        $result = $value;
                    } else {
                        switch ($subFormat) {
                            case 'currency':
                                $result = '$'.number_format($value, is_numeric($formatArgs) ? $formatArgs : 2);
                                break;
                            case 'integer':
                                $result = (string)round($value);
                                if (is_numeric($formatArgs) && strlen($result) < $formatArgs) {
                                    $result = str_repeat('0', $formatArgs - strlen($result)).$result;
                                }
                                break;
                            case 'percent':
                                $result = round($value * 100, is_numeric($formatArgs) ? $formatArgs : 0);
                                break;
                            default:
                                $result = number_format($value, is_numeric($formatArgs) ? $formatArgs : 0);
                                break;
                        }
                    }
                    break;
                case 'plural':
                    if (is_array($value)) {
                        $value = count($value);
                    } elseif (stringEndsWith($field, 'UserID', true)) {
                        $value = 1;
                    }

                    if (!is_numeric($value)) {
                        $result = $value;
                    } else {
                        if (!$subFormat) {
                            $subFormat = rtrim("%s $field", 's');
                        }
                        if (!$formatArgs) {
                            $formatArgs = $subFormat.'s';
                        }

                        $result = plural($value, $subFormat, $formatArgs);
                    }
                    break;
                case 'rawurlencode':
                    $result = rawurlencode($value);
                    break;
                case 'text':
                    $result = Gdn_Format::text($value, false);
                    break;
                case 'time':
                    $result = Gdn_Format::date($value, '%l:%M%p');
                    break;
                case 'url':
                    if (strpos($field, '/') !== false) {
                        $value = $field;
                    }
                    $result = url($value, $subFormat == 'domain');
                    break;
                case 'exurl':
                    if (strpos($field, '/') !== false) {
                        $value = $field;
                    }
                    $result = externalUrl($value);
                    break;
                case 'urlencode':
                    $result = urlencode($value);
                    break;
                case 'gender':
                    // Format in the form of FieldName,gender,male,female,unknown[,plural]
                    if (is_array($value) && count($value) == 1) {
                        $value = array_shift($value);
                    }

                    $gender = 'u';

                    if (!is_array($value)) {
                        $user = Gdn::userModel()->getID($value);
                        if ($user) {
                            $gender = $user->Gender;
                        }
                    } else {
                        $gender = 'p';
                    }

                    switch ($gender) {
                        case 'm':
                            $result = $subFormat;
                            break;
                        case 'f':
                            $result = $formatArgs;
                            break;
                        case 'p':
                            $result = val(5, $parts, val(4, $parts));
                            break;
                        case 'u':
                        default:
                            $result = val(4, $parts);
                    }

                    break;
                case 'user':
                case 'you':
                case 'his':
                case 'her':
                case 'your':
//                    $Result = print_r($Value, true);
                    $argsBak = $args;
                    if (is_array($value) && count($value) == 1) {
                        $value = array_shift($value);
                    }

                    if (is_array($value)) {
                        if (isset($value['UserID'])) {
                            $user = $value;
                            $user['Name'] = formatUsername($user, $format, $contextUserID);

                            $result = userAnchor($user);
                        } else {
                            $max = c('Garden.FormatUsername.Max', 5);
                            // See if there is another count.
                            $extraCount = valr($field.'_Count', $args, 0);

                            $count = count($value);
                            $result = '';
                            for ($i = 0; $i < $count; $i++) {
                                if ($i >= $max && $count > $max + 1) {
                                    $others = $count - $i + $extraCount;
                                    $result .= ' '.t('sep and', 'and').' '
                                        .plural($others, '%s other', '%s others');
                                    break;
                                }

                                $iD = $value[$i];
                                if (is_array($iD)) {
                                    continue;
                                }

                                if ($i == $count - 1) {
                                    $result .= ' '.t('sep and', 'and').' ';
                                } elseif ($i > 0) {
                                    $result .= ', ';
                                }

                                $special = [-1 => t('everyone'), -2 => t('moderators'), -3 => t('administrators')];
                                if (isset($special[$iD])) {
                                    $result .= $special[$iD];
                                } else {
                                    $user = Gdn::userModel()->getID($iD);
                                    if ($user) {
                                        $user->Name = formatUsername($user, $format, $contextUserID);
                                        $result .= userAnchor($user);
                                    }
                                }
                            }
                        }
                    } else {
                        $user = Gdn::userModel()->getID($value);
                        if ($user) {
                            // Store this name separately because of special 'You' case.
                            $name = formatUsername($user, $format, $contextUserID);
                            // Manually build instead of using userAnchor() because of special 'You' case.
                            $result = anchor(htmlspecialchars($name), userUrl($user));
                        } else {
                            $result = '';
                        }
                    }

                    $args = $argsBak;
                    break;
                default:
                    $result = $value;
                    break;
            }
        }
        return $result;
    }
}

if (!function_exists('forceBool')) {
    /**
     * Force a mixed value to a boolean.
     *
     * @param mixed $value The value to force.
     * @param bool $defaultValue The default value to return if conversion to a boolean is not possible.
     * @param mixed $true The value to return for true.
     * @param mixed $false The value to return for false.
     * @return mixed Returns {@link $true} if the value is true or {@link $false} otherwiese.
     */
    function forceBool($value, $defaultValue = false, $true = true, $false = false) {
        if (is_bool($value)) {
            return $value ? $true : $false;
        } elseif (is_numeric($value)) {
            return $value == 0 ? $false : $true;
        } elseif (is_string($value)) {
            return strtolower($value) == 'true' ? $true : $false;
        } else {
            return $defaultValue;
        }
    }
}

if (!function_exists('getAppCookie')) {
    /**
     * Get a cookie with the application prefix.
     *
     * @param string $name The name of the cookie to get.
     * @param mixed $default The default to return if the cookie is not found.
     * @return string Returns the cookie value or {@link $default}.
     */
    function getAppCookie($name, $default = null) {
        $px = c('Garden.Cookie.Name');
        return getValue("$px-$name", $_COOKIE, $default);
    }
}

if (!function_exists('getConnectionString')) {
    /**
     * Construct a PDO connection string.
     *
     * @param string $databaseName The name of the database to connect to.
     * @param string $hostName The database host.
     * @param string $serverType The type of database server.
     * @return string Returns the PDO connection string.
     */
    function getConnectionString($databaseName, $hostName = 'localhost', $serverType = 'mysql') {
        $hostName = explode(':', $hostName);
        $port = count($hostName) == 2 ? $hostName[1] : '';
        $hostName = $hostName[0];
        $string = $serverType.':host='.$hostName;
        if ($port != '') {
            $string .= ';port='.$port;
        }
        $string .= ';dbname='.$databaseName;

        return $string;
    }
}

if (!function_exists('getMentions')) {
    /**
     * Get all usernames mentioned in an HTML string.
     *
     * Optionally skips the contents of an anchor tag <a> or a code tag <code>.
     *
     * @param string $html The html-formatted string to parse.
     * @param bool $skipAnchors Whether to call the callback function on anchor tag content.
     * @param bool $skipCode  Whether to call the callback function on code tag content.
     * @return array An array of usernames that are mentioned.
     */
    function getMentions($html, $skipAnchors = true, $skipCode = true) {
        // Check for a custom mentions formatter and use it.
        $formatter = Gdn::factory('mentionsFormatter');
        if (is_object($formatter)) {
            return $formatter->getMentions($html);
        }

        $regex = "`([<>])`i";
        $parts = preg_split($regex, $html, null, PREG_SPLIT_DELIM_CAPTURE);

        $inTag = false;
        $inAnchor = false;
        $inCode = false;
        $tagName = false;
        $mentions = [];

        // Only format mentions that are not parts of html tags and are not already enclosed
        // within anchor tags or code tags.
        foreach ($parts as $i => $str) {
            if ($str) {
                if ($str == '<') {
                    $inTag = true;
                }
                if ($str == '>') {
                    $inTag = false;
                }
                if ($inTag) {
                    if ($str[0] == '/') {
                        $tagName = preg_split('`\s`', substr($str, 1), 2);
                        $tagName = $tagName[0];

                        if ($tagName == 'a') {
                            $inAnchor = false;
                        }
                        if ($tagName == 'code') {
                            $inCode = false;
                        }
                    } else {
                        $tagName = preg_split('`\s`', trim($str), 2);
                        $tagName = $tagName[0];

                        if ($tagName == 'a') {
                            $inAnchor = true;
                        }
                        if ($tagName == 'code') {
                            $inCode = true;
                        }
                    }
                } elseif (!($inAnchor && $skipAnchors) && !($inCode && $skipCode)) {
                    // Passes all tests, let's extract all the mentions from this segment.
                    $mentions = array_merge($mentions, getAllMentions($str));
                }
            }
        }
        return array_unique($mentions);
    }
}

if (!function_exists('getAllMentions')) {
    /**
     * Parses a string for all mentioned usernames and returns an array of these usernames.
     *
     * @param string $str The string to parse.
     * @return array The mentioned usernames.
     */
    function getAllMentions($str) {
        $parts = preg_split('`\B@`', $str);
        $mentions = [];
        if (count($parts) == 1) {
            return [];
        }
        foreach ($parts as $i => $part) {
            if (empty($part) || $i == 0) {
                continue;
            }
            // Grab the mention.
            $mention = false;
            if ($part[0] == '"') {
                // Quoted mention.
                $pos = strpos($part, '"', 1);

                if ($pos === false) {
                    $part = substr($part, 1);
                } else {
                    $mention = substr($part, 1, $pos - 1);
                }
            }
            if (!$mention && !empty($part)) {
                // Unquoted mention.
                $parts2 = preg_split('`([\s.,;?!:])`', $part, 2, PREG_SPLIT_DELIM_CAPTURE);
                $mention = $parts2[0];
            }
            $mentions[] = $mention;
        }

        return $mentions;
    }
}

if (!function_exists('getRecord')) {
    /**
     * Get a record from the database.
     *
     * @param string $recordType The type of record to get. This is usually the un-prefixed table name of the record.
     * @param int $id The ID of the record.
     * @param bool $throw Whether or not to throw an exception if the record isn't found.
     * @return array|false Returns an array representation of the record or false if the record isn't found.
     * @throws Exception Throws an exception with a 404 code if the record isn't found and {@link $throw} is true.
     * @throws Gdn_UserException Throws an exception when {@link $recordType} is unknown.
     */
    function getRecord($recordType, $id, $throw = false) {
        $recordType = strtolower($recordType);

        /** @var \Garden\Container\Container $container */
        $container = Gdn::getContainer();

        switch ($recordType) {
            case 'discussion':
                /** @var DiscussionModel $discussionModel */
                $discussionModel = $container->get(DiscussionModel::class);
                $row = $discussionModel->getID($id, DATASET_TYPE_ARRAY);
                if (!$discussionModel->canView($row)) {
                    throw permissionException();
                }
                $row['ShareUrl'] = $row['Url'] = discussionUrl($row);
                break;
            case 'comment':
                /** @var CommentModel $commentModel */
                $commentModel = $container->get(CommentModel::class);
                $row = $commentModel->getID($id, DATASET_TYPE_ARRAY);
                if ($row) {
                    /** @var DiscussionModel $discussionModel */
                    $discussionModel = $container->get(DiscussionModel::class);
                    $row['Url'] = url("/discussion/comment/{$id}#Comment_{$id}", true);

                    $discussion = $discussionModel->getID($row['DiscussionID'], DATASET_TYPE_ARRAY);
                    if ($discussion) {
                        if (!$discussionModel->canView($discussion)) {
                            throw permissionException();
                        }
                        $discussion->Url = discussionUrl($discussion);
                        $row['ShareUrl'] = $row['Url'];
                        $row['Name'] = $discussion['Name'];
                        $row['Discussion'] = $discussion;
                    }
                }
                break;
            case 'activity':
                /** @var ActivityModel $activityModel */
                $activityModel = $container->get(ActivityModel::class);
                $row = $activityModel->getID($id, DATASET_TYPE_ARRAY);
                if (!$activityModel->canView($row)) {
                    throw permissionException();
                }
                if ($row) {
                    $row['Name'] = formatString($row['HeadlineFormat'], $row);
                    $row['Body'] = $row['Story'];
                }
                break;
            default:
                throw new Gdn_UserException('Unknown record type requested.');
        }

        if (!$row && $throw) {
            throw notFoundException($recordType);
        }

        return $row;
    }
}

if (!function_exists('getValueR')) {
    /**
     * Return the value from an associative array or an object.
     *
     * This function differs from getValue() in that $Key can be a string consisting of dot notation that will be used
     * to recursively traverse the collection.
     *
     * @param string $key The key or property name of the value.
     * @param mixed $collection The array or object to search.
     * @param mixed $default The value to return if the key does not exist.
     * @return mixed The value from the array or object.
     */
    function getValueR($key, $collection, $default = false) {
        $path = explode('.', $key);

        $value = $collection;
        for ($i = 0; $i < count($path); ++$i) {
            $subKey = $path[$i];

            if (is_array($value) && isset($value[$subKey])) {
                $value = $value[$subKey];
            } elseif (is_object($value) && isset($value->$subKey)) {
                $value = $value->$subKey;
            } else {
                return $default;
            }
        }
        return $value;
    }
}

if (!function_exists('htmlEntityDecode')) {
    /**
     * Decode all of the entities out of an HTML string.
     *
     * @param string $string The string to decode.
     * @param int $quote_style One of the `ENT_*` constants.
     * @param string $charset The character set of the string.
     * @return string Returns {@link $string} with HTML entities decoded.
     * @since 2.1
     */
    function htmlEntityDecode($string, $quote_style = ENT_QUOTES, $charset = "utf-8") {
        $string = html_entity_decode($string, $quote_style, $charset);
        $string = str_ireplace('&apos;', "'", $string);
        $string = preg_replace_callback('/&#x([0-9a-fA-F]+);/i', "chr_utf8_callback", $string);
        $string = preg_replace_callback('/&#([0-9]+);/', function($matches) { return chr_utf8($matches[1]); }, $string);
        return $string;
    }

    /**
     * A callback helper for {@link htmlEntityDecode()}.
     *
     * @param array[string] $matches An array of matches from {@link preg_replace_callback()}.
     * @return string Returns the match passed through {@link chr_utf8()}.
     * @access private
     */
    function chr_utf8_callback($matches) {
        return chr_utf8(hexdec($matches[1]));
    }

    /**
     * Multi-byte chr(): Will turn a numeric argument into a UTF-8 string.
     *
     * @param mixed $num A UTF-8 character code.
     * @return string Returns a UTF-8 string representation of {@link $num}.
     */
    function chr_utf8($num) {
        if ($num < 128) {
            return chr($num);
        }
        if ($num < 2048) {
            return chr(($num >> 6) + 192).chr(($num & 63) + 128);
        }
        if ($num < 65536) {
            return chr(($num >> 12) + 224).chr((($num >> 6) & 63) + 128).chr(($num & 63) + 128);
        }
        if ($num < 2097152) {
            return
                chr(($num >> 18) + 240).
                chr((($num >> 12) & 63) + 128).
                chr((($num >> 6) & 63) + 128).
                chr(($num & 63) + 128);
        }
        return '';
    }
}

if (!function_exists('htmlEsc')) {
    /**
     * Alias htmlspecialchars() for code brevity.
     *
     * @param string $string
     * @param int $flags See: htmlspecialchars().
     * @return string|array Escaped string or array.
     */
    function htmlEsc($string, $flags = ENT_COMPAT) {
        return htmlspecialchars($string, $flags, 'UTF-8');
    }
}

if (!function_exists('implodeAssoc')) {
    /**
     * A version of implode() that operates on array keys and values.
     *
     * @param string $keyGlue The glue between keys and values.
     * @param string $elementGlue The glue between array elements.
     * @param array $array The array to implode.
     * @return string The imploded array.
     */
    function implodeAssoc($keyGlue, $elementGlue, $array) {
        $result = '';

        foreach ($array as $key => $value) {
            if (strlen($result) > 0) {
                $result .= $elementGlue;
            }

            $result .= $key.$keyGlue.$value;
        }
        return $result;
    }
}

if (!function_exists('inArrayI')) {
    /**
     * Case-insensitive version of php's native in_array function.
     *
     * @param mixed $needle The array value to search for.
     * @param array $haystack The array to search.
     * @return bool Returns true if the value is found in the array.
     */
    function inArrayI($needle, $haystack) {
        $needle = strtolower($needle);
        foreach ($haystack as $item) {
            if (strtolower($item) == $needle) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('inMaintenanceMode')) {
    /**
     * Determine if the site is in maintenance mode.
     *
     * @return bool
     */
    function inMaintenanceMode() {
        $updateMode = c('Garden.UpdateMode');

        return (bool)$updateMode;
    }
}

if (!function_exists('inSubArray')) {
    /**
     * Loop through {@link $haystack} looking for subarrays that contain {@link $needle}.
     *
     * @param mixed $needle The value to search for.
     * @param array $haystack The array to search.
     * @return bool Returns true if the value is found in the array.
     */
    function inSubArray($needle, $haystack) {
        foreach ($haystack as $key => $val) {
            if (is_array($val) && in_array($needle, $val)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('ipDecode')) {
    /**
     * Decode a packed IP address to its human-readable form.
     *
     * @param string $packedIP A string representing a packed IP address.
     * @return string|null A human-readable representation of the provided IP address.
     */
    function ipDecode($packedIP) {
        if (filter_var($packedIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6)) {
            // If it's already a valid IP address, don't bother unpacking it.
            $result = $packedIP;
        } elseif ($iP = @inet_ntop($packedIP)) {
            $result = $iP;
        } else {
            $result = null;
        }

        return $result;
    }
}

if (!function_exists('ipEncode')) {
    /**
     * Encode a human-readable IP address as a packed string.
     *
     * @param string $iP A human-readable IP address.
     * @return null|string A packed string representing a packed IP address.
     */
    function ipEncode($iP) {
        $result = null;

        if ($packedIP = @inet_pton($iP)) {
            $result = $packedIP;
        }

        return $result;
    }
}

if (!function_exists('isMobile')) {
    /**
     * Determine whether or not the site is in mobile mode.
     *
     * @param mixed $value Sets a new value for mobile. Pass one of the following:
     * - true: Force mobile.
     * - false: Force desktop.
     * - null: Reset and use the system determined mobile setting.
     * - not specified: Use the current setting or use the system determined mobile setting.
     * @return bool
     */
    function isMobile($value = '') {
        if ($value === true || $value === false) {
            $type = $value ? 'mobile' : 'desktop';
            userAgentType($type);
        } elseif ($value === null) {
            userAgentType(false);
        }

        $type = userAgentType();
        // Check the config for an override. (ex. Consider tablets mobile)
        $type = c('Garden.Devices.'.ucfirst($type), $type);

        switch ($type) {
            case 'app':
            case 'mobile':
                $isMobile = true;
                break;
            default:
                $isMobile = false;
                break;
        }

        return $isMobile;
    }
}

if (!function_exists('isSearchEngine')) {
    /**
     * Determines whether or not the current request is being made by a search engine.
     *
     * @return bool Returns true if the current request is a search engine or false otherwise.
     */
    function isSearchEngine() {
        $engines = [
            'googlebot',
            'slurp',
            'search.msn.com',
            'nutch',
            'simpy',
            'bot',
            'aspseek',
            'crawler',
            'msnbot',
            'libwww-perl',
            'fast',
            'baidu',
        ];
        $httpUserAgent = strtolower(val('HTTP_USER_AGENT', $_SERVER, ''));
        if ($httpUserAgent != '') {
            foreach ($engines as $engine) {
                if (strpos($httpUserAgent, $engine) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('isTimestamp')) {
    /**
     * Check to make sure a value is a valid timestamp.
     *
     * @param int $stamp The timestamp to check.
     * @return bool Returns true if {@link $stamp} is valid or false otherwise.
     */
    function isTimestamp($stamp) {
        return checkdate(
            @date("m", $stamp),
            @date("d", $stamp),
            @date("Y", $stamp)
        );
    }
}

if (!function_exists('isUrl')) {
    /**
     * Determine whether or not a string is a url in the form http://, https://, or //.
     *
     * @param string $str The string to check.
     * @return bool
     * @since 2.1
     */
    function isUrl($str) {
        if (!$str) {
            return false;
        }
        if (substr($str, 0, 2) == '//') {
            return true;
        }
        if (preg_match('`^https?://`i', $str)) {
            return true;
        }
        return false;
    }
}

if (!function_exists('isWritable')) {
    /**
     * Determine whether or not a path is writable.
     *
     * PHP's native is_writable() function fails to correctly determine write
     * capabilities on some systems (Windows), and in our tests it returned true
     * despite not being able to create subfolders within the folder being
     * checked. Our version truly verifies permissions by performing file-write
     * tests.
     *
     * @param string $path The past to test.
     * @return bool Returns true if {@link $path} is writable or false otherwise.
     */
    function isWritable($path) {
        if ($path{strlen($path) - 1} == DS) {
            // Recursively return a temporary file path
            return isWritable($path.uniqid(mt_rand()).'.tmp');
        } elseif (is_dir($path)) {
            return isWritable($path.'/'.uniqid(mt_rand()).'.tmp');
        }
        // Check tmp file for read/write capabilities
        $keepPath = file_exists($path);
        $file = @fopen($path, 'a');
        if ($file === false) {
            return false;
        }

        fclose($file);

        if (!$keepPath) {
            safeUnlink($path);
        }

        return true;
    }
}

if (!function_exists('markString')) {
    /**
     * Wrap occurrences of {@link $needle} in {@link $haystack} with `<mark>` tags.
     *
     * This method explodes {@link $needle} on spaces and returns {@link $haystack} with replacements.
     *
     * @param string|array $needle The strings to search for in {@link $haystack}.
     * @param string $haystack The string to search for replacements.
     * @return string Returns a marked version of {@link $haystack}.
     */
    function markString($needle, $haystack) {
        if (!$needle) {
            return $haystack;
        }
        if (!is_array($needle)) {
            $needle = explode(' ', $needle);
        }

        foreach ($needle as $n) {
            if (strlen($n) <= 2 && preg_match('`^\w+$`', $n)) {
                $word = '\b';
            } else {
                $word = '';
            }

            $haystack = preg_replace(
                '#(?!<.*?)('.$word.preg_quote($n, '#').$word.')(?![^<>]*?>)#i',
                '<mark>\1</mark>',
                $haystack
            );
        }
        return $haystack;
    }
}

if (!function_exists('joinRecords')) {
    /**
     * Join external records to an array.
     *
     * @param array &$data The data to join.
     * In order to join records each row must have the a RecordType and RecordID column.
     * @param string $column The name of the column to put the record in.
     * If this is blank then the record will be merged into the row.
     * @param bool $unset Whether or not to unset rows that don't have a record.
     * @since 2.3
     */
    function joinRecords(&$data, $column = '', $unset = false, $checkCategoryPermission = true) {
        $iDs = [];
        $allowedCats = DiscussionModel::categoryPermissions();

        if ($checkCategoryPermission && $allowedCats === false) {
            // This user does not have permission to view anything.
            $data = [];
            return;
        }

        // Gather all of the ids to fetch.
        foreach ($data as &$row) {
            if (!$row['RecordType']) {
                continue;
            }

            $recordType = ucfirst(stringEndsWith($row['RecordType'], '-Total', true, true));
            $row['RecordType'] = $recordType;
            $iD = $row['RecordID'];
            $iDs[$recordType][$iD] = $iD;
        }

        // Fetch all of the data in turn.
        $joinData = [];
        foreach ($iDs as $recordType => $recordIDs) {
            if ($recordType == 'Comment') {
                Gdn::sql()->select('d.Name, d.CategoryID')->join('Discussion d', 'd.DiscussionID = r.DiscussionID');
            }

            $rows = Gdn::sql()
                ->select('r.*')
                ->whereIn($recordType.'ID', array_values($recordIDs))
                ->get($recordType.' r')
                ->resultArray();

            $joinData[$recordType] = Gdn_DataSet::index($rows, [$recordType.'ID']);
        }

        // Join the rows.
        $unsets = [];
        foreach ($data as $index => &$row) {
            $recordType = $row['RecordType'];
            $iD = $row['RecordID'];

            if (!isset($joinData[$recordType][$iD])) {
                if ($unset) {
                    $unsets[] = $index;
                }
                continue; // orphaned?
            }

            $record = $joinData[$recordType][$iD];

            if ($checkCategoryPermission && $allowedCats !== true) {
                // Check to see if the user has permission to view this record.
                $categoryID = getValue('CategoryID', $record, -1);
                if (!in_array($categoryID, $allowedCats)) {
                    if ($unset) {
                        $unsets[] = $index;
                    } else {
                        $row['RecordType'] = null;
                        $row['RecordID'] = null;
                        unset($row['RecordBody'], $row['RecordFormat']);
                    }
                    continue;
                }
            }

            switch ($recordType) {
                case 'Discussion':
                    $url = discussionUrl($record, '', '/').'#latest';
                    break;
                case 'Comment':
                    $url = commentUrl($record, '/');
                    $record['Name'] = sprintf(t('Re: %s'), $record['Name']);
                    break;
                default:
                    $url = '';
            }
            $record['Url'] = $url;

            if ($column) {
                $row[$column] = $record;
            } else {
                $row = array_merge($row, $record);
            }
        }

        foreach ($unsets as $index) {
            unset($data[$index]);
        }

        // Join the users.
        Gdn::userModel()->joinUsers($data, ['InsertUserID']);

        if (!empty($unsets)) {
            $data = array_values($data);
        }
    }

}

if (!function_exists('jsonEncodeChecked')) {
    /**
     * Encode a value as JSON or throw an exception on error.
     *
     * @param mixed $value
     * @param int|null $options
     * @return string
     * @throws Exception
     */
    function jsonEncodeChecked($value, $options = null) {
         if ($options === null) {
            $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        }
        $encoded = json_encode($value, $options);
        $errorMessage = null;
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                // Do absolutely nothing since all went well!
                break;
            case JSON_ERROR_UTF8:
                $errorMessage = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            case JSON_ERROR_RECURSION:
                $errorMessage = 'One or more recursive references in the value to be encoded.';
                break;
            case JSON_ERROR_INF_OR_NAN:
                $errorMessage = 'One or more NAN or INF values in the value to be encoded';
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $errorMessage = 'A value of a type that cannot be encoded was given.';
                break;
            default:
                $errorMessage = 'An unknown error has occurred.';
        }
        if ($errorMessage !== null) {
            throw new Exception("JSON encoding error: {$errorMessage}", 500);
        }
        return $encoded;
    }
}

if (!function_exists('jsonFilter')) {
    /**
     * Prepare data for json_encode.
     *
     * @param mixed $value
     */
    function jsonFilter(&$value) {
        $fn = function (&$value, $key = '', $parentKey = '') use (&$fn) {
            if (is_array($value)) {
                array_walk($value, function(&$childValue, $childKey) use ($fn, $key) {
                    $fn($childValue, $childKey, $key);
                });
            } elseif ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTime::RFC3339);
            } elseif (is_string($value)) {
                // Only attempt to unpack as an IP address if this field or its parent matches the IP field naming scheme.
                $isIPField = (stringEndsWith($key, 'IPAddress', true) || stringEndsWith($parentKey, 'IPAddresses', true));
                if ($isIPField && ($ip = ipDecode($value)) !== null) {
                    $value = $ip;
                }
            }
        };

        if (is_array($value)) {
            array_walk($value, $fn);
        } else {
            $fn($value);
        }
    }
}

if (!function_exists('now')) {
    /**
     * Get the current time in seconds with a millisecond fraction.
     *
     * @return float Returns the current time.
     */
    function now() {
        return microtime(true);
    }
}

if (!function_exists('offsetLimit')) {
    /**
     * Convert various forms of querystring limit/offset, page, limit/range to database limit/offset.
     *
     * @param string $offsetOrPage The page query in one of the following formats:
     *  - p<x>: Get page x.
     *  - <x>-<y>: This is a range viewing records x through y.
     *  - <x>lim<n>: This is a limit/offset pair.
     *  - <x>: This is a limit where offset is given in the next parameter.
     * @param int $limitOrPageSize The page size or limit.
     * @param bool $throw Whether or not to throw an error if the {@link $offsetOrPage} is too high.
     * @return array Returns an array in the form: `[$offset, $limit]`.
     * @throws Exception Throws a 404 exception if the {@link $offsetOrPage} is too high and {@link $throw} is true.
     */
    function offsetLimit($offsetOrPage = '', $limitOrPageSize = '', $throw = false) {
        $limitOrPageSize = is_numeric($limitOrPageSize) ? (int)$limitOrPageSize : 50;

        if (is_numeric($offsetOrPage)) {
            $offset = (int)$offsetOrPage;
            $limit = $limitOrPageSize;
        } elseif (preg_match('/p(\d+)/i', $offsetOrPage, $matches)) {
            $page = $matches[1];
            $offset = $limitOrPageSize * ($page - 1);
            $limit = $limitOrPageSize;
        } elseif (preg_match('/(\d+)-(\d+)/', $offsetOrPage, $matches)) {
            $offset = $matches[1] - 1;
            $limit = $matches[2] - $matches[1] + 1;
        } elseif (preg_match('/(\d+)lim(\d*)/i', $offsetOrPage, $matches)) {
            $offset = (int)$matches[1];
            $limit = (int)$matches[2];
            if (!is_numeric($limit)) {
                $limit = $limitOrPageSize;
            }
        } elseif (preg_match('/(\d+)lin(\d*)/i', $offsetOrPage, $matches)) {
            $offset = $matches[1] - 1;
            $limit = (int)$matches[2];
            if (!is_numeric($limit)) {
                $limit = $limitOrPageSize;
            }
        } elseif ($offsetOrPage && $throw) {
            // Some unrecognized page string was passed.
            throw notFoundException();
        } else {
            $offset = 0;
            $limit = $limitOrPageSize;
        }

        if ($offset < 0) {
            $offset = 0;
        }
        if ($limit < 0) {
            $limit = 50;
        }

        return [$offset, $limit];
    }
}

if (!function_exists('pageNumber')) {
    /**
     * Get the page number from a database offset and limit.
     *
     * @param int $offset The database offset, starting at zero.
     * @param int $limit The database limit, otherwise known as the page size.
     * @param bool|string $urlParam Whether or not the result should be formatted as a url parameter, suitable for OffsetLimit.
     *  - bool: true means yes, false means no.
     *  - string: The prefix for the page number.
     * @param bool $first Whether or not to return the page number if it is the first page.
     */
    function pageNumber($offset, $limit, $urlParam = false, $first = true) {
        $result = floor($offset / $limit) + 1;

        if ($urlParam !== false && !$first && $result == 1) {
            $result = '';
        } elseif ($urlParam === true) {
            $result = 'p'.$result;
        } elseif (is_string($urlParam)) {
            $result = $urlParam.$result;
        }

        return $result;
    }
}

if (!function_exists('recordType')) {
    /**
     * Return the record type and id of a row.
     *
     * @param array|object $row The record we are looking at.
     * @return array An array with the following items
     *  - 0: record type
     *  - 1: record ID
     * @since 2.1
     */
    function recordType($row) {
        if ($recordType = val('RecordType', $row)) {
            return [$recordType, val('RecordID', $row)];
        } elseif ($commentID = val('CommentID', $row)) {
            return ['Comment', $commentID];
        } elseif ($discussionID = val('DiscussionID', $row)) {
            return ['Discussion', $discussionID];
        } elseif ($activityID = val('ActivityID', $row)) {
            return ['Activity', $activityID];
        } else {
            return [null, null];
        }
    }
}

if (!function_exists('touchConfig')) {
    /**
     * Make sure the config has a setting.
     *
     * This function is useful to call in the setup/structure of plugins to make sure they have some default config set.
     *
     * @param string|array $name The name of the config key or an array of config key value pairs.
     * @param mixed $default The default value to set in the config.
     */
    function touchConfig($name, $default = null) {
        if (!is_array($name)) {
            $name = [$name => $default];
        }

        $save = [];
        foreach ($name as $key => $value) {
            if (!c($key)) {
                $save[$key] = $value;
            }
        }

        if (!empty($save)) {
            saveToConfig($save);
        }
    }
}

if (!function_exists('write_ini_string')) {
    /**
     * Formats an array in INI format.
     *
     * @param array $data The data to format.
     * @return string Returns the {@link $data} array in INI format.
     */
    function write_ini_string($data) {
        $flat = [];
        foreach ($data as $topic => $settings) {
            if (is_array($settings)) {
                $flat[] = "[{$topic}]";
                foreach ($settings as $settingsKey => $settingsVal) {
                    $flat[] = "{$settingsKey} = ".(is_numeric($settingsVal) ? $settingsVal : '"'.$settingsVal.'"');
                }
                $flat[] = "";
            } else {
                $flat[] = "{$topic} = ".(is_numeric($settings) ? $settings : '"'.$settings.'"');
            }
        }
        return implode("\n", $flat);
    }
}

if (!function_exists('write_ini_file')) {
    /**
     * Write an array to an INI file.
     *
     * @param string $file The path of the file to write to.
     * @param array $data The data to write.
     * @throws Exception Throws an exception if there was an error writing the file.
     */
    function write_ini_file($file, $data) {
        $string = write_ini_string($data);
        Gdn_FileSystem::saveFile($file, $string);
    }
}

if (!function_exists('signInPopup')) {
    /**
     * Returns a boolean value indicating if sign in windows should be "popped" into modal in-page popups.
     *
     * @return bool Returns true if signin popups are used.
     */
    function signInPopup() {
        return c('Garden.SignIn.Popup');
    }
}

if (!function_exists('buildUrl')) {
    /**
     * Complementary to {@link parseUrl()}, this function puts the pieces back together and returns a valid url.
     *
     * @param array $parts The ParseUrl array to build.
     */
    function buildUrl($parts) {
        // Full format: http://user:pass@hostname:port/path?querystring#fragment
        $return = $parts['scheme'].'://';
        if ($parts['user'] != '' || $parts['pass'] != '') {
            $return .= $parts['user'].':'.$parts['pass'].'@';
        }

        $return .= $parts['host'];
        // Custom port?
        if ($parts['port'] == '443' && $parts['scheme'] == 'https') {
        } elseif ($parts['port'] == '80' && $parts['scheme'] == 'http') {
        } elseif ($parts['port'] != '') {
            $return .= ':'.$parts['port'];
        }

        if ($parts['path'] != '') {
            if (substr($parts['path'], 0, 1) != '/') {
                $return .= '/';
            }
            $return .= $parts['path'];
        }
        if ($parts['query'] != '') {
            $return .= '?'.$parts['query'];
        }

        if ($parts['fragment'] != '') {
            $return .= '#'.$parts['fragment'];
        }

        return $return;
    }
}

if (!function_exists('prefixString')) {
    /**
     * Takes a string, and prefixes it with $prefix unless it is already prefixed that way.
     *
     * @param string $prefix The prefix to use.
     * @param string $string The string to be prefixed.
     */
    function prefixString($prefix, $string) {
        if (substr($string, 0, strlen($prefix)) != $prefix) {
            $string = $prefix.$string;
        }
        return $string;
    }
}

if (!function_exists('proxyHead')) {
    /**
     * Make a cURL HEAD request to a URL.
     *
     * @param string $url The URL to request.
     * @param array|null $headers An optional array of additional headers to send with the request.
     * @param int|false $timeout The request timeout in seconds.
     * @param bool $followRedirects Whether or not to follow redirects.
     * @return array Returns an array of response headers.
     * @throws Exception Throws an exception when there is an unrecoverable error making the request.
     */
    function proxyHead($url, $headers = null, $timeout = false, $followRedirects = false) {
        deprecated('proxyHead()', 'class ProxyRequest');

        if (is_null($headers)) {
            $headers = [];
        }

        $originalHeaders = $headers;
        $originalTimeout = $timeout;
        if (!$timeout) {
            $timeout = c('Garden.SocketTimeout', 1.0);
        }

        $urlParts = parse_url($url);
        $scheme = val('scheme', $urlParts, 'http');
        $host = val('host', $urlParts, '');
        $port = val('port', $urlParts, '80');
        $path = val('path', $urlParts, '');
        $query = val('query', $urlParts, '');

        // Get the cookie.
        $cookie = '';
        $encodeCookies = c('Garden.Cookie.Urlencode', true);

        foreach ($_COOKIE as $key => $value) {
            if (strncasecmp($key, 'XDEBUG', 6) == 0) {
                continue;
            }

            if (strlen($cookie) > 0) {
                $cookie .= '; ';
            }

            $eValue = ($encodeCookies) ? urlencode($value) : $value;
            $cookie .= "{$key}={$eValue}";
        }
        $cookie = ['Cookie' => $cookie];

        $response = '';
        if (function_exists('curl_init')) {
            //$Url = $Scheme.'://'.$Host.$Path;
            $handler = curl_init();
            curl_setopt($handler, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($handler, CURLOPT_URL, $url);
            curl_setopt($handler, CURLOPT_PORT, $port);
            curl_setopt($handler, CURLOPT_HEADER, 1);
            curl_setopt($handler, CURLOPT_NOBODY, 1);
            curl_setopt($handler, CURLOPT_USERAGENT, val('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'));
            curl_setopt($handler, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);

            if (strlen($cookie['Cookie'])) {
                curl_setopt($handler, CURLOPT_COOKIE, $cookie['Cookie']);
            }

            $response = curl_exec($handler);
            if ($response == false) {
                $response = curl_error($handler);
            }

            curl_close($handler);
        } elseif (function_exists('fsockopen')) {
            $referer = Gdn::request()->webRoot();

            // Make the request
            $pointer = @fsockopen($host, $port, $errorNumber, $error, $timeout);
            if (!$pointer) {
                throw new Exception(
                    sprintf(
                        t('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s'),
                        $url,
                        $errorNumber,
                        $error
                    )
                );
            }

            $request = "HEAD $path?$query HTTP/1.1\r\n";

            $hostHeader = $host.($port != 80) ? ":{$port}" : '';
            $header = [
                'Host' => $hostHeader,
                'User-Agent' => val('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'),
                'Accept' => '*/*',
                'Accept-Charset' => 'utf-8',
                'Referer' => $referer,
                'Connection' => 'close'
            ];

            if (strlen($cookie['Cookie'])) {
                $header = array_merge($header, $cookie);
            }

            $header = array_merge($header, $headers);

            $headerString = "";
            foreach ($header as $headerName => $headerValue) {
                $headerString .= "{$headerName}: {$headerValue}\r\n";
            }
            $headerString .= "\r\n";

            // Send the headers and get the response
            fputs($pointer, $request);
            fputs($pointer, $headerString);
            while ($line = fread($pointer, 4096)) {
                $response .= $line;
            }
            @fclose($pointer);
            $response = trim($response);

        } else {
            throw new Exception(t('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.'));
        }

        $responseLines = explode("\n", trim($response));
        $status = array_shift($responseLines);
        $response = [];
        $response['HTTP'] = trim($status);

        /* get the numeric status code.
       * - trim off excess edge whitespace,
       * - split on spaces,
       * - get the 2nd element (as a single element array),
       * - pop the first (only) element off it...
       * - return that.
       */
        $response['StatusCode'] = array_pop(array_slice(explode(' ', trim($status)), 1, 1));
        foreach ($responseLines as $line) {
            $line = explode(':', trim($line));
            $key = trim(array_shift($line));
            $value = trim(implode(':', $line));
            $response[$key] = $value;
        }

        if ($followRedirects) {
            $code = getValue('StatusCode', $response, 200);
            if (in_array($code, [301, 302])) {
                if (array_key_exists('Location', $response)) {
                    $location = getValue('Location', $response);
                    return proxyHead($location, $originalHeaders, $originalTimeout, $followRedirects);
                }
            }
        }

        return $response;
    }

}

if (!function_exists('proxyRequest')) {
    /**
     * Use curl or fsock to make a request to a remote server.
     *
     * @param string $url The full url to the page being requested (including http://).
     * @param integer $timeout How long to allow for this request.
     * Default Garden.SocketTimeout or 1, 0 to never timeout.
     * @param boolean $followRedirects Whether or not to follow 301 and 302 redirects. Defaults false.
     * @return string Returns the response body.
     */
    function proxyRequest($url, $timeout = false, $followRedirects = false) {
        deprecated('proxyRequest()', 'class ProxyRequest');

        $originalTimeout = $timeout;
        if ($timeout === false) {
            $timeout = c('Garden.SocketTimeout', 1.0);
        }

        $urlParts = parse_url($url);
        $scheme = getValue('scheme', $urlParts, 'http');
        $host = getValue('host', $urlParts, '');
        $port = getValue('port', $urlParts, $scheme == 'https' ? '443' : '80');
        $path = getValue('path', $urlParts, '');
        $query = getValue('query', $urlParts, '');
        // Get the cookie.
        $cookie = '';
        $encodeCookies = c('Garden.Cookie.Urlencode', true);

        foreach ($_COOKIE as $key => $value) {
            if (strncasecmp($key, 'XDEBUG', 6) == 0) {
                continue;
            }

            if (strlen($cookie) > 0) {
                $cookie .= '; ';
            }

            $eValue = ($encodeCookies) ? urlencode($value) : $value;
            $cookie .= "{$key}={$eValue}";
        }
        $response = '';
        if (function_exists('curl_init')) {
            //$Url = $Scheme.'://'.$Host.$Path;
            $handler = curl_init();
            curl_setopt($handler, CURLOPT_URL, $url);
            curl_setopt($handler, CURLOPT_PORT, $port);
            curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($handler, CURLOPT_HEADER, 1);
            curl_setopt($handler, CURLOPT_USERAGENT, val('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'));
            curl_setopt($handler, CURLOPT_RETURNTRANSFER, 1);

            if ($cookie != '') {
                curl_setopt($handler, CURLOPT_COOKIE, $cookie);
            }

            if ($timeout > 0) {
                curl_setopt($handler, CURLOPT_TIMEOUT, $timeout);
            }

            // TIM @ 2010-06-28: Commented this out because it was forcing all requests with parameters to be POST.
            //Same for the $Url above
            //
            //if ($Query != '') {
            //   curl_setopt($Handler, CURLOPT_POST, 1);
            //   curl_setopt($Handler, CURLOPT_POSTFIELDS, $Query);
            //}
            $response = curl_exec($handler);
            $success = true;
            if ($response == false) {
                $success = false;
                $response = '';
                throw new Exception(curl_error($handler));
            }

            curl_close($handler);
        } elseif (function_exists('fsockopen')) {
            $referer = Gdn_Url::webRoot(true);

            // Make the request
            $pointer = @fsockopen($host, $port, $errorNumber, $error, $timeout);
            if (!$pointer) {
                throw new Exception(
                    sprintf(
                        t('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s'),
                        $url,
                        $errorNumber,
                        $error
                    )
                );
            }

            stream_set_timeout($pointer, $timeout);
            if (strlen($cookie) > 0) {
                $cookie = "Cookie: $cookie\r\n";
            }

            $hostHeader = $host.(($port != 80) ? ":{$port}" : '');
            $header = "GET $path?$query HTTP/1.1\r\n"
                ."Host: {$hostHeader}\r\n"
                // If you've got basic authentication enabled for the app, you're going to need to explicitly define
                // the user/pass for this fsock call.
                // "Authorization: Basic ". base64_encode ("username:password")."\r\n" .
                ."User-Agent: ".val('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0')."\r\n"
                ."Accept: */*\r\n"
                ."Accept-Charset: utf-8;\r\n"
                ."Referer: {$referer}\r\n"
                ."Connection: close\r\n";

            if ($cookie != '') {
                $header .= $cookie;
            }

            $header .= "\r\n";

            // Send the headers and get the response
            fputs($pointer, $header);
            while ($line = fread($pointer, 4096)) {
                $response .= $line;
            }
            @fclose($pointer);
            $bytes = strlen($response);
            $response = trim($response);
            $success = true;

            $streamInfo = stream_get_meta_data($pointer);
            if (getValue('timed_out', $streamInfo, false) === true) {
                $success = false;
                $response = "Operation timed out after {$timeout} seconds with {$bytes} bytes received.";
            }
        } else {
            throw new Exception(t('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.'));
        }

        if (!$success) {
            return $response;
        }

        $responseHeaderData = trim(substr($response, 0, strpos($response, "\r\n\r\n")));
        $response = trim(substr($response, strpos($response, "\r\n\r\n") + 4));

        $responseHeaderLines = explode("\n", trim($responseHeaderData));
        $status = array_shift($responseHeaderLines);
        $responseHeaders = [];
        $responseHeaders['HTTP'] = trim($status);

        /* get the numeric status code.
       * - trim off excess edge whitespace,
       * - split on spaces,
       * - get the 2nd element (as a single element array),
       * - pop the first (only) element off it...
       * - return that.
       */
        $status = trim($status);
        $status = explode(' ', $status);
        $status = array_slice($status, 1, 1);
        $status = array_pop($status);
        $responseHeaders['StatusCode'] = $status;
        foreach ($responseHeaderLines as $line) {
            $line = explode(':', trim($line));
            $key = trim(array_shift($line));
            $value = trim(implode(':', $line));
            $responseHeaders[$key] = $value;
        }

        if ($followRedirects) {
            $code = getValue('StatusCode', $responseHeaders, 200);
            if (in_array($code, [301, 302])) {
                if (array_key_exists('Location', $responseHeaders)) {
                    $location = absoluteSource(getValue('Location', $responseHeaders), $url);
                    return proxyRequest($location, $originalTimeout, $followRedirects);
                }
            }
        }

        return $response;
    }
}

if (!function_exists('randomString')) {
    /**
     * Generate a random string of characters.
     *
     * @param int $length The length of the string to generate.
     * @param string $characters The allowed characters in the string. See {@link betterRandomString()} for character options.
     * @return string Returns a random string of characters.
     */
    function randomString($length, $characters = 'A0') {
        return betterRandomString($length, $characters);
    }
}

if (!function_exists('betterRandomString')) {
    /**
     * Generate a random string of characters with additional character options that can be cryptographically strong.
     *
     * This function attempts to use {@link openssl_random_pseudo_bytes()} to generate its randomness.
     * If that function does not exists then it just uses mt_rand().
     *
     * @param int $length The length of the string.
     * @param string $characterOptions Character sets that are allowed in the string.
     * This is a string made up of the following characters.
     *
     * - A: uppercase characters
     * - a: lowercase characters
     * - 0: digits
     * - !: basic punctuation (~!@#$^&*_+-)
     *
     * You can also pass a string of all allowed characters in the string if you pass a string of more than four characters.
     * @return string Returns the random string for the given arguments.
     */
    function betterRandomString($length, $characterOptions = 'A0') {
        $characterClasses = [
            'A' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'a' => 'abcdefghijklmnopqrstuvwxyz',
            '0' => '0123456789',
            '!' => '~!@#$^&*_+-'
        ];

        if (strlen($characterOptions) > count($characterClasses)) {
            $characters = $characterOptions;
        } else {
            $characterOptionsArray = str_split($characterOptions, 1);
            $characters = '';

            foreach ($characterOptionsArray as $char) {
                if (array_key_exists($char, $characterClasses)) {
                    $characters .= $characterClasses[$char];
                }
            }
        }

        $charLen = strlen($characters);
        $randomChars = [];
        $cryptoStrong = false;

        if (function_exists('openssl_random_pseudo_bytes')) {
            $randomChars = unpack('C*', openssl_random_pseudo_bytes($length, $cryptoStrong));
        } elseif (function_exists('mcrypt_create_iv')) {
            $randomChars = unpack('C*', mcrypt_create_iv($length));
            $cryptoStrong = true;
        } else {
            for ($i = 0; $i < $length; $i++) {
                $randomChars[] = mt_rand();
            }
        }

        $string = '';
        foreach ($randomChars as $c) {
            $offset = (int)$c % $charLen;
            $string .= substr($characters, $offset, 1);
        }

        if (!$cryptoStrong) {
            Logger::log(Logger::WARNING, 'Random number generation is not cryptographically strong.');
        }

        return $string;
    }
}

if (!function_exists('redirectTo')) {
    /**
     * Redirect to the supplied destination.
     *
     * @param string|null $destination Destination URL or path.
     *      Redirect to current URL if nothing or null is supplied.
     * @param int $statusCode HTTP status code. 302 by default.
     * @param bool $trustedOnly Non trusted destinations will be redirected to /home/leaving?Target=$destination
     */
    function redirectTo($destination = null, $statusCode = 302, $trustedOnly = true) {
        if ($destination === null) {
            $url = url('');
        } elseif ($trustedOnly) {
            $url = safeURL($destination);
        } else {
            $url = url($destination);
        }

        // Close any db connections before exit
        $database = Gdn::database();
        if ($database instanceof Gdn_Database) {
            $database->closeConnection();
        }
        // Clear out any previously sent content
        @ob_end_clean();

        if (!in_array($statusCode, [301, 302])) {
            $statusCode = 302;
        }

        // Encode backslashes because most modern browsers convert backslashes to slashes.
        // This would cause http://evil.domain\@trusted.domain/ to be converted to http://evil.domain/@trusted.domain/
        $url = str_replace('\\', '%5c', $url);
        safeHeader('Location: '.$url, true, $statusCode);
        exit();
    }
}

if (!function_exists('reflectArgs')) {
    /**
     * Reflect the arguments on a callback and returns them as an associative array.
     *
     * @param callback $callback A callback to the function.
     * @param array $args1 An array of arguments.
     * @param array $args2 An optional other array of arguments.
     * @return array The arguments in an associative array, in order ready to be passed to call_user_func_array().
     */
    function reflectArgs($callback, $args1, $args2 = null) {
        if (is_string($callback) && !function_exists($callback)) {
            throw new Exception("Function $callback does not exist");
        }

        if (is_array($callback) && !method_exists($callback[0], $callback[1])) {
            throw new Exception("Method {$callback[1]} does not exist.");
        }

        if ($args2 !== null) {
            $args1 = array_merge($args2, $args1);
        }
        $args1 = array_change_key_case($args1);

        if (is_string($callback)) {
            $meth = new ReflectionFunction($callback);
            $methName = $meth;
        } else {
            $meth = new ReflectionMethod($callback[0], $callback[1]);
            if (is_string($callback[0])) {
                $methName = $callback[0].'::'.$meth->getName();
            } else {
                $methName = get_class($callback[0]).'->'.$meth->getName();
            }
        }

        $methArgs = $meth->getParameters();

        $args = [];
        $missingArgs = [];

        // Set all of the parameters.
        foreach ($methArgs as $index => $methParam) {
            $paramName = $methParam->getName();
            $paramNameL = strtolower($paramName);

            if (isset($args1[$paramNameL])) {
                $paramValue = $args1[$paramNameL];
            } elseif (isset($args1[$index])) {
                $paramValue = $args1[$index];
            } elseif ($methParam->isDefaultValueAvailable()) {
                $paramValue = $methParam->getDefaultValue();
            } else {
                $paramValue = null;
                $missingArgs[] = '$'.$paramName;
            }

            $args[$paramName] = $paramValue;
        }

        // Add optional parameters so that methods that use get_func_args() will still work.
        for ($index = count($args); array_key_exists($index, $args1); $index++) {
            $args[$index] = $args1[$index];
        }

        if (count($missingArgs) > 0) {
            trigger_error("$methName() expects the following parameters: ".implode(', ', $missingArgs).'.', E_USER_NOTICE);
        }

        return $args;
    }
}

if (!function_exists('remoteIP')) {
    /**
     * Get the IP address of the current request.
     *
     * @return string Returns an IP address as a string.
     */
    function remoteIP() {
        return Gdn::request()->ipAddress();
    }
}

if (!function_exists('removeFromConfig')) {
    /**
     * Remove a value from the configuration.
     *
     * This function removes the value from the application configuration. It will not touch any default configurations.
     *
     * @param string $name The dot-separated name of the config.
     * @param array $options An array of additional options for removal.
     * @see Gdn_Config::removeFromConfig()
     */
    function removeFromConfig($name, $options = []) {
        Gdn::config()->removeFromConfig($name, $options);
    }
}

if (!function_exists('removeKeysFromNestedArray')) {
    /**
     * Recursively remove a set of keys from an array.
     *
     * @param array $array The input array.
     * @param array[string|int] $matches An array of keys to remove.
     * @return array Returns a copy of {@link $array} with the keys removed.
     */
    function removeKeysFromNestedArray($array, $matches) {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $isMatch = false;
                foreach ($matches as $match) {
                    if (stringEndsWith($key, $match)) {
                        unset($array[$key]);
                        $isMatch = true;
                    }
                }
                if (!$isMatch && (is_array($value) || is_object($value))) {
                    $array[$key] = removeKeysFromNestedArray($value, $matches);
                }
            }
        } elseif (is_object($array)) {
            $arr = get_object_vars($array);
            foreach ($arr as $key => $value) {
                $isMatch = false;
                foreach ($matches as $match) {
                    if (stringEndsWith($key, $match)) {
                        unset($array->$key);
                        $isMatch = true;
                    }
                }
                if (!$isMatch && (is_array($value) || is_object($value))) {
                    $array->$key = removeKeysFromNestedArray($value, $matches);
                }
            }
        }
        return $array;
    }
}

if (!function_exists('safeGlob')) {
    /**
     * A version of {@link glob()} that always returns an array.
     *
     * @param string        $pattern    The glob pattern.
     * @param array[string] $extensions An array of file extensions to whitelist.
     *
     * @return array[string] Returns an array of paths that match the glob.
     */
    function safeGlob($pattern, $extensions = []) {
        $result = glob($pattern, GLOB_NOSORT);
        if (!is_array($result)) {
            $result = [];
        }

        // Check against allowed extensions.
        if (count($extensions) > 0) {
            foreach ($result as $index => $path) {
                if (!$path) {
                    continue;
                }
                if (!in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $extensions)) {
                    unset($result[$index]);
                }
            }
        }

        return $result;
    }
}

if (!function_exists('safeImage')) {
    /**
     * Examines the provided url & checks to see if there is a valid image on the other side. Optionally you can specify minimum dimensions.
     *
     * @param string $imageUrl Full url (including http) of the image to examine.
     * @param int $minHeight Minimum height (in pixels) of image. 0 means any height.
     * @param int $minWidth Minimum width (in pixels) of image. 0 means any width.
     * @return mixed The url of the image if safe, false otherwise.
     */
    function safeImage($imageUrl, $minHeight = 0, $minWidth = 0) {
        try {
            list($width, $height, $_, $_) = getimagesize($imageUrl);
            if ($minHeight > 0 && $minHeight < $height) {
                return false;
            }

            if ($minWidth > 0 && $minWidth < $width) {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }
        return $imageUrl;
    }
}

if (!function_exists('safeUnlink')) {
    /**
     * A version of {@link unlink()} that won't raise a warning.
     *
     * @param string $filename Path to the file.
     * @return bool TRUE on success or FALSE on failure.
     */
    function safeUnlink($filename) {
        try {
            $r = unlink($filename);
            return $r;
        } catch (\Exception $ex) {
            return false;
        }
    }
}

if (!function_exists('saveToConfig')) {
    /**
     * Save values to the application's configuration file.
     *
     * @param string|array $name One of the following:
     *  - string: The key to save.
     *  - array: An array of key/value pairs to save.
     * @param mixed|null $value The value to save.
     * @param array|bool $options An array of additional options for the save.
     *  - Save: If this is false then only the in-memory config is set.
     *  - RemoveEmpty: If this is true then empty/false values will be removed from the config.
     * @return bool: Whether or not the save was successful. null if no changes were necessary.
     */
    function saveToConfig($name, $value = '', $options = []) {
        Gdn::config()->saveToConfig($name, $value, $options);
    }
}


if (!function_exists('setAppCookie')) {
    /**
     * Set a cookie with the appropriate application cookie prefix and other cookie information.
     *
     * @param string $name The name of the cookie without a prefix.
     * @param string $value The value of the cookie.
     * @param int $expire When the cookie should expire.
     * @param bool $force Whether or not to set the cookie even if already exists.
     */
    function setAppCookie($name, $value, $expire = 0, $force = false) {
        $px = c('Garden.Cookie.Name');
        $key = "$px-$name";

        // Check to see if the cookie is already set before setting it again.
        if (!$force && isset($_COOKIE[$key]) && $_COOKIE[$key] == $value) {
            return;
        }

        $domain = c('Garden.Cookie.Domain', '');

        // If the domain being set is completely incompatible with the current domain then make the domain work.
        $currentHost = Gdn::request()->host();
        if (!stringEndsWith($currentHost, trim($domain, '.'))) {
            $domain = '';
        }

        // Create the cookie.
        $path = c('Garden.Cookie.Path', '/');
        safeCookie($key, $value, $expire, $path, $domain, null, true);
        $_COOKIE[$key] = $value;
    }
}

if (!function_exists('sliceParagraph')) {
    /**
     * Slices a string at a paragraph.
     *
     * This function will attempt to slice a string at paragraph that is no longer than the specified maximum length.
     * If it can't slice the string at a paragraph it will attempt to slice on a sentence.
     *
     * Note that you should not expect this function to return a string that is always shorter than max-length.
     * The purpose of this function is to provide a string that is reaonably easy to consume by a human.
     *
     * @param string $string The string to slice.
     * @param int|array $limits Either int $maxLength or array($maxLength, $minLength); whereas $maxLength The maximum length of the string; $minLength The intended minimum length of the string (slice on sentence if paragraph is too short).
     * @param string $suffix The suffix if the string must be sliced mid-sentence.
     * @return string
     */
    function sliceParagraph($string, $limits = 500, $suffix = '…') {
        if(is_int($limits)) {
            $limits = [$limits, 32];
        }
        list($maxLength, $minLength) = $limits;
        if ($maxLength >= strlen($string)) {
            return $string;
        }

//      $String = preg_replace('`\s+\n`', "\n", $String);

        // See if there is a paragraph.
        $pos = strrpos(sliceString($string, $maxLength, ''), "\n\n");

        if ($pos === false || $pos < $minLength) {
            // There was no paragraph so try and split on sentences.
            $sentences = preg_split('`([.!?:]\s+)`', $string, null, PREG_SPLIT_DELIM_CAPTURE);

            $result = '';
            if (count($sentences) > 1) {
                $result = $sentences[0].$sentences[1];

                for ($i = 2; $i < count($sentences); $i++) {
                    $sentence = $sentences[$i];

                    if ((strlen($result) + strlen($sentence)) <= $maxLength || preg_match('`[.!?:]\s+`', $sentence)) {
                        $result .= $sentence;
                    } else {
                        break;
                    }
                }
            }

            if ($result) {
                return rtrim($result);
            }

            // There was no sentence. Slice off the last word and call it a day.
            $pos = strrpos(sliceString($string, $maxLength, ''), ' ');
            if ($pos === false) {
                return $string.$suffix;
            } else {
                return sliceString($string, $pos + 1, $suffix);
            }
        } else {
            return substr($string, 0, $pos + 1);
        }
    }
}

if (!function_exists('sliceString')) {
    /**
     * Slice a string, trying to account for multi-byte character sets if support is provided.
     *
     * @param string $string The string to slice.
     * @param int $length The number of characters to slice at.
     * @param string $suffix The suffix to add to the string if it is longer than {@link $length}.
     * @return string Returns a copy of {@link $string} appropriately sliced.
     */
    function sliceString($string, $length, $suffix = '…') {
        if (!$length) {
            return $string;
        }

        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($string, 0, $length, $suffix, 'utf-8');
        } else {
            $trim = substr($string, 0, $length);
            return $trim.((strlen($trim) != strlen($string)) ? $suffix : '');
        }
    }
}

if (!function_exists('smartAsset')) {
    /**
     * Takes the path to an asset (image, js file, css file, etc) and prepends the web root.
     *
     * @param string $destination The subpath of the asset.
     * @param bool|string $withDomain Whether or not to include the domain in the final URL.
     * @param bool $addVersion Whether or not to add a cache-busting version querystring parameter to the URL.
     * @return string Returns the URL of the asset.
     */
    function smartAsset($destination = '', $withDomain = false, $addVersion = false) {
        $destination = str_replace('\\', '/', $destination);
        if (isUrl($destination)) {
            $result = $destination;
        } else {
            $result = Gdn::request()->urlDomain($withDomain).Gdn::request()->assetRoot().'/'.ltrim($destination, '/');
        }

        if ($addVersion) {
            $version = assetVersion($destination);
            $result .= (strpos($result, '?') === false ? '?' : '&').'v='.urlencode($version);
        }
        return $result;
    }
}

if (!function_exists('stringBeginsWith')) {
    /**
     * Checks whether or not string A begins with string B.
     *
     * @param string $haystack The main string to check.
     * @param string $needle The substring to check against.
     * @param bool $caseInsensitive Whether or not the comparison should be case insensitive.
     * @param bool $trim Whether or not to trim $B off of $A if it is found.
     * @return bool|string Returns true/false unless $trim is true.
     */
    function stringBeginsWith($haystack, $needle, $caseInsensitive = false, $trim = false) {
        if (strlen($haystack) < strlen($needle)) {
            return $trim ? $haystack : false;
        } elseif (strlen($needle) == 0) {
            if ($trim) {
                return $haystack;
            }
            return true;
        } else {
            $result = substr_compare($haystack, $needle, 0, strlen($needle), $caseInsensitive) == 0;
            if ($trim) {
                $result = $result ? substr($haystack, strlen($needle)) : $haystack;
            }
            return $result;
        }
    }
}

if (!function_exists('stringEndsWith')) {
    /**
     * Checks whether or not string A ends with string B.
     *
     * @param string $haystack The main string to check.
     * @param string $needle The substring to check against.
     * @param bool $caseInsensitive Whether or not the comparison should be case insensitive.
     * @param bool $trim Whether or not to trim $B off of $A if it is found.
     * @return bool|string Returns true/false unless $trim is true.
     */
    function stringEndsWith($haystack, $needle, $caseInsensitive = false, $trim = false) {
        if (strlen($haystack) < strlen($needle)) {
            return $trim ? $haystack : false;
        } elseif (strlen($needle) == 0) {
            if ($trim) {
                return $haystack;
            }
            return true;
        } else {
            $result = substr_compare($haystack, $needle, -strlen($needle), strlen($needle), $caseInsensitive) == 0;
            if ($trim) {
                $result = $result ? substr($haystack, 0, -strlen($needle)) : $haystack;
            }
            return $result;
        }
    }
}

if (!function_exists('stringIsNullOrEmpty')) {
    /**
     * Checks whether or not a string is null or an empty string (after trimming).
     *
     * @param string $string The string to check.
     * @return bool
     */
    function stringIsNullOrEmpty($string) {
        return is_null($string) === true || (is_string($string) && trim($string) == '');
    }
}


if (!function_exists('setValue')) {
    /**
     * Set the value on an object/array.
     *
     * @param string $needle The key or property name of the value.
     * @param mixed &$haystack The array or object to set.
     * @param mixed $value The value to set.
     */
    function setValue($needle, &$haystack, $value) {
        if (is_array($haystack)) {
            $haystack[$needle] = $value;
        } elseif (is_object($haystack)) {
            $haystack->$needle = $value;
        }
    }
}


if (!function_exists('t')) {
    /**
     * Translates a code into the selected locale's definition.
     *
     * @param string $code The code related to the language-specific definition.
     *   Codes that begin with an '@' symbol are treated as literals and not translated.
     * @param string $default The default value to be displayed if the translation code is not found.
     * @return string The translated string or $code if there is no value in $default.
     * @see Gdn::translate()
     */
    function t($code, $default = false) {
        return Gdn::translate($code, $default);
    }
}

if (!function_exists('translateContent')) {
    /**
     * Translates user-generated content into the selected locale's definition.
     *
     * Currently this function is just an alias for t().
     *
     * @param string $code The code related to the language-specific definition.
     * Codes that begin with an '@' symbol are treated as literals and not translated.
     * @param string $default The default value to be displayed if the translation code is not found.
     * @return string The translated string or $code if there is no value in $default.
     * @see Gdn::translate()
     */
    function translateContent($code, $default = false) {
        return t($code, $default);
    }
}

if (!function_exists('theme')) {
    /**
     * Get the name of the current theme.
     *
     * @return string Returns the name of the current theme.
     */
    function theme() {
        return Gdn::themeManager()->currentTheme();
    }
}

if (!function_exists('safeURL')) {
    /**
     * Transform a destination to make sure that the resulting URL is "Safe".
     *
     * "Safe" means that the domain of the URL is trusted.
     *
     * @param $destination Destination URL or path.
     * @return string The destination if safe, /home/leaving?Target=$destination if not.
     */
    function safeURL($destination) {
        $url = url($destination, true);

        $trustedDomains = trustedDomains();
        $isTrustedDomain = false;

        foreach ($trustedDomains as $trustedDomain) {
            if (urlMatch($trustedDomain, $url)) {
                $isTrustedDomain = true;
                break;
            }
        }

        return ($isTrustedDomain ? $url : url('/home/leaving?Target='.urlencode($destination)));
    }
}

if (!function_exists('touchValue')) {
    /**
     * Set the value on an object/array if it doesn't already exist.
     *
     * @param string $key The key or property name of the value.
     * @param mixed &$collection The array or object to set.
     * @param mixed $default The value to set.
     */
    function touchValue($key, &$collection, $default) {
        if (is_array($collection) && !array_key_exists($key, $collection)) {
            $collection[$key] = $default;
        } elseif (is_object($collection) && !property_exists($collection, $key)) {
            $collection->$key = $default;
        }

        return val($key, $collection);
    }
}

if (!function_exists('touchFolder')) {
    /**
     * Ensure that a folder exists.
     *
     * @param string $path The path to the folder to touch.
     * @param int $perms The permissions to put on the folder if creating it.
     * @since 2.1
     */
    function touchFolder($path, $perms = 0777) {
        if (!file_exists($path)) {
            mkdir($path, $perms, true);
        }
    }
}

if (!function_exists('trace')) {
    /**
     * Trace some information for debugging.
     *
     * @param mixed $value One of the following:
     *
     * - null: The entire trace will be returned.
     * - string: A trace message.
     * - other: A variable to output.
     * @param string $type One of the `TRACE_*` constants or a string label for the trace.
     * @return array Returns the array of traces.
     */
    function trace($value = null, $type = TRACE_INFO) {
        static $traces = [];

        if ($value === null) {
            return $traces;
        }

        $traces[] = [$value, $type];
    }
}

if (!function_exists('trustedDomains')) {
    /**
     * Get an array of all of the trusted domains in the application.
     *
     * @return array
     */
    function trustedDomains() {
        // This domain is safe.
        $trustedDomains = [Gdn::request()->host()];

        $configuredDomains = c('Garden.TrustedDomains', []);
        if (!is_array($configuredDomains)) {
            $configuredDomains = is_string($configuredDomains) ? explode("\n", $configuredDomains) : [];
        }
        $configuredDomains = array_filter($configuredDomains);

        $trustedDomains = array_merge($trustedDomains, $configuredDomains);

        if (!c('Garden.Installed')) {
            // Bail out here because we don't have a database yet.
            return $trustedDomains;
        }

        // Build a collection of authentication provider URLs.
        $authProviderModel = new Gdn_AuthenticationProviderModel();
        $providers = $authProviderModel->getProviders();
        $providerUrls = [
            'PasswordUrl',
            'ProfileUrl',
            'RegisterUrl',
            'SignInUrl',
            'SignOutUrl',
            'URL'
        ];

        // Iterate through the providers, only grabbing URLs if they're not empty and not already present.
        if (is_array($providers) && count($providers) > 0) {
            foreach ($providers as $key => $record) {
                foreach ($providerUrls as $urlKey) {
                    $providerUrl = $record[$urlKey];
                    if ($providerUrl && $providerDomain = parse_url($providerUrl, PHP_URL_HOST)) {
                        if (!in_array($providerDomain, $trustedDomains)) {
                            $trustedDomains[] = $providerDomain;
                        }
                    }
                }
            }
        }

        Gdn::pluginManager()->EventArguments['TrustedDomains'] = &$trustedDomains;
        Gdn::pluginManager()->fireAs('EntryController')->fireEvent('BeforeTargetReturn');

        return array_unique($trustedDomains);
    }
}

if (!function_exists('unicodeRegexSupport')) {
    /**
     * Test for Unicode PCRE support. On non-UTF8 systems this will result in a blank string.
     *
     * @return bool
     */
    function unicodeRegexSupport() {
        return (preg_replace('`[\pP]`u', '', 'P') != '');
    }
}

if (!function_exists('url')) {
    /**
     * Takes a route and prepends the web root (expects "/controller/action/params" as $Destination).
     *
     * @param string $path The path of the controller method.
     * @param mixed $withDomain Whether or not to include the domain with the url. This can take the following values.
     * - true: Include the domain name.
     * - false: Do not include the domain. This is a relative path.
     * - //: Include the domain name, but use the "//" schemeless notation.
     * - /: Just return the path.
     * @return string Returns the resulting URL.
     */
    function url($path = '', $withDomain = false) {
        $result = Gdn::request()->url($path, $withDomain);
        return $result;
    }
}

if (!function_exists('passwordStrength')) {
    /**
     * Check a password's strength.
     *
     * @param string $password The password to test.
     * @param string $username The username that relates to the password.
     * @return array Returns an analysis of the supplied password, comprised of an array with the following keys:
     *
     *    - Pass: Whether or not the password passes the minimum strength requirements.
     *    - Symbols: The number of characters in the alphabet used by the password.
     *    - Length: The length of the password.
     *    - Entropy: The amount of entropy in the password.
     *    - Score: The password's complexity score.
     */
    function passwordStrength($password, $username) {
        $translations = explode(',', t('Password Translations', 'Too Short,Contains Username,Very Weak,Weak,Ok,Good,Strong'));

        // calculate $Entropy
        $alphabet = 0;
        if (preg_match('/[0-9]/', $password)) {
            $alphabet += 10;
        }
        if (preg_match('/[a-z]/', $password)) {
            $alphabet += 26;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $alphabet += 26;
        }
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $alphabet += 31;
        }

        $length = strlen($password);
        $entropy = log(pow($alphabet, $length), 2);

        $requiredLength = c('Garden.Password.MinLength', 6);
        $requiredScore = c('Garden.Password.MinScore', 2);
        $response = [
            'Pass' => false,
            'Symbols' => $alphabet,
            'Length' => $length,
            'Entropy' => $entropy,
            'Required' => $requiredLength,
            'Score' => 0
        ];

        if ($length < $requiredLength) {
            $response['Reason'] = $translations[0];
            return $response;
        }

        // password1 == username
        if (strpos(strtolower($username), strtolower($password)) !== false) {
            $response['Reason'] = $translations[1];
            return $response;
        }

        if ($entropy < 30) {
            $response['Score'] = 1;
            $response['Reason'] = $translations[2];
        } elseif ($entropy < 40) {
            $response['Score'] = 2;
            $response['Reason'] = $translations[3];
        } elseif ($entropy < 55) {
            $response['Score'] = 3;
            $response['Reason'] = $translations[4];
        } elseif ($entropy < 70) {
            $response['Score'] = 4;
            $response['Reason'] = $translations[5];
        } else {
            $response['Score'] = 5;
            $response['Reason'] = $translations[6];
        }

        $response['Pass'] = $response['Score'] >= $requiredScore;

        return $response;
    }
}

if (!function_exists('isSafeUrl')) {
    /**
     * Used to determine if a URL is on safe for use.
     *
     * A URL is considered safe it is a valid URL and is on the same domain as the site.
     *
     * @param string $url The Http url to be checked.
     * @return bool Returns true if the URL is safe or false otherwise.
     */
    function isSafeUrl($url) {

        $parsedUrl = parse_url($url);
        if (empty($parsedUrl['host']) || $parsedUrl['host'] == Gdn::request()->host()) {
            return true;
        }

        return false;
    }

}

if (!function_exists('isTrustedDomain')) {
    /**
     * Check to see if a URL or domain name is in a trusted domain.
     *
     * @param string $url The URL or domain name to check.
     * @return bool True if verified as a trusted domain.  False if unable to verify domain.
     */
    function isTrustedDomain($url) {
        static $trusted = null;

        if (empty($url)) {
            return false;
        }

        // Short circuit on our own domain.
        if (urlMatch(Gdn::request()->host(), $url)) {
            return true;
        }

        // If we haven't already compiled an array of trusted domains, grab them.
        if ($trusted === null) {
            $trusted = [];
            $trustedDomains = trustedDomains();
            foreach ($trustedDomains as $domain) {
                // Store the trusted domain by its host name.
                if (strpos($domain, '//') === false) {
                    $domain = '//'.$domain;
                }
                $host = preg_replace('`^(\*?\.)`', '', parse_url($domain, PHP_URL_HOST));
                $trusted[$host] = $domain;
            }
        }

        // Make sure the domain.
        if (strpos($url, '//') === false) {
            $url = '//'.$url;
        }

        // Check the URL against all domains by host part.
        for ($host = parse_url($url, PHP_URL_HOST); !empty($host); $host = ltrim(strstr($host, '.'), '.')) {
            if (isset($trusted[$host]) && urlMatch($trusted[$host], $url)) {
                return true;
            }
        }

        // No matches?  Must not be a trusted domain.
        return false;
    }
}

if (!function_exists('userAgentType')) {
    /**
     * Get or set the type of user agent.
     *
     * This method checks the user agent to try and determine the type of device making the current request.
     * It also checks for a special X-UA-Device header that a server module can set to more quickly determine the device.
     *
     * @param string|null $value The new value to set. This should be one of desktop, mobile, tablet, or app.
     * @return string Returns one of desktop, mobile, tablet, or app.
     */
    function userAgentType($value = null) {
        static $type = null;

        if ($value !== null) {
            $type = $value;
        }

        if ($type !== null) {
            return $type;
        }

        // Try and get the user agent type from the header if it was set from the server, varnish, etc.
        $type = strtolower(val('HTTP_X_UA_DEVICE', $_SERVER, ''));
        if ($type) {
            return $type;
        }

        // See if there is an override in the cookie.
        if ($type = val('X-UA-Device-Force', $_COOKIE)) {
            return $type;
        }

        // Now we will have to figure out the type based on the user agent and other things.
        $allHttp = strtolower(val('ALL_HTTP', $_SERVER));
        $httpAccept = strtolower(val('HTTP_ACCEPT', $_SERVER));
        $userAgent = strtolower(val('HTTP_USER_AGENT', $_SERVER));

        // Check for a mobile app.
        if (strpos($userAgent, 'vanillamobileapp') !== false) {
            return $type = 'app';
        }

        // Match wap Accepts: header
        if ((strpos($httpAccept, 'application/vnd.wap.xhtml+xml') > 0)
            || ((isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])))
        ) {
            return $type = 'mobile';
        }

        // Match mobile androids
        if (strpos($userAgent, 'android') !== false && strpos($userAgent, 'mobile') !== false) {
            return $type = 'mobile';
        }

        // Match operamini in 'ALL_HTTP'
        if (strpos($allHttp, 'operamini') > 0) {
            return $type = 'mobile';
        }

        // Match discrete chunks of known mobile agents
        $directAgents = [
            'up.browser',
            'up.link',
            'mmp',
            'symbian',
            'smartphone',
            'midp',
            'wap',
            'phone',
            'opera m',
            'kindle',
            'webos',
            'playbook',
            'bb10',
            'playstation vita',
            'windows phone',
            'iphone',
            'ipod',
            'nintendo 3ds'
        ];
        $directAgentsMatch = implode('|', $directAgents);
        if (preg_match("/({$directAgentsMatch})/i", $userAgent)) {
            return $type = 'mobile';
        }

        // Match starting chunks of known
        $mobileUserAgent = substr($userAgent, 0, 4);
        $mobileUserAgents = [
            'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
            'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
            'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
            'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
            'newt', 'noki', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox', 'qwap',
            'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar', 'sie-',
            'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-', 'tosh',
            'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp', 'wapr',
            'webc', 'winw', 'winw', 'xda', 'xda-'];

        if (in_array($mobileUserAgent, $mobileUserAgents)) {
            return $type = 'mobile';
        }

        // None of the mobile matches work so we must be a desktop browser.
        return $type = 'desktop';
    }
}

if (!function_exists('increaseMaxExecutionTime')) {
    /**
     * Used to increase php max_execution_time value.
     *
     * @param int $maxExecutionTime PHP max execution time in seconds.
     * @return bool Returns true if max_execution_time was increased (or stayed the same) or false otherwise.
     */
    function increaseMaxExecutionTime($maxExecutionTime) {

        $iniMaxExecutionTime = ini_get('max_execution_time');

        // max_execution_time == 0 means no limit.
        if ($iniMaxExecutionTime === '0') {
            return true;
        }

        if (((string)$maxExecutionTime) === '0') {
            return set_time_limit(0);
        }

        if (!ctype_digit($iniMaxExecutionTime) || $iniMaxExecutionTime < $maxExecutionTime) {
            return set_time_limit($maxExecutionTime);
        }

        return true;
    }
}

if (!function_exists('slugify')) {
    /**
     * Converts a string to a slug-type string.
     *
     * Based off Symfony's Jobeet tutorial, and found here:
     * http://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string
     *
     * @param string $text The text to convert.
     * @return string mixed|string The slugified text.
     */
    function slugify($text) {
        // replace non letter or digits by -
        $text = preg_replace('/[^\pL\d]+/u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('/[^-\w]+/', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('/-+/', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}

if (!function_exists('sprintft')) {
    /**
     * A version of {@link sprintf()} That translates the string format.
     *
     * @param string $formatCode The format translation code.
     * @param mixed $arg1 The arguments to pass to {@link sprintf()}.
     * @return string The translated string.
     */
    function sprintft($formatCode, $arg1 = null) {
        $args = func_get_args();
        $args[0] = t($formatCode, $formatCode);
        return call_user_func_array('sprintf', $args);
    }
}

if (!function_exists('urlMatch')) {

    /**
     * Match a URL against a pattern.
     *
     * @param string $pattern The URL pattern.
     * @param string $url The URL to test.
     * @return bool Returns **true** if {@link $url} matches against {@link $pattern} or **false** otherwise.
     */
    function urlMatch($pattern, $url) {
        if (empty($pattern)) {
            return false;
        }
        $urlParts = parse_url($url);
        $patternParts = parse_url($pattern);

        if ($urlParts === false || $patternParts === false) {
            return false;
        }
        $urlParts += ['scheme' => '', 'host' => '', 'path' => ''];

        // Fix a pattern with no path.
        if (empty($patternParts['host'])) {
            $pathParts = explode('/', val('path', $patternParts), 2);
            $patternParts['host'] = $pathParts[0];
            $patternParts['path'] = '/'.trim(val(1, $pathParts), '/');
        }

        if (!empty($patternParts['scheme']) && $patternParts['scheme'] !== $urlParts['scheme']) {
            return false;
        }

        if (!empty($patternParts['host'])) {
            $p = $patternParts['host'];
            $host = $urlParts['host'];

            if (!fnmatch($p, $host)) {
                if (substr($p, 0, 2) !== '*.' || !fnmatch(substr($p, 2), $host)) {
                    return false;
                }
            }
        }

        if (!empty($patternParts['path']) && $patternParts['path'] !== '/') {
            $p = $patternParts['path'];
            $path = '/'.trim(val('path', $urlParts), '/');

            if (!fnmatch($p, $path)) {
                if (substr($p, -2) !== '/*' || !fnmatch(substr($p, 0, -2), $path)) {
                    return false;
                }
            }
        }

        return true;
    }
}

if (!function_exists('walkAllRecursive')) {
    /**
     * Recursively walk through all array elements or object properties.
     *
     * @param array|object $input
     * @param callable $callback
     */
    function walkAllRecursive(&$input, $callback) {
        $currentDepth = 0;
        $maxDepth = 128;

        $walker = function(&$input, $callback, $parent = null) use (&$walker, &$currentDepth, $maxDepth) {
            $currentDepth++;

            if ($currentDepth > $maxDepth) {
                throw new Exception('Maximum recursion depth exceeded.', 500);
            }
            foreach ($input as $key => &$val) {
                if (is_array($val) || is_object($val)) {
                    call_user_func_array($walker, [&$val, $callback, $key]);
                } else {
                    call_user_func_array($callback, [&$val, $key, $parent]);
                }
            }

            $currentDepth--;
        };

        call_user_func_array($walker, [&$input, $callback]);
    }
}

if (!function_exists('ipEncodeRecursive')) {
    /**
     * Recursively walk through all array elements or object properties and encode IP fields.
     *
     * @param array|object $input
     * @return array|object
     */
    function ipEncodeRecursive($input) {
        walkAllRecursive($input, function(&$val, $key = null, $parent = null) {
            if (is_string($val)) {
                if (stringEndsWith($key, 'IPAddress', true) || stringEndsWith($parent, 'IPAddresses', true)) {
                    $val = ipEncode($val);
                }
            }
        });
        return $input;
    }
}

if (!function_exists('ipDecodeRecursive')) {
    /**
     * Recursively walk through all array elements or object properties and decode IP fields.
     *
     * @param array|object $input
     * @return array|object
     */
    function ipDecodeRecursive($input) {
        walkAllRecursive($input, function(&$val, $key = null, $parent = null) {
            if (is_string($val)) {
                if (stringEndsWith($key, 'IPAddress', true) || stringEndsWith($parent, 'IPAddresses', true)) {
                    $val = ipDecode($val);
                }
            }
        });
        return $input;
    }
}

if (!function_exists('paramPreference')) {
    /**
     * Conditionally save and load a query parameter value from a user's preferences.
     *     If the parameter is not sent in the request query, attempt to load from the user's preferences.
     *     If the parameter is set, save to the user's preferences.
     * @param string $param Query string parameter name
     * @param string $preference User preference name
     * @param string|null $config Config value, used as a conditional for performing this action
     * @param string null $configVal Look for a specific config value, instead of allowing truthy values.
     * @param bool $save Save the parameter value to the user preference, if available.
     * @return mixed
     */
    function paramPreference($param, $preference, $config = null, $configVal = null, $save = false) {
        $value = Gdn::request()->get($param, null);

        if ($config === null || (($configVal === null && c($config)) || c($config) === $configVal)) {
            if ($value === null) {
                $value = Gdn::session()->getPreference($preference, null);
                if ($value) {
                    Gdn::request()->setQueryItem($param, $value);
                }
            } elseif ($save) {
                Gdn::session()->setPreference($preference, $value);
            }
        }

        return $value;
    }
}

if (!function_exists('TagUrl')) {
    /**
     *
     *
     * @param $row
     * @param string $page
     * @param mixed $withDomain
     * @see url() for $withDomain docs.
     * @return string
     */
    function tagUrl($row, $page = '', $withDomain = false) {
        static $useCategories;
        if (!isset($useCategories)) {
            $useCategories = c('Vanilla.Tagging.UseCategories');
        }

        // Add the p before a numeric page.
        if (is_numeric($page)) {
            if ($page > 1) {
                $page = 'p'.$page;
            } else {
                $page = '';
            }
        }
        if ($page) {
            $page = '/'.$page;
        }

        $tag = rawurlencode(val('Name', $row));

        if ($useCategories) {
            $category = CategoryModel::categories($row['CategoryID']);
            if ($category && $category['CategoryID'] > 0) {
                $category = rawurlencode(val('UrlCode', $category, 'x'));
            } else {
                $category = 'x';
            }
            $result = "/discussions/tagged/$category/$tag{$page}";
        } else {
            $result = "/discussions/tagged/$tag{$page}";
        }

        return url($result, $withDomain);
    }
}

if (!function_exists('TagFullName')) {
    /**
     *
     *
     * @param $row
     * @return mixed
     */
    function tagFullName($row) {
        $result = val('FullName', $row);
        if (!$result) {
            $result = val('Name', $row);
        }
        return $result;
    }
}
