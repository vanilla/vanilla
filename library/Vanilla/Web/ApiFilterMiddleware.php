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
        $response  = Data::box($next($request));
        $responseData = $response->getData();
        $responseMeta = $response->getMetaArray();

        // Make sure filtering is done for apiv2.
        if (is_array($responseData)) {
            // Check for blacklisted fields.
            array_walk_recursive($responseData, function (&$value, $key) use ($responseMeta) {
                $result = false;
                if (isset($responseMeta['api-allow'])) {
                    $result = in_array(strtolower($key), $responseMeta['api-allow']);
                }
                if (in_array(strtolower($key), $this->blacklist) && !$result) {
                    throw new ServerException('Validation failed for field'.' '.$key);
                }
            });
        }
        return $response;
    }

    /**
     * Modify the blacklist.
     *
     * @param array $fields The fields to add to the blacklist.
     */
    protected function addBlacklistFields(array $fields) {
        $this->blacklist = array_merge($this->blacklist, $fields);
    }
}
