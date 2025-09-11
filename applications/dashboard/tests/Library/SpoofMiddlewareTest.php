<?php
/**
 * Unit tests for the SpoofMiddleware class.
 *
 * @category Tests
 * @package  Dashboard
 * @author   Vanilla Forums Inc.
 * @license  GPL-2.0-only https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/vanilla/vanilla
 */

namespace Library;

/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license   GPL-2.0-only
 */

use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Gdn_Session;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SpoofMiddleware;
use UserModel;
use Vanilla\Exception\PermissionException;
use Vanilla\Logging\LogDecorator;
use Vanilla\Web\SmartIDMiddleware;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\Request;
use Garden\Web\Exception\ForbiddenException;

/**
 * Tests for the SpoofMiddleware module.
 */
class SpoofMiddlewareTest extends TestCase
{
    use BootstrapTrait;

    /** @var MockObject|LogDecorator */
    private $logger;

    /** @var MockObject|SpoofMiddleware */
    private $middleware;

    /** @var MockObject|Gdn_Session */
    private $session;

    /** @var MockObject|SmartIDMiddleware */
    private $smartIDMiddleware;

    /** @var MockObject|UserModel */
    private $userModel;

    /**
     * Invoke the middleware on an instance of Request.
     *
     * @param Request $request
     * @param Data|null $response
     * @return Data
     */
    private function callMiddleware(Request $request, ?Data $response = null): Data
    {
        if ($response === null) {
            $response = Data::box([]);
        }

        return call_user_func($this->middleware, $request, function () use ($response): Data {
            return $response;
        });
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->smartIDMiddleware = $this->createMock(SmartIDMiddleware::class);
        $this->session = $this->createMock(Gdn_Session::class);
        $this->logger = $this->createMock(LogDecorator::class);
        $this->userModel = $this->createMock(UserModel::class);

        // Create a test-specific middleware that accepts UserModel as constructor parameter
        $this->middleware = new class ($this->session, $this->smartIDMiddleware, $this->logger, $this->userModel)
            extends SpoofMiddleware
        {
            private $testUserModel;

            public function __construct($session, $smartIDMiddleware, $logger, $userModel)
            {
                parent::__construct($session, $smartIDMiddleware, $logger, $userModel);
                $this->testUserModel = $userModel;
            }

            protected function getUserModel(): UserModel
            {
                return $this->testUserModel;
            }
        };
        // Assert that the middleware returns the same mock instance
        $this->assertSame(
            $this->userModel,
            (function () {
                return $this->getUserModel();
            })->call($this->middleware)
        );
    }

    /**
     * Verify insufficient permissions will throw an exception.
     */
    public function testInsufficientPermission(): void
    {
        $this->expectException(PermissionException::class);

        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 1);

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(false);

        $this->callMiddleware($request);
    }

    /**
     * Verify attempting to use an invalid smart ID will throw an exception.
     */
    public function testInvalidSmartID(): void
    {
        $userRef = "invalid-smart-ID";

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(SpoofMiddleware::SPOOF_HEADER . " is not a valid smart ID: {$userRef}");

        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, $userRef);

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->callMiddleware($request);
    }

    /**
     * Verify that no one can spoof into a system user.
     */
    public function testCannotSpoofSystemUser(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You do not have permission to spoof a system user account.");

        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 3); // System user ID

        // Current user is admin (level 1)
        $currentUser = (object) [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];
        $this->session->User = $currentUser;

        // Target user is system user (level 2)
        $targetUser = [
            "UserID" => 3,
            "Name" => "System",
            "Admin" => 2,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel
            ->expects($this->exactly(2))
            ->method("getID")
            ->withConsecutive([3, DATASET_TYPE_ARRAY], [2, DATASET_TYPE_ARRAY])
            ->willReturnOnConsecutiveCalls($targetUser, ["UserID" => 2, "Name" => "Admin", "Admin" => 1]);

        $this->logger
            ->expects($this->once())
            ->method("info")
            ->with(
                "SpoofMiddleware: Non-system user spoofing system user blocked (targetAdminLevel = 2, currentAdminLevel = 1)"
            );

        $this->callMiddleware($request);
    }

    /**
     * Verify that a user cannot spoof themselves.
     */
    public function testCannotSpoofSelf(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You cannot spoof your own account.");

        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 2); // Same as current user ID

        // Current user is admin (level 1)
        $currentUser = (object) [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];
        $this->session->User = $currentUser;

        // Target user is the same as current user
        $targetUser = [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel
            ->expects($this->exactly(2))
            ->method("getID")
            ->withConsecutive([2, DATASET_TYPE_ARRAY], [2, DATASET_TYPE_ARRAY])
            ->willReturnOnConsecutiveCalls($targetUser, $targetUser);

        $this->logger
            ->expects($this->once())
            ->method("info")
            ->with("SpoofMiddleware: Self-spoofing blocked (targetAdminLevel = 1, currentAdminLevel = 1)");

        $this->callMiddleware($request);
    }

    /**
     * Verify that a non-system user cannot spoof into a system user.
     */
    public function testNonSystemUserCannotSpoofSystemUser(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You do not have permission to spoof a system user account.");

        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 3); // System user ID

        // Current user is admin (level 1)
        $currentUser = (object) [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];
        $this->session->User = $currentUser;

        // Target user is system user (level 2)
        $targetUser = [
            "UserID" => 3,
            "Name" => "System",
            "Admin" => 2,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel
            ->expects($this->exactly(2))
            ->method("getID")
            ->withConsecutive([3, DATASET_TYPE_ARRAY], [2, DATASET_TYPE_ARRAY])
            ->willReturnOnConsecutiveCalls($targetUser, ["UserID" => 2, "Name" => "Admin", "Admin" => 1]);

        $this->logger
            ->expects($this->once())
            ->method("info")
            ->with(
                "SpoofMiddleware: Non-system user spoofing system user blocked (targetAdminLevel = 2, currentAdminLevel = 1)"
            );

        $this->callMiddleware($request);
    }

    /**
     * Verify that an admin cannot spoof as a user with higher privileges (admin level 3).
     */
    public function testCannotSpoofHigherPrivilegeUser(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You do not have permission to spoof a system user account.");

        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 4); // Super admin user ID

        // Current user is admin (level 1)
        $currentUser = (object) [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];
        $this->session->User = $currentUser;

        // Target user is super admin (level 3)
        $targetUser = [
            "UserID" => 4,
            "Name" => "SuperAdmin",
            "Admin" => 3,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel
            ->expects($this->exactly(2))
            ->method("getID")
            ->withConsecutive([4, DATASET_TYPE_ARRAY], [2, DATASET_TYPE_ARRAY])
            ->willReturnOnConsecutiveCalls($targetUser, ["UserID" => 2, "Name" => "Admin", "Admin" => 1]);

        $this->logger
            ->expects($this->once())
            ->method("info")
            ->with(
                "SpoofMiddleware: Non-system user spoofing system user blocked (targetAdminLevel = 3, currentAdminLevel = 1)"
            );

        $this->callMiddleware($request);
    }

    /**
     * Verify that a system user can spoof as another system user.
     */
    public function testSystemUserCanSpoofSystemUser(): void
    {
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 4); // Another system user ID

        // Current user is system user (level 2)
        $currentUser = (object) [
            "UserID" => 3,
            "Name" => "System",
            "Admin" => 2,
        ];
        $this->session->User = $currentUser;

        // Target user is also system user (level 2)
        $targetUser = [
            "UserID" => 4,
            "Name" => "AnotherSystem",
            "Admin" => 2,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel->method("getID")->willReturnMap([
            [4, DATASET_TYPE_ARRAY, $targetUser], // target user
            [3, DATASET_TYPE_ARRAY, ["UserID" => 3, "Name" => "System", "Admin" => 2]], // current user
        ]);

        $this->session
            ->expects($this->once())
            ->method("start")
            ->with(4, false, false);

        $this->callMiddleware($request);
    }

    /**
     * Verify that an admin can spoof a regular user.
     */
    public function testAdminCanSpoofRegularUser(): void
    {
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 5); // Regular user ID

        // Current user is admin (level 1)
        $currentUser = (object) [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];
        $this->session->User = $currentUser;

        // Target user is regular user (level 0)
        $targetUser = [
            "UserID" => 5,
            "Name" => "RegularUser",
            "Admin" => 0,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel->method("getID")->willReturnMap([
            [5, DATASET_TYPE_ARRAY, $targetUser], // target user
            [2, DATASET_TYPE_ARRAY, ["UserID" => 2, "Name" => "Admin", "Admin" => 1]], // current user
        ]);

        $this->session
            ->expects($this->once())
            ->method("start")
            ->with(5, false, false);

        $this->callMiddleware($request);
    }

    /**
     * Verify the logger has its context defaults updated to reflect a spoof in this request.
     */
    public function testLogContext(): void
    {
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 1);

        $currentUser = (object) [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];
        $this->session->User = $currentUser;

        $targetUser = [
            "UserID" => 1,
            "Name" => "Target",
            "Admin" => 0,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel->method("getID")->willReturnMap([
            [1, DATASET_TYPE_ARRAY, $targetUser], // target user
            [2, DATASET_TYPE_ARRAY, ["UserID" => 2, "Name" => "Admin", "Admin" => 1]], // current user
        ]);

        $this->logger
            ->expects($this->once())
            ->method("addStaticContextDefaults")
            ->with([
                "spoofBy" => [
                    "userID" => $currentUser->UserID,
                    "name" => $currentUser->Name,
                ],
            ]);

        $this->callMiddleware($request);
    }

    /**
     * Verify the logger has its context defaults updated to reflect a spoof in this session.
     */
    public function testSessionLogContext(): void
    {
        $request = new Request();

        $user = [
            "UserID" => 2,
            "Name" => "Vanilla",
        ];
        $this->session->User = (object) $user;
        $this->session->SessionID = 1012;
        $this->session->Session = [
            "Attributes" => ["SpoofUserID" => $user["UserID"], "SpoofUserName" => $user["Name"]],
        ];

        $this->logger
            ->expects($this->once())
            ->method("addStaticContextDefaults")
            ->with([
                "user" => [
                    "SpoofUserID" => $user["UserID"],
                    "SpoofUserName" => $user["Name"],
                ],
            ]);

        $this->callMiddleware($request);
    }

    /**
     * Verify no operations are performed when the spoof header isn't present.
     */
    public function testNoSpoof(): void
    {
        $request = new Request();

        $this->logger->expects($this->never())->method("addStaticContextDefaults");
        $this->session->expects($this->never())->method("checkPermission");
        $this->session->expects($this->never())->method("start");
        $this->smartIDMiddleware->expects($this->never())->method("replaceSmartID");

        $result = $this->callMiddleware($request);
        $this->assertNull($result->getHeader(SpoofMiddleware::SPOOF_BY_HEADER));
    }

    /**
     * Verify the spoof-by header is included in the response.
     */
    public function testResponseHeader(): void
    {
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 1);

        $currentUser = (object) [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];
        $this->session->User = $currentUser;

        $targetUser = [
            "UserID" => 1,
            "Name" => "Target",
            "Admin" => 0,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel->method("getID")->willReturnMap([
            [1, DATASET_TYPE_ARRAY, $targetUser], // target user
            [2, DATASET_TYPE_ARRAY, ["UserID" => 2, "Name" => "Admin", "Admin" => 1]], // current user
        ]);

        $result = $this->callMiddleware($request);
        $this->assertSame($currentUser->Name, $result->getHeader(SpoofMiddleware::SPOOF_BY_HEADER));
    }

    /**
     * Verify the middleware doesn't unduly molest the response.
     */
    public function testResponsePassthrough(): void
    {
        $request = new Request();
        $value = uniqid(__FUNCTION__);

        $response = Data::box(["value" => $value]);
        $result = $this->callMiddleware($request, $response);

        $this->assertSame($value, $result->getDataItem("value"));
    }

    /**
     * Verify a valid smart ID leads to a valid session start.
     */
    public function testValidSmartID(): void
    {
        $userRef = SmartIDMiddleware::SMART . "name:Vanilla";
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, $userRef);

        $currentUser = (object) [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];
        $this->session->User = $currentUser;

        $targetUser = [
            "UserID" => 1,
            "Name" => "Target",
            "Admin" => 0,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel->method("getID")->willReturnMap([
            [1, DATASET_TYPE_ARRAY, $targetUser], // target user
            [2, DATASET_TYPE_ARRAY, ["UserID" => 2, "Name" => "Admin", "Admin" => 1]], // current user
        ]);

        $this->session
            ->expects($this->once())
            ->method("start")
            ->with(1, false, false);

        $this->smartIDMiddleware
            ->expects($this->once())
            ->method("replaceSmartID")
            ->with("UserID", $userRef)
            ->willReturn(1);

        $this->callMiddleware($request);
    }

    /**
     * Verify a basic spoof using a simple user ID.
     */
    public function testValidSpoof(): void
    {
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 1);

        $currentUser = (object) [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];
        $this->session->User = $currentUser;

        $targetUser = [
            "UserID" => 1,
            "Name" => "Target",
            "Admin" => 0,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel->method("getID")->willReturnMap([
            [1, DATASET_TYPE_ARRAY, $targetUser], // target user
            [2, DATASET_TYPE_ARRAY, ["UserID" => 2, "Name" => "Admin", "Admin" => 1]], // current user
        ]);

        $this->session
            ->expects($this->once())
            ->method("start")
            ->with(1, false, false);

        $this->callMiddleware($request);
    }

    /**
     * Test that a system user can spoof another system user.
     */
    public function testSystemUserCanSpoofOtherSystemUser(): void
    {
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 2);

        // Current user is system user (level 2)
        $currentUser = (object) [
            "UserID" => 1,
            "Name" => "System",
            "Admin" => 2,
        ];
        $this->session->User = $currentUser;

        // Target user is also system user (level 2)
        $targetUser = [
            "UserID" => 2,
            "Name" => "AnotherSystem",
            "Admin" => 2,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel->method("getID")->willReturnMap([
            [2, DATASET_TYPE_ARRAY, $targetUser], // target user
            [1, DATASET_TYPE_ARRAY, ["UserID" => 1, "Name" => "System", "Admin" => 2]], // current user
        ]);

        $this->session
            ->expects($this->once())
            ->method("start")
            ->with(2, false, false);

        $this->callMiddleware($request);
    }

    /**
     * Test that a system user can spoof a regular admin.
     */
    public function testSystemUserCanSpoofRegularAdmin(): void
    {
        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 2);

        // Current user is system user (level 2)
        $currentUser = (object) [
            "UserID" => 1,
            "Name" => "System",
            "Admin" => 2,
        ];
        $this->session->User = $currentUser;

        // Target user is regular admin (level 1)
        $targetUser = [
            "UserID" => 2,
            "Name" => "Admin",
            "Admin" => 1,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel->method("getID")->willReturnMap([
            [2, DATASET_TYPE_ARRAY, $targetUser], // target user
            [1, DATASET_TYPE_ARRAY, ["UserID" => 1, "Name" => "System", "Admin" => 2]], // current user
        ]);

        $this->session
            ->expects($this->once())
            ->method("start")
            ->with(2, false, false);

        $this->callMiddleware($request);
    }

    /**
     * Test that a regular admin cannot spoof a system user.
     */
    public function testRegularAdminCannotSpoofSystemUser(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("You do not have permission to spoof a system user account.");

        $request = new Request();
        $request->setHeader(SpoofMiddleware::SPOOF_HEADER, 2);

        // Current user is regular admin (level 1)
        $currentUser = (object) [
            "UserID" => 1,
            "Name" => "Admin",
            "Admin" => 1,
        ];
        $this->session->User = $currentUser;

        // Target user is system user (level 2)
        $targetUser = [
            "UserID" => 2,
            "Name" => "System",
            "Admin" => 2,
        ];

        $this->session
            ->method("checkPermission")
            ->with(SpoofMiddleware::PERMISSION)
            ->willReturn(true);

        $this->userModel
            ->expects($this->exactly(2))
            ->method("getID")
            ->withConsecutive([2, DATASET_TYPE_ARRAY], [1, DATASET_TYPE_ARRAY])
            ->willReturnOnConsecutiveCalls($targetUser, ["UserID" => 1, "Name" => "Admin", "Admin" => 1]);

        $this->logger
            ->expects($this->once())
            ->method("info")
            ->with(
                "SpoofMiddleware: Non-system user spoofing system user blocked (targetAdminLevel = 2, currentAdminLevel = 1)"
            );

        $this->callMiddleware($request);
    }
}
