<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 4.0
 */

use Vanilla\Web\CacheControlMiddleware;

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
            $result = Gdn::request()->urlDomain($withDomain).Gdn::request()->getAssetRoot().'/'.ltrim($destination, '/');
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

if (!function_exists('c')) {
    /**
     * Retrieves a configuration setting.
     *
     * @param string|bool $name The name of the configuration setting.
     * Settings in different sections are separated by dots.
     * @param mixed $default The result to return if the configuration setting is not found.
     * @return mixed The configuration setting.
     * @see Gdn::config()
     * @deprecated
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
     * @deprecated
     */
    function config($name = false, $default = false) {
        return Gdn::config($name, $default);
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

if (!function_exists('paramPreference')) {
    /**
     * Conditionally save and load a query parameter value from a user's preferences.
     *     If the parameter is not sent in the request query, attempt to load from the user's preferences.
     *     If the parameter is set, save to the user's preferences.
     *
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
    function _formatstringcallback($match, $setArgs = false) {
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
        $format = trim(($parts[1] ?? ''));
        $subFormat = isset($parts[2]) ? strtolower(trim($parts[2])) : '';
        $formatArgs = $parts[3] ?? '';

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
                            $result = ($parts[5] ?? ($parts[4] ?? false));
                            break;
                        case 'u':
                        default:
                            $result = ($parts[4] ?? false);
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

                                $separator = '';
                                if ($i == $count - 1) {
                                    $separator = ' '.t('sep and', 'and').' ';
                                } elseif ($i > 0) {
                                    $separator = ', ';
                                }
                                $special = [-1 => t('everyone'), -2 => t('moderators'), -3 => t('administrators')];
                                if (isset($special[$iD])) {
                                    $result .= $separator.$special[$iD];
                                } else {
                                    $user = Gdn::userModel()->getID($iD);
                                    if ($user && $user->Deleted == 0) {
                                        $user->Name = formatUsername($user, $format, $contextUserID);
                                        $result .= $separator.userAnchor($user);
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

if (!function_exists('getMentions')) {
    /**
     * Get all usernames mentioned in an HTML string.
     *
     * Optionally skips the contents of an anchor tag <a> or a code tag <code>.
     *
     * @param string $html The html-formatted string to parse.
     * @param bool $skipAnchors Whether to call the callback function on anchor tag content.
     * @param bool $skipCode Whether to call the callback function on code tag content.
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
                if ($row) {
                    if (!$activityModel->canView($row)) {
                        throw permissionException();
                    }

                    $row['Name'] = $row['ActivityName'];
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

        if (defined('TESTMODE_ENABLED') && constant('TESTMODE_ENABLED')) {
            $trusted = null;
        }

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

if (!function_exists('joinRecords')) {
    /**
     * Join external records to an array.
     *
     * @param array $data The data to join.
     * In order to join records each row must have the a RecordType and RecordID column.
     * @param string $column The name of the column to put the record in.
     * If this is blank then the record will be merged into the row.
     * @param bool $unset Whether or not to unset rows that don't have a record.
     * @param bool $checkCategoryPermission Only include results from categories the user has access to.
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
     * @throws Exception If an error occurred while encoding.
     * @deprecated 2.8 Use \Vanilla\Utility\StringUtils::jsonEncodeChecked instead.
     */
    function jsonEncodeChecked($value, $options = null) {
        return \Vanilla\Utility\StringUtils::jsonEncodeChecked($value, $options);
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

        if ($statusCode === 302) {
            CacheControlMiddleware::sendCacheControlHeaders(CacheControlMiddleware::NO_CACHE);
        }

        if (Gdn::controller() !== null
            && in_array(Gdn::controller()->deliveryType(), [DELIVERY_TYPE_ASSET, DELIVERY_TYPE_VIEW], true)
            && Gdn::controller()->deliveryMethod() === DELIVERY_METHOD_JSON) {
            // This is a bit of a kludge, but it solves a perpetual gotcha when we switch full page forms to AJAX forms and forget about redirects.
            echo json_encode([
                'FormSaved' => true,
                'RedirectUrl' => $url,
                'RedirectTo' => $url,
            ]);
        } else {
            safeHeader('Location: ' . $url, true, $statusCode);
        }
        exit();
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

if (!function_exists('safeURL')) {
    /**
     * Transform a destination to make sure that the resulting URL is "Safe".
     *
     * "Safe" means that the domain of the URL is trusted.
     *
     * @param string $destination Destination URL or path.
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
     * @deprecated
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
            $result = Gdn::request()->urlDomain($withDomain).Gdn::request()->getAssetRoot().'/'.ltrim($destination, '/');
        }

        if ($addVersion) {
            $version = assetVersion($destination);
            $result .= (strpos($result, '?') === false ? '?' : '&').'v='.urlencode($version);
        }
        return $result;
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

if (!function_exists('t')) {
    /**
     * Translates a code into the selected locale's definition.
     *
     * @param string $code The code related to the language-specific definition.
     *   Codes that begin with an '@' symbol are treated as literals and not translated.
     * @param string $default The default value to be displayed if the translation code is not found.
     * @return string The translated string or $code if there is no value in $default.
     * @see Gdn::translate()
     * @deprecated
     */
    function t($code, $default = false) {
        return Gdn::translate($code, $default);
    }
}

if (!function_exists('TagUrl')) {
    /**
     * Get a URL to a list of discussions with the specified tag.
     *
     * @param array|object $row
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
     * @deprecated
     */
    function translateContent($code, $default = false) {
        \Vanilla\Utility\Deprecation::log();
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
        $result = Gdn::request()->url(strval($path), $withDomain);
        return $result;
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
