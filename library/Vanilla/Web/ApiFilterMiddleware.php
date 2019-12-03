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
 * Class ApiFilterMiddleWare A middleware to filter api v2 responses.
 *
 * @package Vanilla\Web
 */
class ApiFilterMiddleware {

    /**
     * @var array The blacklisted fields.
     */
    private $blacklist = ['password', 'email', 'insertipaddress', 'updateipaddress'];

    /**
     * Validate an api v2 response.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return Data
     */
    public function __invoke(RequestInterface $request, callable $next) {
        /** @var Data $response */
        $response = $next($request);
        $data = $response->getData();
        $apiAllow = $response->getMeta('api-allow');
        if (!is_array($apiAllow)) {
            $apiAllow = [];
        }
        // Make sure filtering is done for apiv2.
        if (is_array($data)) {
            // Check for blacklisted fields.
            $apiAllow = array_flip($apiAllow);
            $blacklist = array_flip($this->blacklist);
            array_walk_recursive($data, function (&$value, $key) use ($apiAllow, $blacklist) {
                $isBlacklisted = isset($blacklist[strtolower($key)]);
                $isAllowedField = isset($apiAllow[strtolower($key)]);
                if ($isBlacklisted && !$isAllowedField) {
                    throw new ServerException('Validation failed for field'.' '.$key);
                }
            });
        }
        return $response;
    }

    /**
     * Modify the blacklist.
     *
     * @param string $field The field to add to the blacklist.
     */
    protected function addBlacklistField(string $field) {
        if (!in_array(strtolower($field), $this->blacklist)) {
            array_push($this->blacklist, strtolower($field));
        }
    }

    /**
     * Remove a blacklisted field.
     *
     * @param string $field The field to remove from the blacklist.
     */
    protected function removeBlacklistField(string $field) {
        if (($key = array_search(strtolower($field), $this->blacklist)) !== false) {
            unset($this->blacklist[$key]);
        }
    }
}
