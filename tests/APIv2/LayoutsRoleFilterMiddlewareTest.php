<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Tests for api/v2/layouts endpoints
 */
class LayoutsRoleFilterMiddlewareTest extends SiteTestCase {

    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    //region Properties

    /** {@inheritdoc} */
    protected $baseUrl = '/layouts';

    /** {@inheritdoc} */
    protected $pk = 'layoutID';

    /** {@inheritdoc} */
    protected $editFields = ['name', 'layout'];

    /** {@inheritdoc} */
    protected $patchFields = ['name', 'layout'];

    /** {@inheritdoc} */
    protected $record = [
        'name' => 'Layout',
        'layout' => [
            ['foo' => 'bar'],
            ['fizz' => 'buzz', 'flip' => ['flap', 'flop'], 'drip' => ['drop' => 'derp']]
        ],
        'layoutViewType' => 'home'
    ];

    /**
     * Test that we can apply role-filter middleware before hydrating.
     *
     * @return void
     */
    public function testRoleFilterHydrate() {
        $customRole = $this->createRole();
        $roleID = $customRole['roleID'];
        $user = $this->createUser(['roleID' => [$roleID, \RoleModel::MEMBER_ID]]);

        $layoutDefinition = json_decode('
            {
                "children": [
                    {
                        "$hydrate": "literal",
                        "$middleware": {
                            "role-filter": {
                                "roleIDs": [
                                    '.$roleID.'
                                ]
                            }
                        },
                        "data": {
                            "$hydrate": "literal",
                            "data": "This will get resolved '.$roleID.'."
                        }
                    },
                    {
                        "$hydrate": "literal",
                        "$middleware": {
                            "role-filter": {
                                "roleIDs": [
                                    '.$roleID.',
                                    '.($roleID+1).'
                                ]
                            }
                        },
                        "data": {
                            "$hydrate": "literal",
                            "data": "Will get resolved having roleID '.$roleID.' and roleID '.($roleID+1).'."
                        }
                    },
                    {
                        "$hydrate": "literal",
                        "$middleware": {
                            "role-filter": {
                                "roleIDs": [
                                    '.($roleID+1).'
                                ]
                            }
                        },
                        "data": {
                            "$hydrate": "literal",
                            "data": "WILL NOT get resolved for roleID '.($roleID+1).'."
                        }
                    },
                    {
                        "$hydrate": "literal",
                        "data": {
                            "$hydrate": "literal",
                            "data": "No middleware will get resolved."
                        }
                    }
                ]
            }', true);

        $expected = [
            0 => [
                'children' => [
                    [
                        '$hydrate' => 'literal',
                        'data' => 'This will get resolved '.($roleID).'.'
                    ],
                    [
                        '$hydrate' => 'literal',
                        'data' => 'Will get resolved having roleID '.($roleID).' and roleID '.($roleID+1).'.'
                    ],
                        null, // this one is stripped
                    [
                        '$hydrate' => 'literal',
                        'data' => 'No middleware will get resolved.'
                    ]
                ]
            ]
        ];
        // Posting to the main hydrate endpoints will have the correct result.
        $response = $this->api()->post('/layouts', [
            'layout' => [$layoutDefinition],
            'name' => 'middleware layout',
            'layoutViewType' => 'home',
        ]);
        $layoutID = $response->getBody()['layoutID'];

        $this->runWithUser(function () use ($layoutID, $expected) {
                // Now save it and check filter applied to GET by ID.
                $response = $this->api()->get("/layouts/{$layoutID}/hydrate", [
                    'params' => [],
                ]);
                $this->assertEquals(200, $response->getStatusCode());
                $this->assertSame($expected, $response->getBody()['layout']);
        }, $user);
    }

    //endregion
}
