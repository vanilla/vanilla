<?php
/**
 * General functions
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
        $srcParts = parse_url($srcPath);
        $urlParts = parse_url($url);

        if ($srcParts === false || $urlParts === false) {
            return '';
        }

        // If there is a scheme in the src path already, just return it.
        if (!empty($srcParts['scheme'])) {
            if (in_array($srcParts['scheme'], ['http', 'https'], true)) {
                return $srcPath;
            } else {
                return '';
            }
        } elseif (empty($urlParts['scheme']) || !in_array($urlParts['scheme'], ['http', 'https'])) {
            return '';
        }

        $parts = $srcParts + $urlParts + ['path' => ''];

        if (!empty($srcParts['path']) && $srcParts['path'][0] !== '/') {
            // Work with the path in the url & the provided src path to backtrace if necessary
            $urlPathParts = explode('/', trim(str_replace('\\', '/', $urlParts['path'] ?? ''), '/'));
            $srcPathParts = explode('/', str_replace('\\', '/', $srcParts['path']));
            foreach ($srcPathParts as $part) {
                if (!$part || $part == '.') {
                    continue;
                }

                if ($part == '..') {
                    array_pop($urlPathParts);
                } else {
                    $urlPathParts[] = $part;
                }
            }

            $parts['path'] = '/'.implode('/', $urlPathParts);
        }

        $result = "{$parts['scheme']}://{$parts['host']}{$parts['path']}";
        return $result;
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

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
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

if (!function_exists('arrayPathExists')) {
    /**
     * Whether a sequence of keys (path) exists or not in an array.
     *
     * This function should only be used if isset($array[$key1][$key2]) cannot be used because the value could be null.
     *
     * @param array $keys The sequence of keys (path) to test against the array.
     * @param array $array The array to search.
     * @param mixed $value The path value.
     *
     * @return bool Returns true if the path exists in the array or false otherwise.
     */
    function arrayPathExists(array $keys, array $array, &$value = null) {
        if (!count($keys) || !count($array)) {
            return false;
        }

        $target = $array;
        do {
            $key = array_shift($keys);

            if (array_key_exists($key, $target)) {
                $target = $target[$key];
            } else {
                return false;
            }
        } while (($countKeys = count($keys)) && is_array($target));

        $found = $countKeys === 0;
        if ($found) {
            $value = $target;
        }

        return $found;
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
     * @param string $value The value to find in array.
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
     * @return string Returns the concatenated path.
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

        $fn = function ($array, $previousLevel = null) use ($sep, &$fn, &$result) {
            foreach ($array as $key => $value) {
                $currentLevel = $previousLevel ? "{$previousLevel}{$sep}{$key}" : $key;

                if (is_array($value)) {
                    $fn($value, $currentLevel);
                } else {
                    $result[$currentLevel] = $value;
                }
            }
        };

        $fn($array);

        return $result;
    }
}

if (!function_exists('unflattenArray')) {

    /**
     * Convert a flattened array into a multi dimensional array.
     *
     * See {@link flattenArray}
     *
     * @param string $sep The string used to separate keys.
     * @param array $array The array to flatten.
     * @return array|bool Returns the flattened array or false.
     */
    function unflattenArray($sep, $array) {
        $result = [];

        try {
            foreach ($array as $flattenedKey => $value) {
                $keys = explode($sep, $flattenedKey);

                $target = &$result;
                while (count($keys) > 1) {
                    $key = array_shift($keys);
                    if (!array_key_exists($key, $target)) {
                        $target[$key] = [];
                    }
                    $target = &$target[$key];
                }

                $key = array_shift($keys);
                $target[$key] = $value;
                unset($target);
            }
        } catch (\Throwable $t) {
            $result = false;
        }

        return $result;
    }
}

if (!function_exists('safePrint')) {
    /**
     * Return/print human-readable and non casted information about a variable.
     *
     * @param mixed $mixed The variable to return/echo.
     * @param bool $returnData Whether or not to return the data instead of echoing it.
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

        // Sort by size, biggest one first
        $imageSort = [];
        // Only look at first 4 images (speed!)
        $i = 0;
        foreach ($images as $imageInfo) {
            $image = $imageInfo['Src'];

            if (empty($image) || strpos($image, 'doubleclick.') !== false) {
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
     *
     * @deprecated we are now storing IPV6, IPV4 notation is not required
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

            // Filter empty mentions
            if ($mention) {
                $mentions[] = $mention;
            }
        }

        return $mentions;
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
        if (substr($path, -1) === DS) {
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

if (!function_exists('jsonFilter')) {
    /**
     * Prepare data for json_encode.
     *
     * @param mixed $value
     */
    function jsonFilter(&$value) {
        $fn = function (&$value, $key = '', $parentKey = '') use (&$fn) {
            if (is_array($value)) {
                array_walk($value, function (&$childValue, $childKey) use ($fn, $key) {
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
     * @param string $limitOrPageSize The page size or limit.
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

if (!function_exists('reflectArgs')) {
    /**
     * Reflect the arguments on a callback and returns them as an associative array.
     *
     * @param callback|ReflectionFunctionAbstract $callback A callback to the function.
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
        } elseif ($callback instanceof ReflectionFunctionAbstract) {
            $meth = $callback;
        } else {
            $meth = new ReflectionMethod($callback[0], $callback[1]);
        }

        if ($meth instanceof ReflectionMethod) {
            $methName = $meth->getDeclaringClass()->getName().'::'.$meth->getName();
        } else {
            $methName = $meth->getName();
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

if (!function_exists('sliceParagraph')) {
    /**
     * Slices a string at a paragraph.
     *
     * This function will attempt to slice a string at paragraph that is no longer than the specified maximum length.
     * If it can't slice the string at a paragraph it will attempt to slice on a sentence.
     *
     * Note that you should not expect this function to return a string that is always shorter than max-length.
     * The purpose of this function is to provide a string that is reasonably easy to consume by a human.
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
     * @param mixed $haystack The array or object to set.
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

if (!function_exists('userAgentType')) {
    /**
     * Get or set the type of user agent.
     *
     * This method checks the user agent to try and determine the type of device making the current request.
     * It also checks for a special X-UA-Device header that a server module can set to more quickly determine the device.
     *
     * @param string|null|false $value The new value to set or **false** to clear. This should be one of desktop, mobile, tablet, or app.
     * @return string Returns one of desktop, mobile, tablet, or app.
     */
    function userAgentType($value = null) {
        static $type = null;

        if ($value === false) {
            $type = null;
            return '';
        } elseif ($value !== null) {
            $type = $value;
        }

        if ($type !== null) {
            return $type;
        }

        // A function to make sure the type is one of our supported types.
        $validateType = function (string $type): string {
            $validTypes = ['desktop', 'tablet', 'app', 'mobile'];

            if (in_array($type, $validTypes)) {
                return $type;
            } else {
                // There is no exact match so look for a partial match.
                foreach ($validTypes as $validType) {
                    if (strpos($type, $validType) !== false) {
                        return $validType;
                    }
                }
            }
            return 'desktop';
        };

        // Try and get the user agent type from the header if it was set from the server, varnish, etc.
        $type = strtolower(val('HTTP_X_UA_DEVICE', $_SERVER, ''));
        if ($type) {
            return $validateType($type);
        }

        // See if there is an override in the cookie.
        if ($type = val('X-UA-Device-Force', $_COOKIE)) {
            return $validateType($type);
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
                if (stringEndsWith($key, 'IPAddress', true) || stringEndsWith($parent, 'IPAddresses', true) || $key === 'IP') {
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
                if (stringEndsWith($key, 'IPAddress', true) || stringEndsWith($parent, 'IPAddresses', true) || $key === 'IP') {
                    $val = ipDecode($val);
                }
            }
        });
        return $input;
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
