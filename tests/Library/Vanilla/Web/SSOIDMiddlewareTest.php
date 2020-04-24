<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Data;
use PHPUnit\Framework\TestCase;
use Vanilla\Web\SSOIDMiddleware;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\Request;

/**
 * Tests for the `SSOIDMiddleware` class.
 */
class SSOIDMiddlewareTest extends TestCase {
    use BootstrapTrait;

    /**
     * @var SSOIDMiddleware
     */
    protected $middleware;

    /**
     * Create a configured test middleware for each test.
     */
    public function setUp(): void {
        $this->middleware = new TestSSOIDMiddleware('/');
    }

    /**
     * Getters and setters should work.
     */
    public function testGettersSetters(): void {
        $this->middleware->setBasePath('/foo');
        $this->assertSame('/foo', $this->middleware->getBasePath());
    }

    /**
     * Test a basic expansion.
     */
    public function testBasicExpand(): void {
        $request = new Request('/?expand=insertUser.ssoID');
        $next = function ($r) {
            return [
                'insertUserID' => 1
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'insertUserID' => 1,
            'insertUser' => [
                'ssoID' => 'sso-1',
            ]
        ], $actual->getData());
    }

    /**
     * You should be able to specify two expand fields.
     */
    public function testTwoExpandFields(): void {
        $request = new Request('/?expand=insertUser.ssoID,updateUser.ssoID');
        $next = function ($r) {
            return [
                'insertUserID' => 1,
                'body' => 'foo',
                'updateUserID' => 2,
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'insertUserID' => 1,
            'body' => 'foo',
            'updateUserID' => 2,
            'insertUser' => [
                'ssoID' => 'sso-1',
            ],
            'updateUser' => [
                'ssoID' => 'sso-2',
            ],
        ], $actual->getData());
    }

    /**
     * The expansion should work an an array of results.
     */
    public function testArrayExpandFields(): void {
        $request = new Request('/?expand=insertUser.ssoID');
        $next = function ($r) {
            return [
                [
                    'insertUserID' => 1
                ],
                [
                    'insertUserID' => 2
                ],
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            [
                'insertUserID' => 1,
                'insertUser' => [
                    'ssoID' => 'sso-1',
                ],
            ],
            [
                'insertUserID' => 2,
                'insertUser' => [
                    'ssoID' => 'sso-2',
                ],
            ]
        ], $actual->getData());
    }

    /**
     * Expansion should be recursive.
     */
    public function testRecursiveExpand(): void {
        $request = new Request('/?expand=insertUser.ssoID');
        $next = function ($r) {
            return [
                'foo' => [
                    'insertUserID' => 1
                ]
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'foo' => [
                'insertUserID' => 1,
                'insertUser' => [
                    'ssoID' => 'sso-1',
                ]
            ]
        ], $actual->getData());
    }

    /**
     * Expanding SSO IDs should not overwrite the result.
     */
    public function testNonDestructiveExpansion(): void {
        $request = new Request('/?expand=insertUser.ssoID');
        $next = function ($r) {
            return [
                'insertUserID' => 1,
                'insertUser' => [
                    'name' => 'foo',
                ],
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'insertUserID' => 1,
            'insertUser' => [
                'name' => 'foo',
                'ssoID' => 'sso-1',
            ]
        ], $actual->getData());
    }

    /**
     * Field expansion should be driven by the querystring.
     */
    public function testNoExpansion(): void {
        $request = new Request('/');
        $next = function ($r) {
            return [
                'insertUserID' => 1
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'insertUserID' => 1,
        ], Data::box($actual)->getData());
    }
}
