<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout\Resolvers;

use Garden\Hydrate\DataHydrator;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Layout\LayoutHydrator;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the ApiResolver.
 */
class ApiResolverTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    /**
     * Test that we can resolve an API result and transform it.
     *
     * @return void
     */
    public function testResolvesAndTransforms()
    {
        $discussion1 = $this->createDiscussion(["name" => "myname1"]);
        $discussion2 = $this->createDiscussion(["name" => "myname2"]);

        $input = [
            "discussionNames" => [
                '$hydrate' => "api",
                "url" => "/discussions",
                "query" => [
                    "discussionID" => [$discussion2["discussionID"], $discussion1["discussionID"]],
                ],
                "jsont" => [
                    '$each' => "/",
                    '$item' => "name",
                ],
            ],
        ];

        $expected = [
            "discussionNames" => ["myname2", "myname1"],
        ];

        $actual = $this->getHydrator()->resolve($input);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * Test that exceptions are thrown from the resolver.
     *
     * They can be handled by the hydrator's exception handler.
     */
    public function testThrowsExceptions()
    {
        $this->expectException(NotFoundException::class);
        $this->getHydrator()->resolve([
            '$hydrate' => "api",
            "url" => "/doesnt/exist",
        ]);
    }

    /**
     * @return DataHydrator
     */
    private function getHydrator(): DataHydrator
    {
        return self::container()
            ->get(LayoutHydrator::class)
            ->getHydrator(null);
    }
}
