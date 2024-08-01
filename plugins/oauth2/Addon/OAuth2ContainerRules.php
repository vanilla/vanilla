<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\OAuth2\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Vanilla\AddonContainerRules;
use Vanilla\Dashboard\AuthenticatorTypeService;
use Vanilla\OAuth2\OAuth2AuthenticationTypeProvider;

/**
 * Container rules for the groups addon.
 */
class OAuth2ContainerRules extends AddonContainerRules
{
    /**
     * @inheritdoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container
            ->rule(AuthenticatorTypeService::class)
            ->addCall("addAuthenticatorType", [new Reference(OAuth2AuthenticationTypeProvider::class)]);
    }
}
