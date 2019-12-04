<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/applicants endpoints.
 */
class ApplicantsTest extends AbstractResourceTest {

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/applicants';
        $this->patchFields = ['status'];
        $this->pk = 'applicantID';

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        static $inc = 0;
        $name = 'vanilla_'.($inc++);
        $record = [
            'email' => "{$name}@example.com",
            'name' => $name,
            'discoveryText' => 'Hello world.',
        ];
        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        /** @var \Gdn_Configuration $configuration */
        $configuration = static::container()->get('Config');
        $configuration->set('Garden.Registration.Method', 'Approval');
        $configuration->set('Garden.Registration.ConfirmEmail', false);
        $configuration->set('Garden.Registration.SkipCaptcha', true);
        $configuration->set('Garden.Email.Disabled', true);
    }

    /**
     * Approving a user application.
     */
    public function testApprove() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The applicant specified is already an active user.');

        $row = $this->testPost();

        // This user isn't in the Member role...
        $user = $this->api()->get("/users/{$row[$this->pk]}")->getBody();
        $roles = array_column($user['roles'], 'name');
        $this->assertNotContains('Member', $roles);

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            ['status' => 'approved']
        );
        $this->assertEquals(200, $r->getStatusCode());

        // ...and now they are.
        $user = $this->api()->get("/users/{$row[$this->pk]}")->getBody();
        $roles = array_column($user['roles'], 'name');
        $this->assertContains('Member', $roles);

        // Re-approving should fail.
        $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            ['status' => 'approved']
        );
    }

    /**
     * Approving a user application.
     */
    public function testDecline() {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Applicant not found.');

        $row = $this->testPost();
        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            ['status' => 'declined']
        );
        $this->assertEquals(200, $r->getStatusCode());

        // Re-declining should fail.
        $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            ['status' => 'declined']
        );
    }

    /**
     * {@inheritdoc}
     * @requires function ApplicantsApiController::get_edit
     * @group ignore
     */
    public function testGetEdit($record = null) {
        $this->fail(__METHOD__.' needs to be implemented.');
    }

    /**
     * {@inheritdoc}
     * @requires function ApplicantsApiController::get_edit
     * @group ignore
     */
    public function testGetEditFields() {
        $this->fail(__METHOD__.' needs to be implemented.');
    }

    /**
     * {@inheritdoc}
     * @group ignore
     */
    public function testPatchFull() {
        $this->markTestSkipped();
    }

    /**
     * {@inheritdoc}
     * @dataProvider providePatchFields
     * @group ignore
     */
    public function testPatchSparse($field) {
        $this->markTestSkipped();
    }

    /**
     * {@inheritdoc}
     */
    public function testPost($record = null, array $extra = []) {
        $record = $this->record();
        $fields = [
            'password' => 'vanilla123',
            'termsOfService' => 1
        ];
        $result = parent::testPost($record, $fields);
        return $result;
    }
}
