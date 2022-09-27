<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Psr\Container\ContainerInterface;
use Vanilla\Web\PageDispatchController;

/**
 * Route for a PageDispatchController.
 */
class PageControllerRoute extends ResourceRoute
{
    /**
     * Initialize a new {@link ResourceRoute}.
     *
     * @param string $basePath The base path to route to.
     * @param class-string<PageDispatchController> $controllerClass The class for the controller.
     * @param ContainerInterface|null $container An optional container used to create controller instances.
     */
    public function __construct($basePath, $controllerClass, ContainerInterface $container = null)
    {
        parent::__construct($basePath, "", $container);

        $this->setMeta("CONTENT_TYPE", "text/html; charset=utf-8");
        $this->setRootController($controllerClass);
    }

    /**
     * Configure the container with some routes.
     *
     * @param ContainerConfigurationInterface $dic The container.
     * @param array{string, class-string<PageDispatchController>} $definitions A mapping of prefix => classname.
     * @param string|null $featureFlag A theme feature flag or feature flag to lock the controller definition behind.
     * @param int $priority Priority for the route. Defaults to 0. Higher priorities match first.
     *
     * @return void
     */
    public static function configurePageRoutes(
        ContainerConfigurationInterface $dic,
        array $definitions,
        ?string $featureFlag = null,
        int $priority = 0
    ): void {
        foreach ($definitions as $routePrefix => $controllerClass) {
            $ruleName = "@route/" . $routePrefix;
            $dic->rule($ruleName)
                ->setClass(self::class)
                ->setConstructorArgs([$routePrefix, $controllerClass])
                ->addCall("setPriority", [$priority]);
            if ($featureFlag) {
                $dic->addCall("setFeatureFlag", [$featureFlag]);
            }
            $dic->rule(Dispatcher::class);
            $dic->addCall("addRoute", [new Reference($ruleName)]);
        }
    }
}
