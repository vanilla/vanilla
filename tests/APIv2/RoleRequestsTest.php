<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Gdn;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\RoleRequestMetaModel;
use Vanilla\Dashboard\Models\RoleRequestModel;
use Vanilla\Exception\Database\NoResultsException;
use VanillaTests\Bootstrap;
use Vanilla\Dashboard\Controllers\RequestsController;

/**
 * Tests for the `/api/v2/role-requests` endpoints.
 */
class RoleRequestsTest extends AbstractAPIv2Test {
    private static $count = 1;

    /**
     * @var RoleRequestMetaModel
     */
    private $metaModel;

    /**
     * @var array
     */
    private $meta;

    /**
     * @var RoleRequestModel
     */
    private $requestModel;

    /**
     * @var array
     */
    private $application;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        CurrentTimeStamp::mockTime(time());

        $this->container()->call(function (
            RoleRequestMetaModel $metaModel,
            RoleRequestModel $requestModel
        ) {
            $this->metaModel = $metaModel;
            $this->requestModel = $requestModel;
        });

        $this->metaModel->delete(['roleID' => $this->roleID(Bootstrap::ROLE_MOD)]);
        $this->metaModel->insert([
            'roleID' => $this->roleID(Bootstrap::ROLE_MOD),
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
                'allowReapply' => true,
                'notifyDenied' => false,
                'notifyCommunityManager' => false,
            ]
        ]);
        $this->meta = $this->metaModel->selectSingle([
            'roleID' => $this->roleID(Bootstrap::ROLE_MOD),
            'type' => RoleRequestModel::TYPE_APPLICATION,
        ]);

        $req = [
            'roleID' => $this->meta['roleID'],
            'attributes' => [
                'body' => 'Fixture',
                'format' => 'text',
            ],
        ];
        $this->createUserFixtures('RoleRequestTest'.self::$count++);

        // Add the community manager permission to the moderator for certain test cases.
        $modRoleID = $this->roleID(Bootstrap::ROLE_MOD);
        $this->api()->patch("/roles/$modRoleID/permissions", [[
            'type' => 'global',
            'permissions' => [
                'users.edit' => true,
                'community.manage' => true,
            ],
        ]]);

        // Create a sample application.
        $this->api()->setUserID($this->memberID);
        $this->application = $this->api()->post('/role-requests/applications', $req)->getBody();
        $this->api()->setUserID($this->adminID);
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Test the basic posting of the meta.
     */
    public function testPutMeta(): array {
        $meta = [
            'roleID' => $this->roleID(Bootstrap::ROLE_ADMIN),
            'type' => RoleRequestModel::TYPE_APPLICATION,
            'name' => 'Test',
            'body' => 'Test 0',
            'format' => 'text',
            'attributesSchema' => Schema::parse(['body:s', 'format:s'])->jsonSerialize(),
            'attributes' => [
                'notification' => [
                    'approved' => [
                        'name' => __FUNCTION__.' name',
                        'body' => __FUNCTION__.' body',
                        'format' => 'markdown',
                        'url' => '/foo',
                    ],
                    'denied' => [
                        'name' => __FUNCTION__.' denied name',
                        'body' => __FUNCTION__.' denied body',
                        'format' => 'markdown',
                        'url' => '/denied',
                    ]
                ]
            ]
        ];

        $response = $this->api()->put('/role-requests/metas', $meta);
        $this->assertArraySubsetRecursive($meta, $response->getBody());

        $meta2 = ['body' => 'Test'] + $meta;
        $response2 = $this->api()->put('/role-requests/metas', $meta2);
        $this->assertArraySubsetRecursive($meta2, $response2->getBody());

        return $response2->getBody();
    }

    /**
     * Test a meta with an invalid schema.
     *
     * @param array $schema
     * @param string $expectedMessage
     * @dataProvider provideInvalidSchemas
     */
    public function testPutMetaInvalidSchema(array $schema, string $expectedMessage): void {
        $meta = [
            'roleID' => $this->roleID(Bootstrap::ROLE_ADMIN),
            'type' => RoleRequestModel::TYPE_APPLICATION,
            'name' => 'Test',
            'body' => 'Test',
            'format' => 'text',
            'attributesSchema' => $schema,
        ];

        $this->expectExceptionMessage($expectedMessage);
        $response = $this->api()->put('/role-requests/metas', $meta);
        $this->assertArraySubsetRecursive($meta, $response->getBody());
    }

    /**
     * Provide some invalid schemas.
     *
     * @return array
     */
    public function provideInvalidSchemas(): array {
        $r = [
            [[], 'attributesSchema.type is required. attributesSchema.properties is required.'],
            [['type' => 'a', 'properties' => []], 'attributesSchema.type must be one of: object.'],
            [['type' => 'object', 'properties' => ['a' => []]], 'attributesSchema.properties.a.type is required.'],
        ];
        return array_column($r, null, 1);
    }

    /**
     * Test the basic list meta endpoint.
     *
     * @param array $meta
     * @depends testPutMeta
     */
    public function testIndexMetas(array $meta): void {
        $this->api()->setUserID($this->memberID);
        $response = $this->api()->get('/role-requests/metas', ['roleID' => $meta['roleID'], 'type' => $meta['type']]);
        $this->assertArraySubsetRecursive($meta, $response->getBody()[0]);
    }

    /**
     * Test getting a single meta.
     *
     * @param array $meta
     * @depends testPutMeta
     */
    public function testGetMeta(array $meta): void {
        $this->api()->setUserID($this->memberID);
        $response = $this->api()->get("/role-requests/metas/{$meta['type']}/{$meta['roleID']}");
        $this->assertArraySubsetRecursive($meta, $response->getBody());
    }

    /**
     * Test filtering on `hasRole`.
     */
    public function testFilterMetasOnHasRole(): void {
        $this->testPutMeta();

        $this->api()->patch("/role-requests/applications/{$this->application['roleRequestID']}", ['status' => RoleRequestModel::STATUS_APPROVED]);

        $this->api()->setUserID($this->memberID);
        $metas = $this->api()->get('/role-requests/metas', ['expand' => 'all'])->getBody();
        $hasRole = array_unique(array_column($metas, 'hasRole'));
        $this->assertGreaterThanOrEqual(2, count($hasRole));

        foreach ([true, false] as $hasRole) {
            $metas = $this->api()->get('/role-requests/metas', ['hasRole' => (int)$hasRole, 'expand' => 'all'])->getBody();
            foreach ($metas as $meta) {
                $this->assertSame($hasRole, $meta['hasRole']);
            }
        }
    }

    /**
     * An administrator should be able to delete a meta.
     */
    public function testDeleteMeta(): void {
        $r = $this->api()->delete("/role-requests/metas/{$this->meta['type']}/{$this->meta['roleID']}");
        $this->assertSame(204, $r->getStatusCode());

        $this->expectExceptionCode(404);
        $response = $this->api()->get("/role-requests/metas/{$this->meta['type']}/{$this->meta['roleID']}");
    }

    /**
     * Post a role application.
     *
     * @param array $meta
     * @return array
     * @depends testPutMeta
     */
    public function testPostApplication(array $meta): array {
        $response = $this->applyToRole($meta['roleID'], $req);
        $this->assertArraySubsetRecursive($req, $response);

        $this->setAdminApiUser();
        $applications = $this->api()->get('/role-requests/applications', ['roleRequestID' => $response['roleRequestID'], 'expand' => true]);
        $this->assertArraySubsetRecursive($req, $applications->getBody()[0]);
        foreach ($applications as $application) {
            $this->assertExpansions($application);
        }

        return $response;
    }

    /**
     * Smoke test a basic get.
     */
    public function testGetApplication(): void {
        $response = $this->api()->get("/role-requests/applications/{$this->application['roleRequestID']}", ['expand' => true]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertArraySubsetRecursive($this->application, $response->getBody());
        $this->assertExpansions($response->getBody());

        $this->assertApplicationMeta(false, RoleRequestModel::STATUS_PENDING);
    }

    /**
     * Assert information about the meta attached to the current role request.
     *
     * @param bool $hasRole
     * @param string $status
     */
    protected function assertApplicationMeta(bool $hasRole, string $status): void {
        try {
            $userID = $this->api()->getUserID();
            $this->api()->setUserID($this->application['userID']);

            $meta = $this->api()->get("/role-requests/metas/application/{$this->application['roleID']}", ['expand' => true])->getBody();
            $this->assertSame($hasRole, $meta['hasRole']);
            $this->assertSame($status, $meta['roleRequest']['status']);
        } finally {
            $this->api()->setUserID($userID);
        }
    }

    /**
     * Smoke test role application approval.
     */
    public function testApproveApplication(): void {
        $currentRoles = Gdn::userModel()->getRoleIDs($this->application['userID']);
        $this->assertSame([$this->roleID(Bootstrap::ROLE_MEMBER)], $currentRoles);

        $this->api()->setUserID($this->moderatorID);
        $response = $this->api()->patch(
            "/role-requests/applications/{$this->application['roleRequestID']}",
            ['status' => RoleRequestModel::STATUS_APPROVED]
        );
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEmpty($response['dateExpires']);

        $newRoles = Gdn::userModel()->getRoleIDs($this->application['userID']);
        $this->assertEqualsCanonicalizing(
            [$this->roleID(Bootstrap::ROLE_MEMBER), $this->application['roleID']],
            $newRoles
        );

        $notification = $this->assertNotification($this->application['userID'], ['t.Name' => 'roleRequest']);
        $this->assertSame('Test name approved', $notification['Headline']);
        $this->assertSame('Test body approved', $notification['Story']);
        $this->assertSame('markdown', $notification['Format']);
        $this->assertSame(url('/categories/test/approved', true), $notification['Url']);

        $this->assertApplicationMeta(true, RoleRequestModel::STATUS_APPROVED);
    }

    /**
     * Smoke test role application denial.
     */
    public function testDenyApplication(): array {
        $this->setAdminApiUser();
        $response = $this->api()->patch(
            "/role-requests/applications/{$this->application['roleRequestID']}",
            ['status' => RoleRequestModel::STATUS_DENIED]
        );
        $this->assertSame(200, $response->getStatusCode());
        $newRoles = Gdn::userModel()->getRoleIDs($this->application['userID']);
        $this->assertEqualsCanonicalizing(
            [$this->roleID(Bootstrap::ROLE_MEMBER)],
            $newRoles
        );
        $this->assertApplicationMeta(false, RoleRequestModel::STATUS_DENIED);
        return $response->getBody();
    }

    /**
     * Smoke test denying a role and sending a notification.
     */
    public function testDenyApplicationNotify(): void {
        $this->meta['attributes']['notifyDenied'] = true;
        $this->api()->put("/role-requests/metas", $this->meta);
        $this->setAdminApiUser();
        $response = $this->api()->patch(
            "/role-requests/applications/{$this->application['roleRequestID']}",
            ['status' => RoleRequestModel::STATUS_DENIED]
        );
        $notification = $this->assertNotification($this->application['userID'], ['t.Name' => 'roleRequest']);
        $this->assertSame('Test name denied', $notification['Headline']);
        $this->assertSame('Test body denied', $notification['Story']);
        $this->assertSame('markdown', $notification['Format']);
        $this->assertSame(url('/categories/test/denied', true), $notification['Url']);
    }

    /**
     * A user should not be able to apply for a role twice.
     */
    public function testReApplyNotAllowed(): void {
        $this->api()->setUserID($this->adminID);
        $this->meta['attributes']['allowReapply'] = false;
        $this->api()->put("/role-requests/metas", $this->meta);

        $this->api()->setUserID($this->memberID);

        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('You have already applied.');
        $application = $this->api()->post('/role-requests/applications', $this->application)->getBody();
    }

    /**
     * A user should be able to re-apply for a role if "allowReapply" configured.
     *
     * @param array $application A denied role application.
     * @depends testDenyApplication
     */
    public function testAllowedReApply(array $application): void {
        $this->api()->setUserID($application['userID']);
        $updateAttributes = ['body' => 'new body', 'format' => 'markdown'];
        $application['attributes'] = $updateAttributes;
        $this->api()->post('/role-requests/applications', $application)->getBody();
        $newApplication = $this->api()->get('/role-requests/applications', ['roleRequestID' => $application['roleRequestID']])->getBody();
        $this->assertArraySubsetRecursive($newApplication[0]['attributes'], $updateAttributes);
    }

    /**
     * Regular members can read their own applications.
     */
    public function testIndexWithMember(): void {
        $memberID2 = $this->createUserFixture(Bootstrap::ROLE_MEMBER, __FUNCTION__);
        $req = [
            'roleID' => $this->meta['roleID'],
            'attributes' => [
                'body' => 'Test',
                'format' => 'text',
            ],
        ];
        $this->api()->setUserID($memberID2);
        $response = $this->api()->post('/role-requests/applications', $req);

        $this->api()->setUserID($this->memberID);
        $applications = $this->api()->get('/role-requests/applications')->getBody();
        foreach ($applications as $application) {
            $this->assertSame($this->memberID, $application['userID']);
        }
    }

    /**
     * A community manager should not be able to approve admins.
     *
     * @param array $meta
     * @depends testPutMeta
     */
    public function testBadApprovePermission(array $meta): void {
        $this->assertSame($meta['roleID'], $this->roleID(Bootstrap::ROLE_ADMIN));
        $request = $this->applyToRole($meta['roleID']);

        // Try approving the request as a community manager.
        $this->api()->setUserID($this->moderatorID);
        $this->api()->setUserID($this->moderatorID);

        $this->expectException(ForbiddenException::class);
        $response = $this->api()->patch(
            "/role-requests/applications/{$request['roleRequestID']}",
            ['status' => RoleRequestModel::STATUS_APPROVED]
        );
    }

    /**
     * You can't apply to a role without a meta.
     */
    public function testInvalidRoleApplication(): void {
        $this->expectException(ForbiddenException::class);
        $this->applyToRole($this->roleID(Bootstrap::ROLE_MEMBER));
    }

    /**
     * The role application's attribute schema should be validated.
     */
    public function testRoleApplicationSchemaValidation(): void {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Body is required.');
        $this->applyToRole(['attributes' => ['body' => '']]);
    }

    /**
     * You can't update multiple records.
     */
    public function testInvalidUpdate(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('You are not allowed to update the type or role of an existing request.');
        $this->requestModel->update(
            ['roleID' => $this->roleID(Bootstrap::ROLE_MEMBER)],
            $this->requestModel->pluckPrimaryWhere($this->application)
        );
    }

    /**
     * You can't update multiple records.
     */
    public function testInvalidUpdateWhere(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('roleRequestID is required.');
        $this->requestModel->update(['status' => 'approved'], ['roleID' => $this->meta['roleID']]);
    }

    /**
     * The TTL string must be convertable to a date.
     */
    public function testInvalidTTL(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The TTL was not a valid date string.');
        $this->requestModel->update(['ttl' => 'fdsfds'], $this->requestModel->pluckPrimaryWhere($this->application));
    }

    /**
     * Test filtering by the boolean expires.
     */
    public function testExpiresFilter(): void {
        $where = $this->requestModel->pluckPrimaryWhere($this->application);

        $this->requestModel->update(
            ['dateExpires' => CurrentTimeStamp::getDateTime()],
            $where
        );

        $this->requestModel->selectSingle(['expired' => true] + $where);

        $this->expectException(NoResultsException::class);
        $this->requestModel->selectSingle(['expired' => false] + $where);
    }

    /**
     * Certain status changes should be invalid.
     */
    public function testInvalidStatus(): void {
        $where = $this->requestModel->pluckPrimaryWhere($this->application);

        $this->requestModel->update(['status' => RoleRequestModel::STATUS_APPROVED], $where);

        $this->expectExceptionMessage('You are not allowed to change the status from approved to pending.');
        $this->requestModel->update(['status' => RoleRequestModel::STATUS_PENDING], $where);
    }

    /**
     * Apply to a role as a member.
     *
     * @param int|array $override
     * @param array|null $request
     * @return array
     */
    private function applyToRole($override, array &$request = null): array {
        if (is_int($override)) {
            $override = ['roleID' => $override];
        }

        $request = array_replace_recursive([
            'roleID' => $this->meta['roleID'],
            'attributes' => [
                'body' => 'Test',
                'format' => 'text',
            ],
        ], $override);
        $this->api()->setUserID($this->memberID);

        $response = $this->api()->post('/role-requests/applications', $request)->getBody();
        return $response;
    }

    /**
     * Assert the expansion joins on a request row.
     *
     * @param array $request
     */
    private function assertExpansions(array $request): void {
        $this->assertIsArray($request['role']);
        $this->assertIsArray($request['user']);
        if (isset($request['statusUserID'])) {
            $this->assertIsArray($request['statusUser']);
        }
    }
}
