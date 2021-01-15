<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Data;
use PHPUnit\Framework\TestCase;
use Vanilla\Permissions;
use Vanilla\Web\APIExpandMiddleware;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\Request;

/**
 * Tests for the `APIExpandMiddlewareTest` class.
 */
class APIExpandMiddlewareTest extends TestCase {
    use BootstrapTrait {
        setUpBeforeClass as bootstrapSetUpBeforeClass;
    }

    /**
     * @var APIExpandMiddleware
     */
    protected $middleware;

    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::bootstrapSetUpBeforeClass();
    }

    /**
     * Create a configured test middleware for each test.
     */
    public function setUp(): void {
        $userModel = $this->createStub(\UserModel::class);
        $userModel
            ->method("getDefaultSSOIDs")
            ->willReturnCallback(function (array $userIDs): array {
                $r = [];
                foreach ($userIDs as $userID) {
                    $r[$userID] = "sso-$userID";
                }
                return $r;
            });

        $permissions = new Permissions();
        $permissions->set("Garden.Settings.Manage", true);
        $session = $this->createStub(\Gdn_Session::class);
        $session
            ->method("getPermissions")
            ->willReturn($permissions);

        $this->middleware = new APIExpandMiddleware(
            "/",
            Permissions::RANK_COMMUNITY_MANAGER,
            $userModel,
            $session
        );

        $this->middleware->addExpandField("ssoID", [
            "firstInsertUser.ssoID" => "firstInsertUserID",
            "insertUser.ssoID" => "insertUserID",
            "lastInsertUser.ssoID" => "lastInsertUserID",
            "lastPost.insertUser.ssoID" => "lastPost.insertUserID",
            "lastUser.ssoID" => "lastUserID",
            "updateUser.ssoID" => "updateUserID",
            "user.ssoID" => "userID",
            "ssoID" => "userID",
        ], [$userModel, "getDefaultSSOIDs"]);
    }

    /**
     * Getters and setters should work.
     */
    public function testGettersSetters(): void {
        $this->middleware->setBasePath('/foo');
        $this->assertSame('/foo', $this->middleware->getBasePath());
    }

    /**
     * Test that we don't do anything if the expand field is not preset.
     */
    public function testPreservesEmptyExpand(): void {
        $request = new Request('/');
        $this->assertArrayNotHasKey('expand', $request->getQuery());

        call_user_func($this->middleware, $request, function () {
            return [];
        });
        $this->assertArrayNotHasKey('expand', $request->getQuery());
    }

    /**
     * Test a basic expansion.
     */
    public function testBasicExpand(): void {
        $request = new Request('/?expand=insertUser.ssoID');
        $next = function ($r) {
            return [
                'insertUserID' => 1
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'insertUserID' => 1,
            'insertUser' => [
                'ssoID' => 'sso-1',
            ]
        ], $actual->getData());
    }

    /**
     * You should be able to specify two expand fields.
     */
    public function testTwoExpandFields(): void {
        $request = new Request('/?expand=insertUser.ssoID,updateUser.ssoID');
        $next = function ($r) {
            return [
                'insertUserID' => 1,
                'body' => 'foo',
                'updateUserID' => 2,
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'insertUserID' => 1,
            'body' => 'foo',
            'updateUserID' => 2,
            'insertUser' => [
                'ssoID' => 'sso-1',
            ],
            'updateUser' => [
                'ssoID' => 'sso-2',
            ],
        ], $actual->getData());
    }

    /**
     * You should be able to specify multiple fields from separate field expanders.
     */
    public function testMultipleExpanders(): void {
        // Should already have one expander, added in setup. Add another.
        $this->middleware->addExpandField("extended", [
            "updateUser.foo" => "updateUserID",
        ], function (array $ids) {
            $r = [];
            foreach ($ids as $id) {
                $r[$id] = "bar-{$id}";
            }
            return $r;
        });

        $otherExpand = "baz";
        $expand = ["insertUser.ssoID", "updateUser.foo", $otherExpand];
        $request = new Request("/?expand=" . implode(",", $expand));

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, function () {
            return [
                "insertUserID" => 1,
                "body" => "foo",
                "updateUserID" => 2,
            ];
        });
        $this->assertSame([
            "insertUserID" => 1,
            "body" => "foo",
            "updateUserID" => 2,
            "insertUser" => [
                "ssoID" => "sso-1",
            ],
            "updateUser" => [
                "foo" => "bar-2",
            ],
        ], $actual->getData());
        $this->assertSame($otherExpand, $request->getQuery()["expand"]);
    }

    /**
     * The expansion should work an an array of results.
     */
    public function testArrayExpandFields(): void {
        $request = new Request('/?expand=insertUser.ssoID');
        $next = function ($r) {
            return [
                [
                    'insertUserID' => 1
                ],
                [
                    'insertUserID' => 2
                ],
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            [
                'insertUserID' => 1,
                'insertUser' => [
                    'ssoID' => 'sso-1',
                ],
            ],
            [
                'insertUserID' => 2,
                'insertUser' => [
                    'ssoID' => 'sso-2',
                ],
            ]
        ], $actual->getData());
    }

    /**
     * Expanding SSO IDs should not overwrite the result.
     */
    public function testNonDestructiveExpansion(): void {
        $request = new Request('/?expand=insertUser.ssoID');
        $next = function ($r) {
            return [
                'insertUserID' => 1,
                'insertUser' => [
                    'name' => 'foo',
                ],
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'insertUserID' => 1,
            'insertUser' => [
                'name' => 'foo',
                'ssoID' => 'sso-1',
            ]
        ], $actual->getData());
    }

    /**
     * Field expansion should be driven by the querystring.
     */
    public function testNoExpansion(): void {
        $request = new Request('/');
        $next = function ($r) {
            return [
                'insertUserID' => 1
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'insertUserID' => 1,
        ], Data::box($actual)->getData());
    }

    /**
     * Don't crash if there is a bad parameter.
     */
    public function testBadExpandParameter() {
        $request = new Request();
        $request->setQuery(['expand' => (object)['haha' => 'haha']]);
        $next = function ($r) {
            return [];
        };

        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertTrue(true);
    }

    /**
     * A more realistic version of a bad parameter.
     */
    public function testBadExpandParameter2() {
        $request = new Request();
        $request->setQuery(['expand' => [['nested' => true]]]);
        $next = function ($r) {
            return [];
        };

        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertTrue(true);
    }

    /**
     * A basic test for the OpenAPI factory.
     */
    public function testOpenAPIFactory() {
        $actual = APIExpandMiddleware::filterOpenAPIFactory($this->middleware);
        $this->assertSame($actual[0], $this->middleware);
    }

    /**
     * Open API parameters should be augmented with the `".ssoID"` parameters.
     */
    public function testBasicOpenAPIAugmentation() {
        $in = json_decode(<<<EOT
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
            , true);

        $expected = json_decode(<<<EOT
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
                "updateUser.ssoID"
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
            , true);

        $this->middleware->filterOpenAPI($in);
        $this->assertSame($expected, $in);
    }

    /**
     * Expand parameters should work in the components section of an OpenAPI schema too.
     */
    public function testOpenAPIAugmentationComponents() {
        $in = json_decode(<<<EOT
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
, true);

        $expected = json_decode(<<<EOT
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
              "updateUser.ssoID"
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
            , true);

        $this->middleware->filterOpenAPI($in);
        $this->assertSame($expected, $in);
    }

    /**
     * Expansion shouldn't overly recurse into expanded records.
     */
    public function testNoDoubleExpand(): void {
        $request = new Request('/?expand=insertUser.ssoID');
        $next = function ($r) {
            return [
                'insertUserID' => 1,
                'insertUser' => [
                    'insertUserID' => 1,
                    'name' => 'foo',
                ]
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'insertUserID' => 1,
            'insertUser' => [
                'insertUserID' => 1,
                'name' => 'foo',
                'ssoID' => 'sso-1',
            ]
        ], $actual->getData());
    }

    /**
     * Expansion has a supported nesting with a dot separator.
     */
    public function testExpandDotNotation(): void {
        $request = new Request('/?expand=lastPost.insertUser.ssoID');
        $next = function ($r) {
            return [
                'insertUserID' => 1,
                'lastPostID' => 2,
                'lastPost' => [
                    'name' => 'Pizza',
                    'insertUserID' => 3,
                ],
            ];
        };

        /** @var Data $actual */
        $actual = call_user_func($this->middleware, $request, $next);
        $this->assertSame([
            'insertUserID' => 1,
            'lastPostID' => 2,
            'lastPost' => [
                'name' => 'Pizza',
                'insertUserID' => 3,
                'insertUser' => [
                    'ssoID' => 'sso-3',
                ],
            ],
        ], $actual->getData());
    }
}
