<?php
/**
 * Gdn_Url
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Handles analyzing and returning various parts of the current url.
 */
class Gdn_Url {

    /**
     * Returns the path to the application's dispatcher. Optionally with the domain prepended.
     *  ie. http://domain.com/[web_root]/index.php?/request
     *
     * @param boolean $withDomain Should it include the domain with the WebRoot? Default is FALSE.
     * @return string
     */
    public static function webRoot($withDomain = false) {
        $webRoot = Gdn::request()->webRoot();

        if ($withDomain) {
            $result = Gdn::request()->domain().'/'.$webRoot;
        } else {
            $result = $webRoot;
        }

        return $result;
    }

    /**
     * Returns the domain from the current url. ie. "http://localhost/" in
     * "http://localhost/this/that/garden/index.php?/controller/action/"
     *
     * @return string
     */
    public static function domain() {
        // Attempt to get the domain from the configuration array
        return Gdn::request()->domain();
    }

    /**
     * Returns the host from the current url. ie. "localhost" in
     * "http://localhost/this/that/garden/index.php?/controller/action/"
     *
     * @return string
     */
    public static function host() {
        return Gdn::request()->requestHost();
    }

    /**
     * Returns any GET parameters from the querystring. ie. "this=that&y=n" in
     * http://localhost/index.php?/controller/action/&this=that&y=n"
     *
     * @return string
     */
    public static function queryString() {
        return http_build_query(Gdn::request()->getRequestArguments(Gdn_Request::INPUT_GET));
    }

    /**
     * Returns the Request part of the current url. ie. "/controller/action/" in
     * "http://localhost/garden/index.php?/controller/action/".
     *
     * @param boolean $withWebRoot
     * @param boolean $withDomain
     * @param boolean $removeSyndication
     * @return string
     */
    public static function request($withWebRoot = false, $withDomain = false, $removeSyndication = false) {
        $result = Gdn::request()->path();
        if ($withWebRoot) {
            $result = self::webRoot($withDomain).'/'.$result;
        }
        return $result;
    }
}
