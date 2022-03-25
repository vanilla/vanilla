<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Gdn;
use Gdn_Session;
use SessionModel;

/**
 * Tests for the Gdn_Session class.
 *
 * @package VanillaTests\APIv2
 */
class SessionTest extends AbstractAPIv2Test {

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->createUserFixtures();
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void {
        parent::tearDown();
        $session = $this->getSession();
        $session->end();
    }

    /**
     * Test that when you start a session with a stringified ID, the userID is set as an integer.
     */
    public function testUserIDIsInteger() {
        $stringifiedID = (string)$this->memberID;
        $session = $this->getSession();
        $session->start($stringifiedID);
        $this->assertIsInt($session->UserID);
        $this->assertEquals($session->UserID, $this->memberID);
    }

    /**
     * Test that when you start a session with a UserID, using new session ID in the cookies.
     */
    public function testSessionIDUsage() {
        $this->enableFeature(\Gdn_Session::FEATURE_SESSION_ID_COOKIE);
        $session = $this->getSession();
        $session->start($this->memberID);
        $this->clearIdentity();
        $sessionModel = new SessionModel();
        $sessionID = Gdn::authenticator()->getSession();
        $dbSession = $sessionModel->getID($sessionID);

        $this->assertIsString($session->SessionID, 'Session is a string');
        $this->assertEquals($sessionID, $session->SessionID, 'Session in DB is the same as session returned.');
        $this->assertEquals($this->memberID, $dbSession->UserID, 'User ID used for session creation is the same as found in the DB session record.');
    }

    /**
     * Test ending the session deletes it.
     */
    public function testEndSession() {
        $this->enableFeature(\Gdn_Session::FEATURE_SESSION_ID_COOKIE);
        $session = $this->getSession();
        $session->start($this->memberID);
        $session->end();
        $sessionID = Gdn::authenticator()->getSession();
        $this->assertEquals('', $sessionID);
    }

    /**
     * Test start session will use what's in the cookie, finding it automaticly.
     */
    public function testSessionStart() {
        $session = $this->getSession();
        $session->start($this->memberID);
        $this->enableFeature(\Gdn_Session::FEATURE_SESSION_ID_COOKIE);
        $session->start();
        $this->clearIdentity();
        $sessionID = Gdn::authenticator()->getSession();
        $newSession = $this->getSession();
        $newSession->start();

        $this->assertEquals($sessionID, $newSession->SessionID, "Session ID from authentication same as from start new session.");
    }

    /**
     * Test start session with old method, and change flag, that converts to new session cookie.
     */
    public function testTransitionSessionToNewMethod() {
        $session = $this->getSession();
        $session->start($this->memberID);
        $this->assertEquals($session->UserID, $this->memberID);
        $this->enableFeature(\Gdn_Session::FEATURE_SESSION_ID_COOKIE);
        $this->clearIdentity();
        $session->start();

        $this->assertEquals($this->memberID, $session->UserID);
    }


    /**
     * Test failed session.
     */
    public function testSessionStartWithoutUserID() {
        $this->enableFeature(\Gdn_Session::FEATURE_SESSION_ID_COOKIE);
        $session = $this->getSession();
        $session->start();

        $this->assertEquals("", $session->SessionID, "No Session ID.");
    }


    /**
     * Test load session failed when session is no longer in the database.
     */
    public function testDeletedSession() {
        $this->enableFeature(\Gdn_Session::FEATURE_SESSION_ID_COOKIE);
        $session = $this->getSession();
        $session->start($this->memberID);
        $sessionID = Gdn::authenticator()->getSession();
        $sessionModel = new SessionModel();

        $sessionModel->expireSession($sessionID);
        $this->clearIdentity();
        $session->start();

        $this->assertEquals("", $session->SessionID, "Session ID from authentication is invalid, and would cause guest usage.");
    }

    /**
     * Test load session failed when session Expiration data is in the past.
     */
    public function testExpiredSession() {
        $this->enableFeature(\Gdn_Session::FEATURE_SESSION_ID_COOKIE);
        $session = $this->getSession();
        $session->start($this->memberID);
        $sessionID = Gdn::authenticator()->getSession();
        $sessionModel = new SessionModel();
        $sessionModel->update(
            [
                'DateExpires' => date(MYSQL_DATE_FORMAT, time() - Gdn_Session::VISIT_LENGTH)
            ],
            ['SessionID' => $sessionID]
        );
        $this->clearIdentity();
        $session->start();

        $this->assertEquals("", $session->SessionID, "Session ID from authentication is expired, causes guest permission.");
    }

    /**
     * Clear identity session and user IDs.
     */
    public function clearIdentity() {
        $_Identity = Gdn::factory('Identity');
        $_Identity->UserID = null;
        $_Identity->SessionID = '';
    }
}
