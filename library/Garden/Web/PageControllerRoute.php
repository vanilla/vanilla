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
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\PageDispatchController;

/**
 * Route for a PageDispatchController.
 */
class PageControllerRoute extends ResourceRoute {

    /**
     * Initialize a new {@link ResourceRoute}.
     *
     * @param string $basePath The base path to route to.
     * @param class-string<PageDispatchController> $controllerClass The class for the controller.
     * @param ContainerInterface|null $container An optional container used to create controller instances.
     */
    public function __construct($basePath, $controllerClass, ContainerInterface $container = null) {
        parent::__construct(
            $basePath,
            '',
            $container
        );

        $this->setMeta("CONTENT_TYPE", 'text/html; charset=utf-8');
        $this->setRootController($controllerClass);
    }

    /**
     * Configure the container with some routes.
     *
     * @param ContainerConfigurationInterface $dic The container.
     * @param array{string, class-string<PageDispatchController>} $definitions A mapping of prefix => classname.
     *
     * @return void
     */
    public static function configurePageRoutes(ContainerConfigurationInterface $dic, array $definitions): void {
        $dic = $dic->rule(Dispatcher::class);

        foreach ($definitions as $routePrefix => $controllerClass) {
            $dic->addCall('addRoute', [self::containerDefinition($routePrefix, $controllerClass)]);
        }
    }

    /**
     * Get a reference for this controllers route for the container.
     *
     * @param string $routePrefix A prefix for the route like '/my-prefix'
     * @param class-string<PageDispatchController> $controllerClass The classname of the controller to create the route for.
     *
     * @return Reference
     */
    private static function containerDefinition(string $routePrefix, string $controllerClass): Reference {
        return new Reference(
            self::class,
            [ $routePrefix, $controllerClass ]
        );
    }
}
