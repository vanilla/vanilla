<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Request;
use Garden\Web\RequestInterface;

/**
 * A request factory that abstracts away the `Gdn_Request` object for easier refactoring later.
 */
final class RequestFactory {
    /**
     * @var \Gdn_Request
     */
    private $context;

    /**
     * RequestFactory constructor.
     *
     * @param \Gdn_Request $context
     */
    public function __construct(\Gdn_Request $context) {
        $this->context = $context;
    }

    /**
     * Create a request in the correct context.
     *
     * @param string $method
     * @param string $path
     * @param array $data
     * @return RequestInterface
     */
    public function createRequest(string $method, string $path, array $data = []): RequestInterface {
        $request = new Request($path, $method, $data);

        $request->setRoot($this->context->getRoot());
        $request->setScheme($this->context->getScheme());
        $request->setHost($this->context->getHost());

        return $request;
    }
}
