<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Data;
use Vanilla\Exception\PermissionException;
use Vanilla\Models\UserAuthenticationModel;
use Vanilla\Web\APIExpandMiddleware;
use VanillaTests\Addons\ProfileExtender\ProfileExtenderTestTrait;
use VanillaTests\Fixtures\Request;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the `APIExpandMiddlewareTest` class.
 */
class APIExpandMiddlewareTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use ProfileExtenderTestTrait;

    public static $addons = ["ProfileExtender"];

    /** @var int */
    private static $userID1;

    /** @var int */
    private static $userID2;

    /** @var int */
    private static $basicUserID;

    /**
     * @var APIExpandMiddleware
     */
    protected $middleware;

    /**
     * Create a configured test middleware for each test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->middleware = $this->container()->get(APIExpandMiddleware::class);
    }

    /**
     * Setup users for other tests.
     */
    public function testUserSetup()
    {
        $authProvider = self::container()->get(\Gdn_AuthenticationProviderModel::class);
        $provider = [
            "AuthenticationKey" => "testauth",
            "AuthenticationSchemeAlias" => "testauth",
            "Name" => "testauth",
            "IsDefault" => true,
        ];
        $authProvider->save($provider);
        $user1 = $this->createExtendedUser("user1", "user1 text", "testauth-user1");
        self::$userID1 = $user1["userID"];
        $this->assertIsInt(self::$userID1);
        $user2 = $this->createExtendedUser("user2", "user2 text", "testauth-user2");
        self::$userID2 = $user2["userID"];
        $this->assertIsInt(self::$userID2);

        $basicUser = $this->createUser(["name" => "basic"]);
        self::$basicUserID = $basicUser["userID"];
        $this->assertIsInt(self::$basicUserID);
    }

    /**
     * Test that we don't do anything if the expand field is not preset.
     */
    public function testPreservesEmptyExpand(): void
    {
        $request = new Request("/");
        $this->assertArrayNotHasKey("expand", $request->getQuery());

        call_user_func($this->middleware, $request, function () {
            return [];
        });
        $this->assertArrayNotHasKey("expand", $request->getQuery());
    }

    /**
     * Test a basic expansion.
     *
     * @depends testUserSetup
     */
    public function testExpandAllUsers(): void
    {
        $this->assertExpands(
            "users,users.extended,users.ssoID",
            [
                [
                    "insertUserID" => self::$userID1,
                    "updateUserID" => self::$userID2,
                ],
            ],
            [
                "insertUserID" => [self::$userID1],
                "insertUser.name" => ["user1"],
                "insertUser.ssoID" => ["testauth-user1"],
                "insertUser.extended.text" => ["user1 text"],
                "updateUserID" => [self::$userID2],
                "updateUser.name" => ["user2"],
                "updateUser.ssoID" => ["testauth-user2"],
                "updateUser.extended.text" => ["user2 text"],
            ]
        );
    }

    /**
     * You should be able to specify multiple fields from separate field expanders.
     *
     * @depends testUserSetup
     */
    public function testMultipleExpanders(): void
    {
        $this->assertExpands(
            "insertUser.ssoID,updateUser",
            [
                [
                    "insertUserID" => self::$userID1,
                    "body" => "foo",
                    "updateUserID" => self::$userID2,
                ],
            ],
            [
                "body" => ["foo"],
                "insertUserID" => [self::$userID1],
                "updateUserID" => [self::$userID2],
                "insertUser.ssoID" => ["testauth-user1"],
                "updateUser.name" => ["user2"],
            ]
        );
    }

    /**
     * Test that we can expand on multiple records at once and handle an expand not existing.
     * @depends testUserSetup
     */
    public function testExpandMultipleRecords(): void
    {
        $this->assertExpands(
            "insertUser.ssoID",
            [
                ["insertUserID" => self::$userID1],
                ["insertUserID" => self::$userID2],
                ["insertUserID" => self::$basicUserID],
            ],
            [
                "insertUserID" => [self::$userID1, self::$userID2, self::$basicUserID],
                "insertUser.ssoID" => ["testauth-user1", "testauth-user2", null],
            ]
        );
    }

    /**
     * Test that if an input key isn't provided we won't try to expand it.
     *
     * @depends testUserSetup
     */
    public function testNotProvidedNotExpanded()
    {
        $this->assertExpands(
            "insertUser.ssoID,updateUser.ssoID",
            [["insertUserID" => self::$userID1], ["insertUserID" => self::$userID1, "updateUserID" => self::$userID2]],
            [
                "insertUserID" => [self::$userID1, self::$userID1],
                "updateUserID" => [null, self::$userID2],
                "insertUser.ssoID" => ["testauth-user1", "testauth-user1"],
                "updateUser.ssoID" => [null, "testauth-user2"],
            ]
        );
    }

    /**
     * Expanding SSO IDs should not overwrite the result.
     *
     * @depends testUserSetup
     */
    public function testNonDestructiveExpansion(): void
    {
        $this->assertExpands(
            "insertUser.ssoID",
            [
                [
                    "insertUserID" => self::$userID1,
                    "insertUser" => [
                        "name" => "hello",
                    ],
                ],
            ],
            [
                "insertUserID" => [self::$userID1],
                "insertUser.name" => ["hello"],
                "insertUser.ssoID" => ["testauth-user1"],
            ]
        );
    }

    /**
     * Field expansion should be driven by the querystring.
     */
    public function testNoExpansion(): void
    {
        $request = new Request("/api/v2/resource");
        $next = function ($r) {
            return [
                "insertUserID" => 1,
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame(
            [
                "insertUserID" => 1,
            ],
            Data::box($actual)->getData()
        );
    }

    /**
     * Don't crash if there is a bad parameter.
     */
    public function testBadExpandParameter()
    {
        $request = new Request("/api/v2/resource");
        $request->setQuery(["expand" => (object) ["haha" => "haha"]]);
        $next = function ($r) {
            return [];
        };

        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertTrue(true);
    }

    /**
     * A more realistic version of a bad parameter.
     */
    public function testBadExpandParameter2()
    {
        $request = new Request("/api/v2/resource");
        $request->setQuery(["expand" => [["nested" => true]]]);
        $next = function ($r) {
            return [];
        };

        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertTrue(true);
    }

    /**
     * @depends testUserSetup
     */
    public function testBasePathVerification()
    {
        $request = new Request("/not/api?expand=users");
        $next = function ($r) {
            return [
                "insertUserID" => self::$userID1,
            ];
        };

        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertEquals($actual, [
            "insertUserID" => self::$userID1,
        ]);
        $this->assertTrue(true);
    }

    /**
     * Test that we can use fallback records for expansion.
     */
    public function testDefaultRecord()
    {
        $this->assertExpands(
            "users",
            [["insertUserID" => 1342414124]],
            [
                "insertUserID" => [1342414124],
                "insertUser.name" => ["unknown"],
            ]
        );
    }

    /**
     * Test that we enforce permissions for certain expanders.
     */
    public function testExpansionPermissions()
    {
        $user = $this->createUser();

        $this->runWithUser(function () {
            // This one works.
            $this->assertExpands("users,users.extended", [], []);

            // This one throws because we requested ssoID which has a required permission.
            $this->expectException(PermissionException::class);
            $this->assertExpands("users,users.ssoID", [], []);
        }, $user);
    }

    /**
     * A basic test for the OpenAPI factory.
     */
    public function testOpenAPIFactory()
    {
        $actual = APIExpandMiddleware::filterOpenAPIFactory($this->middleware);
        $this->assertSame($actual[0], $this->middleware);
    }

    /**
     * Open API parameters should be augmented with the `".ssoID"` parameters.
     */
    public function testBasicOpenAPIAugmentation()
    {
        $in = json_decode(
            <<<EOT
{
  "/articles/drafts": {
    "get": {
      "parameters": [
        {
          "in": "query",
          "name": "expand",
          "schema": {
            "items": {
              "enum": [
                "insertUser",
                "updateUser"
              ],
              "type": "string"
            },
            "type": "array"
          }
        }
      ]
    }
  }
}
EOT
            ,
            true
        );

        $expected = json_decode(
            <<<EOT
{
  "/articles/drafts": {
    "get": {
      "parameters": [
        {
          "in": "query",
          "name": "expand",
          "schema": {
            "items": {
              "enum": [
                "insertUser",
                "updateUser",
                "insertUser.ssoID",
                "insertUser.extended",
                "updateUser.ssoID",
                "updateUser.extended"
              ],
              "type": "string"
            },
            "type": "array"
          }
        }
      ]
    }
  }
}
EOT
            ,
            true
        );

        $this->middleware->filterOpenAPI($in);
        $this->assertSame($expected, $in);
    }

    /**
     * Expand parameters should work in the components section of an OpenAPI schema too.
     */
    public function testOpenAPIAugmentationComponents()
    {
        $in = json_decode(
            <<<EOT
{
  "components": {
    "parameters": {
      "foo": {
        "in": "query",
        "name": "expand",
        "schema": {
          "items": {
            "enum": [
              "insertUser",
              "updateUser"
            ],
            "type": "string"
          },
          "type": "array"
        }
      }
    }
  }
}
EOT
            ,
            true
        );

        $expected = json_decode(
            <<<EOT
{
  "components": {
    "parameters": {
      "foo": {
        "in": "query",
        "name": "expand",
        "schema": {
          "items": {
            "enum": [
              "insertUser",
              "updateUser",
              "insertUser.ssoID",
              "insertUser.extended",
              "updateUser.ssoID",
              "updateUser.extended"
            ],
            "type": "string"
          },
          "type": "array"
        }
      }
    }
  }
}
EOT
            ,
            true
        );

        $this->middleware->filterOpenAPI($in);
        $this->assertSame($expected, $in);
    }

    /**
     * Expansion shouldn't overly recurse into expanded records.
     *
     * @depends testUserSetup
     */
    public function testNoDoubleExpand(): void
    {
        $request = new Request("/api/v2/resource?expand=insertUser.ssoID");
        $next = function ($r) {
            return [
                "insertUserID" => self::$userID1,
                "insertUser" => [
                    "insertUserID" => self::$userID1,
                    "name" => "foo",
                ],
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertArrayHasKey("ssoID", $actual["insertUser"]);
        $this->assertArrayNotHasKey("insertUser", $actual["insertUser"]);
    }

    /**
     * Test that expand all applies all expand values, and does not scrub all from the expand query.
     */
    public function testExpandAll()
    {
        $request = new Request("/api/v2/resource?expand=all,insertUser");
        $next = function ($r) {
            return [
                "insertUserID" => self::$userID1,
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertArrayHasKey("ssoID", $actual["insertUser"]);
        $this->assertArrayHasKey("extended", $actual["insertUser"]);
        $this->assertArrayHasKey("name", $actual["insertUser"]);
        $this->assertEquals("all", $request->getQuery()["expand"], "Insert user is scrubbed, but all is not.");
    }

    /**
     * Test that expand all only applies expanders we have permission for.
     */
    public function testExpandAllOnlyAppliesPermissionedItems()
    {
        $memberUser = $this->createUser();
        /** @var Data $actual */
        $actual = $this->runWithUser(function () {
            $request = new Request("/api/v2/resource?expand=all");
            $next = function ($r) {
                return [
                    "insertUserID" => self::$userID1,
                ];
            };
            return call_user_func($this->middleware, $request, $next);
        }, $memberUser);
        $this->assertArrayNotHasKey("ssoID", $actual["insertUser"]); // No permission for this one.
        $this->assertArrayHasKey("extended", $actual["insertUser"]);
        $this->assertArrayHasKey("name", $actual["insertUser"]);
    }

    /**
     * Getters and setters should work.
     */
    public function testGettersSetters(): void
    {
        $this->middleware->setBasePath("/foo");
        $this->assertSame("/foo", $this->middleware->getBasePath());
    }

    ///
    /// Utilities
    ///

    /**
     * Create an extended user.
     *
     * @param string $name
     * @param string|null $extendedText
     * @param string|null $foreignKey
     *
     * @return array
     */
    private function createExtendedUser(string $name, ?string $extendedText, ?string $foreignKey): array
    {
        $user = $this->createUser([
            "name" => $name,
        ]);
        if ($extendedText !== null) {
            $this->api()->patch("/users/{$user["userID"]}/extended", [
                "text" => $extendedText,
            ]);
        }
        if ($foreignKey !== null) {
            $userAuthModel = self::container()->get(UserAuthenticationModel::class);
            $userAuthModel->insert([
                "ForeignUserKey" => $foreignKey,
                "ProviderKey" => "testauth",
                "UserID" => $user["userID"],
            ]);
            \Gdn::cache()->flush();
        }
        return $user;
    }

    /**
     * Assert that some expand definition has an affect on a response.
     *
     * @param string $expandString
     * @param array $initialResponse
     * @param array $expectedResponseShape
     *
     * @return array
     */
    private function assertExpands(string $expandString, array $initialResponse, array $expectedResponseShape): array
    {
        $request = new Request("/api/v2/endpoint?expand=$expandString");
        $next = function () use ($initialResponse) {
            return Data::box($initialResponse);
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $actual = $actual->getSerializedData();
        $this->assertRowsLike($expectedResponseShape, $actual);
        return $actual;
    }
}
