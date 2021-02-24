<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Cloud\ElasticSearch;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Search discussion content with sort: hot, top.
 */
class DiscussionsApiExpandTest extends AbstractAPIv2Test {
    use CommunityApiTestTrait;

    protected static $addons = ['vanilla'];

    /**
     * Prepare data for tests
     */
    public function testPrepareData() {
        $discussions = [
                ['name' => 'Discussion one', 'body' => 'Body one'],
                ['name' => 'Discussion two', 'body' => 'Body two'],
        ];

        foreach ($discussions as $discussion) {
            $this->createDiscussion($discussion);
        }

        $discussions = $this->api()->get("/discussions", ['limit' => 30])->getBody();
        $this->assertEquals(2, count($discussions));
    }

    /**
     * Tests for discussions expand options: excerpt, -body
     *
     * @param array $params
     * @param array $expectedResults
     * @param string $paramKey
     * @depends testPrepareData
     * @dataProvider queryDataProvider
     */
    public function testDiscussionsExpand(array $params, array $expectedResults, string $paramKey = null) {
        $this->assertApiResults('/discussions', $params, $expectedResults, false, count($expectedResults[$paramKey]));
    }

    /**
     * @return array
     */
    public function queryDataProvider() {
        return [
            'no expand options' => [
                [
                    'expand' => [],
                ],
                [
                    'name' => [
                        'Discussion one',
                        'Discussion two',
                    ],
                    'body' => [
                        'Body one',
                        'Body two',
                    ],
                    'excerpt' => null
                ],
                'name'
            ],
            'expand -body' => [
                [
                    'expand' => ['-body'],
                ],
                [
                    'name' => [
                        'Discussion one',
                        'Discussion two',
                    ],
                    'body' => null,
                    'excerpt' => null
                ],
                'name'
            ],
            'expand excerpt' => [
                [
                    'expand' => ['excerpt'],
                ],
                [
                    'name' => [
                        'Discussion one',
                        'Discussion two',
                    ],
                    'body' => [
                        'Body one',
                        'Body two',
                    ],
                    'excerpt' => [
                        'Body one',
                        'Body two',
                    ],
                ],
                'name'
            ],
            'expand: excerpt, -body' => [
                [
                    'expand' => ['excerpt', '-body'],
                ],
                [
                    'name' => [
                        'Discussion one',
                        'Discussion two',
                    ],
                    'body' => null,
                    'excerpt' => [
                        'Body one',
                        'Body two',
                    ],
                ],
                'name'
            ],
        ];
    }
}
