<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Plugins\Spoof\Library\SpoofMiddleware;
use Vanilla\Exception\PermissionException;
use Vanilla\Logging\LogDecorator;
use Vanilla\Web\SmartIDMiddleware;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\Request;

/**
 * Tests for the SpoofMiddleware module.
 */
class SpoofMiddlewareTest extends TestCase {

    use BootstrapTrait;

    /** @var MockObject|LogDecorator  */
    private $logger;

    /** @var MockObject|SpoofMiddleware */
    private $middleware;

    /** @var MockObject|Gdn_Session */
    private $session;

    /** @var MockObject|SmartIDMiddleware */
    private $smartIDMiddleware;

    /**
     * Invoke the middleware on an instance of Request.
     *
     * @param Request $request
     * @param Data|null $response
     * @return Data
     */
    private function callMiddleware(Request $request, ?Data $response = null): Data {
        if ($response === null) {
            $response = Data::box([]);
        }

        return call_user_func($this->middleware, $request, function () use ($response): Data {
            return $response;
        });
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->smartIDMiddleware = $this->createMock(SmartIDMiddleware::class);
        $this->session = $this->createMock(Gdn_Session::class);
        $this->logger = $this->createMock(LogDecorator::class);
        $this->middleware = new SpoofMiddleware($this->session, $this->smartIDMiddleware, $this->logger);
    }

    /**
     * Verify insufficient permissions will throw an exception.
     */
    public function testInsufficientPermission(): void {
        $this->expectException(PermissionException::class);

        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 1);

        $this->session->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(false);

        $this->callMiddleware($request);
    }

    /**
     * Verify attempting to use an invalid smart ID will throw an exception.
     */
    public function testInvalidSmartID(): void {
        $userRef = "invalid-smart-ID";

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(SpoofMiddleware::SPOOF_HEADER . " is not a valid smart ID: {$userRef}");

        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, $userRef);

        $this->session->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->callMiddleware($request);
    }

    /**
     * Verify the logger has its context defaults updated to reflect a spoof in this request.
     */
    public function testLogContext(): void {
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 1);

        $user = [
            "UserID" => 2,
            "Name" => "Vanilla",
        ];
        $this->session->User = (object)$user;
        $this->session->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);
        $this->logger->expects($this->once())
            ->method("addStaticContextDefaults")
            ->with([
                "spoofBy" => [
                    "userID" => $user["UserID"],
                    "name" => $user["Name"],
                ],
            ]);

        $this->callMiddleware($request);
    }

    /**
     * Verify no operations are performed when the spoof header isn't present.
     */
    public function testNoSpoof(): void {
        $request = new Request();

        $this->logger->expects($this->never())
            ->method("addStaticContextDefaults");
        $this->session->expects($this->never())
            ->method("checkPermission");
        $this->session->expects($this->never())
            ->method("start");
        $this->smartIDMiddleware->expects($this->never())
            ->method("replaceSmartID");

        $result = $this->callMiddleware($request);
        $this->assertNull($result->getHeader(SpoofMiddleware::SPOOF_BY_HEADER));
    }

    /**
     * Verify the spoof-by header is included in the response.
     */
    public function testResponseHeader(): void {
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 1);

        $user = ["Name" => "Vanilla"];
        $this->session->User = (object)$user;
        $this->session->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $result = $this->callMiddleware($request);
        $this->assertSame($user["Name"], $result->getHeader(SpoofMiddleware::SPOOF_BY_HEADER));
    }

    /**
     * Verify the middleware doesn't unduly molest the response.
     */
    public function testResponsePassthrough(): void {
        $request = new Request();
        $value = uniqid(__FUNCTION__);

        $response = Data::box(["value" => $value]);
        $result = $this->callMiddleware($request, $response);

        $this->assertSame($value, $result->getDataItem("value"));
    }

    /**
     * Verify a valid smart ID leads to a valid session start.
     */
    public function testValidSmartID(): void {
        $userRef = SmartIDMiddleware::SMART.'name:Vanilla';
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, $userRef);

        $this->session->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);
        $this->session->expects($this->once())
            ->method("start")
            ->with(1, false, false);
        $this->smartIDMiddleware->expects($this->once())
            ->method("replaceSmartID")
            ->with("UserID", $userRef)
            ->willReturn(1);

        $this->callMiddleware($request);
    }

    /**
     * Verify a basic spoof using a simple user ID.
     */
    public function testValidSpoof(): void {
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 1);

        $this->session->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);
        $this->session->expects($this->once())
            ->method("start")
            ->with(1, false, false);

        $this->callMiddleware($request);
    }
}
