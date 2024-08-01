<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\OpenAPIBuilder;
use VanillaTests\Fixtures\Request;

trait OpenAPIBuilderTrait
{
    /**
     * Create a configured openAPIBuilder
     *
     * @return OpenAPIBuilder
     */
    public function createOpenApiBuilder(): OpenAPIBuilder
    {
        $am = new AddonManager(
            [
                Addon::TYPE_ADDON => ["/applications", "/plugins"],
                Addon::TYPE_THEME => "/themes",
                Addon::TYPE_LOCALE => "/locales",
            ],
            PATH_ROOT . "/tests/cache/open-api-builder/vanilla-manager"
        );

        $request = new Request();
        return new OpenAPIBuilder($am, $request, PATH_ROOT . "/tests/cache/" . __FUNCTION__ . ".php");
    }
}
