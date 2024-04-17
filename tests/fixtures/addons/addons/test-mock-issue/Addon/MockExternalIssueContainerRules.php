<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Addons\TestMockIssue\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Vanilla\AddonContainerRules;
use Vanilla\Dashboard\Models\AttachmentService;
use VanillaTests\Fixtures\Addons\TestMockIssue\MockAttachmentProvider;

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
            ->rule(AttachmentService::class)
            ->addCall("addProvider", [new \Garden\Container\Reference(MockAttachmentProvider::class)]);
    }
}
