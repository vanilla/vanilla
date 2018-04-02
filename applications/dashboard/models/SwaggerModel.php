<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Dashboard\Models;

use Garden\EventManager;
use Garden\Web\Dispatcher;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Garden\Web\ResourceRoute;
use Interop\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use Vanilla\AddonManager;
use OpenApiApiController;

/**
 * Handles the swagger JSON commands.
 *
 * Note this isn't a real Vanilla model.
 */
class SwaggerModel {
    private static $httpMethods = ['get', 'post', 'patch', 'put', 'options', 'delete'];

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var AddonManager
     */
    private $addonManager;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var ResourceRoute
     */
    private $route;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var ContainerInterface The container used to create controllers.
     */
    private $container;

    private $exclude = [
        OpenApiApiController::class,
        \AuthenticateApiController::class,
        \AuthenticatorsApiController::class,
    ];

    /**
     * Construct a {@link SwaggerModel}.
     *
     * @param RequestInterface $request The page request used to construct URLs.
     * @param AddonManager $addonManager The addon manager dependency used to find classes.
     * @param EventManager $eventManager The event manager dependency used to intercept/change controller methods.
     * @param Dispatcher $dispatcher The dispatcher used to inspect routing behavior.
     * @param ContainerInterface $container The container used to construct controllers.
     */
    public function __construct(
        RequestInterface $request,
        AddonManager $addonManager,
        EventManager $eventManager,
        Dispatcher $dispatcher,
        ContainerInterface $container
    ) {
        $this->request = $request;
        $this->addonManager = $addonManager;
        $this->dispatcher = $dispatcher;
        $this->route = $this->dispatcher->getRoute('api-v2');
        $this->eventManager = $eventManager;
        $this->container = $container;
    }

    /**
     * Get the root node of the swagger application.
     *
     * @return array Returns the root node.
     * @throws ServerException Throws an exception when the APIv2 router cannot be found.
     */
    public function getSwaggerObject() {
        if ($this->route === null) {
            throw new ServerException('Could not find the APIv2 router.', 500);
        }

        $r = [
            'swagger' => '2.0',
            'info' => [
                'title' => 'Vanilla API',
                'description' => 'API access to your community.',
                'version' => '2.0-alpha'
            ],
            'host' => $this->request->getHost(),
            'basePath' => $this->request->getRoot().'/api/v2',
            'consumes' => [
                'application/json',
                'application/x-www-form-urlencoded',
                'multipart/form-data'
            ],
            'paths' => []
        ];

        foreach ($this->getActions() as $action) {
            try {
                $r['paths'][$action->getPath()][strtolower($action->getHttpMethod())] = $action->getOperation();
            } catch (\Exception $ex) {
                $r['paths'][$action->getPath()][strtolower($action->getHttpMethod())] = $ex->getTraceAsString();
            }
        }

        $r = $this->gatherDefinitions($r);

        return $r;
    }

    /**
     * Get all of the controller actions in the API.
     *
     * @return \Generator|ReflectionAction[] Yields all of the API actions in a flat list.
     */
    private function getActions() {
        $controllers = $this->addonManager->findClasses('*\\*ApiController');
        usort($controllers, function ($a, $b) {
            return strnatcasecmp(EventManager::classBasename($a), EventManager::classBasename($b));
        });

        foreach ($controllers as $controller) {
            if (in_array($controller, $this->exclude, true)) {
                continue;
            }

            $class = new ReflectionClass($controller);
            if (!$class->isInstantiable()) {
                continue;
            }

            try {
                $instance = $this->container->get($controller);
            } catch (\Exception $e) {
                continue;
            }

            $actions = iterator_to_array($this->getControllerActions($class, $instance));

            usort($actions, function (ReflectionAction $a, ReflectionAction $b) {
                $cmp1 = strcasecmp($a->getPath(), $b->getPath());
                if ($cmp1 !== 0) {
                    return $cmp1;
                }

                $cmpa = array_search(strtolower($a->getHttpMethod()), static::$httpMethods);
                $cmpb = array_search(strtolower($b->getHttpMethod()), static::$httpMethods);

                if ($cmpa !== false && $cmpb !== false) {
                    return strnatcmp($cmpa, $cmpb);
                } elseif ($cmpa !== false) {
                    return -1;
                } elseif ($cmpb !== false) {
                    return 1;
                } else {
                    return strcmp($a->getHttpMethod(), $b->getHttpMethod());
                }

            });

            foreach ($actions as $action) {
                yield $action;
            }
        }
    }

    /**
     * Gather all of the named models used in the schema for the definitions element.
     *
     * @param array $arr The schema array.
     * @return array Returns an array of models definitions.
     */
    private function gatherDefinitions(array $arr) {
        $definitions = [];

        $fn = function (array $arr, $itemKey = null) use (&$definitions, &$fn) {
            $result = $arr;

            foreach ($result as $key => &$value) {
                if (is_array($value)) {
                    $value = $fn($value, $key);
                }
            }

            if ($itemKey !== 'properties' && isset($result['type'], $result['id'])) {
                $id = $result['id'];
                unset($result['id']);

                $definitions[$id] = $result;
                return ['$ref' => "#/definitions/$id"];
            }
            return $result;
        };

        $result = $fn($arr);

        if (!empty($definitions)) {
            ksort($definitions);
            $result['definitions'] = $definitions;
        }
        return $result;
    }

    /**
     * Get all of the actions for a controller.
     *
     * @param ReflectionClass $controller The controller class to reflect.
     * @param object $controllerInstance The controller instance used to call the action and capture events.
     * @return \Generator|ReflectionAction[] Yields the actions for the controller.
     */
    private function getControllerActions(ReflectionClass $controller, $controllerInstance) {
        $controllerMethods = [
            // Controller instance methods
            [
                'instance' => $controllerInstance,
                'methods' => $controller->getMethods(ReflectionMethod::IS_PUBLIC),
            ],
        ];

        // Event bounds methods
        $handlers = $this->eventManager->getAllHandlers();
        foreach ($handlers as $handlerName => $callbacks) {
            if (stripos($handlerName, $controller->getName().'_') === 0) {
                foreach ($callbacks as $callbackInfo) {
                    try {
                        $callbackInstance = $this->container->get($callbackInfo->class);
                    } catch (\Exception $e) {
                        continue;
                    }
                    $callbackClass = new ReflectionClass($callbackInstance);

                    $controllerMethods[] = [
                        'instance' => $callbackInstance,
                        'methods' => [$callbackClass->getMethod($callbackInfo->method)],
                    ];
                }

            }
        }

        foreach ($controllerMethods as $data) {
            $methodInstance = $data['instance'];
            $methods = $data['methods'];

            foreach ($methods as $method) {
                if ($method->isAbstract() || $method->isStatic() || $method->getName()[0] === '_' || $method->getName() === 'options') {
                    continue;
                }

                try {
                    $action = new ReflectionAction($method, $methodInstance, $controllerInstance, $this->route, $this->eventManager);

                    yield $action;
                } catch (\InvalidArgumentException $ex) {
                    continue;
                } catch (\Exception $ex) {
                    continue;
                }
            }
        }


    }
}
