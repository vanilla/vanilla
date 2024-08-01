<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\JsConnect\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Vanilla\AddonContainerRules;
use Vanilla\Dashboard\AuthenticatorTypeService;
use Vanilla\JsConnect\Models\JsConnectAuthenticatorTypeProvider;

/**
 * Container rules for the jsconnect addon.
 */
class JsConnectContainerRules extends AddonContainerRules
{
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container
            ->rule(AuthenticatorTypeService::class)
            ->addCall("addAuthenticatorType", [new Reference(JsConnectAuthenticatorTypeProvider::class)]);
    }
}
