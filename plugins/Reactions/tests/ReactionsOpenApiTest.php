<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Reactions;

use VanillaTests\OpenAPIBuilderTrait;
use VanillaTests\VanillaTestCase;

/**
 * Tests for the Reactions openApi.
 */
class ReactionsOpenApiTest extends VanillaTestCase
{
    use OpenAPIBuilderTrait;

    public static $addons = ["vanilla", "reactions"];

    /**
     * Test that the reactionType parameter is added to the /discussions get endpoint.
     */
    public function testReactionsAdditionsToOpenAPI()
    {
        $builder = $this->createOpenApiBuilder();
        $data = $builder->generateFullOpenAPI();
        $discussionsGetParams = array_column($data["paths"]["/discussions"]["get"]["parameters"], "name");
        $this->assertContains("reactionType", $discussionsGetParams);
    }
}
