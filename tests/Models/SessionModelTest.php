<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use SessionModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Formatting\TimeUnit;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `RoleModel`.
 */
class SessionModelTest extends SiteTestCase
{
    /**
     * @var \SessionModel
     */
    private $sessionModel;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->sessionModel = new SessionModel();
        $this->createUserFixtures();
    }

    /**
     * Test adding a role and getting it through the static roles method.
     */
    public function testInsertSession(): void
    {
        $session = [
            "UserID" => $this->memberID,
            "DateInserted" => date(MYSQL_DATE_FORMAT),
            "DateExpires" => date(MYSQL_DATE_FORMAT, time() + \Gdn_Session::VISIT_LENGTH),
            "Attributes" => [],
        ];
        $id = $this->sessionModel->insert($session);
        $this->assertNotFalse($id);

        $currentSession = $this->sessionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertIsArray($currentSession);
        $userID = val("UserID", $currentSession, 0);
        $this->assertEquals($session["UserID"], $userID);
        $this->assertEquals($session["DateExpires"], val("DateExpires", $currentSession, 0));
    }

    /**
     * Test refreshSession session.
     *
     * @dataProvider sessionToRefresh
     */
    public function testRefreshSession(int $expirationDate, string $sessionLength = null, $isRefreshed = false): void
    {
        // If we are updating session lenght config, set it.
        if ($sessionLength != null) {
            self::container()->call(function (ConfigurationInterface $config) use ($sessionLength) {
                $config->saveToConfig(["Garden.Cookie.PersistExpiry" => $sessionLength]);
            });
        }
        //Retrieve session lenght config,
        self::container()->call(function (ConfigurationInterface $config) use (&$sessionLength) {
            $sessionLength = $config->get("Garden.Cookie.PersistExpiry", "30 days");
        });

        $expired = date(MYSQL_DATE_FORMAT, time() + $expirationDate);
        //Create a session entry with preset expiration time, to make it easy to test different stages of the session lifespan.
        $session = [
            "UserID" => $this->memberID,
            "DateInserted" => date(MYSQL_DATE_FORMAT),
            "DateExpires" => $expired,
            "Attributes" => [],
        ];

        $id = $this->sessionModel->insert($session);
        $this->sessionModel->refreshSession($id);
        $newExpiration = date(MYSQL_DATE_FORMAT, strtotime($sessionLength));
        $currentSession = $this->sessionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertIsArray($currentSession);

        //Test if the expiration should or should not be updated.
        if ($isRefreshed) {
            $this->assertGreaterThan($expired, $currentSession["DateExpires"], "Expiration date updated.");
            //Since we can't guarantee refresh time is exact to expected, CPU delay, calculating the difference, and it should be with 5 seconds.
            $difference = strtotime($currentSession["DateExpires"]) - strtotime($newExpiration);
            $this->assertLessThanOrEqual(5, $difference, "New expiration date correct length time + " . $sessionLength);
        } else {
            $this->assertSame($expired, $currentSession["DateExpires"], "Expiration date not updated.");
        }
    }

    /**
     * Data Provider for refresh session test.
     *
     * @return array[]
     */
    public function sessionToRefresh(): array
    {
        // $expirationDate, $sessionLength, $isRefreshed
        return [
            "Session refreshed 1 minute session." => [TimeUnit::ONE_MINUTE, null, true],
            "Session refreshed 1 Day session, with 30 Day PersistExpiry" => [TimeUnit::ONE_DAY, null, true],
            "Session refreshed 7 Day session, with 30 Day PersistExpiry" => [TimeUnit::ONE_WEEK, null, true],
            "Session refreshed 10 Day session, with 30 Day PersistExpiry" => [TimeUnit::ONE_DAY * 10, null, true],
            "Session refreshed 15 Day session, with 30 Day PersistExpiry" => [TimeUnit::ONE_DAY * 15 - 10, null, true],
            "Session refreshed 20 Day session, with 30 Day PersistExpiry" => [TimeUnit::ONE_DAY * 20, null, false],
            "Session refreshed 30 Day session, with 30 Day PersistExpiry" => [TimeUnit::ONE_DAY * 30, null, false],

            "Session not refreshed 1 hour session, with 30 minute PersistExpiry" => [
                TimeUnit::ONE_HOUR,
                "30 Minute",
                false,
            ],
            "Session not refreshed 1 hour session, with 1 hour PersistExpiry" => [TimeUnit::ONE_HOUR, "1 HOUR", false],
            "Session not refreshed 1 hour session, with 1.5 hour PersistExpiry" => [
                TimeUnit::ONE_HOUR,
                "90 Minutes",
                false,
            ],
            "Session refreshed 1 hour session, with 2 hour PersistExpiry" => [TimeUnit::ONE_HOUR - 20, "2 HOUR", true],
            "Session refreshed .5 hour session, with 2 hour PersistExpiry" => [TimeUnit::ONE_HOUR / 2, "2 HOUR", true],

            "Session not refreshed 2 hour session, with 1 hour PersistExpiry" => [
                TimeUnit::ONE_HOUR * 2,
                "1 HOUR",
                false,
            ],
        ];
    }

    /**
     * Test refreshSession session not refreshing.
     */
    public function testDoesNotRefreshSession(): void
    {
        $expired = $this->sessionModel->getPersistExpiry()->format(MYSQL_DATE_FORMAT);
        $session = [
            "UserID" => $this->memberID,
            "DateInserted" => date(MYSQL_DATE_FORMAT),
            "DateExpires" => $expired,
            "Attributes" => [],
        ];

        $id = $this->sessionModel->insert($session);
        $this->sessionModel->refreshSession($id);
        $currentSession = $this->sessionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertIsArray($currentSession);
        $this->assertSame($expired, $currentSession["DateExpires"], "Expiration does not get updated.");
    }

    /**
     * Test refreshSession session with expired.
     */
    public function testRefreshExpiredFail(): void
    {
        $expired = date(MYSQL_DATE_FORMAT, time() - TimeUnit::ONE_MINUTE);
        $session = [
            "UserID" => $this->memberID,
            "DateInserted" => date(MYSQL_DATE_FORMAT),
            "DateExpires" => $expired,
            "Attributes" => [],
        ];
        $id = $this->sessionModel->insert($session);
        $this->sessionModel->refreshSession($id);
        $currentSession = $this->sessionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertIsArray($currentSession);
        $this->assertEquals($expired, $currentSession["DateExpires"], "Not renewing, session expired.");
    }

    /**
     * Test expiring a session using the SessionModel.
     */
    public function testExpireSession(): void
    {
        $session = [
            "UserID" => $this->memberID,
            "DateInserted" => date(MYSQL_DATE_FORMAT),
            "DateExpires" => date(MYSQL_DATE_FORMAT, time() + \Gdn_Session::VISIT_LENGTH),
            "Attributes" => [],
        ];
        $id = $this->sessionModel->insert($session);
        $this->sessionModel->expireSession($id);
        $currentSession = $this->sessionModel->getID($id);
        $this->assertIsBool($currentSession);
    }

    /**
     * Test Get Session method
     */
    public function testGetSessions()
    {
        $session = [
            "UserID" => $this->memberID,
            "DateInserted" => date(MYSQL_DATE_FORMAT),
            "DateExpires" => date(MYSQL_DATE_FORMAT, time() + \Gdn_Session::VISIT_LENGTH),
            "Attributes" => [],
        ];
        $id = $this->sessionModel->insert($session);

        $exist = $this->sessionModel->sessionExists($this->memberID);
        $this->assertSame(true, $exist);

        $exist = $this->sessionModel->sessionExists($this->memberID, "test");
        $this->assertSame(false, $exist);

        $sessions = $this->sessionModel->getSessions($this->memberID, "valid");
        $this->assertSame($id, $sessions[0]["SessionID"]);
        $expiredSessions = $this->sessionModel->getSessions($this->memberID, "invalid");
        $this->assertCount(0, $expiredSessions);
        $this->sessionModel->expireSession($id);
        $expiredSessions = $this->sessionModel->getSessions($this->memberID, "invalid");
        $this->assertCount(0, $expiredSessions);
    }

    /**
     * Test session stash, with DateExpires.
     */
    public function testSessionStash()
    {
        $session = $this->getSession();
        $sessionID = null;
        //Set session stash that expires in 10 minutes from now.
        $dateTime = (new \DateTimeImmutable("now + 10 minutes"))->format(MYSQL_DATE_FORMAT);
        $session->stash("testStash", "Tester", true, $dateTime, $sessionID);
        $sessionModel = new SessionModel();
        //Load session to see that DateExpires is set.
        $sessionData = $sessionModel->getID($sessionID, DATASET_TYPE_ARRAY);
        //Load stashed value for comparison.
        $sessionValue = $session->stash("testStash");
        $this->assertEquals($dateTime, $sessionData["DateExpires"], "expiration date matches");
        $this->assertEquals("Tester", $sessionValue, "Stash retrieves value stored.");
    }
}
