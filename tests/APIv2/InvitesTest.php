<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/invites endpoints.
 */
class InvitesTest extends AbstractResourceTest {

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/invites';

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        static $inc = 0;
        $record = ['email' => 'vanilla-'.($inc++).'@example.com'];
        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();

        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Garden.Email.Disabled', true, true, false);
        $config->set('Garden.Registration.Method', 'Invitation');

    }

    /**
     * {@inheritdoc}
     */
    public function testDelete() {
        parent::testDelete();
    }

    /**
     * {@inheritdoc}
     * @requires function InvitationsApiController::get_edit
     * @group ignore
     */
    public function testGetEdit($record = null) {
        $this->fail(__METHOD__.' needs to be implemented.');
    }

    /**
     * {@inheritdoc}
     * @requires function InvitationsApiController::get_edit
     * @group ignore
     */
    public function testGetEditFields() {
        $this->fail(__METHOD__.' needs to be implemented.');
    }

    /**
     * {@inheritdoc}
     */
    public function testIndex() {
        parent::testIndex();
    }

    /**
     * {@inheritdoc}
     * @requires function InvitationsApiController::patch
     */
    public function testPatchFull() {
        $this->fail(__METHOD__.' needs to be implemented.');
    }

    /**
     * {@inheritdoc}
     * @requires function InvitationsApiController::patch
     */
    public function testPatchSparse($field) {
        $this->fail(__METHOD__.' needs to be implemented.');
    }
}
