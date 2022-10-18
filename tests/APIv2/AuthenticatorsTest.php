<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Gdn_AuthenticationProviderModel;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Web\CacheControlConstantsInterface;

/**
 * Class AuthenticatorsTest
 */
class AuthenticatorsTest extends AbstractAPIv2Test
{
    /** Authentication alias used by authenticators generated by this class. */
    private const AUTHENTICATION_SCHEME_ALIAS = "test";

    /** @var Gdn_AuthenticationProviderModel */
    private $authenticatorModel;

    /** @var string[] */
    private $patchFields = ["active", "default", "visible"];

    protected static $addons = ["OAuth2"];

    protected static $authType = "OAuth2";

    /**
     * Add a new authenticator.
     *
     * @param array $fields
     * @return array
     */
    private function addAuthenticator(array $fields = []): array
    {
        $fields += [
            "AuthenticationKey" => "test-" . uniqid(),
            "AuthenticationSchemeAlias" => self::AUTHENTICATION_SCHEME_ALIAS,
        ];
        $rowID = $this->authenticatorModel->save($fields);
        $row = $this->authenticatorModel->getID($rowID, DATASET_TYPE_ARRAY);
        return $row;
    }

    /**
     * Modify fields in a row.
     *
     * @param array $row
     * @return array
     */
    private function modifyRow(array $row): array
    {
        $row["active"] = !$row["active"];
        $row["default"] = !$row["default"];
        $row["visible"] = !$row["visible"];
        return $row;
    }

    /**
     * Provide fields for testing sparse updates.
     */
    public function providePatchFields(): array
    {
        $result = [];
        foreach ($this->patchFields as $field) {
            $result[$field] = [$field];
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()->call(function (Gdn_AuthenticationProviderModel $authenticatorModel) {
            $this->authenticatorModel = $authenticatorModel;
        });
    }

    /**
     * Test deleting a single authenticator.
     */
    public function testDelete(): void
    {
        $row = $this->addAuthenticator();
        $rowID = $row["UserAuthenticationProviderID"];

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("Authenticator not found.");

        $response = $this->api()->delete("authenticators/{$rowID}");
        $this->assertSame(204, $response->getStatusCode());

        $this->api()->get("authenticators/{$rowID}");
    }

    /**
     * Test getting a single authenticator.
     */
    public function testGet(): void
    {
        $row = $this->addAuthenticator();
        $rowID = $row["UserAuthenticationProviderID"];

        $response = $this->api()->get("authenticators/{$rowID}");
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCamelCase($response->getBody());

        $header = $response->getHeaderLines("cache-control");
        $this->assertSame(CacheControlConstantsInterface::NO_CACHE, $header[0]);
    }

    /**
     * Verify basic behavior of the authenticators index.
     */
    public function testIndex(): void
    {
        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $rows[] = $this->addAuthenticator();
        }
        $this->pagingTest("authenticators");
    }

    /**
     * Verify filtering authenticators by type.
     */
    public function testIndexByType(): void
    {
        $total = 2;
        for ($i = 1; $i <= $total; $i++) {
            $this->addAuthenticator(["AuthenticationSchemeAlias" => __FUNCTION__]);
        }

        // Make sure we have other types of authenticators.
        $this->addAuthenticator();
        $this->addAuthenticator();

        $result = $this->api()
            ->get("authenticators", ["type" => __FUNCTION__])
            ->getBody();
        $this->assertCount($total, $result);

        $validType = true;
        foreach ($result as $row) {
            if ($row["type"] !== __FUNCTION__) {
                $validType = false;
                break;
            }
        }
        $this->assertTrue($validType);
    }

    /**
     * Test editing multiple fields in a single request.
     */
    public function testPatchFull(): void
    {
        $dbRow = $this->addAuthenticator();
        $rowID = $dbRow["UserAuthenticationProviderID"];

        $row = $this->api()
            ->get("authenticators/{$rowID}")
            ->getBody();
        $fields = $this->modifyRow($row);
        $expected = array_intersect_key($fields, array_flip($this->patchFields));
        $response = $this->api()->patch("authenticators/{$rowID}", $fields);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArraySubsetRecursive($expected, $response->getBody());
    }

    /**
     * Test sparse row updating.
     *
     * @param string $field
     * @dataProvider providePatchFields
     */
    public function testPatchSparse(string $field): void
    {
        $dbRow = $this->addAuthenticator();
        $rowID = $dbRow["UserAuthenticationProviderID"];

        $row = $this->api()
            ->get("authenticators/{$rowID}")
            ->getBody();
        $fields = $this->modifyRow($row);
        $expected = $fields[$field];
        $this->api()->patch("authenticators/{$rowID}", [$field => $expected]);

        $response = $this->api()
            ->get("authenticators/{$rowID}")
            ->getBody();
        $this->assertSame($expected, $response[$field]);
    }

    /**
     * Sends an authentication data, and tests result/error validation.
     *
     * @param $postData array for sent to authenticator post endpoint.
     * @param $exceptionClass string|null Exception class to expect.
     * @param $message string|null Exception message to expect.
     * @dataProvider providerTestPost
     */
    public function testPost(array $postData, string $exceptionClass = null, string $message = null): void
    {
        // If exceptionClass provided, expect exception.
        if ($exceptionClass !== null) {
            $this->expectException($exceptionClass);
            $this->expectExceptionMessage($message);
        }

        $apiResponse = $this->api()->post("authenticators", $postData);
        $apiResponseStatusCode = $apiResponse->getStatusCode();
        // If no exception, expect successful result.
        $apiResponseBody = $apiResponse->getBody();
        if ($exceptionClass === null) {
            $this->assertEquals(201, $apiResponseStatusCode);
            $this->assertCount(8, $apiResponseBody);
            $this->assertEquals(static::$authType, $apiResponseBody["type"]);
        }
    }

    /**
     * Data Provider for testPost method.
     * @return array[]
     */
    public function providerTestPost()
    {
        $r = [
            "Valid information" => [
                [
                    "name" => "Provider Test",
                    "clientID" => "test1",
                    "default" => false,
                    "active" => false,
                    "visible" => false,
                    "type" => "OAuth2",
                    "urls" => [
                        "signInUrl" => "https://google.com/signIn",
                        "signOutUrl" => "https://google.com/signOut",
                        "authenticateUrl" => "https://google.com/authenticate",
                        "registerUrl" => "https://google.com/register",
                        "passwordUrl" => "https://google.com/password",
                        "profileUrl" => "https://google.com/profile",
                    ],
                    "authenticationKey" => "key",
                    "associationKey" => "key",
                    "secret" => "secret",
                    "authenticatorConfig" => [
                        "associationKey" => "key",
                        "authorizeUrl" => "https://google.com/authorize",
                        "tokenUrl" => "https://google.com/token",
                        "baseUrl" => "https://google.com",
                    ],
                ],
            ],
            "Invalid OAUTH2 information" => [
                [
                    "name" => "Provider Test",
                    "clientID" => "test1",
                    "default" => false,
                    "active" => false,
                    "visible" => false,
                    "type" => "OAuth2",
                    "urls" => [
                        "signInUrl" => "https://google.com/signIn",
                        "signOutUrl" => "https://google.com/signout",
                        "authenticateUrl" => "https://google.com/authenticate",
                        "registerUrl" => "https://google.com/register",
                        "passwordUrl" => "https://google.com/password",
                        "profileUrl" => "https://google.com/profile",
                    ],
                    "associationKey" => "key",
                    "authenticatorConfig" => [
                        "authorizeUrl" => "https://google.com/authorize",
                        "tokenUrl" => "https://google.com/token",
                    ],
                ],
                ClientException::class,
                "secret is required. authenticatorConfig.associationKey is required. authenticatorConfig.baseUrl is required.",
            ],
            "Invalid Auth Type information" => [
                [
                    "name" => "Provider Test",
                    "clientID" => "test1",
                    "default" => false,
                    "active" => false,
                    "visible" => false,
                    "type" => "23OAuth2",
                    "urls" => [
                        "signInUrl" => "https://google.com/SignIn",
                        "signOutUrl" => "https://google.com/SignOut",
                        "authenticateUrl" => "https://google.com/Authenticate",
                        "registerUrl" => "https://google.com/register",
                        "passwordUrl" => "https://google.com/password",
                        "profileUrl" => "https://google.com/profile",
                    ],
                    "associationKey" => "key",
                    "authenticatorConfig" => [
                        "authorizeUrl" => "https://google.com/authorize",
                        "tokenUrl" => "https://google.com/token",
                    ],
                ],
                NotFoundException::class,
                "Authenticator type not found",
            ],
        ];

        return $r;
    }

    /**
     * Test authentication patch endpoint
     */
    public function testPatch()
    {
        $postData = $this->providerTestPost()["Valid information"][0];
        $apiResponse = $this->api()->post("authenticators", $postData);
        $this->assertEquals(201, $apiResponse->getStatusCode());
        $authenticationRecord = $apiResponse->getBody();
        $patchData = $this->getPatchData();
        $expectedData = array_merge($authenticationRecord, $patchData);
        unset($expectedData["attributes"]);
        $apiResponse = $this->api()->patch("authenticators/" . $authenticationRecord["authenticatorID"], $patchData);
        $this->assertEquals(200, $apiResponse->getStatusCode());
        $updatedResponse = $apiResponse->getBody();
        $this->assertArraySubsetRecursive($patchData["urls"], $updatedResponse["urls"]);
        $updatedAttributes = dbdecode(
            $this->authenticatorModel
                ->getWhere([$this->authenticatorModel->PrimaryKey => $authenticationRecord["authenticatorID"]])
                ->column("Attributes")[0]
        );
        $this->assertArraySubsetRecursive($patchData["authenticatorConfig"], $updatedAttributes);

        //Check invalid patch requests get 404
        $this->expectExceptionCode("404");
        $apiResponse = $this->api()->patch("authenticators/" . 10, $patchData);
    }

    /**
     * Return Patch data for Patch Test
     *
     * @return array
     */
    protected function getPatchData()
    {
        return [
            "visible" => "true",
            "urls" => [
                "signInUrl" => "https://google.com/v3/signin",
                "registerUrl" => "https://google.com/signup/v2/",
            ],
            "authenticatorConfig" => [
                "tokenUrl" => "https://google.com/tokenize",
                "baseUrl" => "https://google.com",
            ],
        ];
    }
}
