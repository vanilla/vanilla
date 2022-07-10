<?php
/**
 * General functions Interim Compatibility Map.
 *
 * These functions are copies of existing functions but with new and improved
 * names. Parent functions will be deprecated in a future release.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.2
 */

use Garden\Web\Cookie;
use Vanilla\Logging\ErrorLogger;

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
     * @param array|false $new_url If set, it will be filled with the parts of the composed url like parse_url() would return.
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
        if (($_SERVER['HTTPS'] ?? '') === "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if (($_SERVER["SERVER_PORT"] ?? "80") != "80") {
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
     *
     * @deprecated After 5000+ uses, it's time to say goodbye to our dear friend because we suspect it's wasting memory. We'll miss you, `val()`. Alternatives: `isset()`, `property_exists()`, `??`, and `:?`.
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

        //TODO: Remove this after cleaning up header newline error(s)
        if (strpos($header, "\n") !== false) {
            ErrorLogger::warning(
                "Header contained newline"
                ["headers"],
                [
                    "header" => $header,
                ]
            );
        }

        if ($context == 'http') {
            header($header, $replace, $http_response_code);
        }
    }
}

if (!function_exists('safeCookie')) {
    /**
     * Context-aware call to \Garden\Web\Cookie setCookie().
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
     * @param string|null $sameSite This could be one of (\Garden\Web\Cookie::SAME_SITE_NONE, SAME_SITE_LAX, SAME_SITE_STRICT).
     * @throws \Garden\Container\ContainerException If there was an error while retrieving an item from the container.
     * @throws \Garden\Container\NotFoundException If the item was not found in the container.
     */
    function safeCookie($name, $value = null, $expire = 0, $path = null, $domain = null, $secure = null, $httponly = false, $sameSite = null) {
        static $context = null;
        if (is_null($context)) {
            $context = requestContext();
        }

        if ($context == 'http') {
            /** @var Cookie $cookie */
            $cookie = Gdn::getContainer()->get(Cookie::class);
            $cookie->setCookie('/'.$name, $value, $expire, $path, $domain, $secure, $httponly, $sameSite);
        }
    }
}
