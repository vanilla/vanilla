<?php
/**
 * @author Dani M <danim@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;

/**
 * Class ApiFilterMiddleWare A middleware to filter api v2 response.
 *
 * @package Vanilla\Web
 */
class ApiFilterMiddleware {
    /**
     * @var array The blacklisted fields.
     */
    protected $blacklist = ['password', 'email', 'insertipaddress', 'updateipaddress'];

    /**
     * @var string
     */
    protected $basePath;

    /**
     * ApiMiddleware constructor.
     *
     * @param string $basePath
     */
    public function __construct(string $basePath = '/api/v2') {
        $this->basePath = $basePath;
    }
    /**
     * Validate an api v2 response.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return Data
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $response = $next($request);
        $data = $response->getData();
        // Make sure filtering is done for apiv2.
        if (is_array($data) && strcasecmp(substr($request->getPath(), 0, strlen($this->basePath)), $this->basePath) === 0) {
            // Check if the api sent some fields to override the blacklist.
            $this->checkSentWhitelist($data);
            // Check for blacklisted fields.
            array_walk_recursive($response->getData(), function (&$value, $key) {
                if (in_array(strtolower($key), $this->blacklist)) {
                    throw new ServerException('Validation failed for field'.' '.$key);
                }
            });
        }
        return $response;
    }

    /**
     * Check if an endpoint sent a record to be whitelisted.
     *
     * @param array $data The array to check for fields to whitelist.
     */
    private function checkSentWhitelist($data) {
        if ($data['api-allow']) {
            foreach ($data['api-allow'] as $key => $value) {
                $searchKey = array_search($value, $this->blacklist);
                if ($searchKey !== false) {
                    unset($this->blacklist[$searchKey]);
                }
            }
        }
    }
}
