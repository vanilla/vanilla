<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use Gdn_Session;
use PHPUnit\Framework\MockObject\MockObject;
use UnexpectedValueException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Permissions;
use Vanilla\Web\Middleware\SystemTokenMiddleware;
use Vanilla\Web\SystemTokenUtils;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\Request;

/**
 * Basic tests for SystemTokenMiddleware.
 */
class SystemTokenMiddlewareTest extends BootstrapTestCase
{
    private const CONTEXT_SECRET = "abc123";

    /** @var SystemTokenMiddleware */
    private $middleware;

    /** @var Gdn_Session&MockObject */
    private $session;

    /** @var SystemTokenUtils */
    private $tokenUtils;

    /**
     * Call the middleware.
     *
     * @param Request|null $request
     * @param Data|array|null $response
     * @return Data|array
     */
    private function invokeMiddleware(?Request $request = null, $response = null)
    {
        $request = $request ?? new Request("/");
        $response = $response ?? new Data([]);

        return call_user_func($this->middleware, $request, function () use ($response) {
            return $response;
        });
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Gdn_Session::class)
            ->onlyMethods(["setPermission", "start", "validateTransientKey"])
            ->getMock();
        $this->tokenUtils = new SystemTokenUtils(self::CONTEXT_SECRET, $this->session);
        $this->middleware = new SystemTokenMiddleware("/", $this->tokenUtils, $this->session);
    }

    /**
     * Verify expired tokens fail.
     */
    public function testExpiredToken(): void
    {
        CurrentTimeStamp::mockTime(time() - SystemTokenUtils::TOKEN_TTL - 10);
        $expiredToken = $this->tokenUtils->encode();
        CurrentTimeStamp::clearMockTime();
        $request = new Request();
        $request->setHeader("Content-Type", SystemTokenMiddleware::AUTH_CONTENT_TYPE);
        $request->setBody($expiredToken);

        $this->expectException(ExpiredException::class);
        $this->expectExceptionMessage("Expired token");
        $this->invokeMiddleware($request);
    }

    /**
     * Verify an invalid token is a noisy failure.
     */
    public function testInvalidToken(): void
    {
        $invalidToken = JWT::encode(["sub" => 1337], "xyz456", SystemTokenUtils::JWT_ALGO);
        $request = new Request();
        $request->setHeader("Content-Type", SystemTokenMiddleware::AUTH_CONTENT_TYPE);
        $request->setBody($invalidToken);

        $this->expectException(SignatureInvalidException::class);
        $this->expectExceptionMessage("Signature verification failed");
        $this->invokeMiddleware($request);
    }

    /**
     * Verify basic token authentication and setting of the system permission.
     *
     * @param array|null $body
     * @param array|null $query
     * @dataProvider provideValidTokens
     */
    public function testValidToken(?array $body = null, ?array $query = null): void
    {
        // Tokens would usually be generated in a separate request (or session), so simulate that here.
        $userID = 1337;
        $tokenSession = $this->createMock(Gdn_Session::class);
        $tokenSession->UserID = $userID;
        $tokenUtils = new SystemTokenUtils(self::CONTEXT_SECRET, $tokenSession);

        $this->session
            ->expects($this->once())
            ->method("start")
            ->with($userID, false, false);
        $this->session
            ->expects($this->once())
            ->method("setPermission")
            ->with(Permissions::PERMISSION_SYSTEM, true);
        $this->session
            ->expects($this->once())
            ->method("validateTransientKey")
            ->with(true, true);

        $token = $tokenUtils->encode($body, $query);
        $request = new Request();
        $request->setHeader("Content-Type", SystemTokenMiddleware::AUTH_CONTENT_TYPE);
        $request->setBody($token);
        if ($query) {
            $request->setQuery($query);
        }

        $this->invokeMiddleware($request);
        $this->assertSame($body ?? $token, $request->getBody());
    }

    /**
     * Provide arguments for testing valid tokens.
     *
     * @return array
     */
    public function provideValidTokens(): array
    {
        return [
            "Basic token" => [null, null],
            "Body override" => [["foo" => "bar"]],
            "Request query" => [null, ["foo" => "bar"]],
            "Body override + request query" => [["foo" => "bar"], ["bar" => "baz"]],
        ];
    }

    /**
     * Verify a valid token fails when the current request query does not match.
     */
    public function testValidTokenInvalidQuery(): void
    {
        $token = $this->tokenUtils->encode(null, ["foo" => "bar"]);
        $request = new Request();
        $request->setHeader("Content-Type", SystemTokenMiddleware::AUTH_CONTENT_TYPE);
        $request->setBody($token);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Token query does not match request.");
        $this->invokeMiddleware($request);
    }

    /**
     * Verify a valid token fails when the current request query does not contain the same values.
     */
    public function testValidTokenIncompatibleQuery(): void
    {
        $token = $this->tokenUtils->encode(null, ["foo" => "bar"]);
        $request = new Request();
        $request->setHeader("Content-Type", SystemTokenMiddleware::AUTH_CONTENT_TYPE);
        $request->setQuery(["foo" => "baz"]);
        $request->setBody($token);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Token query value differs from request.");
        $this->invokeMiddleware($request);
    }

    /**
     * Verify a plain ole request means no change to the session and no errors.
     */
    public function testNoToken(): void
    {
        $this->invokeMiddleware();
        $this->assertSame(0, $this->session->UserID);
        $this->assertFalse($this->session->checkPermission(Permissions::PERMISSION_SYSTEM));
    }

    /**
     * Verify an error is reported if the token utils does not have a proper secret configured.
     */
    public function testNoSecretConfigured(): void
    {
        $tokenUtils = new SystemTokenUtils("", $this->session);
        $middleware = new SystemTokenMiddleware("/", $tokenUtils, $this->session);

        $request = new Request();
        $request->setHeader("Content-Type", SystemTokenMiddleware::AUTH_CONTENT_TYPE);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage("System token secret has not been configured.");
        $middleware($request, function () {});
    }
}
