<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GNU GPLv2 http://www.opensource.org/licenses/gpl-2.0.php
 */

require 'class.socketwriteread.php';
/**
 * Akismet anti-comment spam service
 *
 * The class in this package allows use of the {@link http://akismet.com Akismet} anti-comment spam service in any PHP5 application.
 *
 * This service performs a number of checks on submitted data and returns whether or not the data is likely to be spam.
 *
 * Please note that in order to use this class, you must have a vaild {@link http://wordpress.com/api-keys/ WordPress API key}.
 * They are free for non/small-profit types and getting one will only take a couple of minutes.
 *
 * For commercial use, please {@link http://akismet.com/commercial/ visit the Akismet commercial licensing page}.
 *
 * Please be aware that this class is PHP5 only.  Attempts to run it under PHP4 will most likely fail.
 *
 * See the Akismet class documentation page linked to below for usage information.
 *
 * @package        akismet
 * @author        Alex Potsides, {@link http://www.achingbrain.net http://www.achingbrain.net}
 * @version        0.4
 * @copyright    Alex Potsides, {@link http://www.achingbrain.net http://www.achingbrain.net}
 * @license        http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 *    The Akismet PHP5 Class
 *
 *  This class takes the functionality from the Akismet WordPress plugin written by {@link http://photomatt.net/ Matt Mullenweg}
 *  and allows it to be integrated into any PHP5 application or website.
 *
 *  The original plugin is {@link http://akismet.com/download/ available on the Akismet website}.
 *
 *  <b>Usage:</b>
 *  <code>
 *    $akismet = new akismet('http://www.example.com/blog/', 'aoeu1aoue');
 *    $akismet->setCommentAuthor($name);
 *    $akismet->setCommentAuthorEmail($email);
 *    $akismet->setCommentAuthorURL($url);
 *    $akismet->setCommentContent($comment);
 *    $akismet->setPermalink('http://www.example.com/blog/alex/someurl/');
 *    if($akismet->isCommentSpam())
 *      // store the comment but mark it as spam (in case of a mis-diagnosis)
 *    else
 *      // store the comment normally
 *  </code>
 *
 *  Optionally you may wish to check if your WordPress API key is valid as in the example below.
 *
 * <code>
 *   $akismet = new akismet('http://www.example.com/blog/', 'aoeu1aoue');
 *
 *   if($akismet->isKeyValid()) {
 *     // api key is okay
 *   } else {
 *     // api key is invalid
 *   }
 * </code>
 *
 * @package    akismet
 * @name        Akismet
 * @version    0.4
 * @author        Alex Potsides
 * @link        http://www.achingbrain.net/
 */

class Akismet {
    private $version = '0.4';
    private $wordPressAPIKey;
    private $blogURL;
    private $comment;
    private $apiPort;
    private $akismetServer;
    private $akismetVersion;

    // This prevents some potentially sensitive information from being sent accross the wire.
    private $ignore = ['HTTP_COOKIE',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED_HOST',
        'HTTP_MAX_FORWARDS',
        'HTTP_X_FORWARDED_SERVER',
        'REDIRECT_STATUS',
        'SERVER_PORT',
        'PATH',
        'DOCUMENT_ROOT',
        'SERVER_ADMIN',
        'QUERY_STRING',
        'PHP_SELF'];

    /**
     * Constructor.
     *
     * @param String $blogURL The URL of your blog.
     * @param String $wordPressAPIKey WordPress APTI key.
     */
    public function __construct($blogURL, $wordPressAPIKey) {
        $this->blogURL = $blogURL;
        $this->wordPressAPIKey = $wordPressAPIKey;

        // Set some default values
        $this->apiPort = 80;
        $this->akismetServer = 'rest.akismet.com';
        $this->akismetVersion = '1.1';

        // Start to populate the comment data
        $this->comment['blog'] = $blogURL;
        $this->comment['user_agent'] = @$_SERVER['HTTP_USER_AGENT'];

        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->comment['referrer'] = $_SERVER['HTTP_REFERER'];
        }

        /*
         * This is necessary if the server PHP5 is running on has been set up to run PHP4 and
         * PHP5 concurently and is actually running through a separate proxy al a these instructions:
         * http://www.schlitt.info/applications/blog/archives/83_How_to_run_PHP4_and_PHP_5_parallel.html
         * and http://wiki.coggeshall.org/37.html
         * Otherwise the user_ip appears as the IP address of the PHP4 server passing the requests to the
         * PHP5 one...
         */
        $this->comment['user_ip'] = $_SERVER['REMOTE_ADDR'] != getenv('SERVER_ADDR') ? $_SERVER['REMOTE_ADDR'] : getenv('HTTP_X_FORWARDED_FOR');
    }

    /**
     * Makes a request to the Akismet service to see if the API key passed to the constructor is valid.
     *
     * Use this method if you suspect your API key is invalid.
     *
     * @return bool True is if the key is valid, false if not.
     */
    public function isKeyValid() {
        // Check to see if the key is valid
        $response = $this->sendRequest(
            'key=' . $this->wordPressAPIKey . '&blog=' . $this->blogURL,
            $this->akismetServer,
            '/' . $this->akismetVersion . '/verify-key'
        );

        return $response[1] == 'valid';
    }

    /**
     * Makes a request to the Akismet service.
     *
     * @param string $request Http request.
     * @param string $host The HostName.
     * @param string $path The Path.
     *
     * @return array
     */
    private function sendRequest($request, $host, $path) {
        $http_request = "POST " . $path . " HTTP/1.0\r\n";
        $http_request .= "Host: " . $host . "\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n";
        $http_request .= "Content-Length: " . strlen($request) . "\r\n";
        $http_request .= "User-Agent: Akismet PHP5 Class " . $this->version . " | Akismet/1.11\r\n";
        $http_request .= "\r\n";
        $http_request .= $request;

        $socketWriteRead = new SocketWriteRead($host, $this->apiPort, $http_request);
        $socketWriteRead->send();

        return explode("\r\n\r\n", $socketWriteRead->getResponse(), 2);
    }

    /**
     * Formats the data for transmission.
     *
     * @return string
     */
    private function getQueryString() {
        foreach ($_SERVER as $key => $value) {
            if (!in_array($key, $this->ignore)) {
                if ($key == 'REMOTE_ADDR') {
                    $this->comment[$key] = $this->comment['user_ip'];
                } else {
                    $this->comment[$key] = $value;
                }
            }
        }

        $query_string = '';

        foreach ($this->comment as $key => $data) {
            if (!is_array($data)) {
                $query_string .= $key . '=' . urlencode(stripslashes($data)) . '&';
            }
        }

        return $query_string;
    }

    /**
     * Test for spam.
     *
     * Uses the web service provided by {@link http://www.akismet.com Akismet} to see whether
     * or not the submitted comment is spam.  Returns a boolean value.
     *
     * @return bool True if the comment is spam, false if not.
     * @throws exception Will throw an exception if the API key passed to the constructor is invalid.
     */
    public function isCommentSpam() {
        $response = $this->sendRequest($this->getQueryString(), $this->wordPressAPIKey . '.' . $this->akismetServer, '/' . $this->akismetVersion . '/comment-check');

        if ($response[1] == 'invalid' && !$this->isKeyValid()) {
            throw new exception('The Wordpress API key passed to the Akismet constructor is invalid.  Please obtain a valid one from http://wordpress.com/api-keys/');
        }

        return ($response[1] == 'true');
    }

    /**
     * Submit spam that is incorrectly tagged as ham.
     *
     * Using this function will make you a good citizen as it helps Akismet to learn from its mistakes.
     * This will improve the service for everybody.
     *
     * @return string.
     */
    public function submitSpam() {
        $this->sendRequest(
            $this->getQueryString(),
            $this->wordPressAPIKey . '.' . $this->akismetServer,
            '/' . $this->akismetVersion . '/submit-spam'
        );
    }

    /**
     * Submit ham that is incorrectly tagged as spam.
     *
     * Using this function will make you a good citizen as it helps Akismet to learn from its mistakes.
     * This will improve the service for everybody.
     *
     * @return string.
     */
    public function submitHam() {
        $this->sendRequest(
            $this->getQueryString(),
            $this->wordPressAPIKey . '.' . $this->akismetServer,
            '/' . $this->akismetVersion . '/submit-ham'
        );
    }

    /**
     * To override the user IP address when submitting spam/ham later on.
     *
     * @param string $userip An IP address  Optional.
     */
    public function setUserIP($userip) {
        $this->comment['user_ip'] = $userip;
    }

    /**
     * To override the referring page when submitting spam/ham later on
     *
     * @param string $referrer The referring page.  Optional.
     */
    public function setReferrer($referrer) {
        $this->comment['referrer'] = $referrer;
    }

    /**
     * A permanent URL referencing the blog post the comment was submitted to.
     *
     * @param string $permalink The URL.  Optional.
     */
    public function setPermalink($permalink) {
        $this->comment['permalink'] = $permalink;
    }

    /**
     * The type of comment being submitted.
     *
     * May be blank, comment, trackback, pingback, or a made up value like "registration" or "wiki".
     *
     * @param string $commentType The comment type.
     */
    public function setCommentType($commentType) {
        $this->comment['comment_type'] = $commentType;
    }

    /**
     * The name that the author submitted with the comment.
     *
     * @param string $commentAuthor The comment author.
     */
    public function setCommentAuthor($commentAuthor) {
        $this->comment['comment_author'] = $commentAuthor;
    }

    /**
     * The email address that the author submitted with the comment.  The address is assumed to be valid.
     *
     * @param string $authorEmail The email author.
     */
    public function setCommentAuthorEmail($authorEmail) {
        $this->comment['comment_author_email'] = $authorEmail;
    }

    /**
     * The URL that the author submitted with the comment.
     *
     * @param string $authorURL The author URL.
     */
    public function setCommentAuthorURL($authorURL) {
        $this->comment['comment_author_url'] = $authorURL;
    }

    /**
     * The comment's body text.
     *
     * @param string $commentBody The comment body.
     */
    public function setCommentContent($commentBody) {
        $this->comment['comment_content'] = $commentBody;
    }

    /**
     * Defaults to 80
     *
     * @param string $apiPort The apiPort.
     */
    public function setAPIPort($apiPort) {
        $this->apiPort = $apiPort;
    }

    /**
     * Defaults to rest.akismet.com
     *
     * @param string $akismetServer The akismetServer.
     */
    public function setAkismetServer($akismetServer) {
        $this->akismetServer = $akismetServer;
    }

    /**
     * Defaults to '1.1'
     *
     * @param string $akismetVersion The akismetVersion.
     */
    public function setAkismetVersion($akismetVersion) {
        $this->akismetVersion = $akismetVersion;
    }
}
