<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use SessionModel;
use Vanilla\Formatting\TimeUnit;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `RoleModel`.
 */
class SessionModelTest extends SiteTestCase {
    /**
     * @var \SessionModel
     */
    private $sessionModel;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->sessionModel = new SessionModel();
        $this->createUserFixtures();
    }

    /**
     * Test adding a role and getting it through the static roles method.
     */
    public function testInsertSession(): void {
        $session = [
            'UserID' => $this->memberID,
            'DateInserted' => date(MYSQL_DATE_FORMAT),
            'DateExpires' => date(MYSQL_DATE_FORMAT, time() + \Gdn_Session::VISIT_LENGTH),
            'Attributes' => [],
        ];
        $id = $this->sessionModel->insert($session);
        $this->assertNotFalse($id);

        $currentSession = $this->sessionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertIsArray($currentSession);
        $userID = val('UserID', $currentSession, 0);
        $this->assertEquals($session['UserID'], $userID);
        $this->assertEquals($session['DateExpires'], val('DateExpires', $currentSession, 0));
    }

    /**
     * Test refreshSession session.
     */
    public function testRefreshSession(): void {
        $expired = date(MYSQL_DATE_FORMAT, time() + TimeUnit::ONE_MINUTE);
        $session = [
            'UserID' => $this->memberID,
            'DateInserted' => date(MYSQL_DATE_FORMAT),
            'DateExpires' => $expired,
            'Attributes' => [],
        ];
        $id = $this->sessionModel->insert($session);
        $this->sessionModel->refreshSession($id);
        $currentSession = $this->sessionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertIsArray($currentSession);
        $this->assertGreaterThan($expired, $currentSession['DateExpires']);
    }

    /**
     * Test refreshSession session with expired.
     */
    public function testRefreshExpiredFail(): void {
        $expired = date(MYSQL_DATE_FORMAT, time() - TimeUnit::ONE_MINUTE);
        $session = [
            'UserID' => $this->memberID,
            'DateInserted' => date(MYSQL_DATE_FORMAT),
            'DateExpires' => $expired,
            'Attributes' => [],
        ];
        $id = $this->sessionModel->insert($session);
        $this->sessionModel->refreshSession($id);
        $currentSession = $this->sessionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertIsArray($currentSession);
        $this->assertEquals($expired, $currentSession['DateExpires']);
    }

    /**
     * Saving a role should work like inserting and updating.
     */
    public function testExpireSession(): void {
        $session = [
            'UserID' => $this->memberID,
            'DateInserted' => date(MYSQL_DATE_FORMAT),
            'DateExpires' => date(MYSQL_DATE_FORMAT, time() + \Gdn_Session::VISIT_LENGTH),
            'Attributes' => [],
        ];
        $id = $this->sessionModel->insert($session);
        $this->sessionModel->expireSession($id);
        $currentSession = $this->sessionModel->getID($id);
        $this->assertIsBool($currentSession);
    }
}
