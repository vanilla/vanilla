<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

if (!function_exists('addActivity')) {
    /**
     * A convenience function that allows adding to the activity table with a single line.
     *
     * @param int $ActivityUserID The user committing the activity.
     * @param string $ActivityType The type of activity.
     * @param string $Story The story section of the activity.
     * @param string $RegardingUserID The user the activity is being performed on.
     * @param string $Route The path of the data the activity is for.
     * @param string $SendEmail Whether or not to send an email with the activity.
     * @return int The ID of the new activity or zero on error.
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

if (!function_exists('arrayValue')) {
    /**
     * Get the value associated with a {@link $Needle} key in a {@link $Haystack} array.
     *
     * @param string $needle The key to look for in the $Haystack associative array.
     * @param array $haystack The associative array in which to search for the $Needle key.
     * @param string $default The default value to return if the requested value is not found. Default is false.
     * @deprecated since 2.3
     */
    function arrayValue($needle, $haystack, $default = false) {
        deprecated('arrayValue', 'val');
        $result = val($needle, $haystack, $default);
        return $result;
    }
}

if (!function_exists('arrayValuesToKeys')) {
    /**
     * Take an array's values and apply them to a new array as both the keys and values.
     *
     * @param array $Array The array to combine.
     * @deprecated
     * @see array_combine()
     */
    function arrayValuesToKeys($Array) {
        return array_combine(array_values($Array), $Array);
    }
}

if (!function_exists('compareHashDigest')) {
    /**
     * Determine whether or not two strings are equal in a time that is independent of partial matches.
     *
     * This snippet prevents HMAC Timing attacks (http://codahale.com/a-lesson-in-timing-attacks/).
     * Thanks to Eric Karulf (ekarulf @ github) for this fix.
     *
     * @param string $Digest1 The first digest to compare.
     * @param string $Digest2 The second digest to compare.
     * @return bool Returns true if the digests match or false otherwise.
     */
    function compareHashDigest($Digest1, $Digest2) {
        deprecated('compareHashDigest', 'hash_equals');
        return hash_equals($Digest1, $Digest2);
    }
}

if (!function_exists('ConsolidateArrayValuesByKey')) {
    /**
     * Return the values from a single column in the input array.
     *
     * Take an array of associative arrays (ie. a dataset array), a $Key, and
     * merges all of the values for that key into a single array, returning it.
     *
     * @param array $Array The input array.
     * @param string|int $Key The key to consolidate by.
     * @param string|int $ValueKey An optional secondary key to use take the values for.
     * @param mixed $DefaultValue The value to use if there is no {@link $ValueKey} in the array.
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
        if (c('Garden.AllowSSL')) {
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

if (!function_exists('getIncomingValue')) {
    /**
     * Grab {@link $FieldName} from either the GET or POST collections.
     *
     * This function checks $_POST first.
     *
     * @param string $FieldName The key of the field to get.
     * @param mixed $Default The value to return if the field is not found.
     * @return mixed Returns the value of the field or {@link $Default}.
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
     * @param string $FieldName The key of the field to get.
     * @param mixed $Default The value to return if the field is not found.
     * @return mixed Returns the value of the field or {@link $Default}.
     * @deprecated
     */
    function getPostValue($FieldName, $Default = false) {
        return array_key_exists($FieldName, $_POST) ? $_POST[$FieldName] : $Default;
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

if (!function_exists('parseUrl')) {
    /**
     * A Vanilla wrapper for php's parse_url, which doesn't always return values for every url part.
     *
     * @param string $url The url to parse.
     * @param int $component Use PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PORT, PHP_URL_USER, PHP_URL_PASS, PHP_URL_PATH,
     * PHP_URL_QUERY or PHP_URL_FRAGMENT to retrieve just a specific url component.
     * @deprecated
     */
    function parseUrl($url, $component = -1) {
        $defaults = [
            'scheme' => 'http',
            'host' => '',
            'port' => null,
            'user' => '',
            'pass' => '',
            'path' => '',
            'query' => '',
            'fragment' => ''
        ];

        $parts = parse_url($url);
        if (is_array($parts)) {
            $parts = array_replace($defaults, $parts);
        } else {
            $parts = $defaults;
        }

        if ($parts['port'] === null) {
            $parts['port'] = $parts['scheme'] === 'https' ? '443' : '80';
        }

        // Return
        switch ($component) {
            case PHP_URL_SCHEME:
                return $parts['scheme'];
            case PHP_URL_HOST:
                return $parts['host'];
            case PHP_URL_PORT:
                return $parts['port'];
            case PHP_URL_USER:
                return $parts['user'];
            case PHP_URL_PASS:
                return $parts['pass'];
            case PHP_URL_PATH:
                return $parts['path'];
            case PHP_URL_QUERY:
                return $parts['query'];
            case PHP_URL_FRAGMENT:
                return $parts['fragment'];
            default:
                return $parts;
        }
    }
}

if (!function_exists('prepareArray')) {
    /**
     * Makes sure that the key in question exists and is of the specified type, by default also an array.
     *
     * @param string $Key Key to prepare.
     * @param array &$Array Array to prepare.
     * @param string $PrepareType Optional.
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

// Functions relating to data/variable types and type casting
if (!function_exists('removeKeyFromArray')) {
    /**
     * Remove a value from an array at a certain key.
     *
     * @param array $Array The input array.
     * @param string|int $Key The key to remove.
     * @return mixed Returns a copy of {@link $Array} with the key removed.
     * @deprecated Use unset() instead.
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

if (!function_exists('removeQuoteSlashes')) {
    /**
     * Remove the slashes from escaped quotes in a string.
     *
     * @param string $String The input string.
     * @return string Returns a copy of {@link $String} with the slashes removed.
     * @deprecated
     */
    function removeQuoteSlashes($String) {
        deprecated('removeQuoteSlashes()');
        return str_replace("\\\"", '"', $String);
    }
}

if (!function_exists('removeValueFromArray')) {
    /**
     * Remove a value from an array.
     *
     * @param array &$Array The input array.
     * @param mixed $Value The value to search for and remove.
     * @deprecated
     */
    function removeValueFromArray(&$Array, $Value) {
        deprecated('removeValueFromArray()');
        foreach ($Array as $key => $val) {
            if ($val == $Value) {
                unset($Array[$key]);
                break;
            }
        }
    }
}

if (!function_exists('safeParseStr')) {
    /**
     * An alternate implementation of {@link parse_str()}.
     *
     * @param string $Str The query string to parse.
     * @param array &$Output The array of results.
     * @param array|null $Original Do not use.
     * @deprecated
     * @see parse_str()
     */
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

if (!function_exists('trueStripSlashes')) {
    if (get_magic_quotes_gpc()) {
        /**
         * @deprecated
         */
        function trueStripSlashes($String) {
            deprecated('trueStripSlashes()');
            return stripslashes($String);
        }
    } else {
        /**
         * @deprecated
         */
        function trueStripSlashes($String) {
            deprecated('trueStripSlashes()');
            return $String;
        }
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
     * @deprecated
     */
    function viewLocation($View, $Controller, $Folder) {
        deprecated('viewLocation()');
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