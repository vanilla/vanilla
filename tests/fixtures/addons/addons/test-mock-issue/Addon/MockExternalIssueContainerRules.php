<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\addons\addons\TestMockIssue\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Vanilla\AddonContainerRules;
use Vanilla\Dashboard\Models\ExternalIssueService;
use VanillaTests\Fixtures\addons\addons\TestMockIssue\MockExternalIssueProvider;

/**
 * MockExternalIssue container rules for tests.
 */
class MockExternalIssueContainerRules extends AddonContainerRules
{
    /**
     * @inheritDoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container
            ->rule(ExternalIssueService::class)
            ->addCall("addProvider", [new \Garden\Container\Reference(MockExternalIssueProvider::class)]);
    }
}
