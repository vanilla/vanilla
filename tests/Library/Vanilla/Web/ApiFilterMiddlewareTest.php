<?php
/**
 * @author Dani M <danim@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use PHPUnit\Framework\TestCase;
use Vanilla\Web\ApiFilterMiddleware;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\Request;

/**
 * Test ApiFilterMiddleware.
 */
class ApiFilterMiddlewareTest extends TestCase {
    use BootstrapTrait;

    /**
     * @var ApiFilterMiddleware
     */
    protected $middleware;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var array An array with whitlisted field.
     */
    protected $testSuccessArray;
    /**
     * @var array An array with blacklisted field.
     */
    protected $testFailureArray;

    /**
     * Setup
     */
    public function setUp() {
        $this->path = '/api/v2/discussions';
        $this->middleware = new ApiFilterMiddleware($this->path);
    }

    /**
     * Test ApiFilterMiddleware with a whitlisted field.
     */
    public function testValidationSuccess() {
        $request = new Request($this->path);
        $this->testSuccessArray = ['discussionid' => ['discussionid' => 1,'password' => 123],
            'api-allow' =>[0 => 'discussionid', 1 =>'password']];
        $this->callMiddlewareSuccess($request);
        $this->addToAssertionCount(1);
    }

    /**
     * Test ApiFilterMiddleware with a blacklisted field.
     *
     * @expectedException \Garden\Web\Exception\ServerException
     * @expectedExceptionMessage Validation failed for field password
     */
    public function testValidationFail() {
        $request = new Request($this->path);
        $this->testFailureArray = ['insertuserid' => ['discussionid' => 1,'password' => 123]];
        $this->callMiddlewareFail($request);
    }
    /**
     * Call the middleware with a valid data array.
     *
     * @param RequestInterface $request The request being called.
     * @return Data Returns the augmented request.
     */
    protected function callMiddlewareSuccess(RequestInterface $request): Data {
        /* @var Data $data */
        $data = call_user_func($this->middleware, $request, function ($request) {
            return new Data($this->testSuccessArray, ['request' => $request]);
        });
        return $data;
    }

    /**
     *  Call the Middleware with an invalid data array.
     *
     * @param RequestInterface $request The request being called.
     * @return Data Returns the augmented request.
     */
    protected function callMiddlewareFail(RequestInterface $request): Data {
        /* @var Data $data */
        return $data = call_user_func($this->middleware, $request, function ($request) {
            return new Data($this->testFailureArray, ['request' => $request]);
        });
    }
}
