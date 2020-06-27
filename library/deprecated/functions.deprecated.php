<?php
// phpcs:ignoreFile
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

if (!function_exists('addActivity')) {
    /**
     * A convenience function that allows adding to the activity table with a single line.
     *
     * @param int $activityUserID The user committing the activity.
     * @param string $activityType The type of activity.
     * @param string $story The story section of the activity.
     * @param string $regardingUserID The user the activity is being performed on.
     * @param string $route The path of the data the activity is for.
     * @param string $sendEmail Whether or not to send an email with the activity.
     * @return int The ID of the new activity or zero on error.
     * @deprecated
     */
    function addActivity(
        $activityUserID,
        $activityType,
        $story = '',
        $regardingUserID = '',
        $route = '',
        $sendEmail = ''
    ) {
        $activityModel = new ActivityModel();
        return $activityModel->add($activityUserID, $activityType, $story, $regardingUserID, '', $route, $sendEmail);
    }
}

if (!function_exists('arrayInArray')) {
    /**
     * Check to see if an array contains another array.
     *
     * Searches {@link $haystack} array for items in {@link $needle} array. If FullMatch is true,
     * all items in Needle must also be in Haystack. If FullMatch is false, only
     * one-or-more items in Needle must be in Haystack.
     *
     * @param array $needle The array containing items to match to Haystack.
     * @param array $haystack The array to search in for Needle items.
     * @param bool $fullMatch Should all items in Needle be found in Haystack to return true?
     * @deprecated
     */
    function arrayInArray($needle, $haystack, $fullMatch = true) {
        $count = count($needle);
        $return = $fullMatch ? true : false;
        for ($i = 0; $i < $count; ++$i) {
            if ($fullMatch === true) {
                if (in_array($needle[$i], $haystack) === false) {
                    $return = false;
                }
            } else {
                if (in_array($needle[$i], $haystack) === true) {
                    $return = true;
                    break;
                }
            }
        }
        return $return;
    }
}

/**
 * The array_merge_recursive function does indeed merge arrays, but it converts values with duplicate
 * keys to arrays rather than overwriting the value in the first array with the duplicate
 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
 * this happens (documented behavior):
 *
 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('org value', 'new value'));
 *
 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
 * Matching keys' values in the second array overwrite those in the first array, as is the
 * case with array_merge, i.e.:
 *
 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('new value'));
 *
 * Parameters are passed by reference, though only for performance reasons. They're not
 * altered by this function.
 *
 * @param array $array1
 * @param mixed $array2
 * @return array
 * @author daniel@danielsmedegaardbuus.dk
 * @deprecated
 */
function &arrayMergeRecursiveDistinct(array &$array1, &$array2 = null) {
    deprecated('arrayMergeRecursiveDistinct');
    $merged = $array1;

    if (is_array($array2)) {
        foreach ($array2 as $key => $val) {
            if (is_array($array2[$key])) {
                $merged[$key] = is_array($merged[$key]) ? arrayMergeRecursiveDistinct($merged[$key], $array2[$key]) : $array2[$key];
            } else {
                $merged[$key] = $val;
            }
        }
    }

    return $merged;
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
     * @param array $array The array to combine.
     * @deprecated
     * @see array_combine()
     */
    function arrayValuesToKeys($array) {
        return array_combine(array_values($array), $array);
    }
}

if (!function_exists('boop')) {
    /**
     * Logs a message or print_r()'s an array to the screen.
     *
     * @param mixed $message The object or string to log to the screen.
     * @param array $arguments An optional list of arguments to log to the screen as if from a function call.
     * @param bool $vardump Whether or not to dump
     * @deprecated
     */
    function boop($message, $arguments = [], $vardump = false) {
        if (!defined('BOOP') || !BOOP) {
            return;
        }

        if (is_array($message) || is_object($message) || $vardump === true) {
            if ($vardump) {
                var_dump($message);
            } else {
                print_r($message);
            }
        } else {
            echo $message;
        }

        if (!is_null($arguments) && sizeof($arguments)) {
            echo " (".implode(', ', $arguments).")";
        }

        echo "\n";
    }
}

if (!function_exists('checkRequirements')) {
    /**
     * Check an addon's requirements.
     *
     * @param string $itemName The name of the item checking requirements.
     * @param array $requiredItems An array of requirements.
     * @param array $enabledItems An array of currently enabled items to check against.
     * @throws Gdn_UserException Throws an exception if there are missing requirements.
     * @deprecated
     */
    function checkRequirements($itemName, $requiredItems, $enabledItems) {
        deprecated('checkRequirements()', 'AddonManager');

        // 1. Make sure that $RequiredItems are present
        if (is_array($requiredItems)) {
            $missingRequirements = [];

            foreach ($requiredItems as $requiredItemName => $requiredVersion) {
                if (!array_key_exists($requiredItemName, $enabledItems)) {
                    $missingRequirements[] = "$requiredItemName $requiredVersion";
                } elseif ($requiredVersion && $requiredVersion != '*') { // * means any version
                    // If the item exists and is enabled, check the version
                    $enabledVersion = val('Version', val($requiredItemName, $enabledItems, []), '');
                    // Compare the versions.
                    if (version_compare($enabledVersion, $requiredVersion, '<')) {
                        $missingRequirements[] = "$requiredItemName $requiredVersion";
                    }
                }
            }
            if (count($missingRequirements) > 0) {
                $msg = sprintf(
                    "%s is missing the following requirement(s): %s.",
                    $itemName,
                    implode(', ', $missingRequirements)
                );
                throw new Gdn_UserException($msg);
            }
        }
    }
}

if (!function_exists('arrayCombine')) {
    /**
     * PHP's array_combine has a limitation that doesn't allow array_combine to work if either of the arrays are empty.
     *
     * @param array $keys Array of keys to be used. Illegal values for key will be converted to string.
     * @param array $values Array of values to be used.
     * @return array
     * @deprecated
     */
    function arrayCombine($keys, $values) {
        deprecated('arrayCombine', 'array_combine');
        if (!is_array($keys)) {
            $keys = [];
        }

        if (!is_array($values)) {
            $values = [];
        }

        if (count($keys) > 0 && count($values) > 0) {
            return array_combine($keys, $values);
        } elseif (count($keys) == 0) {
            return $values;
        } else {
            return $keys;
        }
    }
}

if (!function_exists('compareHashDigest')) {
    /**
     * Determine whether or not two strings are equal in a time that is independent of partial matches.
     *
     * This snippet prevents HMAC Timing attacks (http://codahale.com/a-lesson-in-timing-attacks/).
     * Thanks to Eric Karulf (ekarulf @ github) for this fix.
     *
     * @param string $digest1 The first digest to compare.
     * @param string $digest2 The second digest to compare.
     * @return bool Returns true if the digests match or false otherwise.
     */
    function compareHashDigest($digest1, $digest2) {
        deprecated('compareHashDigest', 'hash_equals');
        return hash_equals($digest1, $digest2);
    }
}

if (!function_exists('condense')) {
    /**
     *
     *
     * @param string $html
     * @return mixed
     */
    function condense($html) {
        $html = preg_replace('`(?:<br\s*/?>\s*)+`', "<br />", $html);
        $html = preg_replace('`/>\s*<br />\s*<img`', "/> <img", $html);
        return $html;
    }
}

if (!function_exists('ConsolidateArrayValuesByKey')) {
    /**
     * Return the values from a single column in the input array.
     *
     * Take an array of associative arrays (ie. a dataset array), a $key, and
     * merges all of the values for that key into a single array, returning it.
     *
     * @param array $array The input array.
     * @param string|int $key The key to consolidate by.
     * @param string|int $valueKey An optional secondary key to use take the values for.
     * @param mixed $defaultValue The value to use if there is no {@link $valueKey} in the array.
     * @deprecated Use {@link array_column()} instead.
     */
    function consolidateArrayValuesByKey($array, $key, $valueKey = '', $defaultValue = null) {
        deprecated(__FUNCTION__, 'array_column');

        $return = [];
        foreach ($array as $index => $associativeArray) {
            if (is_object($associativeArray)) {
                if ($valueKey === '') {
                    $return[] = $associativeArray->$key;
                } elseif (property_exists($associativeArray, $valueKey)) {
                    $return[$associativeArray->$key] = $associativeArray->$valueKey;
                } else {
                    $return[$associativeArray->$key] = $defaultValue;
                }
            } elseif (is_array($associativeArray) && array_key_exists($key, $associativeArray)) {
                if ($valueKey === '') {
                    $return[] = $associativeArray[$key];
                } elseif (array_key_exists($valueKey, $associativeArray)) {
                    $return[$associativeArray[$key]] = $associativeArray[$valueKey];
                } else {
                    $return[$associativeArray[$key]] = $defaultValue;
                }
            }
        }
        return $return;
    }
}

if (!function_exists('cTo')) {
    /**
     * Set a value in an deep array.
     *
     * @param array &$data The array to set.
     * @param string $name A dot separated set of keys to set.
     * @param mixed $value The value to set.
     * @deprecated Use {@link setvalr()}.
     */
    function cTo(&$data, $name, $value) {
        $name = explode('.', $name);
        $lastKey = array_pop($name);
        $current =& $data;

        foreach ($name as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }

            $current =& $current[$key];
        }
        $current[$lastKey] = $value;
    }
}

if (!function_exists('discussionLink')) {
    /**
     * Build URL for discussion.
     *
     * @deprecated discussionUrl()
     * @param $discussion
     * @param bool $extended
     * @return string
     */
    function discussionLink($discussion, $extended = true) {
        deprecated('discussionLink', 'discussionUrl');

        $discussionID = val('DiscussionID', $discussion);
        $discussionName = val('Name', $discussion);
        $parts = [
            'discussion',
            $discussionID,
            Gdn_Format::url($discussionName)
        ];
        if ($extended) {
            $parts[] = ($discussion->CountCommentWatch > 0) ? '#Item_'.$discussion->CountCommentWatch : '';
        }
        return url(implode('/', $parts), true);
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
     * @deprecated Use ArrayUtils::explodeTrim() instead
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

if (!function_exists('fixnl2br')) {
    /**
     * Removes the break above and below tags that have a natural margin.
     *
     * @param string $text The text to fix.
     * @return string
     * @since 2.1
     *
     * @deprecated 3.2 - Use \Vanilla\Formatting\Html\HtmlFormat::cleanupLineBreaks
     */
    function fixnl2br($text) {
        deprecated(__FUNCTION__, '\Vanilla\Formatting\Formats\HtmlFormat::cleanupLineBreaks');
        /** @var Formats\HtmlFormat $htmlFormat */
        $htmlFormat = Gdn::getContainer()->get(Formats\HtmlFormat::class);
        return $htmlFormat->cleanupLineBreaks((string) $text);
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
            if (Gdn::request()->scheme() != 'https') {
                redirectTo(Gdn::request()->url('', true, true));
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
        if (Gdn::request()->scheme() != 'http') {
            redirectTo(Gdn::request()->url('', true, false));
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
            $isAssociativeArray = array_key_exists(0, $value) === false || is_array($value[0]) === true ? true : false;
            if ($isAssociativeArray === true) {
                foreach ($value as $k => $v) {
                    formatArrayAssignment($array, $prefix.'['.var_export($k, true).']', $v);
                }
            } else {
                // If $Value is not an associative array, just write it like a simple array definition.
                $formattedValue = array_map(['Gdn_Format', 'ArrayValueForPhp'], $value);
                $f2 = var_export($value, true);

                $array[] = $prefix .= ' = '.var_export($value, true).';';
            }
        } elseif (is_int($value)) {
            $array[] = $prefix .= ' = '.$value.';';
        } elseif (is_bool($value)) {
            $array[] = $prefix .= ' = '.($value ? 'true' : 'false').';';
        } elseif (in_array($value, ['true', 'false'])) {
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
        \Vanilla\Utility\Deprecation::log();
        if (is_array($value)) {
            // If $Value doesn't contain a key of "0" OR it does and it's value IS
            // an array, this should be treated as an associative array.
            $isAssociativeArray = array_key_exists(0, $value) === false || is_array($value[0]) === true ? true : false;
            if ($isAssociativeArray === true) {
                foreach ($value as $k => $v) {
                    formatDottedAssignment($array, "{$prefix}.{$k}", $v);
                }
            } else {
                // If $Value is not an associative array, just write it like a simple array definition.
                $formattedValue = array_map(['Gdn_Format', 'ArrayValueForPhp'], $value);
                $prefix .= "']";
                $array[] = $prefix .= " = array('".implode("', '", $formattedValue)."');";
            }
        } else {
            $prefix .= "']";
            if (is_int($value)) {
                $array[] = $prefix .= ' = '.$value.';';
            } elseif (is_bool($value)) {
                $array[] = $prefix .= ' = '.($value ? 'true' : 'false').';';
            } elseif (in_array($value, ['true', 'false'])) {
                $array[] = $prefix .= ' = '.($value == 'true' ? 'true' : 'false').';';
            } else {
                $array[] = $prefix .= ' = '.var_export($value, true).';';
            }
        }
    }
}

if (!function_exists('getIncomingValue')) {
    /**
     * Grab {@link $fieldName} from either the GET or POST collections.
     *
     * This function checks $_POST first.
     *
     * @param string $fieldName The key of the field to get.
     * @param mixed $default The value to return if the field is not found.
     * @return mixed Returns the value of the field or {@link $default}.
     *
     * @deprecated Use the various methods on {@link Gdn::request()}.
     */
    function getIncomingValue($fieldName, $default = false) {
        if (array_key_exists($fieldName, $_POST) === true) {
            $result = filter_input(INPUT_POST, $fieldName, FILTER_SANITIZE_STRING); //FILTER_REQUIRE_ARRAY);
        } elseif (array_key_exists($fieldName, $_GET) === true) {
            $result = filter_input(INPUT_GET, $fieldName, FILTER_SANITIZE_STRING); //, FILTER_REQUIRE_ARRAY);
        } else {
            $result = $default;
        }
        return $result;
    }
}

if (!function_exists('getObject')) {
    /**
     * Get a value off of an object.
     *
     * @param string $property The name of the property on the object.
     * @param object $object The object that contains the value.
     * @param mixed $default The default to return if the object doesn't contain the property.
     * @return mixed
     * @deprecated getObject() is deprecated. Use val() instead.
     */
    function getObject($property, $object, $default) {
        trigger_error('GetObject() is deprecated. Use GetValue() instead.', E_USER_DEPRECATED);
        $result = val($property, $object, $default);
        return $result;
    }
}

if (!function_exists('getPostValue')) {
    /**
     * Return the value for $fieldName from the $_POST collection.
     *
     * @param string $fieldName The key of the field to get.
     * @param mixed $default The value to return if the field is not found.
     * @return mixed Returns the value of the field or {@link $default}.
     * @deprecated
     */
    function getPostValue($fieldName, $default = false) {
        return array_key_exists($fieldName, $_POST) ? $_POST[$fieldName] : $default;
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
        $result = $default;
        if (is_array($collection) && array_key_exists($key, $collection)) {
            $result = $collection[$key];
            if ($remove) {
                unset($collection[$key]);
            }
        } elseif (is_object($collection) && property_exists($collection, $key)) {
            $result = $collection->$key;
            if ($remove) {
                unset($collection->$key);
            }
        }

        return $result;
    }
}

if (!function_exists('mergeArrays')) {
    /**
     * Merge two associative arrays into a single array.
     *
     * @param array &$dominant The "dominant" array, who's values will be chosen over those of the subservient.
     * @param array $subservient The "subservient" array, who's values will be disregarded over those of the dominant.
     * @deprecated Use {@link array_merge_recursive()}
     */
    function mergeArrays(&$dominant, $subservient) {
        foreach ($subservient as $key => $value) {
            if (!array_key_exists($key, $dominant)) {
                // Add the key from the subservient array if it doesn't exist in the
                // dominant array.
                $dominant[$key] = $value;
            } else {
                // If the key already exists in the dominant array, only continue if
                // both values are also arrays - because we don't want to overwrite
                // values in the dominant array with ones from the subservient array.
                if (is_array($dominant[$key]) && is_array($value)) {
                    $dominant[$key] = mergeArrays($dominant[$key], $value);
                }
            }
        }
        return $dominant;
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
        \Vanilla\Utility\Deprecation::log();
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

if (!function_exists('markString')) {
    /**
     * Wrap occurrences of {@link $needle} in {@link $haystack} with `<mark>` tags.
     *
     * This method explodes {@link $needle} on spaces and returns {@link $haystack} with replacements.
     *
     * @param string|array $needle The strings to search for in {@link $haystack}.
     * @param string $haystack The string to search for replacements.
     * @return string Returns a marked version of {@link $haystack}.
     * @deprecated
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

if (!function_exists('prepareArray')) {
    /**
     * Makes sure that the key in question exists and is of the specified type, by default also an array.
     *
     * @param string $key Key to prepare.
     * @param array &$array Array to prepare.
     * @param string $prepareType Optional.
     * @deprecated
     */
    function prepareArray($key, &$array, $prepareType = 'array') {
        if (!array_key_exists($key, $array)) {
            $array[$key] = null;
        }

        switch ($prepareType) {
            case 'array':
                if (!is_array($array[$key])) {
                    $array[$key] = [];
                }
                break;

            case 'integer':
                if (!is_integer($array[$key])) {
                    $array[$key] = 0;
                }
                break;

            case 'float':
                if (!is_float($array[$key])) {
                    $array[$key] = 0.0;
                }
                break;

            case 'null':
                if (!is_null($array[$key])) {
                    $array[$key] = null;
                }
                break;

            case 'string':
                if (!is_string($array[$key])) {
                    $array[$key] = '';
                }
                break;
        }
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to another URL.
     *
     * This function wraps {@link $destination} in the {@link url()} function.
     *
     * @deprecated
     * @param string|false $destination The destination of the redirect.
     * Pass a falsey value to redirect to the current URL.
     * @param int|null $statusCode The status of the redirect. This defaults to 302.
     */
    function redirect($destination = false, $statusCode = null) {
        deprecated(__FUNCTION__, 'redirectTo');

        if (!$destination) {
            $destination = '';
        }

        // Close any db connections before exit
        $database = Gdn::database();
        if ($database instanceof Gdn_Database) {
            $database->closeConnection();
        }
        // Clear out any previously sent content
        @ob_end_clean();

        // assign status code
        $sendCode = (is_null($statusCode)) ? 302 : $statusCode;
        // re-assign the location header
        safeHeader("Location: ".url($destination), true, $sendCode);
        // Exit
        exit();
    }
}

if (!function_exists('redirectUrl')) {
    /**
     * Redirect to a specific url that can be outside of the site.
     *
     * @deprecated
     * @param string $url The url to redirect to.
     * @param int $code The http status code.
     */
    function redirectUrl($url, $code = 302) {
        deprecated(__FUNCTION__, 'redirectTo');

        if (!$url) {
            $url = url('', true);
        }

        // Close any db connections before exit
        $database = Gdn::database();
        $database->closeConnection();
        // Clear out any previously sent content
        @ob_end_clean();

        if (!in_array($code, [301, 302])) {
            $code = 302;
        }

        safeHeader("Location: ".$url, true, $code);

        exit();
    }
}

// Functions relating to data/variable types and type casting
if (!function_exists('removeKeyFromArray')) {
    /**
     * Remove a value from an array at a certain key.
     *
     * @param array $array The input array.
     * @param string|int $key The key to remove.
     * @return mixed Returns a copy of {@link $array} with the key removed.
     * @deprecated Use unset() instead.
     */
    function removeKeyFromArray($array, $key) {
        if (!is_array($key)) {
            $key = [$key];
        }

        $count = count($key);
        for ($i = 0; $i < $count; $i++) {
            $keyIndex = array_keys(array_keys($array), $key[$i]);
            if (count($keyIndex) > 0) {
                array_splice($array, $keyIndex[0], 1);
            }
        }
        return $array;
    }
}

if (!function_exists('removeQuoteSlashes')) {
    /**
     * Remove the slashes from escaped quotes in a string.
     *
     * @param string $string The input string.
     * @return string Returns a copy of {@link $string} with the slashes removed.
     * @deprecated
     */
    function removeQuoteSlashes($string) {
        deprecated('removeQuoteSlashes()');
        return str_replace("\\\"", '"', $string);
    }
}

if (!function_exists('removeValueFromArray')) {
    /**
     * Remove a value from an array.
     *
     * @param array &$array The input array.
     * @param mixed $value The value to search for and remove.
     * @deprecated
     */
    function removeValueFromArray(&$array, $value) {
        deprecated('removeValueFromArray()');
        foreach ($array as $key => $val) {
            if ($val == $value) {
                unset($array[$key]);
                break;
            }
        }
    }
}

if (!function_exists('safeParseStr')) {
    /**
     * An alternate implementation of {@link parse_str()}.
     *
     * @param string $str The query string to parse.
     * @param array &$output The array of results.
     * @param array|null $original Do not use.
     * @deprecated
     * @see parse_str()
     */
    function safeParseStr($str, &$output, $original = null) {
        \Vanilla\Utility\Deprecation::log();
        $exploded = explode('&', $str);
        $output = [];
        if (is_array($original)) {
            $firstValue = reset($original);
            $firstKey = key($original);
            unset($original[$firstKey]);
        }
        foreach ($exploded as $parameter) {
            $parts = explode('=', $parameter);
            $key = $parts[0];
            $value = count($parts) > 1 ? $parts[1] : '';

            if (!is_null($original)) {
                $output[$key] = $firstValue;
                $output = array_merge($output, $original);
                break;
            }

            $output[$key] = $value;
        }
    }
}

if (!function_exists('safeRedirect')) {
    /**
     * Redirect, but only to a safe domain.
     *
     * @deprecated
     * @param string $destination Where to redirect.
     * @param int $statusCode The status of the redirect. Defaults to 302.
     */
    function safeRedirect($destination = false, $statusCode = null) {
        deprecated(__FUNCTION__, 'redirectTo');

        if (!$destination) {
            $destination = url('', true);
        } else {
            $destination = url($destination, true);
        }

        $trustedDomains = trustedDomains();
        $isTrustedDomain = false;

        foreach ($trustedDomains as $trustedDomain) {
            if (urlMatch($trustedDomain, $destination)) {
                $isTrustedDomain = true;
                break;
            }
        }

        if ($isTrustedDomain) {
            redirectTo($destination, $statusCode, false);
        } else {
            Logger::notice('Redirect to untrusted domain: {url}.', [
                'url' => $destination
            ]);

            redirectTo("/home/leaving?Target=".urlencode($destination));
        }
    }
}

if (!function_exists('viewLocation')) {
    /**
     * Get the path of a view.
     *
     * @param string $view The name of the view.
     * @param string $controller The name of the controller invoking the view or blank.
     * @param string $folder The application folder or plugins/plugin folder.
     * @return string|false The path to the view or false if it wasn't found.
     * @deprecated
     */
    function viewLocation($view, $controller, $folder) {
        \Vanilla\Utility\Deprecation::log();
        $paths = [];

        if (strpos($view, '/') !== false) {
            // This is a path to the view from the root.
            $paths[] = $view;
        } else {
            $view = strtolower($view);
            $controller = strtolower(stringEndsWith($controller, 'Controller', true, true));
            if ($controller) {
                $controller = '/'.$controller;
            }

            $extensions = ['tpl', 'php'];

            // 1. First we check the theme.
            if (Gdn::controller() && $theme = Gdn::controller()->Theme) {
                foreach ($extensions as $ext) {
                    $paths[] = PATH_THEMES."/{$theme}/views{$controller}/$view.$ext";
                }
            }

            // 2. Then we check the application/plugin.
            if (stringBeginsWith($folder, 'plugins/')) {
                // This is a plugin view.
                foreach ($extensions as $ext) {
                    $paths[] = PATH_ROOT."/{$folder}/views{$controller}/$view.$ext";
                }
            } else {
                // This is an application view.
                $folder = strtolower($folder);
                foreach ($extensions as $ext) {
                    $paths[] = PATH_APPLICATIONS."/{$folder}/views{$controller}/$view.$ext";
                }

                if ($folder != 'dashboard' && stringEndsWith($view, '.master')) {
                    // This is a master view that can always fall back to the dashboard.
                    foreach ($extensions as $ext) {
                        $paths[] = PATH_APPLICATIONS."/dashboard/views{$controller}/$view.$ext";
                    }
                }
            }
        }

        // Now let's search the paths for the view.
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        trace(['view' => $view, 'controller' => $controller, 'folder' => $folder], 'View');
        trace($paths, 'ViewLocation()');

        return false;
    }
}

if (!function_exists('TagFullName')) {
    /**
     * Return the full name of a tag row.
     *
     * @param array|object $row
     * @return mixed
     * @deprecated
     */
    function tagFullName($row) {
        $result = val('FullName', $row);
        if (!$result) {
            $result = val('Name', $row);
        }
        return $result;
    }
}

if (!function_exists('\Gdn::config()->touch')) {
    /**
     * Make sure the config has a setting.
     *
     * This function is useful to call in the setup/structure of plugins to
     * make sure they have some default config set.
     *
     * @param string|array $name The name of the config key or an array of config key value pairs.
     * @param mixed $default The default value to set in the config.
     *
     * @deprecated 2.8 Use Gdn_Configuration::touch()
     */
    function touchConfig($name, $default = null) {
        deprecated(__FUNCTION__, 'Gdn_Configuration::touch()');
        Gdn::config()->touch($name, $default);
    }
}

if (!function_exists('attribute')) {
    /**
     * Takes an attribute (or array of attributes) and formats them in attribute="value" format.
     *
     * @param string|array $name The attribute array or the name of the attribute.
     * @param mixed $valueOrExclude The value of the attribute or a prefix of attribute names to exclude.
     * @return string Returns a string in attribute="value" format.
     * @deprecated Use HtmlUtils::attributes() instead.
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
     * @deprecated
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
     * @deprecated
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
