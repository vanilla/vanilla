<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Gdn_Session;
use Ramsey\Uuid\Uuid;
use SessionModel;
use Vanilla\CurrentTimeStamp;
use Vanilla\Utility\StringUtils;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the Gdn_Session class.
 *
 * @package VanillaTests\APIv2
 */
class SessionTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUserFixtures();
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $session = $this->getSession();
        $session->end();
    }

    /**
     * Test that when you start a session with a stringified ID, the userID is set as an integer.
     */
    public function testUserIDIsInteger()
    {
        $stringifiedID = (string) $this->memberID;
        $session = $this->getSession();
        $session->start($stringifiedID);
        $this->assertIsInt($session->UserID);
        $this->assertEquals($session->UserID, $this->memberID);
    }

    /**
     * Test that when you start a session with a UserID, using new session ID in the cookies.
     */
    public function testSessionIDUsage()
    {
        $session = $this->getSession();
        $session->start($this->memberID);
        $this->clearIdentity();
        $sessionModel = new SessionModel();
        $sessionID = Gdn::authenticator()->getSession();
        $dbSession = $sessionModel->getID($sessionID, DATASET_TYPE_ARRAY);

        $this->assertIsString($session->SessionID, "Session is a string");
        $this->assertEquals($sessionID, $session->SessionID, "Session in DB is the same as session returned.");
        $this->assertEquals(
            $this->memberID,
            $dbSession["UserID"],
            "User ID used for session creation is the same as found in the DB session record."
        );
    }

    /**
     * Test that when you start a session with a UserID and existing SessionID, using provided session ID in the cookies.
     */
    public function testUsingExistingSessionID()
    {
        $session = $this->getSession();
        $existingSessionID = str_replace("-", "", Uuid::uuid1()->toString());
        $session->start($this->memberID, true, false, $existingSessionID);
        $this->clearIdentity();
        $sessionModel = new SessionModel();
        $sessionID = Gdn::authenticator()->getSession();
        $dbSession = $sessionModel->getID($sessionID, DATASET_TYPE_ARRAY);

        $this->assertSame($existingSessionID, $session->SessionID, "Existing session is the same as returned form DB.");
        $this->assertEquals($sessionID, $session->SessionID, "Session in DB is the same as getSession returned.");
        $this->assertEquals(
            $this->memberID,
            $dbSession["UserID"],
            "User ID used for session creation is the same as found in the DB session record."
        );
    }

    /**
     * Test ending the session deletes it.
     */
    public function testEndSession()
    {
        $session = $this->getSession();
        $session->start($this->memberID);
        $session->end();
        $sessionID = Gdn::authenticator()->getSession();
        $this->assertEquals("", $sessionID);
    }

    /**
     * Test start session will use what's in the cookie, finding it automaticly.
     */
    public function testSessionStart()
    {
        $session = $this->getSession();
        $session->start($this->memberID);
        $session->start();
        $this->clearIdentity();
        $sessionID = Gdn::authenticator()->getSession();
        $newSession = $this->getSession();
        $newSession->start();

        $this->assertEquals(
            $sessionID,
            $newSession->SessionID,
            "Session ID from authentication same as from start new session."
        );
    }

    /**
     * Test start session with old method, and change flag, that converts to new session cookie.
     */
    public function testTransitionSessionToNewMethod()
    {
        $session = $this->getSession();
        $session->start($this->memberID);
        $this->assertEquals($session->UserID, $this->memberID);
        $this->clearIdentity();
        $session->start();

        $this->assertEquals($this->memberID, $session->UserID);
    }

    /**
     * Test failed session.
     */
    public function testSessionStartWithoutUserID()
    {
        $session = $this->getSession();
        $session->start();

        $this->assertEquals("", $session->SessionID, "No Session ID.");
    }

    /**
     * Test load session failed when session is no longer in the database.
     */
    public function testDeletedSession()
    {
        $session = $this->getSession();
        $session->start($this->memberID);
        $sessionID = Gdn::authenticator()->getSession();
        $sessionModel = new SessionModel();

        $sessionModel->expireSession($sessionID);
        $this->clearIdentity();
        $session->start();

        $this->assertEquals(
            "",
            $session->SessionID,
            "Session ID from authentication is invalid, and would cause guest usage."
        );
    }

    /**
     * Test removing session from database using the DELETE `/sessions/{sessionID}` API endpoint.
     */
    public function testApiDeleteSession()
    {
        $sessionID = $this->runWithUser(function () {
            $session = $this->getSession();
            $session->start($this->memberID);

            // Get the session ID from an API call to GET `/sessions/user`.
            return $this->api()
                ->get("/sessions", ["userID" => $this->memberID])
                ->getBody()[0]["sessionID"];
        }, $this->memberID);

        // Expire the session using DELETE `/sessions/{sessionID}`
        $deletionStatusCode = $this->api()
            ->delete("/sessions", ["userID" => $this->memberID, "sessionID" => $sessionID])
            ->getStatusCode();
        // Assert we got a success response code from the deletion process.
        $this->assertEquals(204, $deletionStatusCode);

        // Try to expire the session AGAIN using DELETE `/sessions/{sessionID}`.
        // We are expecting this will throw a not found exception.
        $this->expectException(NotFoundException::class);
        $this->api()
            ->delete("/sessions", ["userID" => $this->memberID, "sessionID" => $sessionID])
            ->getStatusCode();
    }

    /**
     * Test removing session from database using the DELETE `/sessions/{sessionID}` API endpoint.
     */
    public function testApiListSessions()
    {
        // We start a session
        $session = $this->getSession();
        $session->start($this->memberID);

        // Check that the admin user has the `Garden.Moderation.Manage` permission.
        $this->assertTrue($this->userModel->checkPermission($this->adminID, ["Garden.Moderation.Manage"]));
        // Poke the GET `/sessions` API with the `Garden.Moderation.Manage` permission & assert that we get results.
        $sessions = $this->runWithUser(function () {
            return $this->api()
                ->get("/sessions", ["userID" => $this->memberID])
                ->getBody();
        }, $this->adminID);
        $this->assertTrue(count($sessions) > 0);

        $sessions = $this->runWithUser(function () {
            return $this->api()
                ->get("/sessions", ["userID" => $this->memberID])
                ->getBody();
        }, $this->memberID);
        $this->assertTrue(count($sessions) > 0);
        // Poke the GET `/sessions` API with the member user & assert that we are met with a forbidden exception.
        $this->runWithUser(function () {
            $this->expectException(ForbiddenException::class);
            $this->api()
                ->get("/sessions", ["userID" => $this->adminID])
                ->getBody();
        }, $this->memberID);
    }

    /**
     * Test that guest users without Active logged-in Session cannot call the session list end point
     */
    public function testListingUserSessionNeedsValidLoginSession(): void
    {
        $session = $this->getSession();
        //Stating as a guest
        $session->start(0);
        $this->runWithUser(function () {
            $this->expectException(ForbiddenException::class);
            $this->expectExceptionCode(403);
            return $this->api()
                ->get("/sessions", ["userID" => $this->memberID])
                ->getBody();
        }, 0);
    }

    /**
     * Test load session failed when session Expiration data is in the past.
     */
    public function testExpiredSession()
    {
        $session = $this->getSession();
        $session->start($this->memberID);
        $sessionID = Gdn::authenticator()->getSession();
        $sessionModel = new SessionModel();
        $sessionModel->update(
            [
                "DateExpires" => date(MYSQL_DATE_FORMAT, time() - Gdn_Session::VISIT_LENGTH),
            ],
            ["SessionID" => $sessionID]
        );
        $this->clearIdentity();
        $session->start();

        $this->assertEquals(
            "",
            $session->SessionID,
            "Session ID from authentication is expired, causes guest permission."
        );
    }

    /**
     * Test session cache gets cleared when update is ran.
     */
    public function testSessionCache()
    {
        $session = $this->getSession();
        $session->start($this->memberID);
        $sessionID = Gdn::authenticator()->getSession();
        $sessionModel = new SessionModel();
        $sessionModel->update(
            [
                "DateExpires" => date(MYSQL_DATE_FORMAT, time() - Gdn_Session::VISIT_LENGTH),
            ],
            ["SessionID" => $sessionID]
        );
        $session = $sessionModel->getID($sessionID, DATASET_TYPE_ARRAY);
        $session["Attributes"]["test"] = "some Value";
        $sessionModel->update(
            [
                "Attributes" => $session["Attributes"],
            ],
            ["SessionID" => $sessionID]
        );
        $session = $sessionModel->getID($sessionID, DATASET_TYPE_ARRAY);

        $this->assertEquals(
            "some Value",
            $session["Attributes"]["test"],
            "Session attribute value is updated, when cache is cleared."
        );
    }

    /**
     * Clear identity session and user IDs.
     */
    public function clearIdentity()
    {
        $_Identity = Gdn::factory("Identity");
        $_Identity->UserID = null;
        $_Identity->SessionID = "";
    }

    public function testSessionRefresh()
    {
        $currentTime = CurrentTimeStamp::mockTime("2020-01-01");
        $session = $this->getSession();
        \Gdn::config()->saveToConfig("Garden.Cookie.PersistExpiry", "1 hour");
        \Gdn::authenticator()
            ->identity()
            ->init();
        $session->start($this->memberID, true, true);

        // Session expiration should match configured value.
        $originalExpiry = $currentTime->modify("+1 hour");
        $this->assertSessionExpiration($session->SessionID, $originalExpiry);

        // Another session start before early in the session doesn't change anything.
        $currentTime = CurrentTimeStamp::mockTime($currentTime->modify("+30 minutes"));
        $this->clearIdentity();
        $session->start(false, false);
        $this->assertSessionExpiration($session->SessionID, $originalExpiry);

        // But after half of the session duration we refresh the session.
        $currentTime = CurrentTimeStamp::mockTime($currentTime->modify("+1 second"));
        $newExpiry = $currentTime->modify("+1 hour");
        $this->clearIdentity();
        $session->start(false, false);
        $this->assertSessionExpiration($session->SessionID, $newExpiry);
    }

    private function assertSessionExpiration(string $sessionID, \DateTimeInterface $expectedDate)
    {
        $session = $this->sessionModel()->getID($sessionID, DATASET_TYPE_ARRAY);
        $this->assertEquals($expectedDate->format(CurrentTimeStamp::MYSQL_DATE_FORMAT), $session["DateExpires"]);

        // A cookie is set with this sessionID and the same expiration.
        $rawCookieJwt = $_COOKIE["Vanilla"];
        $payload = StringUtils::decodeJwtPayload($rawCookieJwt);
        $this->assertEquals($expectedDate->getTimestamp(), $payload["exp"], "Expiration was incorrect.");
        $this->assertEquals($sessionID, $payload["sid"], "Wrong sessionID found.");
    }

    private function sessionModel(): SessionModel
    {
        return self::container()->get(SessionModel::class);
    }
}
