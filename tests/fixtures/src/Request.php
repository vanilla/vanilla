<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;

use Garden\Web\RequestInterface;

/**
 * A mock request object for testing.
 */
class Request implements RequestInterface {
    private $method;
    private $path;
    private $query = [];
    private $body = [];


    public function __construct($path = '/', $method = 'GET', array $data = []) {
        $query = [];

        if (strpos($path, '?')) {
            list($path, $queryString) = explode('?', $path, 2);
            parse_str($queryString, $query);
        }

        if (in_array($method, ['GET'])) {
            $query += $data;
            $body = [];
        } else {
            $body = $data;
        }

        $this->path = '/'.ltrim($path, '/');
        $this->method = $method;
        $this->query = $query;
        $this->body = $body;
    }


    public function getPath() {
        return $this->path;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getQuery() {
        return $this->query;
    }

    public function getBody() {
        $this->body;
    }
}
