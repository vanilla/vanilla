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
            $blacklist = array_flip($this->blacklist);
            $apiAllow = array_change_key_case(array_flip($apiAllow));
            array_walk_recursive($data, function (&$value, $key) use ($apiAllow, $blacklist) {
                $key = strtolower($key);
                $isBlacklisted = isset($blacklist[$key]);
                $isAllowedField = isset($apiAllow[$key]);
                if ($isBlacklisted && !$isAllowedField) {
                    throw new ServerException("Unexpected field in content: {$key}");
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
        $field = strtolower($field);
        if (!in_array($field, $this->blacklist)) {
            $this->blacklist[] = $field;
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
