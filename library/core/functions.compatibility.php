<?php
/**
 * General functions Interim Compatibility Map.
 *
 * These functions are copies of existing functions but with new and improved
 * names. Parent functions will be deprecated in a future release.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.2
 */

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

        $resultArray = [];

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

if (!function_exists('apc_fetch') && function_exists('apcu_fetch')) {
    /**
     * Fetch a stored variable from the cache.
     *
     * @param mixed $key The key used to store the value.
     * @param bool &$success Set to **true** in success and **false** in failure.
     * @return mixed The stored variable or array of variables on success; **false** on failure
     * @see http://php.net/manual/en/function.apcu-fetch.php
     */
    function apc_fetch($key, &$success = null) {
        return apcu_fetch($key, $success);
    }
}

if (!function_exists('apc_store') && function_exists('apcu_store')) {
    /**
     * Cache a variable in the data store.
     *
     * @param string $key Store the variable using this name.
     * @param mixed $var The variable to store.
     * @param int $ttl The time to live.
     * @return bool Returns **true** on success or **false** on failure.
     * @see http://php.net/manual/en/function.apcu-store.php
     */
    function apc_store($key, $var = null, $ttl = 0) {
        return apcu_store($key, $var, $ttl);
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
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

/**
 * Allow gzopen64 to be a fallback for gzopen. Workaround for a PHP bug.
 *
 * @see http://php.net/manual/en/function.gzopen.php
 * @return mixed File pointer or false.
 */
if (!function_exists('gzopen') && function_exists('gzopen64')) {
    function gzopen($filename, $mode, $use_include_path = 0) {
        return gzopen64($filename, $mode, $use_include_path);
    }
}

if (!function_exists('hash_equals')) {
    /**
     * Determine whether or not two strings are equal in a time that is independent of partial matches.
     *
     * This snippet prevents HMAC Timing attacks (http://codahale.com/a-lesson-in-timing-attacks/).
     * Thanks to Eric Karulf (ekarulf @ github) for this fix.
     *
     * @param string $known_string The string of known length to compare against.
     * @param string $user_string The user-supplied string.
     * @return bool Returns **true** when the two strings are equal, **false** otherwise.
     * @see http://php.net/manual/en/function.hash-equals.php
     */
    function hash_equals($known_string, $user_string) {
        if (strlen($known_string) !== strlen($user_string)) {
            return false;
        }

        $result = 0;
        for ($i = strlen($known_string) - 1; $i >= 0; $i--) {
            $result |= ord($known_string[$i]) ^ ord($user_string[$i]);
        }

        return 0 === $result;
    }
}

if (!function_exists('http_build_url')) {
    define('HTTP_URL_REPLACE', 1);  // Replace every part of the first URL when there's one of the second URL
    define('HTTP_URL_JOIN_PATH', 2); // Join relative paths
    define('HTTP_URL_JOIN_QUERY', 4); // Join query strings
    define('HTTP_URL_STRIP_USER', 8); // Strip any user authentication information
    define('HTTP_URL_STRIP_PASS', 16); // Strip any password authentication information
    define('HTTP_URL_STRIP_AUTH', 32); // Strip any authentication information
    define('HTTP_URL_STRIP_PORT', 64); // Strip explicit port numbers
    define('HTTP_URL_STRIP_PATH', 128); // Strip complete path
    define('HTTP_URL_STRIP_QUERY', 256); // Strip query string
    define('HTTP_URL_STRIP_FRAGMENT', 512); // Strip any fragments (#identifier)
    define('HTTP_URL_STRIP_ALL', 1024); // Strip anything but scheme and host

    /**
     * Takes an associative array in the layout of parse_url, and constructs a URL from it.
     *
     * @param mixed $url part(s) of a URL in form of a string or associative array like parse_url() returns.
     * @param mixed $parts Same as the first argument.
     * @param int $flags A bit mask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default.
     * @param array &$new_url If set, it will be filled with the parts of the composed url like parse_url() would return.
     * @return  string  Returns the constructed URL.
     * @see http://www.php.net/manual/en/function.http-build-url.php#96335
     * @see https://github.com/fuel/core/blob/974281dde67345ca8d7cfa27bcf4aa55c984d48e/base.php#L248
     * @see http://stackoverflow.com/questions/7751679/php-http-build-url-and-pecl-install/7753154#comment11239561_7753154
     */
    function http_build_url($url, $parts = [], $flags = HTTP_URL_REPLACE, &$new_url = false) {
        $keys = ['user', 'pass', 'port', 'path', 'query', 'fragment'];

        // HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
        if ($flags & HTTP_URL_STRIP_ALL) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
            $flags |= HTTP_URL_STRIP_PORT;
            $flags |= HTTP_URL_STRIP_PATH;
            $flags |= HTTP_URL_STRIP_QUERY;
            $flags |= HTTP_URL_STRIP_FRAGMENT;
        } elseif ($flags & HTTP_URL_STRIP_AUTH) {
            // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
        }

        // parse the current URL
        $current_url = parse_url(current_url());

        // parse the original URL
        $parse_url = is_array($url) ? $url : parse_url($url);

        // make sure we always have a scheme, host and path
        empty($parse_url['scheme']) and $parse_url['scheme'] = $current_url['scheme'];
        empty($parse_url['host']) and $parse_url['host'] = $current_url['host'];
        isset($parse_url['path']) or $parse_url['path'] = '';

        // make the path absolute if needed
        if (!empty($parse_url['path']) and substr($parse_url['path'], 0, 1) != '/') {
            $parse_url['path'] = '/'.$parse_url['path'];
        }

        // scheme and host are always replaced
        isset($parts['scheme']) and $parse_url['scheme'] = $parts['scheme'];
        isset($parts['host']) and $parse_url['host'] = $parts['host'];

        // replace the original URL with it's new parts (if applicable)
        if ($flags & HTTP_URL_REPLACE) {
            foreach ($keys as $key) {
                if (isset($parts[$key])) {
                    $parse_url[$key] = $parts[$key];
                }
            }
        } else {
            // join the original URL path with the new path
            if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
                if (isset($parse_url['path'])) {
                    $parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/').'/'.ltrim($parts['path'], '/');
                } else {
                    $parse_url['path'] = $parts['path'];
                }
            }

            // join the original query string with the new query string
            if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
                if (isset($parse_url['query'])) {
                    $parse_url['query'] .= '&'.$parts['query'];
                } else {
                    $parse_url['query'] = $parts['query'];
                }
            }
        }

        // strips all the applicable sections of the URL
        // note: scheme and host are never stripped
        foreach ($keys as $key) {
            if ($flags & (int)constant('HTTP_URL_STRIP_'.strtoupper($key))) {
                unset($parse_url[$key]);
            }
        }


        $new_url = $parse_url;

        return
            ((isset($parse_url['scheme'])) ? $parse_url['scheme'].'://' : '')
            .((isset($parse_url['user'])) ? $parse_url['user'].((isset($parse_url['pass'])) ? ':'.$parse_url['pass'] : '').'@' : '')
            .((isset($parse_url['host'])) ? $parse_url['host'] : '')
            .((isset($parse_url['port'])) ? ':'.$parse_url['port'] : '')
            .((isset($parse_url['path'])) ? $parse_url['path'] : '')
            .((isset($parse_url['query'])) ? '?'.$parse_url['query'] : '')
            .((isset($parse_url['fragment'])) ? '#'.$parse_url['fragment'] : '');
    }

    function current_url() {
        $pageURL = 'http';
        if (val('HTTPS', $_SERVER) === "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }
}

if (!function_exists('is_id')) {
    /**
     * Finds whether the type given variable is a database id.
     *
     * @param mixed $val The variable being evaluated.
     * @return bool Returns true if the variable is a database id or false if it isn't.
     */
    function is_id($val) {
        return is_numeric($val);
    }
}

if (!function_exists('parse_ini_string')) {
    /**
     * The parse_ini_string function is not supported until PHP 5.3.0, and we currently support PHP 5.2.0.
     *
     * @param string $ini The INI string to parse.
     * @return array Returns the array representation of the INI string.
     */
    function parse_ini_string($ini) {
        $lines = explode("\n", $ini);
        $result = [];
        foreach ($lines as $line) {
            $parts = explode('=', $line, 2);
            if (count($parts) == 1) {
                $result[trim($parts[0])] = '';
            } elseif (count($parts) >= 2) {
                $result[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $result;
    }
}

if (!function_exists('paths')) {
    /**
     * Concatenate path elements into single string.
     *
     * Takes a variable number of arguments and concatenates them. Delimiters will
     * not be duplicated. Example: all of the following invocations will generate
     * the path "/path/to/vanilla/applications/dashboard"
     *
     * '/path/to/vanilla', 'applications/dashboard'
     * '/path/to/vanilla/', '/applications/dashboard'
     * '/path', 'to', 'vanilla', 'applications', 'dashboard'
     * '/path/', '/to/', '/vanilla/', '/applications/', '/dashboard'
     *
     * @return string Returns the concatenated path.
     */
    function paths() {
        $paths = func_get_args();
        $delimiter = '/';
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

if (!function_exists('val')) {
    /**
     * Return the value from an associative array or an object.
     *
     * @param string $key The key or property name of the value.
     * @param mixed $collection The array or object to search.
     * @param mixed $default The value to return if the key does not exist.
     * @return mixed The value from the array or object.
     */
    function val($key, $collection, $default = false) {
        if (is_array($collection)) {
            if (array_key_exists($key, $collection)) {
                return $collection[$key];
            } else {
                return $default;
            }
        } elseif (is_object($collection) && property_exists($collection, $key)) {
            return $collection->$key;
        }
        return $default;
    }
}

if (!function_exists('valr')) {
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
    function valr($key, $collection, $default = false) {
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

/**
 * Set a key to a value in a collection.
 *
 * Works with single keys or "dot" notation. If $key is an array, a simple
 * shallow array_merge is performed.
 *
 * @param string $key The key or property name of the value.
 * @param array &$collection The array or object to search.
 * @param mixed $value The value to set.
 * @return mixed Newly set value or if array merge.
 */
function setvalr($key, &$collection, $value = null) {
    if (is_array($key)) {
        $collection = array_merge($collection, $key);
        return null;
    }

    if (strpos($key, '.')) {
        $path = explode('.', $key);

        $selection = &$collection;
        $mx = count($path) - 1;
        for ($i = 0; $i <= $mx; ++$i) {
            $subSelector = $path[$i];

            if (is_array($selection)) {
                if (!isset($selection[$subSelector])) {
                    $selection[$subSelector] = [];
                }
                $selection = &$selection[$subSelector];
            } elseif (is_object($selection)) {
                if (!isset($selection->$subSelector)) {
                    $selection->$subSelector = new stdClass();
                }
                $selection = &$selection->$subSelector;
            } else {
                return null;
            }
        }
        return $selection = $value;
    } else {
        if (is_array($collection)) {
            return $collection[$key] = $value;
        } else {
            return $collection->$key = $value;
        }
    }
}

if (!function_exists('svalr')) {
    /**
     * Set a key to a value in a collection.
     *
     * Works with single keys or "dot" notation. If $key is an array, a simple
     * shallow array_merge is performed.
     *
     * @param string $key The key or property name of the value.
     * @param array &$collection The array or object to search.
     * @param mixed $value The value to set.
     * @return mixed Newly set value or if array merge
     * @deprecated Use {@link setvalr()}.
     */
    function svalr($key, &$collection, $value = null) {
        setvalr($key, $collection, $value);
    }
}

if (!function_exists('requestContext')) {
    /**
     * Get request context.
     *
     * This method determines if current request is operating within HTTP, or
     * elsewhere such as the command line.
     *
     * @staticvar string $context
     * @return string
     */
    function requestContext() {
        static $context = null;
        if (is_null($context)) {
            $context = c('Garden.RequestContext', null);
            if (is_null($context)) {
                $protocol = val('SERVER_PROTOCOL', $_SERVER);
                if (preg_match('`^HTTP/`', $protocol)) {
                    $context = 'http';
                } else {
                    $context = $protocol;
                }
            }
            if (is_null($context)) {
                $context = 'unknown';
            }
        }
        return $context;
    }
}

if (!function_exists('safeHeader')) {
    /**
     * Context-aware call to header().
     *
     * This method is context-aware and will avoid sending headers if the request
     * context is not HTTP.
     *
     * @param string $header
     * @param bool $replace
     * @param int|null $http_response_code
     */
    function safeHeader($header, $replace = true, $http_response_code = null) {
        static $context = null;
        if (headers_sent()) {
            return false;
        }
        if (is_null($context)) {
            $context = requestContext();
        }

        if ($context == 'http') {
            header($header, $replace, $http_response_code);
        }
    }
}

if (!function_exists('safeCookie')) {
    /**
     * Context-aware call to setcookie().
     *
     * This method is context-aware and will avoid setting cookies if the request
     * context is not HTTP.
     *
     * @param string $name
     * @param string $value
     * @param integer $expire
     * @param string $path
     * @param string $domain
     * @param boolean|null $secure
     * @param boolean $httponly
     */
    function safeCookie($name, $value = null, $expire = 0, $path = null, $domain = null, $secure = null, $httponly = false) {
        static $context = null;
        if (is_null($context)) {
            $context = requestContext();
        }

        if ($context == 'http') {
            if ($secure === null && c('Garden.ForceSSL') && Gdn::request()->scheme() === 'https') {
                $secure = true;
            }

            setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }
}
