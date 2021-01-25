<?php
/**
 * @author Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers\Requests;

use Garden\Schema\Schema;
use Gdn_Configuration;
use RequestsController;
use VanillaTests\Bootstrap;
use VanillaTests\SiteTestCase;
use \Vanilla\Dashboard\Models\RoleRequestMetaModel;
use \Vanilla\Dashboard\Models\RoleRequestModel;

/**
 * Tests for /requests/role-applications endpoint.
 *
 * Class RequestsControllerTest
 * @package VanillaTests\Controllers\Requests
 */
class RequestsControllerTest extends SiteTestCase {

    /**
     * @var RequestsController
     */
    private $requestsController;

    /**
     * @var RoleRequestModel
     */
    private $requestsModel;

    /**
     * @var RoleRequestMetaModel
     */
    private $metaModel;

    /**
     * @var array
     */
    private $meta;

    /**
     * @var Gdn_Configuration
     */
    private $config;

    /**
     * {@inheritDoc}
     * @throws \Garden\Container\ContainerException
     * @throws \Exception
     */
    public function setUp(): void {
        parent::setUp();
        $this->container()->call(function (
            RequestsController $requestsController,
            RoleRequestMetaModel $metaModel,
            RoleRequestModel $requestModel,
            Gdn_Configuration $configuration
        ) {
            $this->requestsController = $requestsController;
            $this->metaModel = $metaModel;
            $this->requestsModel = $requestModel;
            $this->config = $configuration;
        });

        $this->config->set('Feature.'.\ManageController::FEATURE_ROLE_APPLICATIONS.'.Enabled', true, true, false);
        $this->createUserFixtures();
        $this->metaModel->insert([
            'roleID' => $this->roleID(Bootstrap::ROLE_MEMBER),
            'type' => RoleRequestModel::TYPE_APPLICATION,
            'name' => 'Test',
            'body' => 'Test',
            'format' => 'text',
            'attributesSchema' => Schema::parse(['body:s', 'format:s'])->jsonSerialize(),
            'attributes' => [
                'notification' => [
                    'approved' => [
                        'name' => 'Test name approved',
                        'body' => 'Test body approved',
                        'format' => 'markdown',
                        'url' => '/categories/test/approved',
                    ],
                    'denied' => [
                        'name' => 'Test name denied',
                        'body' => 'Test body denied',
                        'format' => 'markdown',
                        'url' => '/categories/test/denied',
                    ],
                    'communityManager' => [
                        'name' => 'Test name admin',
                        'body' => 'Test body admin',
                        'format' => 'markdown',
                        'url' => '/categories/test/admin',
                    ]
                ],
                'allowReapply' => false,
                'notifyDenied' => false,
                'notifyCommunityManager' => false,
            ]
        ]);

        $this->meta = $this->metaModel->selectSingle([
            'roleID' => $this->roleID(Bootstrap::ROLE_MEMBER),
            'type' => RoleRequestModel::TYPE_APPLICATION,
        ]);
    }

    /**
     * Test that the Meta data is put into the DB.
     */
    public function testSetup() {
        $this->assertIsArray($this->meta);
    }

    /**
     * Make sure we do not serve forms when there is not a valid role.
     */
    public function testFormRoleRequired() {
        $this->expectException(\Exception::class);
        $this->expectErrorMessage('Role not found.');
        $this->bessy()->getHtml('/requests/role-applications?role=9999');
    }

    /**
     * Test that the form does exist when there is a valid role.
     */
    public function testFormExists() {
        $this->bessy()->getHtml('/requests/role-applications?role='.$this->meta['roleID'])->assertFormInput('attributes[body]');
    }

    /**
     * Test that a user can apply.
     *
     * @return array Role Application
     */
    public function testUserCanApply() {
        $this->getSession()->start($this->moderatorID);
        $this->bessy()->getHtml('/requests/role-applications?role='.$this->meta['roleID'])->assertFormInput('attributes[body]');
        $response = $this->bessy()->post('/requests/role-applications?role='.$this->meta['roleID'], ['attributes' => ['body' => 'flip', 'format' => 'flap']])->Data;
        $this->assertEquals('success', $response['state']);
        $roleRequest = $this->requestsModel->select(['roleID' => $this->meta['roleID'], 'userID' => $this->moderatorID]);
        $this->assertEquals('flip', $roleRequest[0]['attributes']['body']);
        return $roleRequest[0];
    }

    /**
     * Test that once a user has applied he cannot re-apply.
     */
    public function testPendingUserCannotReapply() {
        $roleRequest = $this->testUserCanApply();
        $this->getSession()->start($roleRequest['userID']);
        $html = $this->bessy()->getHtml('/requests/role-applications?role='.$this->meta['roleID']);
        $response = $this->bessy()->post('/requests/role-applications?role='.$this->meta['roleID'], ['attributes' => ['body' => 'flip', 'format' => 'flap']])->Data;
        $this->assertEquals('alreadyApplied', $response['state']);
        $this->assertStringContainsStringIgnoringCase('You have already applied.', $html->getRawHtml());
    }

    /**
     * Test that a denied user cannot re-apply.
     *
     * @throws \Exception Form errors.
     */
    public function testDeniedUserCannotReapply() {
        $roleRequest = $this->testUserCanApply();
        $this->requestsModel->update(['status' => 'denied'], ['roleRequestID' => $roleRequest['roleRequestID']]);
        $this->getSession()->start($roleRequest['userID']);
        $html = $this->bessy()->getHtml('/requests/role-applications?role='.$this->meta['roleID']);
        $response = $this->bessy()->post('/requests/role-applications?role='.$this->meta['roleID'], ['attributes' => ['body' => 'flip 2', 'format' => 'flap 2']])->Data;
        $this->assertEquals('alreadyApplied', $response['state']);
        $this->assertStringContainsStringIgnoringCase('You have already applied.', $html->getRawHtml());
        $roleRequest = $this->requestsModel->select(['roleID' => $this->meta['roleID'], 'userID' => $this->getSession()->UserID]);
        $this->assertNotEquals('flip 2', $response['roleRequest']['attributes']['body']);
    }

    /**
     * If the Meta is configured, test that a denied user **can** re-apply.
     *
     * @throws \Exception Form errors.
     */
    public function testDeniedUserCanReapply() {
        $roleRequest = $this->testUserCanApply();
        if ($roleRequest['roleRequestID'] !== 1) {
            $this->requestsModel->delete(['roleRequestID' => 1]);
        }

        $this->metaModel->update(['attributes' => ['allowReapply' => true]], ['roleID' => $this->meta['roleID']]);
        $this->requestsModel->update(['status' => 'denied'], ['roleRequestID' => $roleRequest['roleRequestID']]);
        $this->getSession()->start($this->moderatorID);
        $this->bessy()->getHtml('/requests/role-applications?role='.$this->meta['roleID'])->assertFormInput('attributes[body]');
        $response = $this->bessy()->post('/requests/role-applications?role='.$this->meta['roleID'], ['attributes' => ['body' => 'flip 2', 'format' => 'flap 2']])->Data;
        $this->assertEquals('success', $response['state']);
        $roleRequest = $this->requestsModel->select(['roleID' => $this->meta['roleID'], 'userID' => $this->getSession()->UserID]);
        $this->assertEquals('flip 2', $roleRequest[0]['attributes']['body']);
    }

    /**
     * If user already has the role, test that he cannot re-apply.
     */
    public function testUserHasRole() {
        $this->getSession()->start($this->memberID);
        $html = $this->bessy()->getHtml('/requests/role-applications?role='.$this->meta['roleID']);
        $response = $this->bessy()->post('/requests/role-applications?role='.$this->meta['roleID'], ['attributes' => ['body' => 'flip', 'format' => 'flap']])->Data;
        $this->assertEquals('hasRole', $response['state']);
        $this->assertStringContainsStringIgnoringCase('You already have this role.', $html->getRawHtml());
    }
}
