<?php

use Garden\Http\HttpClient;
use Vanilla\Web\SafeCurlHttpHandler;

/**
 * This class provides a simple interface for OpenID (1.1 and 2.0) authentication.
 * Supports Yadis discovery.
 * The authentication process is stateless/dumb.
 *
 * Usage:
 * Sign-on with OpenID is a two step process:
 * Step one is authentication with the provider:
 * <code>
 * $openid = new LightOpenID;
 * $openid->identity = 'ID supplied by user';
 * header('Location: ' . $openid->authUrl());
 * </code>
 * The provider then sends various parameters via GET, one of them is openid_mode.
 * Step two is verification:
 * <code>
 * if ($this->data['openid_mode']) {
 *     $openid = new LightOpenID;
 *     echo $openid->validate() ? 'Logged in.' : 'Failed';
 * }
 * </code>
 *
 * Optionally, you can set $returnUrl and $realm (or $trustRoot, which is an alias).
 * The default values for those are:
 * $openid->realm     = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
 * $openid->returnUrl = $openid->realm . $_SERVER['REQUEST_URI'];
 * If you don't know their meaning, refer to any openid tutorial, or specification. Or just guess.
 *
 * AX and SREG extensions are supported.
 * To use them, specify $openid->required and/or $openid->optional before calling $openid->authUrl().
 * These are arrays, with values being AX schema paths (the 'path' part of the URL).
 * For example:
 *   $openid->required = array('namePerson/friendly', 'contact/email');
 *   $openid->optional = array('namePerson/first');
 * If the server supports only SREG or OpenID 1.1, these are automaticaly
 * mapped to SREG names, so that user doesn't have to know anything about the server.
 *
 * To get the values, use $openid->getAttributes().
 *
 *
 * The library requires PHP >= 5.1.2 with curl or http/https stream wrappers enabled.
 * @author Mewp
 * @copyright Copyright (c) 2010, Mewp
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class LightOpenID {
    public $returnUrl
    , $required = []
    , $optional = []
    , $verify_peer = null
    , $capath = null
    , $cainfo = null;
    private $identity, $claimed_id;
    protected $httpClient;
    protected $server, $version, $trustRoot, $aliases, $identifier_select = false
    , $ax = false, $sreg = false, $data;
    static protected $ax_to_sreg = [
        'namePerson/friendly' => 'nickname',
        'contact/email' => 'email',
        'namePerson' => 'fullname',
        'birthDate' => 'dob',
        'person/gender' => 'gender',
        'contact/postalCode/home' => 'postcode',
        'contact/country/home' => 'country',
        'pref/language' => 'language',
        'pref/timezone' => 'timezone',
    ];

    public function __construct() {
        $safeCurlHandler = new SafeCurlHttpHandler();
        $safeCurlHandler->setFollowLocation(false);
        $options = [
            'timeout' => 30,
            'verifyPeer' => false
        ];
        $httpClient = new HttpClient('', $safeCurlHandler);
        $httpClient->setDefaultOptions($options);
        $this->httpClient = $httpClient;
        $this->trustRoot = Gdn::request()->scheme().'://'.Gdn::request()->host();
        $uri = rtrim(preg_replace('#((?<=\?)|&)openid\.[^&]+#', '', $_SERVER['REQUEST_URI']), '?');
        $this->returnUrl = $this->trustRoot.$uri;

        $this->data = $_POST + $_GET; # OPs may send data as POST or GET.
    }

    public function __set($name, $value) {
        switch ($name) {
            case 'identity':
                if (strlen($value = trim((String)$value))) {
                    if (preg_match('#^xri:/*#i', $value, $m)) {
                        $value = substr($value, strlen($m[0]));
                    } elseif (!preg_match('/^(?:[=@+\$!\(]|https?:)/i', $value)) {
                        $value = "http://$value";
                    }
                    if (preg_match('#^https?://[^/]+$#i', $value, $m)) {
                        $value .= '/';
                    }
                }
                $this->$name = $this->claimed_id = $value;
                break;
            case 'trustRoot':
            case 'realm':
                $this->trustRoot = trim($value);
        }
    }

    public function __get($name) {
        switch ($name) {
            case 'identity':
                # We return claimed_id instead of identity,
                # because the developer should see the claimed identifier,
                # i.e. what he set as identity, not the op-local identifier (which is what we verify)
                return $this->claimed_id;
            case 'trustRoot':
            case 'realm':
                return $this->trustRoot;
            case 'mode':
                return empty($this->data['openid_mode']) ? null : $this->data['openid_mode'];
        }
    }

    protected function fix_url($url) {
        // Fix a malformed return url.
        $urlp = parse_url($url);
        if (isset($urlp['query'])) {
            parse_str($urlp['query'], $query);
            $urlp['query'] = http_build_query($query);

            $url = "{$urlp['scheme']}://{$urlp['host']}{$urlp['path']}?{$urlp['query']}";
        }
        return $url;
    }

    /**
     * Checks if the server specified in the url exists.
     *
     * @param $url url to check
     * @return true, if the server exists; false otherwise
     */
    public function hostExists($url) {
        if (strpos($url, '/') === false) {
            $server = $url;
        } else {
            $server = @parse_url($url, PHP_URL_HOST);
        }

        if (!$server) {
            return false;
        }

        return !!gethostbynamel($server);
    }

    /**
     * Prepares the httpClient request.
     *
     * @param string $url OpenID url.
     * @param string $method
     * @param array $params
     * @return \Garden\Http\HttpResponse
     */
    protected function request($url, $method = 'GET', $params = []) {
        $timeStart = microtime(true);
        if ($method === 'POST') {
            $result = $this->httpClient->post($url, $params);
        } elseif ($method === 'HEAD') {
            $result = $this->httpClient->head($url, $params);
        } else {
            $result = $this->httpClient->get($url, $params);
        }
        $timeDiff = microtime(true) - $timeStart;
        // Make sure every request takes at least .5 second.
        // This nullify brute forcing
        if ($timeDiff < 500) {
            usleep($timeDiff);
        }

        return $result;
    }

    protected function build_url($url, $parts) {
        if (isset($url['query'], $parts['query'])) {
            $parts['query'] = $url['query'].'&'.$parts['query'];
        }

        $url = $parts + $url;
        $url = $url['scheme'].'://'
            .(empty($url['username']) ? ''
                : (empty($url['password']) ? "{$url['username']}@"
                    : "{$url['username']}:{$url['password']}@"))
            .$url['host']
            .(empty($url['port']) ? '' : ":{$url['port']}")
            .(empty($url['path']) ? '' : $url['path'])
            .(empty($url['query']) ? '' : "?{$url['query']}")
            .(empty($url['fragment']) ? '' : "#{$url['fragment']}");
        return $url;
    }

    /**
     * Helper function used to scan for <meta>/<link> tags and extract information from them
     *
     * @param string $tag
     * @param string $attrName
     * @param string $attrValue
     * @param string $valueName
     * @return string
     */
    protected function htmlTag($content, $tag, $attrName, $attrValue, $valueName) {
        preg_match_all("#<{$tag}[^>]*$attrName=['\"].*?$attrValue.*?['\"][^>]*$valueName=['\"](.+?)['\"][^>]*/?>#i", $content, $matches1);
        preg_match_all("#<{$tag}[^>]*$valueName=['\"](.+?)['\"][^>]*$attrName=['\"].*?$attrValue.*?['\"][^>]*/?>#i", $content, $matches2);

        $result = array_merge($matches1[1], $matches2[1]);
        return empty($result) ? false : $result[0];
    }

    /**
     * Performs Yadis and HTML discovery. Normally not used.
     * @param $url Identity URL.
     * @return String OP Endpoint (i.e. OpenID provider address).
     * @throws ErrorException
     */
    public function discover($url) {
        if (!$url) {
            throw new ErrorException('No identity supplied.');
        }
        # Use xri.net proxy to resolve i-name identities
        if (!preg_match('#^https?:#', $url)) {
            $url = "https://xri.net/$url";
        }

        # We save the original url in case of Yadis discovery failure.
        # It can happen when we'll be lead to an XRDS document
        # which does not have any OpenID2 services.
        $originalUrl = $url;

        # A flag to disable yadis discovery in case of failure in headers.
        $yadis = true;

        # We'll jump a maximum of 5 times, to avoid endless redirections.
        for ($i = 0; $i < 5; $i++) {
            if ($yadis) {
                $headers = $this->request($url, 'HEAD');

                $next = false;
                if (isset($headers['x-xrds-location'])) {
                    $url = $this->build_url(parse_url($url), parse_url(trim($headers['x-xrds-location'])));
                    $next = true;
                }
                $header = $headers->getHeader('content-type');
                if ($header && (strpos($header, 'application/xrds+xml') !== false
                        || (strpos($header, 'text/xml') !== false))) {
                    # Apparently, some providers return XRDS documents as text/html.
                    # While it is against the spec, allowing this here shouldn't break
                    # compatibility with anything.
                    # ---
                    # Found an XRDS document, now let's find the server, and optionally delegate.
                    $content = $this->request($url, 'GET')->getRawBody();
                    preg_match_all('#<Service.*?>(.*?)</Service>#s', $content, $m);
                    foreach ($m[1] as $content) {
                        $content = ' '.$content; # The space is added, so that strpos doesn't return 0.

                        # OpenID 2
                        $ns = preg_quote('http://specs.openid.net/auth/2.0/');
                        if (preg_match('#<Type>\s*'.$ns.'(server|signon)\s*</Type>#s', $content, $type)) {
                            if ($type[1] == 'server') {
                                $this->identifier_select = true;
                            }

                            preg_match('#<URI.*?>(.*)</URI>#', $content, $server);
                            preg_match('#<(Local|Canonical)ID>(.*)</\1ID>#', $content, $delegate);
                            if (empty($server)) {
                                return false;
                            }
                            # Does the server advertise support for either AX or SREG?
                            $this->ax = (bool)strpos($content, '<Type>http://openid.net/srv/ax/1.0</Type>');
                            $this->sreg = strpos($content, '<Type>http://openid.net/sreg/1.0</Type>')
                                || strpos($content, '<Type>http://openid.net/extensions/sreg/1.1</Type>');

                            $server = $server[1];
                            if (isset($delegate[2])) {
                                $this->identity = trim($delegate[2]);
                            }
                            $this->version = 2;

                            $this->server = $server;
                            return $server;
                        }

                        # OpenID 1.1
                        $ns = preg_quote('http://openid.net/signon/1.1');
                        if (preg_match('#<Type>\s*'.$ns.'\s*</Type>#s', $content)) {
                            preg_match('#<URI.*?>(.*)</URI>#', $content, $server);
                            preg_match('#<.*?Delegate>(.*)</.*?Delegate>#', $content, $delegate);
                            if (empty($server)) {
                                return false;
                            }
                            # AX can be used only with OpenID 2.0, so checking only SREG
                            $this->sreg = strpos($content, '<Type>http://openid.net/sreg/1.0</Type>')
                                || strpos($content, '<Type>http://openid.net/extensions/sreg/1.1</Type>');

                            $server = $server[1];
                            if (isset($delegate[1])) {
                                $this->identity = $delegate[1];
                            }
                            $this->version = 1;

                            $this->server = $server;
                            return $server;
                        }
                    }

                    $next = true;
                    $yadis = false;
                    $url = $originalUrl;
                    $content = null;
                    break;
                }
                if ($next) {
                    continue;
                }

                # There are no relevant information in headers, so we search the body.
                $content = $this->request($url, 'GET')->getRawBody();
                $location = $this->htmlTag($content, 'meta', 'http-equiv', 'X-XRDS-Location', 'content');
                if ($location) {
                    $url = $this->build_url(parse_url($url), parse_url($location));
                    continue;
                }
            }

            if (!$content) {
                $content = $this->request($url, 'GET');
            }

            # At this point, the YADIS Discovery has failed, so we'll switch
            # to openid2 HTML discovery, then fallback to openid 1.1 discovery.
            $server = $this->htmlTag($content, 'link', 'rel', 'openid2.provider', 'href');
            $delegate = $this->htmlTag($content, 'link', 'rel', 'openid2.local_id', 'href');
            $this->version = 2;

            if (!$server) {
                # The same with openid 1.1
                $server = $this->htmlTag($content, 'link', 'rel', 'openid.server', 'href');
                $delegate = $this->htmlTag($content, 'link', 'rel', 'openid.delegate', 'href');
                $this->version = 1;
            }

            if ($server) {
                # We found an OpenID2 OP Endpoint
                if ($delegate) {
                    # We have also found an OP-Local ID.
                    $this->identity = $delegate;
                }
                $this->server = $server;
                return $server;
            }

            throw new ErrorException('No servers found!');
        }
        throw new ErrorException('Endless redirection!');
    }

    protected function sregParams() {
        $params = [];
        # We always use SREG 1.1, even if the server is advertising only support for 1.0.
        # That's because it's fully backwards compatibile with 1.0, and some providers
        # advertise 1.0 even if they accept only 1.1. One such provider is myopenid.com
        $params['openid.ns.sreg'] = 'http://openid.net/extensions/sreg/1.1';
        if ($this->required) {
            $params['openid.sreg.required'] = [];
            foreach ($this->required as $required) {
                if (!isset(self::$ax_to_sreg[$required])) {
                    continue;
                }
                $params['openid.sreg.required'][] = self::$ax_to_sreg[$required];
            }
            $params['openid.sreg.required'] = implode(',', $params['openid.sreg.required']);
        }

        if ($this->optional) {
            $params['openid.sreg.optional'] = [];
            foreach ($this->optional as $optional) {
                if (!isset(self::$ax_to_sreg[$optional])) {
                    continue;
                }
                $params['openid.sreg.optional'][] = self::$ax_to_sreg[$optional];
            }
            $params['openid.sreg.optional'] = implode(',', $params['openid.sreg.optional']);
        }
        return $params;
    }

    protected function axParams() {
        $params = [];
        if ($this->required || $this->optional) {
            $params['openid.ns.ax'] = 'http://openid.net/srv/ax/1.0';
            $params['openid.ax.mode'] = 'fetch_request';
            $this->aliases = [];
            $counts = [];
            $required = [];
            $optional = [];
            foreach (['required', 'optional'] as $type) {
                foreach ($this->$type as $alias => $field) {
                    if (is_int($alias)) {
                        $alias = strtr($field, '/', '_');
                    }
                    $this->aliases[$alias] = 'http://axschema.org/'.$field;
                    if (empty($counts[$alias])) {
                        $counts[$alias] = 0;
                    }
                    $counts[$alias] += 1;
                    ${$type}[] = $alias;
                }
            }
            foreach ($this->aliases as $alias => $ns) {
                $params['openid.ax.type.'.$alias] = $ns;
            }
            foreach ($counts as $alias => $count) {
                if ($count == 1) {
                    continue;
                }
                $params['openid.ax.count.'.$alias] = $count;
            }

            # Don't send empty ax.requied and ax.if_available.
            # Google and possibly other providers refuse to support ax when one of these is empty.
            if ($required) {
                $params['openid.ax.required'] = implode(',', $required);
            }
            if ($optional) {
                $params['openid.ax.if_available'] = implode(',', $optional);
            }
        }
        return $params;
    }

    protected function authUrl_v1() {
        $returnUrl = $this->returnUrl;
        # If we have an openid.delegate that is different from our claimed id,
        # we need to somehow preserve the claimed id between requests.
        # The simplest way is to just send it along with the return_to url.
        if ($this->identity != $this->claimed_id) {
            $returnUrl .= (strpos($returnUrl, '?') ? '&' : '?').'openid.claimed_id='.$this->claimed_id;
        }

        $params = [
                'openid.return_to' => $returnUrl,
                'openid.mode' => 'checkid_setup',
                'openid.identity' => $this->identity,
                'openid.trust_root' => $this->trustRoot,
            ] + $this->sregParams();

        return $this->build_url(parse_url($this->server), ['query' => http_build_query($params, '', '&')]);
    }

    protected function authUrl_v2($identifier_select) {
        $params = [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $this->returnUrl,
            'openid.realm' => $this->trustRoot,
        ];
        if ($this->ax) {
            $params += $this->axParams();
        }
        if ($this->sreg) {
            $params += $this->sregParams();
        }
        if (!$this->ax && !$this->sreg) {
            # If OP doesn't advertise either SREG, nor AX, let's send them both
            # in worst case we don't get anything in return.
            $params += $this->axParams() + $this->sregParams();
        }

        if ($identifier_select) {
            $params['openid.identity'] = $params['openid.claimed_id']
                = 'http://specs.openid.net/auth/2.0/identifier_select';
        } else {
            $params['openid.identity'] = $this->identity;
            $params['openid.claimed_id'] = $this->claimed_id;
        }

        return $this->build_url(parse_url($this->server), ['query' => http_build_query($params, '', '&')]);
    }

    /**
     * Returns authentication url. Usually, you want to redirect your user to it.
     *
     * @throws ErrorException
     * @return string The authentication url.
     */
    public function authUrl($identifier_select = null) {
        if (!$this->server) {
            $this->discover($this->identity);
        }

        if ($this->version == 2) {
            if ($identifier_select === null) {
                return $this->authUrl_v2($this->identifier_select);
            }
            return $this->authUrl_v2($identifier_select);
        }
        return $this->authUrl_v1();
    }

    /**
     * Performs OpenID verification with the OP.
     * @return Bool Whether the verification was successful.
     * @throws ErrorException
     */
    public function validate() {
        $this->claimed_id = isset($this->data['openid_claimed_id']) ? $this->data['openid_claimed_id'] : $this->data['openid_identity'];
        $params = [
            'openid.assoc_handle' => $this->data['openid_assoc_handle'],
            'openid.signed' => $this->data['openid_signed'],
            'openid.sig' => $this->data['openid_sig'],
        ];

        if (isset($this->data['openid_ns'])) {
            # We're dealing with an OpenID 2.0 server, so let's set an ns
            # Even though we should know location of the endpoint,
            # we still need to verify it by discovery, so $server is not set here
            $params['openid.ns'] = 'http://specs.openid.net/auth/2.0';
        } elseif (isset($this->data['openid_claimed_id'])
            && $this->data['openid_claimed_id'] != $this->data['openid_identity']
        ) {
            # If it's an OpenID 1 provider, and we've got claimed_id,
            # we have to append it to the returnUrl, like authUrl_v1 does.
            $this->returnUrl .= (strpos($this->returnUrl, '?') ? '&' : '?')
                .'openid.claimed_id='.$this->claimed_id;
        }

        if ($this->fix_url($this->data['openid_return_to']) != $this->returnUrl) {
            # The return_to url must match the url of current request.
            # I'm assuing that noone will set the returnUrl to something that doesn't make sense.
            return false;
        }

        $server = $this->discover($this->claimed_id);

        foreach (explode(',', $this->data['openid_signed']) as $item) {
            $value = $this->data['openid_'.str_replace('.', '_', $item)];
            $params['openid.'.$item] = $value;

        }

        $params['openid.mode'] = 'check_authentication';

        $response = $this->request($server, 'POST', $params);
        $responseBody = $response->getRawBody();
        return preg_match('/is_valid\s*:\s*true/i', $responseBody);
    }

    protected function getAxAttributes() {
        $alias = null;
        if (isset($this->data['openid_ns_ax'])
            && $this->data['openid_ns_ax'] != 'http://openid.net/srv/ax/1.0'
        ) { # It's the most likely case, so we'll check it before
            $alias = 'ax';
        } else {
            # 'ax' prefix is either undefined, or points to another extension,
            # so we search for another prefix
            foreach ($this->data as $key => $val) {
                if (substr($key, 0, strlen('openid_ns_')) == 'openid_ns_'
                    && $val == 'http://openid.net/srv/ax/1.0'
                ) {
                    $alias = substr($key, strlen('openid_ns_'));
                    break;
                }
            }
        }
        if (!$alias) {
            # An alias for AX schema has not been found,
            # so there is no AX data in the OP's response
            return [];
        }

        $attributes = [];
        foreach ($this->data as $key => $value) {
            $keyMatch = 'openid_'.$alias.'_value_';
            if (substr($key, 0, strlen($keyMatch)) != $keyMatch) {
                continue;
            }
            $key = substr($key, strlen($keyMatch));
            if (!isset($this->data['openid_'.$alias.'_type_'.$key])) {
                # OP is breaking the spec by returning a field without
                # associated ns. This shouldn't happen, but it's better
                # to check, than cause an E_NOTICE.
                continue;
            }
            $key = substr(
                $this->data['openid_'.$alias.'_type_'.$key],
                strlen('http://axschema.org/')
            );
            $attributes[$key] = $value;
        }
        return $attributes;
    }

    protected function getSregAttributes() {
        $attributes = [];
        $sreg_to_ax = array_flip(self::$ax_to_sreg);
        foreach ($this->data as $key => $value) {
            $keyMatch = 'openid_sreg_';
            if (substr($key, 0, strlen($keyMatch)) != $keyMatch) {
                continue;
            }
            $key = substr($key, strlen($keyMatch));
            if (!isset($sreg_to_ax[$key])) {
                # The field name isn't part of the SREG spec, so we ignore it.
                continue;
            }
            $attributes[$sreg_to_ax[$key]] = $value;
        }
        return $attributes;
    }

    /**
     * Gets AX/SREG attributes provided by OP. should be used only after successful validaton.
     * Note that it does not guarantee that any of the required/optional parameters will be present,
     * or that there will be no other attributes besides those specified.
     * In other words. OP may provide whatever information it wants to.
     *     * SREG names will be mapped to AX names.
     *     * @return Array Array of attributes with keys being the AX schema names, e.g. 'contact/email'
     * @see http://www.axschema.org/types/
     */
    public function getAttributes() {
        if (isset($this->data['openid_ns'])
            && $this->data['openid_ns'] == 'http://specs.openid.net/auth/2.0'
        ) { # OpenID 2.0
            # We search for both AX and SREG attributes, with AX taking precedence.
            return $this->getAxAttributes() + $this->getSregAttributes();
        }
        return $this->getSregAttributes();
    }

    /**
     * Get data for the OpenID authentication attempt.
     *
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Set OpenID authentication data.
     *
     * @param array $data
     * @return array
     */
    public function setData(array $data) {
        $this->data = $data;
        return $this->data;
    }
}
