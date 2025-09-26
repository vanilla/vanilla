<?php
namespace Vanilla\SwaggerUI\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Web\PageControllerRoute;
use Vanilla\AddonContainerRules;
use Vanilla\SwaggerUI\Controllers\ApiDocsPageController;

/**
 * Container rules for the Swagger UI addon.
 */
class SwaggerUIContainerRules extends AddonContainerRules
{
    /**
     * @inheritdoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        PageControllerRoute::configurePageRoutes($container, [
            "/settings/api-docs" => ApiDocsPageController::class,
        ]);
    }
}
