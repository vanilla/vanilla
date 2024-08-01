<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Middleware;

use Firebase\JWT\JWT;
use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Permissions;
use Vanilla\Web\CacheControlConstantsInterface;
use Vanilla\Web\Middleware\RoleTokenAuthMiddleware;
use Vanilla\Web\RoleToken;
use Vanilla\Web\RoleTokenAuthTrait;
use Vanilla\Web\RoleTokenFactory;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\Request;

/**
 * Tests for RoleTokenAuthMiddleware
 */
class RoleTokenAuthMiddlewareTest extends BootstrapTestCase implements CacheControlConstantsInterface
{
    use RoleTokenAuthTrait;

    /**
     * Test that the role token auth middleware ignores requests that do not meet its criteria and allows pass-thru
     *
     * @param RequestInterface $request
     * @throws \Garden\Web\Exception\ForbiddenException Does not apply here.
     * @throws \Garden\Web\Exception\MethodNotAllowedException Does not apply here.
     * @dataProvider roleTokenAuthMiddlewareDoesNotApplyDataProvider
     */
    public function testRoleTokenAuthMiddlewareDoesNotApply(RequestInterface $request)
    {
        // Mock out all middleware dependencies so that if any methods on these mocks are called, test fails.
        [$mockSession, $mockPermissionModel, $mockRoleTokenFactory] = $this->getMockDependenciesForFailureTests();

        $middleware = new RoleTokenAuthMiddleware($mockSession, $mockPermissionModel, $mockRoleTokenFactory);
        $_ = $middleware($request, function ($request) {
            return [
                "insertUserID" => 1,
                "body" => "foo",
                "updateUserID" => 2,
            ];
        });
    }

    /**
     * Data Provider for roleTokenAuthMiddlewareDoesNotApply tests
     *
     * @return iterable
     */
    public function roleTokenAuthMiddlewareDoesNotApplyDataProvider(): iterable
    {
        $request = new Request("/", "GET");
        yield "GET request w/o required query param, no path" => [$request];

        $request = new Request("/", "GET");
        $request->setScheme("https");
        yield "GET request w/o required query param https" => [$request];

        $request = new Request("/", "GET", ["foo" => "bar", "fizz" => "buzz"]);
        $request->setScheme("https");
        yield "GET request w/o required query param, no path with some query params" => [$request];

        $request = new Request("/path/to/test", "GET", ["foo" => "bar", "fizz" => "buzz"]);
        $request->setScheme("https");
        yield "GET request w/o required query param, with path with some query params" => [$request];

        $request = new Request("/path/to/test", "DELETE", ["id" => 12345]);
        $request->setScheme("https");
        yield "DELETE request w/o required query param" => [$request];

        $request = new Request("/path/to/test", "GET", ["foo" => "bar", "fizz" => "buzz"]);
        $request->setScheme("https");
        $request->setMethod("POST");
        $request->setBody(["flip" => ["flop" => "flap"], "drip" => "drop"]);
        yield "POST request w/o required query param with body" => [$request];

        $request = new Request("/path/to/test", "GET", ["foo" => "bar", "fizz" => "buzz"]);
        $request->setScheme("https");
        $request->setMethod("PATCH");
        $request->setBody(["flip" => ["flop" => "flap"], "drip" => "drop"]);
        yield "PATCH request w/o required query param with body" => [$request];

        $request = new Request("/path/to/test", "GET", ["foo" => "bar", "fizz" => "buzz"]);
        $request->setScheme("https");
        $request->setMethod("POST");
        $request->setBody(["flip" => "flop", static::getRoleTokenParamName() => "foo"]);
        yield "POST request w/o required query param with body that has query param name as prop" => [$request];

        $request = new Request("/path/to/test", "GET", ["foo" => "bar", "fizz" => "buzz"]);
        $request->setScheme("https");
        $request->setMethod("PATCH");
        $request->setBody(["flip" => "flop", static::getRoleTokenParamName() => "foo"]);
        yield "PATCH request w/o required query param with body that has query param name as prop" => [$request];
    }

    /**
     * Test that role token auth middleware throws exception when processing an invalid request
     *
     * @param RequestInterface $request
     * @param int $expectedExceptionCode
     * @throws \Garden\Web\Exception\ForbiddenException Expected exception.
     * @throws \Garden\Web\Exception\MethodNotAllowedException Expected exception.
     * @dataProvider roleTokenAuthThrowsExceptionOnInvalidRequestDataProvider
     */
    public function testRoleTokenAuthThrowsExceptionOnInvalidRequest(
        RequestInterface $request,
        int $expectedExceptionCode
    ): void {
        // Mock out all middleware dependencies so that if any methods on these mocks are called, test fails.
        [$mockSession, $mockPermissionModel, $mockRoleTokenFactory] = $this->getMockDependenciesForFailureTests();

        $middleware = new RoleTokenAuthMiddleware($mockSession, $mockPermissionModel, $mockRoleTokenFactory);
        $this->expectExceptionCode($expectedExceptionCode);
        $_ = $middleware($request, function ($request) use ($expectedExceptionCode) {
            $this->fail("Exception with code {$expectedExceptionCode} should have been thrown");
        });
    }

    /**
     * Data Provider for roleTokenAuthThrowsExceptionOnInvalidRequest test
     *
     * @return iterable
     */
    public function roleTokenAuthThrowsExceptionOnInvalidRequestDataProvider(): iterable
    {
        // Exception will get thrown before we attempt to decode query param so can just put a dummy value in here.
        $query = [static::getRoleTokenParamName() => "dummy"];
        $request = new Request("/path/to/resource", "GET", $query);
        $expectedExceptionCode = 403;
        $request->setScheme("http");
        yield "GET http" => [$request, $expectedExceptionCode];

        $expectedExceptionCode = 405;
        $request = new Request("/path/to/resource", "GET", $query);
        $request->setScheme("https");
        $request->setMethod("DELETE");
        yield "DELETE" => [$request, $expectedExceptionCode];

        $request = new Request("/path/to/resource", "GET", $query);
        $request
            ->setScheme("https")
            ->setMethod("POST")
            ->setBody(["foo" => "bar", "flip" => ["flap", "flop"]]);
        yield "POST" => [$request, $expectedExceptionCode];

        $request = new Request("/path/to/resource", "GET", $query);
        $request
            ->setScheme("https")
            ->setMethod("PATCH")
            ->setBody(["foo" => "bar", "flip" => ["flap", "flop"]]);
        yield "PATCH" => [$request, $expectedExceptionCode];

        $request = new Request("/path/to/resource", "GET", $query);
        $request
            ->setScheme("https")
            ->setMethod("PUT")
            ->setBody(["foo" => "bar", "flip" => ["flap", "flop"]]);
        yield "PUT" => [$request, $expectedExceptionCode];
    }

    /**
     * Test that role token auth middleware throws exception when a user with a session is detected
     *
     * @throws \Garden\Web\Exception\ForbiddenException Expected exception.
     * @throws \Garden\Web\Exception\MethodNotAllowedException Expected exception.
     */
    public function testRoleTokenAuthThrowsExceptionOnSessionedUser(): void
    {
        // Mock out all middleware dependencies so that if any methods on these mocks are called, test fails.
        [, $mockPermissionModel, $mockRoleTokenFactory] = $this->getMockDependenciesForFailureTests();
        $mockSession = new \Gdn_Session();
        $mockSession->UserID = 42;

        // Exception will get thrown before we attempt to decode query param so can just put a dummy value in here.
        $query = [static::getRoleTokenParamName() => "dummy"];
        $request = new Request("/path/to/resource", "GET", $query);
        $request->setScheme("https");

        $expectedExceptionCode = 403;

        $middleware = new RoleTokenAuthMiddleware($mockSession, $mockPermissionModel, $mockRoleTokenFactory);
        $this->expectExceptionCode(403);
        $_ = $middleware($request, function ($_) use ($expectedExceptionCode) {
            $this->fail("Exception with code {$expectedExceptionCode} should have been thrown");
        });
    }

    /**
     * Test that role token auth middleware throws exception when invalid JWT detected during decode processing
     *
     * @param RoleTokenFactory $roleTokenFactory
     * @param string $encodedJwt
     * @throws \Garden\Web\Exception\ForbiddenException Expected exception.
     * @throws \Garden\Web\Exception\MethodNotAllowedException Does not apply here.
     * @dataProvider roleTokenAuthThrowsExceptionOnInvalidJwtDecodeDataProvider
     */
    public function testRoleTokenAuthThrowsExceptionOnInvalidJwtDecode(
        RoleTokenFactory $roleTokenFactory,
        string $encodedJwt
    ): void {
        // Mock out all middleware dependencies so that if any methods on these mocks are called, test fails.
        [, $mockPermissionModel, $_] = $this->getMockDependenciesForFailureTests();
        $mockSession = new \Gdn_Session();
        $mockSession->UserID = 0;

        $query = [static::getRoleTokenParamName() => $encodedJwt];
        $request = new Request("/path/to/resource", "GET", $query);
        $request->setScheme("https");

        $expectedExceptionCode = 403;

        $middleware = new RoleTokenAuthMiddleware($mockSession, $mockPermissionModel, $roleTokenFactory);
        $this->expectExceptionCode(403);
        $_ = $middleware($request, function ($_) use ($expectedExceptionCode) {
            $this->fail("Exception with code {$expectedExceptionCode} should have been thrown");
        });
    }

    /**
     * Data Provider for roleTokenAuthThrowsExceptionOnInvalidJwtDecode test
     *
     * @return iterable
     */
    public function roleTokenAuthThrowsExceptionOnInvalidJwtDecodeDataProvider(): iterable
    {
        $dummySecret = "abcdefghijklmnopqurstuvwxyz1234567890";
        $roleTokenFactory = $this->getRoleTokenFactory($dummySecret);

        $encodedJwt = JWT::encode(
            ["exp" => time() + 1000, "iat" => time() - 5, "nbf" => time() - 5],
            $dummySecret,
            RoleToken::SIGNING_ALGORITHM
        );
        yield "JWT w/o roles" => [$roleTokenFactory, $encodedJwt];

        $roleToken = $roleTokenFactory->forEncoding([4, 5]);
        CurrentTimeStamp::mockTime(time() - 3600);
        $encodedJwt = $roleToken->encode();
        CurrentTimeStamp::mockTime(time());
        yield "Expired" => [$roleTokenFactory, $encodedJwt];

        CurrentTimeStamp::mockTime(time() + 3600);
        $encodedJwt = $roleToken->encode();
        CurrentTimeStamp::mockTime(time());
        yield "Issued in future" => [$roleTokenFactory, $encodedJwt];

        $encodedJwt = $roleToken->encode();
        $roleTokenFactory = $this->getRoleTokenFactory(str_rot13($dummySecret));
        yield "Signature failed" => [$roleTokenFactory, $encodedJwt];
    }

    /**
     * Test successful role token auth middleware processing.
     *
     * @throws \Garden\Web\Exception\ForbiddenException Does not apply here.
     * @throws \Garden\Web\Exception\MethodNotAllowedException Does not apply here.
     */
    public function testRoleTokenAuthMiddlewareSuccess(): void
    {
        $dummySecret = "abcdefghijklmnopqrstuvwxyz1234567890";
        $roleTokenFactory = $this->getRoleTokenFactory($dummySecret);
        $roleToken = $roleTokenFactory->forEncoding([4, 5]);
        $encodedJwt = $roleToken->encode();

        $query = [static::getRoleTokenParamName() => $encodedJwt];
        $request = new Request("/path/to/resource", "GET", $query);
        $request->setScheme("https");

        $mockRolePermissions = [
            [
                "type" => "category",
                "id" => 12,
                "permissions" => [
                    "discussions.view" => true,
                ],
            ],
            [
                "type" => "category",
                "id" => 21,
                "permissions" => [
                    "discussions.view" => true,
                ],
            ],
        ];

        $mockPermissions = $this->getMockBuilder(Permissions::class)->getMock();
        $mockPermissions
            ->expects($this->once())
            ->method("addBan")
            ->with($this->equalTo(Permissions::BAN_ROLE_TOKEN), $this->anything());

        $mockPermissionModel = $this->getMockBuilder(\PermissionModel::class)->getMock();
        $mockPermissionModel
            ->expects($this->once())
            ->method("getPermissionsByRole")
            ->with($this->equalTo(4), $this->equalTo(5))
            ->willReturn($mockRolePermissions);

        $mockSession = $this->getMockBuilder(\Gdn_Session::class)->getMock();
        $mockSession
            ->expects($this->once())
            ->method("isValid")
            ->willReturn(false);
        $mockSession
            ->expects($this->once())
            ->method("getPermissions")
            ->willReturn($mockPermissions);
        $mockSession
            ->expects($this->once())
            ->method("addPermissions")
            ->with($this->equalTo($mockRolePermissions));

        $middleware = new RoleTokenAuthMiddleware($mockSession, $mockPermissionModel, $roleTokenFactory);
        /** @var Data $response */
        $_ = $middleware($request, function ($_) {
            return Data::box([
                "insertUserID" => 1,
                "body" => "foo",
                "updateUserID" => 2,
            ]);
        });
    }

    /**
     * Mock out all middleware dependencies so that if any methods on these mocks are called, test fails
     *
     * @return array
     */
    private function getMockDependenciesForFailureTests(): array
    {
        $mockSession = $this->getMockBuilder(\Gdn_Session::class)
            ->disableAutoReturnValueGeneration()
            ->getMock();
        $mockSession->expects($this->never())->method($this->anything());

        $mockPermissionModel = $this->getMockBuilder(\PermissionModel::class)
            ->disableAutoReturnValueGeneration()
            ->getMock();
        $mockPermissionModel->expects($this->never())->method($this->anything());

        $mockRoleTokenFactory = $this->getMockBuilder(RoleTokenFactory::class)
            ->disableOriginalConstructor()
            ->disableAutoReturnValueGeneration()
            ->getMock();
        $mockRoleTokenFactory->expects($this->never())->method($this->anything());

        return [$mockSession, $mockPermissionModel, $mockRoleTokenFactory];
    }

    /**
     * Get role token factory for use in tests.
     *
     * @param string $secret
     * @return RoleTokenFactory
     * @throws \Exception Does not apply here.
     */
    private function getRoleTokenFactory(string $secret): RoleTokenFactory
    {
        $mockConfig = $this->getMockBuilder(ConfigurationInterface::class)->getMock();
        $mockConfig
            ->expects($this->atLeastOnce())
            ->method("get")
            ->with($this->stringContains("RoleToken"), $this->anything())
            ->willReturnCallback(function ($name, $default) use ($secret) {
                return str_contains($name, "Secret") ? $secret : $default;
            });
        $roleTokenFactory = new RoleTokenFactory($mockConfig);
        return $roleTokenFactory;
    }
}
