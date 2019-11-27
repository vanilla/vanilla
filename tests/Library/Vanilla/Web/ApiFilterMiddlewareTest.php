<?php
/**
 * @author Dani M <danim@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use PHPUnit\Framework\TestCase;
use Vanilla\Web\ApiFilterMiddleware;
use VanillaTests\Fixtures\Request;

/**
 * Test ApiFilterMiddleware.
 */
class ApiFilterMiddlewareTest extends TestCase {

    /**
     * @var ApiFilterMiddleware
     */
    protected $middleware;

    /**
     * @var string
     */
    protected $path;

    /**
     * Setup
     */
    public function setUp() {
        $this->path = '/api/v2/discussions';
        $this->middleware = new ApiFilterMiddleware($this->path);
    }

    /**
     * Test ApiFilterMiddleware with a whitelisted field.
     */
    public function testValidationSuccess() {
        $request = new Request($this->path);
        $testSuccessArray = ['discussionid' => ['discussionid' => 1, 'password' => 123],
            'api-allow' => [0 => 'discussionid', 1 => 'password']];
        $response =  call_user_func($this->middleware, $request, function ($request) use ($testSuccessArray) {
            return new Data($testSuccessArray, ['request' => $request]);
        });
        $this->assertEquals($testSuccessArray, $response->getData());
    }

    /**
     * Test ApiFilterMiddleware with a blacklisted field.
     */
    public function testValidationFail() {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Validation failed for field password');
        $request = new Request($this->path);
        $testFailureArray = ['insertuserid' => ['discussionid' => 1,'password' => 123]];
        call_user_func($this->middleware, $request, function ($request) use ($testFailureArray) {
            return new Data($testFailureArray, ['request' => $request]);
        });
    }
}
