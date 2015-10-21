<?php
/**
 * General functions
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
     * @param string $SrcPath The source path to make absolute (if not absolute already).
     * @param string $Url The full url to the page containing the src reference.
     * @return string Absolute source path.
     */
    function absoluteSource($SrcPath, $Url) {
        // If there is a scheme in the srcpath already, just return it.
        if (!is_null(parse_url($SrcPath, PHP_URL_SCHEME))) {
            return $SrcPath;
        }

        // Does SrcPath assume root?
        if (in_array(substr($SrcPath, 0, 1), array('/', '\\'))) {
            return parse_url($Url, PHP_URL_SCHEME)
            .'://'
            .parse_url($Url, PHP_URL_HOST)
            .$SrcPath;
        }

        // Work with the path in the url & the provided src path to backtrace if necessary
        $UrlPathParts = explode('/', str_replace('\\', '/', parse_url($Url, PHP_URL_PATH)));
        $SrcParts = explode('/', str_replace('\\', '/', $SrcPath));
        $Result = array();
        foreach ($SrcParts as $Part) {
            if (!$Part || $Part == '.') {
                continue;
            }

            if ($Part == '..') {
                array_pop($UrlPathParts);
            } else {
                $Result[] = $Part;
            }
        }
        // Put it all together & return
        return parse_url($Url, PHP_URL_SCHEME)
        .'://'
        .parse_url($Url, PHP_URL_HOST)
        .'/'.implode('/', array_filter(array_merge($UrlPathParts, $Result)));
    }
}

if (!function_exists('addActivity')) {
    /**
     * A convenience function that allows adding to the activity table with a single line.
     *
     * @param int $ActivityUserID
     * @param string $ActivityType
     * @param string $Story
     * @param string $RegardingUserID
     * @param string $Route
     * @param string $SendEmail
     * @return int
     * @deprecated
     */
    function addActivity(
        $ActivityUserID,
        $ActivityType,
        $Story = '',
        $RegardingUserID = '',
        $Route = '',
        $SendEmail = ''
    ) {
        $ActivityModel = new ActivityModel();
        return $ActivityModel->Add($ActivityUserID, $ActivityType, $Story, $RegardingUserID, '', $Route, $SendEmail);
    }
}


/**
 * This file is part of the array_column library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) 2013 Ben Ramsey <http://benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

if (!function_exists('array_column')) {
    /**
     * Returns the values from a single column of the input array, identified by the $columnKey.
     *
     * Optionally, you may provide an $indexKey to index the values in the returned
     * array by the values from the $indexKey column in the input array.
     *
     * @param array $input A multi-dimensional array (record set) from which to pull a column of values.
     * @param mixed $columnKey The column of values to return. This value may be the integer key of the column you wish
     * to retrieve, or it may be the string key name for an associative array.
     * @param mixed $indexKey The column to use as the index/keys for the returned array. This value may be the integer
     * key of the column, or it may be the string key name.
     * @return array
     */
    function array_column($input = null, $columnKey = null, $indexKey = null) {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();

        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }

        if (!is_array($params[0])) {
            trigger_error(
                'array_column() expects parameter 1 to be array, '.gettype($params[0]).' given',
                E_USER_WARNING
            );
            return null;
        }

        if (!is_int($params[1])
            && !is_float($params[1])
            && !is_string($params[1])
            && $params[1] !== null
            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        if (isset($params[2])
            && !is_int($params[2])
            && !is_float($params[2])
            && !is_string($params[2])
            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string)$params[1] : null;

        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int)$params[2];
            } else {
                $paramsIndexKey = (string)$params[2];
            }
        }

        $resultArray = array();

        foreach ($paramsInput as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;

            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string)$row[$paramsIndexKey];
            }

            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }

        }

        return $resultArray;
    }
}

if (!function_exists('arrayCombine')) {
    /**
     * PHP's array_combine has a limitation that doesn't allow array_combine to work if either of the arrays are empty.
     *
     * @param array $Array1 Array of keys to be used. Illegal values for key will be converted to string.
     * @param array $Array2 Array of values to be used.
     */
    function arrayCombine($Array1, $Array2) {
        if (!is_array($Array1)) {
            $Array1 = array();
        }

        if (!is_array($Array2)) {
            $Array2 = array();
        }

        if (count($Array1) > 0 && count($Array2) > 0) {
            return array_combine($Array1, $Array2);
        } elseif (count($Array1) == 0) {
            return $Array2;
        } else {
            return $Array1;
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
     * Searches $Array (and all arrays it contains) for $Value.
     */
    function arrayHasValue($Array, $Value) {
        if (in_array($Value, $Array)) {
            return true;
        } else {
            foreach ($Array as $k => $v) {
                if (is_array($v) && ArrayHasValue($v, $Value) === true) {
                    return true;
                }
            }
            return false;
        }
    }
}

if (!function_exists('arrayKeyExistsI')) {
    /**
     * Case-insensitive ArrayKeyExists search.
     */
    function arrayKeyExistsI($Key, $Search) {
        if (is_array($Search)) {
            foreach ($Search as $k => $v) {
                if (strtolower($Key) == strtolower($k)) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('arrayInArray')) {
    /**
     * Check to see if an array contains another array.
     *
     * Searches {@link $Haystack} array for items in {@link $Needle} array. If FullMatch is true,
     * all items in Needle must also be in Haystack. If FullMatch is false, only
     * one-or-more items in Needle must be in Haystack.
     *
     * @param array $Needle The array containing items to match to Haystack.
     * @param array $Haystack The array to search in for Needle items.
     * @param bool $FullMatch Should all items in Needle be found in Haystack to return true?
     * @deprecated
     */
    function arrayInArray($Needle, $Haystack, $FullMatch = true) {
        $Count = count($Needle);
        $Return = $FullMatch ? true : false;
        for ($i = 0; $i < $Count; ++$i) {
            if ($FullMatch === true) {
                if (in_array($Needle[$i], $Haystack) === false) {
                    $Return = false;
                }
            } else {
                if (in_array($Needle[$i], $Haystack) === true) {
                    $Return = true;
                    break;
                }
            }
        }
        return $Return;
    }
}

if (!function_exists('arraySearchI')) {
    /**
     * Case-insensitive version of array_search.
     *
     * @param array $Value The value to find in array.
     * @param array $Search The array to search in for $Value.
     * @return mixed Key of $Value in the $Search array.
     */
    function arraySearchI($Value, $Search) {
        return array_search(strtolower($Value), array_map('strtolower', $Search));
    }
}

if (!function_exists('arrayTranslate')) {
    /**
     * Take all of the items specified in an array and make a new array with them specified by mappings.
     *
     * @param array $Array The input array to translate.
     * @param array $Mappings The mappings to translate the array.
     * @param bool $AddRemaining Whether or not to add the remaining items to the array.
     * @return array
     */
    function arrayTranslate($Array, $Mappings, $AddRemaining = false) {
        $Array = (array)$Array;
        $Result = array();
        foreach ($Mappings as $Index => $Value) {
            if (is_numeric($Index)) {
                $Key = $Value;
                $NewKey = $Value;
            } else {
                $Key = $Index;
                $NewKey = $Value;
            }
            if ($NewKey === null) {
                unset($Array[$Key]);
                continue;
            }

            if (isset($Array[$Key])) {
                $Result[$NewKey] = $Array[$Key];
                unset($Array[$Key]);
            } else {
                $Result[$NewKey] = null;
            }
        }

        if ($AddRemaining) {
            foreach ($Array as $Key => $Value) {
                if (!isset($Result[$Key])) {
                    $Result[$Key] = $Value;
                }
            }
        }

        return $Result;
    }
}

if (!function_exists('arrayValue')) {
    /**
     * Get the value associated with a {@link $Needle} key in a {@link $Haystack} array.
     *
     * @param string $needle The key to look for in the $Haystack associative array.
     * @param array $haystack The associative array in which to search for the $Needle key.
     * @param string $default The default value to return if the requested value is not found. Default is false.
     */
    function arrayValue($needle, $haystack, $default = false) {
        $result = val($needle, $haystack, $default);
        return $result;
    }
}

if (!function_exists('arrayValueI')) {
    /**
     * Get the value associated with the {@link $Needle} in the {@link $Haystack}. This is a CASE-INSENSITIVE search.
     *
     * @param string $Needle The key to look for in the $Haystack associative array.
     * @param array $Haystack The associative array in which to search for the $Needle key.
     * @param mixed $Default The default value to return if the requested value is not found. Default is false.
     * @return mixed Returns the value at {@link $Needle} in {@link $Haystack} or {@link $Default} if it isn't found.
     */
    function arrayValueI($Needle, $Haystack, $Default = false) {
        $Return = $Default;
        if (is_array($Haystack)) {
            foreach ($Haystack as $Key => $Value) {
                if (strtolower($Needle) == strtolower($Key)) {
                    $Return = $Value;
                    break;
                }
            }
        }
        return $Return;
    }
}

if (!function_exists('arrayValuesToKeys')) {
    /**
     * Take an array's values and apply them to a new array as both the keys and values.
     *
     * @deprecated
     */
    function arrayValuesToKeys($Array) {
        return array_combine(array_values($Array), $Array);
    }
}

if (!function_exists('asset')) {
    /**
     * Takes the path to an asset (image, js file, css file, etc) and prepends the webroot.
     *
     * @param string $Destination
     * @param boolean $WithDomain
     * @param boolean $AddVersion
     * @param string $Version Forced version, skips auto-lookup.
     * @return string
     */
    function asset($Destination = '', $WithDomain = false, $AddVersion = false, $Version = null) {
        $Destination = str_replace('\\', '/', $Destination);
        if (IsUrl($Destination)) {
            $Result = $Destination;
        } else {
            $Result = Gdn::Request()->UrlDomain($WithDomain).Gdn::Request()->AssetRoot().'/'.ltrim($Destination, '/');
        }

        if ($AddVersion) {
            if (strpos($Result, '?') === false) {
                $Result .= '?';
            } else {
                $Result .= '&';
            }

            // Figure out which version to put after the asset.
            if (is_null($Version)) {
                $Version = APPLICATION_VERSION;
                if (preg_match('`^/([^/]+)/([^/]+)/`', $Destination, $Matches)) {
                    $Type = $Matches[1];
                    $Key = $Matches[2];
                    static $ThemeVersion = null;

                    switch ($Type) {
                        case 'plugins':
                            $PluginInfo = Gdn::PluginManager()->GetPluginInfo($Key);
                            $Version = val('Version', $PluginInfo, $Version);
                            break;
                        case 'applications':
                            $AppInfo = Gdn::ApplicationManager()->GetApplicationInfo($Key);
                            $Version = val('Version', $AppInfo, $Version);
                            break;
                        case 'themes':
                            if ($ThemeVersion === null) {
                                $ThemeInfo = Gdn::ThemeManager()->GetThemeInfo(Theme());
                                if ($ThemeInfo !== false) {
                                    $ThemeVersion = val('Version', $ThemeInfo, $Version);
                                } else {
                                    $ThemeVersion = $Version;
                                }
                            }
                            $Version = $ThemeVersion;
                            break;
                    }
                }
            }

            $Result .= 'v='.urlencode($Version);
        }
        return $Result;
    }
}

if (!function_exists('attribute')) {
    /**
     * Takes an attribute (or array of attributes) and formats them in attribute="value" format.
     */
    function attribute($Name, $ValueOrExclude = '') {
        $Return = '';
        if (!is_array($Name)) {
            $Name = array($Name => $ValueOrExclude);
            $Exclude = '';
        } else {
            $Exclude = $ValueOrExclude;
        }
        foreach ($Name as $Attribute => $Val) {
            if ($Exclude && StringBeginsWith($Attribute, $Exclude)) {
                continue;
            }

            if ($Val != '' && $Attribute != 'Standard') {
                $Return .= ' '.$Attribute.'="'.htmlspecialchars($Val, ENT_COMPAT, C('Garden.Charset', 'UTF-8')).'"';
            }
        }
        return $Return;
    }
}

if (!function_exists('c')) {
    /**
     * Retrieves a configuration setting.
     *
     * @param string|bool $Name The name of the configuration setting.
     * Settings in different sections are separated by dots.
     * @param mixed $Default The result to return if the configuration setting is not found.
     * @return mixed The configuration setting.
     * @see Gdn::Config()
     */
    function c($Name = false, $Default = false) {
        return Gdn::config($Name, $Default);
    }
}

if (!function_exists('config')) {
    /**
     * Retrieves a configuration setting.
     *
     * @param string|bool $Name The name of the configuration setting.
     * Settings in different sections are separated by dots.
     * @param mixed $Default The result to return if the configuration setting is not found.
     * @return mixed The configuration setting.
     * @see Gdn::Config()
     */
    function config($Name = false, $Default = false) {
        return Gdn::config($Name, $Default);
    }
}

if (!function_exists('cTo')) {
    /**
     * Set a value in an deep array.
     *
     * @param array &$Data The array to set.
     * @param string $Name A dot separated set of keys to set.
     * @param mixed $Value The value to set.
     * @deprecated Use {@link setvalr()}.
     */
    function cTo(&$Data, $Name, $Value) {
        $Name = explode('.', $Name);
        $LastKey = array_pop($Name);
        $Current =& $Data;

        foreach ($Name as $Key) {
            if (!isset($Current[$Key])) {
                $Current[$Key] = array();
            }

            $Current =& $Current[$Key];
        }
        $Current[$LastKey] = $Value;
    }
}

if (!function_exists('calculateNumberOfPages')) {
    /**
     * Calculate the total number of pages based on the total items and items per page.
     *
     * Based on the total number of items and the number of items per page,
     * this function will calculate how many pages there are.
     * Returns the number of pages available
     */
    function calculateNumberOfPages($ItemCount, $ItemsPerPage) {
        $TmpCount = ($ItemCount / $ItemsPerPage);
        $RoundedCount = intval($TmpCount);

        if ($TmpCount > 1) {
            if ($TmpCount > $RoundedCount) {
                $PageCount = $RoundedCount + 1;
            } else {
                $PageCount = $RoundedCount;
            }
        } else {
            $PageCount = 1;
        }
        return $PageCount;
    }
}

if (!function_exists('changeBasename')) {
    /**
     * Change the basename part of a filename for a given path.
     *
     * @param string $Path The path to alter.
     * @param string $NewBasename The new basename. A %s will be replaced by the old basename.
     * @return string
     */
    function changeBasename($Path, $NewBasename) {
        $NewBasename = str_replace('%s', '$2', $NewBasename);
        $Result = preg_replace('/^(.*\/)?(.*?)(\.[^.]+)$/', '$1'.$NewBasename.'$3', $Path);

        return $Result;
    }
}

// Smarty
if (!function_exists('checkPermission')) {
    function checkPermission($PermissionName, $Type = '') {
        $Result = Gdn::session()->checkPermission($PermissionName, false, $Type ? 'Category' : '', $Type);
        return $Result;
    }
}

// Negative permission check
if (!function_exists('checkRestriction')) {
    function checkRestriction($PermissionName) {
        $Result = Gdn::session()->checkPermission($PermissionName);
        $Unrestricted = Gdn::session()->checkPermission('Garden.Admin.Only');
        return $Result && !$Unrestricted;
    }
}

// Smarty sux
if (!function_exists('multiCheckPermission')) {
    function multiCheckPermission($PermissionName) {
        $Result = Gdn::session()->checkPermission($PermissionName, false);
        return $Result;
    }
}

if (!function_exists('checkRequirements')) {
    function checkRequirements($ItemName, $RequiredItems, $EnabledItems) {
        // 1. Make sure that $RequiredItems are present
        if (is_array($RequiredItems)) {
            $MissingRequirements = array();

            foreach ($RequiredItems as $RequiredItemName => $RequiredVersion) {
                if (!array_key_exists($RequiredItemName, $EnabledItems)) {
                    $MissingRequirements[] = "$RequiredItemName $RequiredVersion";
                } elseif ($RequiredVersion && $RequiredVersion != '*') { // * means any version
                    // If the item exists and is enabled, check the version
                    $EnabledVersion = ArrayValue('Version', ArrayValue($RequiredItemName, $EnabledItems, array()), '');
                    // Compare the versions.
                    if (version_compare($EnabledVersion, $RequiredVersion, '<')) {
                        $MissingRequirements[] = "$RequiredItemName $RequiredVersion";
                    }
                }
            }
            if (count($MissingRequirements) > 0) {
                $Msg = sprintf(
                    "%s is missing the following requirement(s): %s.",
                    $ItemName,
                    implode(', ', $MissingRequirements)
                );
                throw new Gdn_UserException($Msg);
            }
        }
    }
}

if (!function_exists('check_utf8')) {
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
     * @param array $Paths The array of paths to concatenate.
     * @param string $Delimiter The delimiter to use when concatenating. Defaults to system-defined directory separator.
     * @returns string Returns the concatenated path.
     */
    function combinePaths($Paths, $Delimiter = DS) {
        if (is_array($Paths)) {
            $MungedPath = implode($Delimiter, $Paths);
            $MungedPath = str_replace(
                array($Delimiter.$Delimiter.$Delimiter, $Delimiter.$Delimiter),
                array($Delimiter, $Delimiter),
                $MungedPath
            );
            return str_replace(array('http:/', 'https:/'), array('http://', 'https://'), $MungedPath);
        } else {
            return $Paths;
        }
    }
}

if (!function_exists('compareHashDigest')) {
    /**
     * Determine whether or not two strings are equal in a time that is independent of partial matches.
     *
     * This snippet prevents HMAC Timing attacks ( http://codahale.com/a-lesson-in-timing-attacks/ )
     * Thanks to Eric Karulf (ekarulf @ github) for this fix.
     */
    function compareHashDigest($Digest1, $Digest2) {
        if (strlen($Digest1) !== strlen($Digest2)) {
            return false;
        }

        $Result = 0;
        for ($i = strlen($Digest1) - 1; $i >= 0; $i--) {
            $Result |= ord($Digest1[$i]) ^ ord($Digest2[$i]);
        }

        return 0 === $Result;
    }
}

if (!function_exists('concatSep')) {
    /**
     * Concatenate a string to another string with a separator.
     *
     * @param string $Sep The separator string to use between the concatenated strings.
     * @param string $Str1 The first string in the concatenation chain.
     * @param mixed $Str2 The second string in the concatenation chain.
     *  - This parameter can be an array in which case all of its elements will be concatenated.
     *  - If this parameter is a string then the function will look for more arguments to concatenate.
     * @return string
     */
    function concatSep($Sep, $Str1, $Str2) {
        if (is_array($Str2)) {
            $Strings = array_merge((array)$Str1, $Str2);
        } else {
            $Strings = func_get_args();
            array_shift($Strings);
        }

        $Result = '';
        foreach ($Strings as $String) {
            if (!$String) {
                continue;
            }

            if ($Result) {
                $Result .= $Sep;
            }
            $Result .= $String;
        }
        return $Result;
    }
}

if (!function_exists('ConsolidateArrayValuesByKey')) {
    /**
     * Return the values from a single column in the input array.
     *
     * Take an array of associative arrays (ie. a dataset array), a $Key, and
     * merges all of the values for that key into a single array, returning it.
     *
     * @deprecated Use {@link array_column()} instead.
     */
    function consolidateArrayValuesByKey($Array, $Key, $ValueKey = '', $DefaultValue = null) {
        $Return = array();
        foreach ($Array as $Index => $AssociativeArray) {
            if (is_object($AssociativeArray)) {
                if ($ValueKey === '') {
                    $Return[] = $AssociativeArray->$Key;
                } elseif (property_exists($AssociativeArray, $ValueKey)) {
                    $Return[$AssociativeArray->$Key] = $AssociativeArray->$ValueKey;
                } else {
                    $Return[$AssociativeArray->$Key] = $DefaultValue;
                }
            } elseif (is_array($AssociativeArray) && array_key_exists($Key, $AssociativeArray)) {
                if ($ValueKey === '') {
                    $Return[] = $AssociativeArray[$Key];
                } elseif (array_key_exists($ValueKey, $AssociativeArray)) {
                    $Return[$AssociativeArray[$Key]] = $AssociativeArray[$ValueKey];
                } else {
                    $Return[$AssociativeArray[$Key]] = $DefaultValue;
                }
            }
        }
        return $Return;
    }
}

if (!function_exists('decho')) {
    /**
     * Echo's debug variables if user is root admin.
     */
    function decho($Mixed, $Prefix = 'DEBUG', $Permission = false) {
        $Prefix = StringEndsWith($Prefix, ': ', true, true).': ';

        if (!$Permission || Gdn::Session()->CheckPermission('Garden.Debug.Allow')) {
            echo '<pre style="text-align: left; padding: 0 4px;">'.$Prefix;
            if (is_string($Mixed)) {
                echo $Mixed;
            } else {
                echo htmlspecialchars(print_r($Mixed, true));
            }

            echo '</pre>';
        }
    }
}

if (!function_exists('dateCompare')) {
    /**
     * Compare two dates.
     *
     * This function compares two dates in a way that is similar to strcmp().
     *
     * @param int|string $Date1
     * @param int|string $Date2
     * @return int
     * @since 2.1
     */
    function dateCompare($Date1, $Date2) {
        if (!is_numeric($Date1)) {
            $Date1 = strtotime($Date1);
        }
        if (!is_numeric($Date2)) {
            $Date2 = strtotime($Date2);
        }

        if ($Date1 == $Date2) {
            return 0;
        }
        if ($Date1 > $Date2) {
            return 1;
        }
        return -1;
    }
}

if (!function_exists('debug')) {
    /**
     * Get or set the current debug state of the application.
     *
     * @param bool? $value The new debug value or null to just return the current value.
     * @return bool Returns the current debug level.
     */
    function debug($value = null) {
        static $Debug = false;
        if ($value === null) {
            return $Debug;
        }

        $Changed = $Debug != $value;
        $Debug = $value;
        if ($Debug) {
            Logger::logLevel(Logger::DEBUG);
        } else {
            if ($Changed) {
                Logger::logLevel(C('Garden.LogLevel', Logger::INFO));
            }
        }
        return $Debug;
    }
}

if (!function_exists('debugMethod')) {
    function debugMethod($MethodName, $MethodArgs = array()) {
        echo $MethodName."(";
        $SA = array();
        foreach ($MethodArgs as $FuncArg) {
            if (is_null($FuncArg)) {
                $SA[] = 'null';
            } elseif (!is_array($FuncArg) && !is_object($FuncArg)) {
                $SA[] = "'{$FuncArg}'";
            } elseif (is_array($FuncArg)) {
                $SA[] = "'Array(".sizeof($FuncArg).")'";
            } else {
                $SA[] = gettype($FuncArg)."/".get_class($FuncArg);
            }
        }
        echo implode(', ', $SA);
        echo ")\n";
    }
}

if (!function_exists('deprecated')) {
    /**
     * Mark a function deprecated.
     *
     * You can pass an optional date to the deprecated function to make errors more noisy in debug mode after 3 months.
     *
     * @param string $oldName The name of the deprecated function.
     * @param string $newName The name of the new function that should be used instead.
     * @param string $date A string in the form "yyyy-mm-dd" representing the date that the code was deprecated.
     */
    function deprecated($oldName, $newName = '', $date = '') {
        $message = "$oldName is deprecated.";
        if ($newName) {
            $message .= " Use $newName instead.";
        }

        if ($date && debug()) {
            $expires = strtotime('+3 months', strtotime($date));
            if ($expires <= time()) {
                trigger_error($message, E_USER_ERROR);
                return;
            }
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
        $urlFormat = C('Garden.ExternalUrlFormat');

        if ($urlFormat && !isUrl($path)) {
            $result = sprintf($urlFormat, ltrim($path, '/'));
        } elseif (stringBeginsWith($path, '//')) {
            $result = Gdn::request()->scheme().':'.$path;
        } else {
            $result = Url($path, true);
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
     * @return array Returns an array containing Url, Title, Description, Images (array) and Exception
     * (if there were problems retrieving the page).
     */
    function fetchPageInfo($url, $timeout = 3, $sendCookies = false) {
        $PageInfo = array(
            'Url' => $url,
            'Title' => '',
            'Description' => '',
            'Images' => array(),
            'Exception' => false
        );

        try {
            // Make sure the URL is valid.
            $urlParts = parse_url($url);
            if ($urlParts === false || !in_array(val('scheme', $urlParts), array('http', 'https'))) {
                throw new Exception('Invalid URL.', 400);
            }

            if (!defined('HDOM_TYPE_ELEMENT')) {
                require_once(PATH_LIBRARY.'/vendors/simplehtmldom/simple_html_dom.php');
            }

            $Request = new ProxyRequest();
            $PageHtml = $Request->Request(array(
                'URL' => $url,
                'Timeout' => $timeout,
                'Cookies' => $sendCookies
            ));

            if (!$Request->status()) {
                throw new Exception('Couldn\'t connect to host.', 400);
            }

            $Dom = str_get_html($PageHtml);
            if (!$Dom) {
                throw new Exception('Failed to load page for parsing.');
            }

            // FIRST PASS: Look for open graph title, desc, images
            $PageInfo['Title'] = domGetContent($Dom, 'meta[property=og:title]');

            Trace('Getting og:description');
            $PageInfo['Description'] = domGetContent($Dom, 'meta[property=og:description]');
            foreach ($Dom->find('meta[property=og:image]') as $Image) {
                if (isset($Image->content)) {
                    $PageInfo['Images'][] = $Image->content;
                }
            }

            // SECOND PASS: Look in the page for title, desc, images
            if ($PageInfo['Title'] == '') {
                $PageInfo['Title'] = $Dom->find('title', 0)->plaintext;
            }

            if ($PageInfo['Description'] == '') {
                Trace('Getting meta description');
                $PageInfo['Description'] = domGetContent($Dom, 'meta[name=description]');
            }

            // THIRD PASS: Look in the page contents
            if ($PageInfo['Description'] == '') {
                foreach ($Dom->find('p') as $element) {
                    Trace('Looking at p for description.');

                    if (strlen($element->plaintext) > 150) {
                        $PageInfo['Description'] = $element->plaintext;
                        break;
                    }
                }
                if (strlen($PageInfo['Description']) > 400) {
                    $PageInfo['Description'] = SliceParagraph($PageInfo['Description'], 400);
                }
            }

            // Final: Still nothing? remove limitations
            if ($PageInfo['Description'] == '') {
                foreach ($Dom->find('p') as $element) {
                    Trace('Looking at p for description (no restrictions)');
                    if (trim($element->plaintext) != '') {
                        $PageInfo['Description'] = $element->plaintext;
                        break;
                    }
                }
            }

            // Page Images
            if (count($PageInfo['Images']) == 0) {
                $Images = domGetImages($Dom, $url);
                $PageInfo['Images'] = array_values($Images);
            }

            $PageInfo['Title'] = htmlEntityDecode($PageInfo['Title']);
            $PageInfo['Description'] = htmlEntityDecode($PageInfo['Description']);

        } catch (Exception $ex) {
            $PageInfo['Exception'] = $ex->getMessage();
        }
        return $PageInfo;
    }
}

if (!function_exists('domGetContent')) {
    function domGetContent($dom, $selector, $default = '') {
        $Element = $dom->getElementsByTagName($selector);
        return isset($Element->content) ? $Element->content : $default;
    }
}

if (!function_exists('domGetImages')) {
    function domGetImages($dom, $url, $maxImages = 4) {
        $Images = array();
        foreach ($dom->find('img') as $element) {
            $Images[] = array(
                'Src' => absoluteSource($element->src, $url),
                'Width' => $element->width,
                'Height' => $element->height
            );
        }

//      Gdn::Controller()->Data['AllImages'] = $Images;

        // Sort by size, biggest one first
        $ImageSort = array();
        // Only look at first 4 images (speed!)
        $i = 0;
        foreach ($Images as $ImageInfo) {
            $Image = $ImageInfo['Src'];

            if (strpos($Image, 'doubleclick.') != false) {
                continue;
            }

            try {
                if ($ImageInfo['Height'] && $ImageInfo['Width']) {
                    $Height = $ImageInfo['Height'];
                    $Width = $ImageInfo['Width'];
                } else {
                    list($Width, $Height) = getimagesize($Image);
                }

                $Diag = (int)floor(sqrt(($Width * $Width) + ($Height * $Height)));

//            Gdn::Controller()->Data['Foo'][] = array($Image, $Width, $Height, $Diag);

                if (!$Width || !$Height) {
                    continue;
                }

                // Require min 100x100 dimension image.
                if ($Width < 100 && $Height < 100) {
                    continue;
                }

                // Don't take a banner-shaped image.
                if ($Height * 4 < $Width) {
                    continue;
                }

                // Prefer images that are less than 800px wide (banners?)
//            if ($Diag > 141 && $Width < 800) { }

                if (!array_key_exists($Diag, $ImageSort)) {
                    $ImageSort[$Diag] = array($Image);
                } else {
                    $ImageSort[$Diag][] = $Image;
                }


                $i++;

                if ($i > $maxImages) {
                    break;
                }
            } catch (Exception $ex) {
                // do nothing
            }
        }

        krsort($ImageSort);
        $GoodImages = array();
        foreach ($ImageSort as $Diag => $Arr) {
            $GoodImages = array_merge($GoodImages, $Arr);
        }
        return $GoodImages;
    }
}

if (!function_exists('forceIPv4')) {
    /**
     * Force a string into ipv4 notation.
     *
     * @param string $IP The IP address to force.
     * @return string Returns the IPv4 address version of {@link IP}.
     * @since 2.1
     */
    function forceIPv4($IP) {
        if ($IP === '::1') {
            return '127.0.0.1';
        } elseif (strpos($IP, ':') === true) {
            return '0.0.0.1';
        } elseif (strpos($IP, '.') === false) {
            return '0.0.0.2';
        } else {
            return substr($IP, 0, 15);
        }
    }
}

/**
 * If a ForeignID is longer than 32 characters, use its hash instead.
 *
 * @param $ForeignID string Current foreign ID value.
 * @return string 32 characters or less.
 */
if (!function_exists('ForeignIDHash')) {
    function foreignIDHash($ForeignID) {
        return strlen($ForeignID) > 32 ? md5($ForeignID) : $ForeignID;
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
     *  - url: Calls Url() function around the value to show a valid url with the site.
     * You can pass a domain to include the domain.
     *  - urlencode, rawurlencode: Calls urlencode/rawurlencode respectively.
     *  - html: Calls htmlspecialchars.
     * @param array $args The array of arguments.
     * If you want to nest arrays then the keys to the nested values can be separated by dots.
     * @return string The formatted string.
     * <code>
     * echo FormatString("Hello {Name}, It's {Now,time}.", array('Name' => 'Frank', 'Now' => '1999-12-31 23:59'));
     * // This would output the following string:
     * // Hello Frank, It's 12:59PM.
     * </code>
     */
    function formatString($string, $args = array()) {
        _formatStringCallback($args, true);
        $Result = preg_replace_callback('/{([^\s][^}]+[^\s]?)}/', '_formatStringCallback', $string);

        return $Result;
    }
}

if (!function_exists('_formatStringCallback')) {
    function _formatStringCallback($Match, $SetArgs = false) {
        static $Args = array(), $ContextUserID = null;
        if ($SetArgs) {
            $Args = $Match;

            if (isset($Args['_ContextUserID'])) {
                $ContextUserID = $Args['_ContextUserID'];
            } else {
                $ContextUserID = Gdn::Session() && Gdn::Session()->IsValid() ? Gdn::Session()->UserID : null;
            }

            return;
        }

        $Match = $Match[1];
        if ($Match == '{') {
            return $Match;
        }

        // Parse out the field and format.
        $Parts = explode(',', $Match);
        $Field = trim($Parts[0]);
        $Format = trim(GetValue(1, $Parts, ''));
        $SubFormat = strtolower(trim(GetValue(2, $Parts, '')));
        $FormatArgs = GetValue(3, $Parts, '');

        if (in_array($Format, array('currency', 'integer', 'percent'))) {
            $FormatArgs = $SubFormat;
            $SubFormat = $Format;
            $Format = 'number';
        } elseif (is_numeric($SubFormat)) {
            $FormatArgs = $SubFormat;
            $SubFormat = '';
        }

        $Value = GetValueR($Field, $Args, '');
        if ($Value == '' && !in_array($Format, array('url', 'exurl', 'number', 'plural'))) {
            $Result = '';
        } else {
            switch (strtolower($Format)) {
                case 'date':
                    switch ($SubFormat) {
                        case 'short':
                            $Result = Gdn_Format::Date($Value, '%d/%m/%Y');
                            break;
                        case 'medium':
                            $Result = Gdn_Format::Date($Value, '%e %b %Y');
                            break;
                        case 'long':
                            $Result = Gdn_Format::Date($Value, '%e %B %Y');
                            break;
                        default:
                            $Result = Gdn_Format::Date($Value);
                            break;
                    }
                    break;
                case 'html':
                case 'htmlspecialchars':
                    $Result = htmlspecialchars($Value);
                    break;
                case 'number':
                    if (!is_numeric($Value)) {
                        $Result = $Value;
                    } else {
                        switch ($SubFormat) {
                            case 'currency':
                                $Result = '$'.number_format($Value, is_numeric($FormatArgs) ? $FormatArgs : 2);
                                break;
                            case 'integer':
                                $Result = (string)round($Value);
                                if (is_numeric($FormatArgs) && strlen($Result) < $FormatArgs) {
                                    $Result = str_repeat('0', $FormatArgs - strlen($Result)).$Result;
                                }
                                break;
                            case 'percent':
                                $Result = round($Value * 100, is_numeric($FormatArgs) ? $FormatArgs : 0);
                                break;
                            default:
                                $Result = number_format($Value, is_numeric($FormatArgs) ? $FormatArgs : 0);
                                break;
                        }
                    }
                    break;
                case 'plural':
                    if (is_array($Value)) {
                        $Value = count($Value);
                    } elseif (StringEndsWith($Field, 'UserID', true)) {
                        $Value = 1;
                    }

                    if (!is_numeric($Value)) {
                        $Result = $Value;
                    } else {
                        if (!$SubFormat) {
                            $SubFormat = rtrim("%s $Field", 's');
                        }
                        if (!$FormatArgs) {
                            $FormatArgs = $SubFormat.'s';
                        }

                        $Result = Plural($Value, $SubFormat, $FormatArgs);
                    }
                    break;
                case 'rawurlencode':
                    $Result = rawurlencode($Value);
                    break;
                case 'text':
                    $Result = Gdn_Format::Text($Value, false);
                    break;
                case 'time':
                    $Result = Gdn_Format::Date($Value, '%l:%M%p');
                    break;
                case 'url':
                    if (strpos($Field, '/') !== false) {
                        $Value = $Field;
                    }
                    $Result = Url($Value, $SubFormat == 'domain');
                    break;
                case 'exurl':
                    if (strpos($Field, '/') !== false) {
                        $Value = $Field;
                    }
                    $Result = externalUrl($Value);
                    break;
                case 'urlencode':
                    $Result = urlencode($Value);
                    break;
                case 'gender':
                    // Format in the form of FieldName,gender,male,female,unknown[,plural]
                    if (is_array($Value) && count($Value) == 1) {
                        $Value = array_shift($Value);
                    }

                    $Gender = 'u';

                    if (!is_array($Value)) {
                        $User = Gdn::UserModel()->GetID($Value);
                        if ($User) {
                            $Gender = $User->Gender;
                        }
                    } else {
                        $Gender = 'p';
                    }

                    switch ($Gender) {
                        case 'm':
                            $Result = $SubFormat;
                            break;
                        case 'f':
                            $Result = $FormatArgs;
                            break;
                        case 'p':
                            $Result = GetValue(5, $Parts, GetValue(4, $Parts));
                            break;
                        case 'u':
                        default:
                            $Result = GetValue(4, $Parts);
                    }

                    break;
                case 'user':
                case 'you':
                case 'his':
                case 'her':
                case 'your':
                    $Result = print_r($Value, true);
                    $ArgsBak = $Args;
                    if (is_array($Value) && count($Value) == 1) {
                        $Value = array_shift($Value);
                    }

                    if (is_array($Value)) {
                        if (isset($Value['UserID'])) {
                            $User = $Value;
                            $User['Name'] = FormatUsername($User, $Format, $ContextUserID);

                            $Result = UserAnchor($User);
                        } else {
                            $Max = C('Garden.FormatUsername.Max', 5);
                            // See if there is another count.
                            $ExtraCount = GetValueR($Field.'_Count', $Args, 0);

                            $Count = count($Value);
                            $Result = '';
                            for ($i = 0; $i < $Count; $i++) {
                                if ($i >= $Max && $Count > $Max + 1) {
                                    $Others = $Count - $i + $ExtraCount;
                                    $Result .= ' '.T('sep and', 'and').' '
                                        .Plural($Others, '%s other', '%s others');
                                    break;
                                }

                                $ID = $Value[$i];
                                if (is_array($ID)) {
                                    continue;
                                }

                                if ($i == $Count - 1) {
                                    $Result .= ' '.T('sep and', 'and').' ';
                                } elseif ($i > 0) {
                                    $Result .= ', ';
                                }

                                $Special = array(-1 => T('everyone'), -2 => T('moderators'), -3 => T('administrators'));
                                if (isset($Special[$ID])) {
                                    $Result .= $Special[$ID];
                                } else {
                                    $User = Gdn::UserModel()->GetID($ID);
                                    if ($User) {
                                        $User->Name = FormatUsername($User, $Format, $ContextUserID);
                                        $Result .= UserAnchor($User);
                                    }
                                }
                            }
                        }
                    } else {
                        $User = Gdn::UserModel()->GetID($Value);
                        if ($User) {
                            $User->Name = FormatUsername($User, $Format, $ContextUserID);

                            $Result = UserAnchor($User);
                        } else {
                            $Result = '';
                        }
                    }

                    $Args = $ArgsBak;
                    break;
                default:
                    $Result = $Value;
                    break;
            }
        }
        return $Result;
    }
}

if (!function_exists('forceBool')) {
    function forceBool($Value, $DefaultValue = false, $True = true, $False = false) {
        if (is_bool($Value)) {
            return $Value ? $True : $False;
        } elseif (is_numeric($Value)) {
            return $Value == 0 ? $False : $True;
        } elseif (is_string($Value)) {
            return strtolower($Value) == 'true' ? $True : $False;
        } else {
            return $DefaultValue;
        }
    }
}

if (!function_exists('forceSSL')) {
    /**
     * Checks the current url for SSL and redirects to an SSL version if not currently on it.
     *
     * Call at the beginning of any method you want forced to be in SSL.
     * Garden.AllowSSL must be true in order for this function to work.
     *
     * @deprecated
     */
    function forceSSL() {
        if (C('Garden.AllowSSL')) {
            if (Gdn::Request()->Scheme() != 'https') {
                Redirect(Gdn::Request()->Url('', true, true));
            }
        }
    }
}

if (!function_exists('forceNoSSL')) {
    /**
     * Checks the current url for SSL and redirects to SSL version if not currently on it.
     *
     * Call at the beginning of any method you want forced to be in SSL.
     * Garden.AllowSSL must be true in order for this function to work.
     *
     * @deprecated
     */
    function forceNoSSL() {
        if (Gdn::Request()->Scheme() != 'http') {
            Redirect(Gdn::Request()->Url('', true, false));
        }
    }
}

if (!function_exists('formatArrayAssignment')) {
    /**
     * Formats values to be saved as PHP arrays.
     *
     * @param array &$array The array to format.
     * @param string $prefix The prefix on the assignment for recursive calls.
     * @param mixed $value The value in the final assignment.
     * @deprecated
     * @todo Move this function to the configuration object.
     */
    function formatArrayAssignment(&$array, $prefix, $value) {
        if (is_array($value)) {
            // If $Value doesn't contain a key of "0" OR it does and it's value IS
            // an array, this should be treated as an associative array.
            $IsAssociativeArray = array_key_exists(0, $value) === false || is_array($value[0]) === true ? true : false;
            if ($IsAssociativeArray === true) {
                foreach ($value as $k => $v) {
                    formatArrayAssignment($array, $prefix."['$k']", $v);
                }
            } else {
                // If $Value is not an associative array, just write it like a simple array definition.
                $FormattedValue = array_map(array('Gdn_Format', 'ArrayValueForPhp'), $value);
                $array[] = $prefix .= " = array('".implode("', '", $FormattedValue)."');";
            }
        } elseif (is_int($value)) {
            $array[] = $prefix .= ' = '.$value.';';
        } elseif (is_bool($value)) {
            $array[] = $prefix .= ' = '.($value ? 'true' : 'false').';';
        } elseif (in_array($value, array('true', 'false'))) {
            $array[] = $prefix .= ' = '.($value == 'true' ? 'true' : 'false').';';
        } else {
            $array[] = $prefix .= ' = '.var_export($value, true).';';
        }
    }
}

if (!function_exists('formatDottedAssignment')) {
    /**
     * Formats values to be saved in dotted notation.
     *
     * @param array &$array The array to format.
     * @param string $prefix A prefix for recursive calls.
     * @param mixed $value The value to assign.
     * @deprecated
     * @todo Move this function to the configuration object.
     */
    function formatDottedAssignment(&$array, $prefix, $value) {
        if (is_array($value)) {
            // If $Value doesn't contain a key of "0" OR it does and it's value IS
            // an array, this should be treated as an associative array.
            $IsAssociativeArray = array_key_exists(0, $value) === false || is_array($value[0]) === true ? true : false;
            if ($IsAssociativeArray === true) {
                foreach ($value as $k => $v) {
                    formatDottedAssignment($array, "{$prefix}.{$k}", $v);
                }
            } else {
                // If $Value is not an associative array, just write it like a simple array definition.
                $FormattedValue = array_map(array('Gdn_Format', 'ArrayValueForPhp'), $value);
                $prefix .= "']";
                $array[] = $prefix .= " = array('".implode("', '", $FormattedValue)."');";
            }
        } else {
            $prefix .= "']";
            if (is_int($value)) {
                $array[] = $prefix .= ' = '.$value.';';
            } elseif (is_bool($value)) {
                $array[] = $prefix .= ' = '.($value ? 'true' : 'false').';';
            } elseif (in_array($value, array('true', 'false'))) {
                $array[] = $prefix .= ' = '.($value == 'true' ? 'true' : 'false').';';
            } else {
                $array[] = $prefix .= ' = '.var_export($value, true).';';
            }
        }
    }
}

if (!function_exists('getallheaders')) {
    /**
     * If PHP isn't running as an apache module, getallheaders doesn't exist in some systems.
     *
     * @return array Returns an array of the current HTTP headers.
     * @see https://github.com/vanilla/vanilla/issues/3
     */
    function getallheaders() {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

if (!function_exists('getAppCookie')) {
    /**
     * Get a cookie with the application prefix.
     *
     * @param string $Name
     * @param mixed $Default
     * @return string
     */
    function getAppCookie($Name, $Default = null) {
        $Px = C('Garden.Cookie.Name');
        return GetValue("$Px-$Name", $_COOKIE, $Default);
    }
}

if (!function_exists('getConnectionString')) {
    /**
     * Construct a PDO connection string.
     *
     * @param string $databaseName
     * @param string $hostName
     * @param string $serverType
     * @return string
     */
    function getConnectionString($databaseName, $hostName = 'localhost', $serverType = 'mysql') {
        $hostName = explode(':', $hostName);
        $Port = count($hostName) == 2 ? $hostName[1] : '';
        $hostName = $hostName[0];
        $String = $serverType.':host='.$hostName;
        if ($Port != '') {
            $String .= ';port='.$Port;
        }
        return $String .= ';dbname='.$databaseName;
    }
}

if (!function_exists('getIncomingValue')) {
    /**
     * Grab {@link $FieldName} from either the GET or POST collections.
     *
     * This function checks $_POST first.
     *
     * @deprecated Use the various methods on {@link Gdn::Request()}.
     */
    function getIncomingValue($FieldName, $Default = false) {
        if (array_key_exists($FieldName, $_POST) === true) {
            $Result = filter_input(INPUT_POST, $FieldName, FILTER_SANITIZE_STRING); //FILTER_REQUIRE_ARRAY);
        } elseif (array_key_exists($FieldName, $_GET) === true) {
            $Result = filter_input(INPUT_GET, $FieldName, FILTER_SANITIZE_STRING); //, FILTER_REQUIRE_ARRAY);
        } else {
            $Result = $Default;
        }
        return $Result;
    }
}

if (!function_exists('getMentions')) {
    /**
     * @param $String
     * @return array
     */
    function getMentions($String) {
        // Check for a custom mentions formatter and use it.
        $Formatter = Gdn::Factory('MentionsFormatter');
        if (is_object($Formatter)) {
            return $Formatter->GetMentions($String);
        }

        // This one grabs mentions that start at the beginning of $String
        preg_match_all(
            '/(?:^|[\s,\.>\(])@(\w{1,64})\b/i',
            $String,
            $Matches
        );
        if (count($Matches) > 1) {
            $Result = array_unique($Matches[1]);
            return $Result;
        }
        return array();
    }
}

if (!function_exists('getObject')) {
    /**
     * Get a value off of an object.
     *
     * @param string $Property The name of the property on the object.
     * @param object $Object The object that contains the value.
     * @param mixed $Default The default to return if the object doesn't contain the property.
     * @return mixed
     * @deprecated getObject() is deprecated. Use val() instead.
     */
    function getObject($Property, $Object, $Default) {
        trigger_error('GetObject() is deprecated. Use GetValue() instead.', E_USER_DEPRECATED);
        $Result = val($Property, $Object, $Default);
        return $Result;
    }
}

if (!function_exists('getPostValue')) {
    /**
     * Return the value for $FieldName from the $_POST collection.
     *
     * @deprecated
     */
    function getPostValue($FieldName, $Default = false) {
        return array_key_exists($FieldName, $_POST) ? $_POST[$FieldName] : $Default;
    }
}

if (!function_exists('getRecord')) {
    function getRecord($recordType, $id, $throw = false) {
        $Row = false;

        switch (strtolower($recordType)) {
            case 'discussion':
                $Model = new DiscussionModel();
                $Row = $Model->GetID($id);
                $Row->Url = DiscussionUrl($Row);
                $Row->ShareUrl = $Row->Url;
                if ($Row) {
                    return (array)$Row;
                }
                break;
            case 'comment':
                $Model = new CommentModel();
                $Row = $Model->GetID($id, DATASET_TYPE_ARRAY);
                if ($Row) {
                    $Row['Url'] = Url("/discussion/comment/$id#Comment_$id", true);

                    $Model = new DiscussionModel();
                    $Discussion = $Model->GetID($Row['DiscussionID']);
                    if ($Discussion) {
                        $Discussion->Url = DiscussionUrl($Discussion);
                        $Row['ShareUrl'] = $Discussion->Url;
                        $Row['Name'] = $Discussion->Name;
                        $Row['Discussion'] = (array)$Discussion;
                    }
                    return $Row;
                }
                break;
            case 'activity':
                $Model = new ActivityModel();
                $Row = $Model->GetID($id, DATASET_TYPE_ARRAY);
                if ($Row) {
                    $Row['Name'] = formatString($Row['HeadlineFormat'], $Row);
                    $Row['Body'] = $Row['Story'];
                    return $Row;
                }
                break;
            default:
                throw new Gdn_UserException(sprintf("I don't know what a %s is.", strtolower($recordType)));
        }

        if ($throw) {
            throw NotFoundException($recordType);
        } else {
            return false;
        }
    }

}

if (!function_exists('getValue')) {
    /**
     * Return the value from an associative array or an object.
     *
     * @param string $key The key or property name of the value.
     * @param mixed &$collection The array or object to search.
     * @param mixed $default The value to return if the key does not exist.
     * @param bool $remove Whether or not to remove the item from the collection.
     * @return mixed The value from the array or object.
     * @deprecated Deprecated since 2.2. Use {@link val()} instead.
     */
    function getValue($key, &$collection, $default = false, $remove = false) {
        $Result = $default;
        if (is_array($collection) && array_key_exists($key, $collection)) {
            $Result = $collection[$key];
            if ($remove) {
                unset($collection[$key]);
            }
        } elseif (is_object($collection) && property_exists($collection, $key)) {
            $Result = $collection->$key;
            if ($remove) {
                unset($collection->$key);
            }
        }

        return $Result;
    }
}

if (!function_exists('getValueR')) {
    /**
     * Return the value from an associative array or an object.
     *
     * This function differs from GetValue() in that $Key can be a string consisting of dot notation that will be used
     * to recursively traverse the collection.
     *
     * @param string $key The key or property name of the value.
     * @param mixed $collection The array or object to search.
     * @param mixed $default The value to return if the key does not exist.
     * @return mixed The value from the array or object.
     */
    function getValueR($key, $collection, $default = false) {
        $Path = explode('.', $key);

        $Value = $collection;
        for ($i = 0; $i < count($Path); ++$i) {
            $SubKey = $Path[$i];

            if (is_array($Value) && isset($Value[$SubKey])) {
                $Value = $Value[$SubKey];
            } elseif (is_object($Value) && isset($Value->$SubKey)) {
                $Value = $Value->$SubKey;
            } else {
                return $default;
            }
        }
        return $Value;
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
        $string = preg_replace_callback('~&#x([0-9a-fA-F]+);~i', "chr_utf8_callback", $string);
        $string = preg_replace('~&#([0-9]+);~e', 'chr_utf8("\\1")', $string);
        return $string;
    }

    /**
     * Callback helper.
     */
    function chr_utf8_callback($matches) {
        return chr_utf8(hexdec($matches[1]));
    }

    /**
     * Multi-byte chr(): Will turn a numeric argument into a UTF-8 string.
     *
     * @param mixed $num
     * @return string
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
        $Result = '';

        foreach ($array as $Key => $Value) {
            if (strlen($Result) > 0) {
                $Result .= $elementGlue;
            }

            $Result .= $Key.$keyGlue.$Value;
        }
        return $Result;
    }
}

if (!function_exists('inArrayI')) {
    /**
     * Case-insensitive version of php's native in_array function.
     */
    function inArrayI($Needle, $Haystack) {
        $Needle = strtolower($Needle);
        foreach ($Haystack as $Item) {
            if (strtolower($Item) == $Needle) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('inSubArray')) {
    /**
     * Loop through $Haystack looking for subarrays that contain $Needle.
     */
    function inSubArray($Needle, $Haystack) {
        foreach ($Haystack as $Key => $Val) {
            if (is_array($Val) && in_array($Needle, $Val)) {
                return true;
            }
        }
        return false;
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
        $type = C('Garden.Devices.'.ucfirst($type), $type);

        switch ($type) {
            case 'app':
            case 'mobile':
                $IsMobile = true;
                break;
            default:
                $IsMobile = false;
                break;
        }

        return $IsMobile;
    }
}

if (!function_exists('isSearchEngine')) {
    function isSearchEngine() {
        $Engines = array(
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
        );
        $HttpUserAgent = strtolower(GetValue('HTTP_USER_AGENT', $_SERVER, ''));
        if ($HttpUserAgent != '') {
            foreach ($Engines as $Engine) {
                if (strpos($HttpUserAgent, $Engine) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('isTimestamp')) {
    function isTimestamp($Stamp) {
        return checkdate(
            @date("m", $Stamp),
            @date("d", $Stamp),
            @date("Y", $Stamp)
        );
    }
}

if (!function_exists('isUrl')) {
    /**
     * Determine whether or not a string is a url in the form http://, https://, or //.
     *
     * @param string $Str The string to check.
     * @return bool
     * @since 2.1
     */
    function isUrl($Str) {
        if (!$Str) {
            return false;
        }
        if (substr($Str, 0, 2) == '//') {
            return true;
        }
        if (preg_match('`^https?://`i', $Str)) {
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
     */
    function isWritable($Path) {
        if ($Path{strlen($Path) - 1} == DS) {
            // Recursively return a temporary file path
            return IsWritable($Path.uniqid(mt_rand()).'.tmp');
        } elseif (is_dir($Path)) {
            return IsWritable($Path.'/'.uniqid(mt_rand()).'.tmp');
        }
        // Check tmp file for read/write capabilities
        $KeepPath = file_exists($Path);
        $File = @fopen($Path, 'a');
        if ($File === false) {
            return false;
        }

        fclose($File);

        if (!$KeepPath) {
            unlink($Path);
        }

        return true;
    }
}

if (!function_exists('markString')) {
    /**
     * Wrap occurrences of {@link $Needle} in {@link $Haystack} with `<mark>` tags.
     *
     * This method explodes {@link $Needle} on spaces and returns {@link $Haystack} with replacements.
     *
     * @param string|array $Needle The strings to search for in {@link $Haystack}.
     * @param string $Haystack The string to search for replacements.
     * @return string Returns a marked version of {@link $Haystack}.
     */
    function markString($Needle, $Haystack) {
        if (!$Needle) {
            return $Haystack;
        }
        if (!is_array($Needle)) {
            $Needle = explode(' ', $Needle);
        }

        foreach ($Needle as $n) {
            if (strlen($n) <= 2 && preg_match('`^\w+$`', $n)) {
                $word = '\b';
            } else {
                $word = '';
            }

            $Haystack = preg_replace(
                '#(?!<.*?)('.$word.preg_quote($n, '#').$word.')(?![^<>]*?>)#i',
                '<mark>\1</mark>',
                $Haystack
            );
        }
        return $Haystack;
    }
}

if (!function_exists('joinRecords')) {
    /**
     * Join external records to an array.
     *
     * @param array &$Data The data to join.
     * In order to join records each row must have the a RecordType and RecordID column.
     * @param string $Column The name of the column to put the record in.
     * If this is blank then the record will be merged into the row.
     * @param bool $Unset Whether or not to unset rows that don't have a record.
     * @since 2.3
     */
    function joinRecords(&$Data, $Column = '', $Unset = false) {
        $IDs = array();
        $AllowedCats = DiscussionModel::CategoryPermissions();

        if ($AllowedCats === false) {
            // This user does not have permission to view anything.
            $Data = array();
            return;
        }

        // Gather all of the ids to fetch.
        foreach ($Data as &$Row) {
            if (!$Row['RecordType']) {
                continue;
            }

            $RecordType = ucfirst(StringEndsWith($Row['RecordType'], '-Total', true, true));
            $Row['RecordType'] = $RecordType;
            $ID = $Row['RecordID'];
            $IDs[$RecordType][$ID] = $ID;
        }

        // Fetch all of the data in turn.
        $JoinData = array();
        foreach ($IDs as $RecordType => $RecordIDs) {
            if ($RecordType == 'Comment') {
                Gdn::SQL()->Select('d.Name, d.CategoryID')->Join('Discussion d', 'd.DiscussionID = r.DiscussionID');
            }

            $Rows = Gdn::SQL()
                ->Select('r.*')
                ->WhereIn($RecordType.'ID', array_values($RecordIDs))
                ->Get($RecordType.' r')
                ->ResultArray();

            $JoinData[$RecordType] = Gdn_DataSet::Index($Rows, array($RecordType.'ID'));
        }

        // Join the rows.
        $Unsets = array();
        foreach ($Data as $Index => &$Row) {
            $RecordType = $Row['RecordType'];
            $ID = $Row['RecordID'];

            if (!isset($JoinData[$RecordType][$ID])) {
                if ($Unset) {
                    $Unsets[] = $Index;
                }
                continue; // orphaned?
            }

            $Record = $JoinData[$RecordType][$ID];

            if ($AllowedCats !== true) {
                // Check to see if the user has permission to view this record.
                $CategoryID = GetValue('CategoryID', $Record, -1);
                if (!in_array($CategoryID, $AllowedCats)) {
                    $Unsets[] = $Index;
                    continue;
                }
            }

            switch ($RecordType) {
                case 'Discussion':
                    $Url = DiscussionUrl($Record, '', '/').'#latest';
                    break;
                case 'Comment':
                    $Url = CommentUrl($Record, '/');
                    $Record['Name'] = sprintf(T('Re: %s'), $Record['Name']);
                    break;
                default:
                    $Url = '';
            }
            $Record['Url'] = $Url;

            if ($Column) {
                $Row[$Column] = $Record;
            } else {
                $Row = array_merge($Row, $Record);
            }
        }

        foreach ($Unsets as $Index) {
            unset($Data[$Index]);
        }

        // Join the users.
        Gdn::UserModel()->JoinUsers($Data, array('InsertUserID'));

        if (!empty($Unsets)) {
            $Data = array_values($Data);
        }
    }

}

if (!function_exists('mergeArrays')) {
    /**
     * Merge two associative arrays into a single array.
     *
     * @param array &$Dominant The "dominant" array, who's values will be chosen over those of the subservient.
     * @param array $Subservient The "subservient" array, who's values will be disregarded over those of the dominant.
     * @deprecated Use {@link array_merge_recursive()}
     */
    function mergeArrays(&$Dominant, $Subservient) {
        foreach ($Subservient as $Key => $Value) {
            if (!array_key_exists($Key, $Dominant)) {
                // Add the key from the subservient array if it doesn't exist in the
                // dominant array.
                $Dominant[$Key] = $Value;
            } else {
                // If the key already exists in the dominant array, only continue if
                // both values are also arrays - because we don't want to overwrite
                // values in the dominant array with ones from the subservient array.
                if (is_array($Dominant[$Key]) && is_array($Value)) {
                    $Dominant[$Key] = MergeArrays($Dominant[$Key], $Value);
                }
            }
        }
        return $Dominant;
    }
}

if (!function_exists('now')) {
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
     */
    function offsetLimit($offsetOrPage = '', $limitOrPageSize = '', $throw = false) {
        $limitOrPageSize = is_numeric($limitOrPageSize) ? (int)$limitOrPageSize : 50;

        if (is_numeric($offsetOrPage)) {
            $Offset = (int)$offsetOrPage;
            $Limit = $limitOrPageSize;
        } elseif (preg_match('/p(\d+)/i', $offsetOrPage, $Matches)) {
            $Page = $Matches[1];
            $Offset = $limitOrPageSize * ($Page - 1);
            $Limit = $limitOrPageSize;
        } elseif (preg_match('/(\d+)-(\d+)/', $offsetOrPage, $Matches)) {
            $Offset = $Matches[1] - 1;
            $Limit = $Matches[2] - $Matches[1] + 1;
        } elseif (preg_match('/(\d+)lim(\d*)/i', $offsetOrPage, $Matches)) {
            $Offset = (int)$Matches[1];
            $Limit = (int)$Matches[2];
            if (!is_numeric($Limit)) {
                $Limit = $limitOrPageSize;
            }
        } elseif (preg_match('/(\d+)lin(\d*)/i', $offsetOrPage, $Matches)) {
            $Offset = $Matches[1] - 1;
            $Limit = (int)$Matches[2];
            if (!is_numeric($Limit)) {
                $Limit = $limitOrPageSize;
            }
        } elseif ($offsetOrPage && $throw) {
            // Some unrecognized page string was passed.
            throw NotFoundException();
        } else {
            $Offset = 0;
            $Limit = $limitOrPageSize;
        }

        if ($Offset < 0) {
            $Offset = 0;
        }
        if ($Limit < 0) {
            $Limit = 50;
        }

        return array($Offset, $Limit);
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
        $Result = floor($offset / $limit) + 1;

        if ($urlParam !== false && !$first && $Result == 1) {
            $Result = '';
        } elseif ($urlParam === true) {
            $Result = 'p'.$Result;
        } elseif (is_string($urlParam)) {
            $Result = $urlParam.$Result;
        }

        return $Result;
    }
}

if (!function_exists('parse_ini_string')) {
    /**
     * The parse_ini_string function is not supported until PHP 5.3.0, and we currently support PHP 5.2.0.
     */
    function parse_ini_string($Ini) {
        $Lines = explode("\n", $Ini);
        $Result = array();
        foreach ($Lines as $Line) {
            $Parts = explode('=', $Line, 2);
            if (count($Parts) == 1) {
                $Result[trim($Parts[0])] = '';
            } elseif (count($Parts) >= 2) {
                $Result[trim($Parts[0])] = trim($Parts[1]);
            }
        }
        return $Result;
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
        if ($RecordType = val('RecordType', $row)) {
            return array($RecordType, val('RecordID', $row));
        } elseif ($CommentID = val('CommentID', $row)) {
            return array('Comment', $CommentID);
        } elseif ($DiscussionID = val('DiscussionID', $row)) {
            return array('Discussion', $DiscussionID);
        } elseif ($ActivityID = val('ActivityID', $row)) {
            return array('Activity', $ActivityID);
        } else {
            return array(null, null);
        }
    }
}

if (!function_exists('touchConfig')) {
    /**
     * Make sure the config has a setting.
     *
     * This function is useful to call in the setup/structure of plugins to make sure they have some default config set.
     *
     * @param string|array $Name The name of the config key or an array of config key value pairs.
     * @param mixed $Default The default value to set in the config.
     */
    function touchConfig($Name, $Default = null) {
        if (!is_array($Name)) {
            $Name = array($Name => $Default);
        }

        $Save = array();
        foreach ($Name as $Key => $Value) {
            if (!C($Key)) {
                $Save[$Key] = $Value;
            }
        }

        if (!empty($Save)) {
            SaveToConfig($Save);
        }
    }
}

if (!function_exists('write_ini_string')) {
    function write_ini_string($Data) {
        $Flat = array();
        foreach ($Data as $Topic => $Settings) {
            if (is_array($Settings)) {
                $Flat[] = "[{$Topic}]";
                foreach ($Settings as $SettingsKey => $SettingsVal) {
                    $Flat[] = "{$SettingsKey} = ".(is_numeric($SettingsVal) ? $SettingsVal : '"'.$SettingsVal.'"');
                }
                $Flat[] = "";
            } else {
                $Flat[] = "{$Topic} = ".(is_numeric($Settings) ? $Settings : '"'.$Settings.'"');
            }
        }
        return implode("\n", $Flat);
    }
}

if (!function_exists('write_ini_file')) {
    function write_ini_file($File, $Data) {
        $String = write_ini_string($Data);
        Gdn_FileSystem::SaveFile($File, $String);
    }
}

if (!function_exists('signInPopup')) {
    /**
     * Returns a boolean value indicating if sign in windows should be "popped" into modal in-page popups.
     *
     * @return bool Returns true if signin popups are used.
     */
    function signInPopup() {
        return C('Garden.SignIn.Popup');
    }
}

if (!function_exists('parseUrl')) {
    /**
     * A Vanilla wrapper for php's parse_url, which doesn't always return values for every url part.
     *
     * @param string $Url The url to parse.
     * @param int $Component Use PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PORT, PHP_URL_USER, PHP_URL_PASS, PHP_URL_PATH,
     * PHP_URL_QUERY or PHP_URL_FRAGMENT to retrieve just a specific url component.
     * @deprecated
     */
    function parseUrl($Url, $Component = -1) {
        // Retrieve all the parts
        $PHP_URL_SCHEME = @parse_url($Url, PHP_URL_SCHEME);
        $PHP_URL_HOST = @parse_url($Url, PHP_URL_HOST);
        $PHP_URL_PORT = @parse_url($Url, PHP_URL_PORT);
        $PHP_URL_USER = @parse_url($Url, PHP_URL_USER);
        $PHP_URL_PASS = @parse_url($Url, PHP_URL_PASS);
        $PHP_URL_PATH = @parse_url($Url, PHP_URL_PATH);
        $PHP_URL_QUERY = @parse_url($Url, PHP_URL_QUERY);
        $PHP_URL_FRAGMENT = @parse_url($Url, PHP_URL_FRAGMENT);

        // Build a cleaned up array to return
        $Parts = array(
            'scheme' => $PHP_URL_SCHEME == null ? 'http' : $PHP_URL_SCHEME,
            'host' => $PHP_URL_HOST == null ? '' : $PHP_URL_HOST,
            'port' => $PHP_URL_PORT == null ? $PHP_URL_SCHEME == 'https' ? '443' : '80' : $PHP_URL_PORT,
            'user' => $PHP_URL_USER == null ? '' : $PHP_URL_USER,
            'pass' => $PHP_URL_PASS == null ? '' : $PHP_URL_PASS,
            'path' => $PHP_URL_PATH == null ? '' : $PHP_URL_PATH,
            'query' => $PHP_URL_QUERY == null ? '' : $PHP_URL_QUERY,
            'fragment' => $PHP_URL_FRAGMENT == null ? '' : $PHP_URL_FRAGMENT
        );

        // Return
        switch ($Component) {
            case PHP_URL_SCHEME:
                return $Parts['scheme'];
            case PHP_URL_HOST:
                return $Parts['host'];
            case PHP_URL_PORT:
                return $Parts['port'];
            case PHP_URL_USER:
                return $Parts['user'];
            case PHP_URL_PASS:
                return $Parts['pass'];
            case PHP_URL_PATH:
                return $Parts['path'];
            case PHP_URL_QUERY:
                return $Parts['query'];
            case PHP_URL_FRAGMENT:
                return $Parts['fragment'];
            default:
                return $Parts;
        }
    }
}
if (!function_exists('buildUrl')) {
    /**
     * Complementary to {@link ParseUrl()}, this function puts the pieces back together and returns a valid url.
     *
     * @param array $Parts The ParseUrl array to build.
     */
    function buildUrl($Parts) {
        // Full format: http://user:pass@hostname:port/path?querystring#fragment
        $Return = $Parts['scheme'].'://';
        if ($Parts['user'] != '' || $Parts['pass'] != '') {
            $Return .= $Parts['user'].':'.$Parts['pass'].'@';
        }

        $Return .= $Parts['host'];
        // Custom port?
        if ($Parts['port'] == '443' && $Parts['scheme'] == 'https') {
        } elseif ($Parts['port'] == '80' && $Parts['scheme'] == 'http') {
        } elseif ($Parts['port'] != '') {
            $Return .= ':'.$Parts['port'];
        }

        if ($Parts['path'] != '') {
            if (substr($Parts['path'], 0, 1) != '/') {
                $Return .= '/';
            }
            $Return .= $Parts['path'];
        }
        if ($Parts['query'] != '') {
            $Return .= '?'.$Parts['query'];
        }

        if ($Parts['fragment'] != '') {
            $Return .= '#'.$Parts['fragment'];
        }

        return $Return;
    }
}

if (!function_exists('prefixString')) {
    /**
     * Takes a string, and prefixes it with $Prefix unless it is already prefixed that way.
     *
     * @param string $Prefix The prefix to use.
     * @param string $String The string to be prefixed.
     */
    function prefixString($Prefix, $String) {
        if (substr($String, 0, strlen($Prefix)) != $Prefix) {
            $String = $Prefix.$String;
        }
        return $String;
    }
}

if (!function_exists('prepareArray')) {
    /**
     * Makes sure that the key in question exists and is of the specified type,
     * by default also an array.
     *
     * @param string $Key Key to prepare
     * @param array $Array Array to repare
     * @param string $PrepareType Optional,
     * @deprecated
     */
    function prepareArray($Key, &$Array, $PrepareType = 'array') {
        if (!array_key_exists($Key, $Array)) {
            $Array[$Key] = null;
        }

        switch ($PrepareType) {
            case 'array':
                if (!is_array($Array[$Key])) {
                    $Array[$Key] = array();
                }
                break;

            case 'integer':
                if (!is_integer($Array[$Key])) {
                    $Array[$Key] = 0;
                }
                break;

            case 'float':
                if (!is_float($Array[$Key])) {
                    $Array[$Key] = 0.0;
                }
                break;

            case 'null':
                if (!is_null($Array[$Key])) {
                    $Array[$Key] = null;
                }
                break;

            case 'string':
                if (!is_string($Array[$Key])) {
                    $Array[$Key] = '';
                }
                break;
        }
    }
}

if (!function_exists('proxyHead')) {
    function proxyHead($Url, $Headers = null, $Timeout = false, $FollowRedirects = false) {
        if (is_null($Headers)) {
            $Headers = array();
        }

        $OriginalHeaders = $Headers;
        $OriginalTimeout = $Timeout;
        if (!$Timeout) {
            $Timeout = C('Garden.SocketTimeout', 1.0);
        }

        $UrlParts = parse_url($Url);
        $Scheme = val('scheme', $UrlParts, 'http');
        $Host = val('host', $UrlParts, '');
        $Port = val('port', $UrlParts, '80');
        $Path = val('path', $UrlParts, '');
        $Query = val('query', $UrlParts, '');

        // Get the cookie.
        $Cookie = '';
        $EncodeCookies = C('Garden.Cookie.Urlencode', true);

        foreach ($_COOKIE as $Key => $Value) {
            if (strncasecmp($Key, 'XDEBUG', 6) == 0) {
                continue;
            }

            if (strlen($Cookie) > 0) {
                $Cookie .= '; ';
            }

            $EValue = ($EncodeCookies) ? urlencode($Value) : $Value;
            $Cookie .= "{$Key}={$EValue}";
        }
        $Cookie = array('Cookie' => $Cookie);

        $Response = '';
        if (function_exists('curl_init')) {
            //$Url = $Scheme.'://'.$Host.$Path;
            $Handler = curl_init();
            curl_setopt($Handler, CURLOPT_TIMEOUT, $Timeout);
            curl_setopt($Handler, CURLOPT_URL, $Url);
            curl_setopt($Handler, CURLOPT_PORT, $Port);
            curl_setopt($Handler, CURLOPT_HEADER, 1);
            curl_setopt($Handler, CURLOPT_NOBODY, 1);
            curl_setopt($Handler, CURLOPT_USERAGENT, ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'));
            curl_setopt($Handler, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($Handler, CURLOPT_HTTPHEADER, $Headers);

            if (strlen($Cookie['Cookie'])) {
                curl_setopt($Handler, CURLOPT_COOKIE, $Cookie['Cookie']);
            }

            $Response = curl_exec($Handler);
            if ($Response == false) {
                $Response = curl_error($Handler);
            }

            curl_close($Handler);
        } elseif (function_exists('fsockopen')) {
            $Referer = Gdn::Request()->WebRoot();

            // Make the request
            $Pointer = @fsockopen($Host, $Port, $ErrorNumber, $Error, $Timeout);
            if (!$Pointer) {
                throw new Exception(
                    sprintf(
                        T('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s'),
                        $Url,
                        $ErrorNumber,
                        $Error
                    )
                );
            }

            $Request = "HEAD $Path?$Query HTTP/1.1\r\n";

            $HostHeader = $Host.($Port != 80) ? ":{$Port}" : '';
            $Header = array(
                'Host' => $HostHeader,
                'User-Agent' => ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'),
                'Accept' => '*/*',
                'Accept-Charset' => 'utf-8',
                'Referer' => $Referer,
                'Connection' => 'close'
            );

            if (strlen($Cookie['Cookie'])) {
                $Header = array_merge($Header, $Cookie);
            }

            $Header = array_merge($Header, $Headers);

            $HeaderString = "";
            foreach ($Header as $HeaderName => $HeaderValue) {
                $HeaderString .= "{$HeaderName}: {$HeaderValue}\r\n";
            }
            $HeaderString .= "\r\n";

            // Send the headers and get the response
            fputs($Pointer, $Request);
            fputs($Pointer, $HeaderString);
            while ($Line = fread($Pointer, 4096)) {
                $Response .= $Line;
            }
            @fclose($Pointer);
            $Response = trim($Response);

        } else {
            throw new Exception(T('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.'));
        }

        $ResponseLines = explode("\n", trim($Response));
        $Status = array_shift($ResponseLines);
        $Response = array();
        $Response['HTTP'] = trim($Status);

        /* get the numeric status code.
       * - trim off excess edge whitespace,
       * - split on spaces,
       * - get the 2nd element (as a single element array),
       * - pop the first (only) element off it...
       * - return that.
       */
        $Response['StatusCode'] = array_pop(array_slice(explode(' ', trim($Status)), 1, 1));
        foreach ($ResponseLines as $Line) {
            $Line = explode(':', trim($Line));
            $Key = trim(array_shift($Line));
            $Value = trim(implode(':', $Line));
            $Response[$Key] = $Value;
        }

        if ($FollowRedirects) {
            $Code = GetValue('StatusCode', $Response, 200);
            if (in_array($Code, array(301, 302))) {
                if (array_key_exists('Location', $Response)) {
                    $Location = GetValue('Location', $Response);
                    return ProxyHead($Location, $OriginalHeaders, $OriginalTimeout, $FollowRedirects);
                }
            }
        }

        return $Response;
    }

}

if (!function_exists('proxyRequest')) {
    /**
     * Use curl or fsock to make a request to a remote server.
     *
     * @param string $Url The full url to the page being requested (including http://).
     * @param integer $Timeout How long to allow for this request.
     * Default Garden.SocketTimeout or 1, 0 to never timeout.
     * @param boolean $FollowRedirects Whether or not to follow 301 and 302 redirects. Defaults false.
     * @return string Returns the response body.
     */
    function proxyRequest($Url, $Timeout = false, $FollowRedirects = false) {
        $OriginalTimeout = $Timeout;
        if ($Timeout === false) {
            $Timeout = C('Garden.SocketTimeout', 1.0);
        }

        $UrlParts = parse_url($Url);
        $Scheme = GetValue('scheme', $UrlParts, 'http');
        $Host = GetValue('host', $UrlParts, '');
        $Port = GetValue('port', $UrlParts, $Scheme == 'https' ? '443' : '80');
        $Path = GetValue('path', $UrlParts, '');
        $Query = GetValue('query', $UrlParts, '');
        // Get the cookie.
        $Cookie = '';
        $EncodeCookies = C('Garden.Cookie.Urlencode', true);

        foreach ($_COOKIE as $Key => $Value) {
            if (strncasecmp($Key, 'XDEBUG', 6) == 0) {
                continue;
            }

            if (strlen($Cookie) > 0) {
                $Cookie .= '; ';
            }

            $EValue = ($EncodeCookies) ? urlencode($Value) : $Value;
            $Cookie .= "{$Key}={$EValue}";
        }
        $Response = '';
        if (function_exists('curl_init')) {
            //$Url = $Scheme.'://'.$Host.$Path;
            $Handler = curl_init();
            curl_setopt($Handler, CURLOPT_URL, $Url);
            curl_setopt($Handler, CURLOPT_PORT, $Port);
            curl_setopt($Handler, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($Handler, CURLOPT_HEADER, 1);
            curl_setopt($Handler, CURLOPT_USERAGENT, ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'));
            curl_setopt($Handler, CURLOPT_RETURNTRANSFER, 1);

            if ($Cookie != '') {
                curl_setopt($Handler, CURLOPT_COOKIE, $Cookie);
            }

            if ($Timeout > 0) {
                curl_setopt($Handler, CURLOPT_TIMEOUT, $Timeout);
            }

            // TIM @ 2010-06-28: Commented this out because it was forcing all requests with parameters to be POST.
            //Same for the $Url above
            //
            //if ($Query != '') {
            //   curl_setopt($Handler, CURLOPT_POST, 1);
            //   curl_setopt($Handler, CURLOPT_POSTFIELDS, $Query);
            //}
            $Response = curl_exec($Handler);
            $Success = true;
            if ($Response == false) {
                $Success = false;
                $Response = '';
                throw new Exception(curl_error($Handler));
            }

            curl_close($Handler);
        } elseif (function_exists('fsockopen')) {
            $Referer = Gdn_Url::WebRoot(true);

            // Make the request
            $Pointer = @fsockopen($Host, $Port, $ErrorNumber, $Error, $Timeout);
            if (!$Pointer) {
                throw new Exception(
                    sprintf(
                        T('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s'),
                        $Url,
                        $ErrorNumber,
                        $Error
                    )
                );
            }

            stream_set_timeout($Pointer, $Timeout);
            if (strlen($Cookie) > 0) {
                $Cookie = "Cookie: $Cookie\r\n";
            }

            $HostHeader = $Host.(($Port != 80) ? ":{$Port}" : '');
            $Header = "GET $Path?$Query HTTP/1.1\r\n"
                ."Host: {$HostHeader}\r\n"
                // If you've got basic authentication enabled for the app, you're going to need to explicitly define
                // the user/pass for this fsock call.
                // "Authorization: Basic ". base64_encode ("username:password")."\r\n" .
                ."User-Agent: ".ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0')."\r\n"
                ."Accept: */*\r\n"
                ."Accept-Charset: utf-8;\r\n"
                ."Referer: {$Referer}\r\n"
                ."Connection: close\r\n";

            if ($Cookie != '') {
                $Header .= $Cookie;
            }

            $Header .= "\r\n";

            // Send the headers and get the response
            fputs($Pointer, $Header);
            while ($Line = fread($Pointer, 4096)) {
                $Response .= $Line;
            }
            @fclose($Pointer);
            $Bytes = strlen($Response);
            $Response = trim($Response);
            $Success = true;

            $StreamInfo = stream_get_meta_data($Pointer);
            if (GetValue('timed_out', $StreamInfo, false) === true) {
                $Success = false;
                $Response = "Operation timed out after {$Timeout} seconds with {$Bytes} bytes received.";
            }
        } else {
            throw new Exception(T('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.'));
        }

        if (!$Success) {
            return $Response;
        }

        $ResponseHeaderData = trim(substr($Response, 0, strpos($Response, "\r\n\r\n")));
        $Response = trim(substr($Response, strpos($Response, "\r\n\r\n") + 4));

        $ResponseHeaderLines = explode("\n", trim($ResponseHeaderData));
        $Status = array_shift($ResponseHeaderLines);
        $ResponseHeaders = array();
        $ResponseHeaders['HTTP'] = trim($Status);

        /* get the numeric status code.
       * - trim off excess edge whitespace,
       * - split on spaces,
       * - get the 2nd element (as a single element array),
       * - pop the first (only) element off it...
       * - return that.
       */
        $Status = trim($Status);
        $Status = explode(' ', $Status);
        $Status = array_slice($Status, 1, 1);
        $Status = array_pop($Status);
        $ResponseHeaders['StatusCode'] = $Status;
        foreach ($ResponseHeaderLines as $Line) {
            $Line = explode(':', trim($Line));
            $Key = trim(array_shift($Line));
            $Value = trim(implode(':', $Line));
            $ResponseHeaders[$Key] = $Value;
        }

        if ($FollowRedirects) {
            $Code = GetValue('StatusCode', $ResponseHeaders, 200);
            if (in_array($Code, array(301, 302))) {
                if (array_key_exists('Location', $ResponseHeaders)) {
                    $Location = absoluteSource(GetValue('Location', $ResponseHeaders), $Url);
                    return ProxyRequest($Location, $OriginalTimeout, $FollowRedirects);
                }
            }
        }

        return $Response;
    }
}

if (!function_exists('randomString')) {
    function randomString($Length, $Characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
        $CharLen = strlen($Characters);
        $String = '';
        for ($i = 0; $i < $Length; ++$i) {
            $Offset = mt_rand() % $CharLen;
            $String .= substr($Characters, $Offset, 1);
        }
        return $String;
    }
}

if (!function_exists('betterRandomString')) {
    /**
     * Generate a random string of characters with additional character options that can be cryptographically strong.
     *
     * This function attempts to use {@link openssl_random_pseudo_bytes()} to generate its randomness.
     * If that function does not exists then it just uses mt_rand().
     *
     * @param int $Length The length of the string.
     * @param string $CharacterOptions Character sets that are allowed in the string.
     * This is a string made up of the following characters.
     *  - A: uppercase characters
     *  - a: lowercase characters
     *  - 0: digits
     *  - !: basic punctuation (~!@#$^&*_+-)
     * @return string Returns the random string for the given arguments.
     */
    function betterRandomString($Length, $CharacterOptions = 'A0') {
        $CharacterClasses = array(
            'A' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'a' => 'abcdefghijklmnopqrstuvwxyz',
            '0' => '0123456789',
            '!' => '~!@#$^&*_+-'
        );

        $Characters = '';
        for ($i = 0; $i < strlen($CharacterOptions); $i++) {
            $Characters .= GetValue($CharacterOptions{$i}, $CharacterClasses);
        }

        $CharLen = strlen($Characters);
        $String = '';

        if (function_exists('openssl_random_pseudo_bytes')) {
            $random_chars = unpack('C*', openssl_random_pseudo_bytes($Length));
            foreach ($random_chars as $c) {
                $Offset = (int)$c % $CharLen;
                $String .= substr($Characters, $Offset, 1);
            }
        } else {
            for ($i = 0; $i < $Length; ++$i) {
                $Offset = mt_rand() % $CharLen;
                $String .= substr($Characters, $Offset, 1);
            }
        }
        return $String;
    }
}

if (!function_exists('redirect')) {
    function redirect($Destination = false, $StatusCode = null) {
        if (!$Destination) {
            $Destination = '';
        }

//      if (Debug() && $Trace = Trace()) {
//         Trace("Redirecting to $Destination");
//         return;
//      }

        // Close any db connections before exit
        $Database = Gdn::Database();
        if ($Database instanceof Gdn_Database) {
            $Database->CloseConnection();
        }
        // Clear out any previously sent content
        @ob_end_clean();

        // assign status code
        $SendCode = (is_null($StatusCode)) ? 302 : $StatusCode;
        // re-assign the location header
        safeHeader("Location: ".Url($Destination), true, $SendCode);
        // Exit
        exit();
    }
}

if (!function_exists('redirectUrl')) {
    /**
     * Redirect to a specific url that can be outside of the site.
     *
     * @param string $url The url to redirect to.
     * @param int $code The http status code.
     */
    function redirectUrl($url, $code = 302) {
        if (!$url) {
            $url = Url('', true);
        }

        // Close any db connections before exit
        $Database = Gdn::Database();
        $Database->CloseConnection();
        // Clear out any previously sent content
        @ob_end_clean();

        if (!in_array($code, array(301, 302))) {
            $code = 302;
        }

        safeHeader("Location: ".$url, true, $code);

        exit();
    }
}

if (!function_exists('reflectArgs')) {
    /**
     * Reflect the arguments on a callback and returns them as an associative array.
     * @param callback $Callback A callback to the function.
     * @param array $Args1 An array of arguments.
     * @param array $Args2 An optional other array of arguments.
     * @return array The arguments in an associative array, in order ready to be passed to call_user_func_array().
     */
    function reflectArgs($Callback, $Args1, $Args2 = null) {
        if (is_string($Callback) && !function_exists($Callback)) {
            throw new Exception("Function $Callback does not exist");
        }

        if (is_array($Callback) && !method_exists($Callback[0], $Callback[1])) {
            throw new Exception("Method {$Callback[1]} does not exist.");
        }

        if ($Args2 !== null) {
            $Args1 = array_merge($Args2, $Args1);
        }
        $Args1 = array_change_key_case($Args1);

        if (is_string($Callback)) {
            $Meth = new ReflectionFunction($Callback);
            $MethName = $Meth;
        } else {
            $Meth = new ReflectionMethod($Callback[0], $Callback[1]);
            if (is_string($Callback[0])) {
                $MethName = $Callback[0].'::'.$Meth->getName();
            } else {
                $MethName = get_class($Callback[0]).'->'.$Meth->getName();
            }
        }

        $MethArgs = $Meth->getParameters();

        $Args = array();
        $MissingArgs = array();

        // Set all of the parameters.
        foreach ($MethArgs as $Index => $MethParam) {
            $ParamName = $MethParam->getName();
            $ParamNameL = strtolower($ParamName);

            if (isset($Args1[$ParamNameL])) {
                $ParamValue = $Args1[$ParamNameL];
            } elseif (isset($Args1[$Index])) {
                $ParamValue = $Args1[$Index];
            } elseif ($MethParam->isDefaultValueAvailable()) {
                $ParamValue = $MethParam->getDefaultValue();
            } else {
                $ParamValue = null;
                $MissingArgs[] = '$'.$ParamName;
            }

            $Args[$ParamName] = $ParamValue;
        }

        // Add optional parameters so that methods that use get_func_args() will still work.
        for ($Index = count($Args); array_key_exists($Index, $Args1); $Index++) {
            $Args[$Index] = $Args1[$Index];
        }

        if (count($MissingArgs) > 0) {
            trigger_error("$MethName() expects the following parameters: ".implode(', ', $MissingArgs).'.', E_USER_NOTICE);
        }

        return $Args;
    }
}

if (!function_exists('remoteIP')) {
    function remoteIP() {
        return Gdn::Request()->IpAddress();
    }
}

if (!function_exists('removeFromConfig')) {
    function removeFromConfig($Name, $Options = array()) {
        Gdn::Config()->RemoveFromConfig($Name, $Options);
    }
}

// Functions relating to data/variable types and type casting
if (!function_exists('removeKeyFromArray')) {
    /**
     * Remove a value from an array at a certain key.
     *
     * @param array $Array
     * @param string|int $Key
     * @return mixed
     * @deprecated Use unset() instead.
     * @todo Remove this function.
     */
    function removeKeyFromArray($Array, $Key) {
        if (!is_array($Key)) {
            $Key = array($Key);
        }

        $Count = count($Key);
        for ($i = 0; $i < $Count; $i++) {
            $KeyIndex = array_keys(array_keys($Array), $Key[$i]);
            if (count($KeyIndex) > 0) {
                array_splice($Array, $KeyIndex[0], 1);
            }
        }
        return $Array;
    }
}

if (!function_exists('removeKeysFromNestedArray')) {
    function removeKeysFromNestedArray($Array, $Matches) {
        if (is_array($Array)) {
            foreach ($Array as $Key => $Value) {
                $IsMatch = false;
                foreach ($Matches as $Match) {
                    if (StringEndsWith($Key, $Match)) {
                        unset($Array[$Key]);
                        $IsMatch = true;
                    }
                }
                if (!$IsMatch && (is_array($Value) || is_object($Value))) {
                    $Array[$Key] = RemoveKeysFromNestedArray($Value, $Matches);
                }
            }
        } elseif (is_object($Array)) {
            $Arr = get_object_vars($Array);
            foreach ($Arr as $Key => $Value) {
                $IsMatch = false;
                foreach ($Matches as $Match) {
                    if (StringEndsWith($Key, $Match)) {
                        unset($Array->$Key);
                        $IsMatch = true;
                    }
                }
                if (!$IsMatch && (is_array($Value) || is_object($Value))) {
                    $Array->$Key = RemoveKeysFromNestedArray($Value, $Matches);
                }
            }
        }
        return $Array;
    }
}

if (!function_exists('removeQuoteSlashes')) {
    function removeQuoteSlashes($String) {
        return str_replace("\\\"", '"', $String);
    }
}

if (!function_exists('removeValueFromArray')) {
    function removeValueFromArray(&$Array, $Value) {
        foreach ($Array as $key => $val) {
            if ($val == $Value) {
                unset($Array[$key]);
                break;
            }
        }
    }
}

if (!function_exists('safeGlob')) {
    function safeGlob($Pattern, $Extensions = array()) {
        $Result = glob($Pattern);
        if (!is_array($Result)) {
            $Result = array();
        }

        // Check against allowed extensions.
        if (count($Extensions) > 0) {
            foreach ($Result as $Index => $Path) {
                if (!$Path) {
                    continue;
                }
                if (!in_array(strtolower(pathinfo($Path, PATHINFO_EXTENSION)), $Extensions)) {
                    unset($Result[$Index]);
                }
            }
        }

        return $Result;
    }
}

if (!function_exists('safeImage')) {
    /**
     * Examines the provided url & checks to see if there is a valid image on the other side. Optionally you can specify minimum dimensions.
     *
     * @param string $ImageUrl Full url (including http) of the image to examine.
     * @param int $MinHeight Minimum height (in pixels) of image. 0 means any height.
     * @param int $MinWidth Minimum width (in pixels) of image. 0 means any width.
     * @return mixed The url of the image if safe, false otherwise.
     */
    function safeImage($ImageUrl, $MinHeight = 0, $MinWidth = 0) {
        try {
            list($Width, $Height, $Type, $Attributes) = getimagesize($ImageUrl);
            if ($MinHeight > 0 && $MinHeight < $Height) {
                return false;
            }

            if ($MinWidth > 0 && $MinWidth < $Width) {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }
        return $ImageUrl;
    }
}

if (!function_exists('safeParseStr')) {
    function safeParseStr($Str, &$Output, $Original = null) {
        $Exploded = explode('&', $Str);
        $Output = array();
        if (is_array($Original)) {
            $FirstValue = reset($Original);
            $FirstKey = key($Original);
            unset($Original[$FirstKey]);
        }
        foreach ($Exploded as $Parameter) {
            $Parts = explode('=', $Parameter);
            $Key = $Parts[0];
            $Value = count($Parts) > 1 ? $Parts[1] : '';

            if (!is_null($Original)) {
                $Output[$Key] = $FirstValue;
                $Output = array_merge($Output, $Original);
                break;
            }

            $Output[$Key] = $Value;
        }
    }
}


if (!function_exists('safeRedirect')) {
    /**
     * Redirect, but only to a safe domain.
     *
     * @param string $Destination Where to redirect.
     * @param int $StatusCode
     */
    function safeRedirect($Destination = false, $StatusCode = null) {
        if (!$Destination) {
            $Destination = Url('', true);
        } else {
            $Destination = Url($Destination, true);
        }

        $Domain = parse_url($Destination, PHP_URL_HOST);
        if (in_array($Domain, TrustedDomains())) {
            Redirect($Destination, $StatusCode);
        } else {
            throw PermissionException();
        }
    }
}

if (!function_exists('saveToConfig')) {
    /**
     * Save values to the application's configuration file.
     *
     * @param string|array $Name One of the following:
     *  - string: The key to save.
     *  - array: An array of key/value pairs to save.
     * @param mixed|null $Value The value to save.
     * @param array|bool $Options An array of additional options for the save.
     *  - Save: If this is false then only the in-memory config is set.
     *  - RemoveEmpty: If this is true then empty/false values will be removed from the config.
     * @return bool: Whether or not the save was successful. null if no changes were necessary.
     */
    function saveToConfig($Name, $Value = '', $Options = array()) {
        Gdn::Config()->SaveToConfig($Name, $Value, $Options);
    }
}


if (!function_exists('setAppCookie')) {
    /**
     * Set a cookie withe the appropriate application cookie prefix and other cookie information.
     *
     * @param string $Name
     * @param string $Value
     * @param int $Expire
     * @param bool $Force Whether or not to set the cookie even if already exists.
     */
    function setAppCookie($Name, $Value, $Expire = 0, $Force = false) {
        $Px = C('Garden.Cookie.Name');
        $Key = "$Px-$Name";

        // Check to see if the cookie is already set before setting it again.
        if (!$Force && isset($_COOKIE[$Key]) && $_COOKIE[$Key] == $Value) {
            return;
        }

        $Domain = C('Garden.Cookie.Domain', '');

        // If the domain being set is completely incompatible with the current domain then make the domain work.
        $CurrentHost = Gdn::Request()->Host();
        if (!StringEndsWith($CurrentHost, trim($Domain, '.'))) {
            $Domain = '';
        }

        // Create the cookie.
        safeCookie($Key, $Value, $Expire, '/', $Domain, null, true);
        $_COOKIE[$Key] = $Value;
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
     * @param string $String The string to slice.
     * @param int $MaxLength The maximum length of the string.
     * @param string $Suffix The suffix if the string must be sliced mid-sentence.
     * @return string
     */
    function sliceParagraph($String, $MaxLength = 500, $Suffix = '…') {
        if ($MaxLength >= strlen($String)) {
            return $String;
        }

//      $String = preg_replace('`\s+\n`', "\n", $String);

        // See if there is a paragraph.
        $Pos = strrpos(SliceString($String, $MaxLength, ''), "\n\n");

        if ($Pos === false) {
            // There was no paragraph so try and split on sentences.
            $Sentences = preg_split('`([.!?:]\s+)`', $String, null, PREG_SPLIT_DELIM_CAPTURE);

            $Result = '';
            if (count($Sentences) > 1) {
                $Result = $Sentences[0].$Sentences[1];

                for ($i = 2; $i < count($Sentences); $i++) {
                    $Sentence = $Sentences[$i];

                    if ((strlen($Result) + strlen($Sentence)) <= $MaxLength || preg_match('`[.!?:]\s+`', $Sentence)) {
                        $Result .= $Sentence;
                    } else {
                        break;
                    }
                }
            }

            if ($Result) {
                return rtrim($Result);
            }

            // There was no sentence. Slice off the last word and call it a day.
            $Pos = strrpos(SliceString($String, $MaxLength, ''), ' ');
            if ($Pos === false) {
                return $String.$Suffix;
            } else {
                return SliceString($String, $Pos + 1, $Suffix);
            }
        } else {
            return substr($String, 0, $Pos + 1);
        }
    }
}

if (!function_exists('sliceString')) {
    function sliceString($String, $Length, $Suffix = '…') {
        if (!$Length) {
            return $String;
        }

        if (function_exists('mb_strimwidth')) {
            static $Charset;
            if (is_null($Charset)) {
                $Charset = Gdn::Config('Garden.Charset', 'utf-8');
            }
            return mb_strimwidth($String, 0, $Length, $Suffix, $Charset);
        } else {
            $Trim = substr($String, 0, $Length);
            return $Trim.((strlen($Trim) != strlen($String)) ? $Suffix : '');
        }
    }
}

if (!function_exists('smartAsset')) {
    /**
     * Takes the path to an asset (image, js file, css file, etc) and prepends the web root.
     */
    function smartAsset($Destination = '', $WithDomain = false, $AddVersion = false) {
        $Destination = str_replace('\\', '/', $Destination);
        if (IsUrl($Destination)) {
            $Result = $Destination;
        } else {
            $Result = Gdn::Request()->UrlDomain($WithDomain).Gdn::Request()->AssetRoot().'/'.ltrim($Destination, '/');
        }

        if ($AddVersion) {
            if (strpos($Result, '?') === false) {
                $Result .= '?';
            } else {
                $Result .= '&';
            }

            // Figure out which version to put after the asset.
            $Version = APPLICATION_VERSION;
            if (preg_match('`^/([^/]+)/([^/]+)/`', $Destination, $Matches)) {
                $Type = $Matches[1];
                $Key = $Matches[2];
                static $ThemeVersion = null;

                switch ($Type) {
                    case 'plugins':
                        $PluginInfo = Gdn::PluginManager()->GetPluginInfo($Key);
                        $Version = GetValue('Version', $PluginInfo, $Version);
                        break;
                    case 'themes':
                        if ($ThemeVersion === null) {
                            $ThemeInfo = Gdn::ThemeManager()->GetThemeInfo(Theme());
                            if ($ThemeInfo !== false) {
                                $ThemeVersion = GetValue('Version', $ThemeInfo, $Version);
                            } else {
                                $ThemeVersion = $Version;
                            }
                        }
                        $Version = $ThemeVersion;
                        break;
                }
            }

            $Result .= 'v='.urlencode($Version);
        }
        return $Result;
    }
}

if (!function_exists('stringBeginsWith')) {
    /**
     * Checks whether or not string A begins with string B.
     *
     * @param string $Haystack The main string to check.
     * @param string $Needle The substring to check against.
     * @param bool $CaseInsensitive Whether or not the comparison should be case insensitive.
     * @param bool $Trim Whether or not to trim $B off of $A if it is found.
     * @return bool|string Returns true/false unless $Trim is true.
     */
    function stringBeginsWith($Haystack, $Needle, $CaseInsensitive = false, $Trim = false) {
        if (strlen($Haystack) < strlen($Needle)) {
            return $Trim ? $Haystack : false;
        } elseif (strlen($Needle) == 0) {
            if ($Trim) {
                return $Haystack;
            }
            return true;
        } else {
            $Result = substr_compare($Haystack, $Needle, 0, strlen($Needle), $CaseInsensitive) == 0;
            if ($Trim) {
                $Result = $Result ? substr($Haystack, strlen($Needle)) : $Haystack;
            }
            return $Result;
        }
    }
}

if (!function_exists('stringEndsWith')) {
    /**
     * Checks whether or not string A ends with string B.
     *
     * @param string $Haystack The main string to check.
     * @param string $Needle The substring to check against.
     * @param bool $CaseInsensitive Whether or not the comparison should be case insensitive.
     * @param bool $Trim Whether or not to trim $B off of $A if it is found.
     * @return bool|string Returns true/false unless $Trim is true.
     */
    function stringEndsWith($Haystack, $Needle, $CaseInsensitive = false, $Trim = false) {
        if (strlen($Haystack) < strlen($Needle)) {
            return $Trim ? $Haystack : false;
        } elseif (strlen($Needle) == 0) {
            if ($Trim) {
                return $Haystack;
            }
            return true;
        } else {
            $Result = substr_compare($Haystack, $Needle, -strlen($Needle), strlen($Needle), $CaseInsensitive) == 0;
            if ($Trim) {
                $Result = $Result ? substr($Haystack, 0, -strlen($Needle)) : $Haystack;
            }
            return $Result;
        }
    }
}

if (!function_exists('stringIsNullOrEmpty')) {
    /**
     * Checks whether or not a string is null or an empty string (after trimming).
     *
     * @param string $String The string to check.
     * @return bool
     */
    function stringIsNullOrEmpty($String) {
        return is_null($String) === true || (is_string($String) && trim($String) == '');
    }
}


if (!function_exists('setValue')) {
    /**
     * Set the value on an object/array.
     *
     * @param string $Needle The key or property name of the value.
     * @param mixed &$Haystack The array or object to set.
     * @param mixed $Value The value to set.
     */
    function setValue($Needle, &$Haystack, $Value) {
        if (is_array($Haystack)) {
            $Haystack[$Needle] = $Value;
        } elseif (is_object($Haystack)) {
            $Haystack->$Needle = $Value;
        }
    }
}


if (!function_exists('t')) {
    /**
     * Translates a code into the selected locale's definition.
     *
     * @param string $Code The code related to the language-specific definition.
     *   Codes that begin with an '@' symbol are treated as literals and not translated.
     * @param string $Default The default value to be displayed if the translation code is not found.
     * @return string The translated string or $Code if there is no value in $Default.
     * @see Gdn::Translate()
     */
    function t($Code, $Default = false) {
        return Gdn::Translate($Code, $Default);
    }
}

if (!function_exists('translateContent')) {
    /**
     * Translates user-generated content into the selected locale's definition. Currently just an
     * alias for t().
     *
     * @param string $Code The code related to the language-specific definition.
     *   Codes thst begin with an '@' symbol are treated as literals and not translated.
     * @param string $Default The default value to be displayed if the translation code is not found.
     * @return string The translated string or $Code if there is no value in $Default.
     * @see Gdn::Translate()
     */
    function translateContent($Code, $Default = false) {
        return t($Code, $Default);
    }
}

if (!function_exists('theme')) {
    function theme() {
        return Gdn::ThemeManager()->CurrentTheme();
    }
}

if (!function_exists('touchValue')) {
    /**
     * Set the value on an object/array if it doesn't already exist.
     *
     * @param string $Key The key or property name of the value.
     * @param mixed &$Collection The array or object to set.
     * @param mixed $Default The value to set.
     */
    function touchValue($Key, &$Collection, $Default) {
        if (is_array($Collection) && !array_key_exists($Key, $Collection)) {
            $Collection[$Key] = $Default;
        } elseif (is_object($Collection) && !property_exists($Collection, $Key)) {
            $Collection->$Key = $Default;
        }

        return val($Key, $Collection);
    }
}

if (!function_exists('touchFolder')) {
    /**
     * Ensure that a folder exists.
     *
     * @param string $Path
     * @param int $Perms
     * @since 2.1
     */
    function touchFolder($Path, $Perms = 0777) {
        if (!file_exists($Path)) {
            mkdir($Path, $Perms, true);
        }
    }
}

if (!function_exists('trace')) {
    function trace($Value = null, $Type = TRACE_INFO) {
        static $Traces = array();

        if ($Value === null) {
            return $Traces;
        }

        if ($Value) {
            $Traces[] = array($Value, $Type);
        }
    }
}

if (!function_exists('trueStripSlashes')) {
    if (get_magic_quotes_gpc()) {
        function trueStripSlashes($String) {
            return stripslashes($String);
        }
    } else {
        function trueStripSlashes($String) {
            return $String;
        }
    }
}

if (!function_exists('trustedDomains')) {
    /**
     * Get an array of all of the trusted domains in the application.
     *
     * @return array
     */
    function trustedDomains() {
        $Result = C('Garden.TrustedDomains', array());
        if (!is_array($Result)) {
            $Result = explode("\n", $Result);
        }

        // This domain is safe.
        $Result[] = Gdn::Request()->Host();

        return array_unique($Result);
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

// Takes a route and prepends the web root (expects "/controller/action/params" as $Destination)
if (!function_exists('url')) {
    function url($Path = '', $WithDomain = false, $RemoveSyndication = false) {
        $Result = Gdn::Request()->Url($Path, $WithDomain);
        return $Result;
    }
}


if (!function_exists('viewLocation')) {
    /**
     * Get the path of a view.
     *
     * @param string $View The name of the view.
     * @param string $Controller The name of the controller invoking the view or blank.
     * @param string $Folder The application folder or plugins/plugin folder.
     * @return string|false The path to the view or false if it wasn't found.
     */
    function viewLocation($View, $Controller, $Folder) {
        $Paths = array();

        if (strpos($View, '/') !== false) {
            // This is a path to the view from the root.
            $Paths[] = $View;
        } else {
            $View = strtolower($View);
            $Controller = strtolower(StringEndsWith($Controller, 'Controller', true, true));
            if ($Controller) {
                $Controller = '/'.$Controller;
            }

            $Extensions = array('tpl', 'php');

            // 1. First we check the theme.
            if (Gdn::Controller() && $Theme = Gdn::Controller()->Theme) {
                foreach ($Extensions as $Ext) {
                    $Paths[] = PATH_THEMES."/{$Theme}/views{$Controller}/$View.$Ext";
                }
            }

            // 2. Then we check the application/plugin.
            if (StringBeginsWith($Folder, 'plugins/')) {
                // This is a plugin view.
                foreach ($Extensions as $Ext) {
                    $Paths[] = PATH_ROOT."/{$Folder}/views{$Controller}/$View.$Ext";
                }
            } else {
                // This is an application view.
                $Folder = strtolower($Folder);
                foreach ($Extensions as $Ext) {
                    $Paths[] = PATH_APPLICATIONS."/{$Folder}/views{$Controller}/$View.$Ext";
                }

                if ($Folder != 'dashboard' && StringEndsWith($View, '.master')) {
                    // This is a master view that can always fall back to the dashboard.
                    foreach ($Extensions as $Ext) {
                        $Paths[] = PATH_APPLICATIONS."/dashboard/views{$Controller}/$View.$Ext";
                    }
                }
            }
        }

        // Now let's search the paths for the view.
        foreach ($Paths as $Path) {
            if (file_exists($Path)) {
                return $Path;
            }
        }

        Trace(array('view' => $View, 'controller' => $Controller, 'folder' => $Folder), 'View');
        Trace($Paths, 'ViewLocation()');

        return false;
    }
}

if (!function_exists('passwordStrength')) {
    /**
     * Check a password's strength.
     *
     * Returns an analysis of the supplied password, comprised of an array with
     * the following keys:
     *
     *    Pass        // Does the password 'pass' our tests
     *    Symbols     //
     *    Length
     *    Entropy
     *    Score
     *
     * @param string $Password
     * @param string $Username
     */
    function passwordStrength($Password, $Username) {
        $Translations = explode(',', T('Password Translations', 'Too Short,Contains Username,Very Weak,Weak,Ok,Good,Strong'));

        // calculate $Entropy
        $Alphabet = 0;
        if (preg_match('/[0-9]/', $Password)) {
            $Alphabet += 10;
        }
        if (preg_match('/[a-z]/', $Password)) {
            $Alphabet += 26;
        }
        if (preg_match('/[A-Z]/', $Password)) {
            $Alphabet += 26;
        }
        if (preg_match('/[^a-zA-Z0-9]/', $Password)) {
            $Alphabet += 31;
        }

        $Length = strlen($Password);
        $Entropy = log(pow($Alphabet, $Length), 2);

        $RequiredLength = C('Garden.Password.MinLength', 6);
        $RequiredScore = C('Garden.Password.MinScore', 2);
        $Response = array(
            'Pass' => false,
            'Symbols' => $Alphabet,
            'Length' => $Length,
            'Entropy' => $Entropy,
            'Required' => $RequiredLength,
            'Score' => 0
        );

        if ($Length < $RequiredLength) {
            $Response['Reason'] = $Translations[0];
            return $Response;
        }

        // password1 == username
        if (strpos(strtolower($Username), strtolower($Password)) !== false) {
            $Response['Reason'] = $Translations[1];
            return $Response;
        }

        if ($Entropy < 30) {
            $Response['Score'] = 1;
            $Response['Reason'] = $Translations[2];
        } elseif ($Entropy < 40) {
            $Response['Score'] = 2;
            $Response['Reason'] = $Translations[3];
        } elseif ($Entropy < 55) {
            $Response['Score'] = 3;
            $Response['Reason'] = $Translations[4];
        } elseif ($Entropy < 70) {
            $Response['Score'] = 4;
            $Response['Reason'] = $Translations[5];
        } else {
            $Response['Score'] = 5;
            $Response['Reason'] = $Translations[6];
        }

        $Response['Pass'] = $Response['Score'] >= $RequiredScore;

        return $Response;
    }
}

if (!function_exists('isSafeUrl')) {
    /**
     * Used to determine if a URL is on safe for use.
     *
     * @param $Url http url to be checked.
     * @return bool
     */
    function isSafeUrl($Url) {

        $ParsedUrl = parse_url($Url);
        if (empty($ParsedUrl['host']) || $ParsedUrl['host'] == Gdn::Request()->Host()) {
            return true;
        }

        return false;
    }

}

if (!function_exists('userAgentType')) {
    /**
     *
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
        $type = strtolower(GetValue('HTTP_X_UA_DEVICE', $_SERVER, ''));
        if ($type) {
            return $type;
        }

        // See if there is an override in the cookie.
        if ($type = val('X-UA-Device-Force', $_COOKIE)) {
            return $type;
        }

        // Now we will have to figure out the type based on the user agent and other things.
        $allHttp = strtolower(GetValue('ALL_HTTP', $_SERVER));
        $httpAccept = strtolower(GetValue('HTTP_ACCEPT', $_SERVER));
        $userAgent = strtolower(GetValue('HTTP_USER_AGENT', $_SERVER));

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
        $directAgents = array(
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
            'ipod'
        );
        $directAgentsMatch = implode('|', $directAgents);
        if (preg_match("/({$directAgentsMatch})/i", $userAgent)) {
            return $type = 'mobile';
        }

        // Match starting chunks of known
        $mobileUserAgent = substr($userAgent, 0, 4);
        $mobileUserAgents = array(
            'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
            'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
            'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
            'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
            'newt', 'noki', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox', 'qwap',
            'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar', 'sie-',
            'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-', 'tosh',
            'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp', 'wapr',
            'webc', 'winw', 'winw', 'xda', 'xda-');

        if (in_array($mobileUserAgent, $mobileUserAgents)) {
            return $type = 'mobile';
        }

        // None of the mobile matches work so we must be a desktop browser.
        return $type = 'desktop';
    }
}
