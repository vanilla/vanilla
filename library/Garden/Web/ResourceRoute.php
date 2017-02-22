<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web;

use Garden\ClassLocator;
use Interop\Container\ContainerInterface;

/**
 * Maps requests to controllers using RESTful URLs.
 *
 * Here are some of the features of this route.
 *
 * - You can attach the route to base path. (ex. /api/controller maps to ApiController).
 * - You can customize the naming scheme of the controller with a controller pattern.
 * - Add parameter constraints to help clean data and disambiguate between different endpoints.
 * - Controllers can be created through an optional dependency injection container.
 * - Class and method lookup can be customized.
 * - Supports different controller methods for different HTTP methods.
 */
class ResourceRoute extends Route {
    private static $specialMethods = ['index', 'get', 'post', 'patch', 'put', 'options', 'delete'];

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $controllerPattern;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ClassLocator
     */
    private $classLocator;

    /**
     * Initialize a new {@link ResourceRoute}.
     *
     * @param string $basePath The base path to route to.
     * @param string $controllerPattern A controller pattern that defines how controller classes are named.
     * @param ContainerInterface|null $container An optional container used to create controller instances.
     * @param ClassLocator|null $classLocator A class locator used to lookup classes and methods on the controllers.
     */
    public function __construct(
        $basePath = '/',
        $controllerPattern = '%sController',
        ContainerInterface $container = null,
        ClassLocator $classLocator = null
    ) {
        parent::__construct();
        $this->setBasePath($basePath);
        $this->setControllerPattern($controllerPattern);
        $this->container = $container;
        $this->classLocator = $classLocator ?: new ClassLocator();
    }

    /**
     * {@inheritdoc}
     */
    public function match(RequestInterface $request) {
        $path = $request->getPath();

        // First check and strip the base path.
        if (stripos($path, $this->basePath) === 0) {
            $pathPart = substr($path, strlen($this->basePath));
        } else {
            return null;
        }

        $pathArgs = explode('/', $pathPart);

        // First look for the controller.
        $controllerSlug = $this->filterName(array_shift($pathArgs));
        if (null === ($controllerClass = $this->classLocator->findClass(sprintf($this->controllerPattern, $controllerSlug)))) {
            return null;
        }

        // Now look for a method.
        $controller = $this->createInstance($controllerClass);
        $result = $this->findAction($controller, $request, $pathArgs);
        return $result;
    }

    /**
     * Convert a dash-cased name into capital case.
     *
     * @param string $name The name to convert.
     * @return string Returns the filtered name.
     */
    private function filterName($name) {
        $result = implode('', array_map('ucfirst', explode('-', $name)));
        return $result;
    }

    /**
     * Create an instance of class.
     *
     * @param string $class The name of the class to instantiate.
     * @return object Returns an instance of the class.
     */
    private function createInstance($class) {
        if ($this->container !== null) {
            return $this->container->get($class);
        } else {
            return new $class;
        }
    }

    /**
     * Find the method call for a controller.
     *
     * @param object $controller The controller to find the method for.
     * @param RequestInterface $request The request being routed.
     * @param array $pathArgs The current path arguments from the request.
     * @return Action|null Returns method call information or **null** if there is no method.
     */
    private function findAction($controller, RequestInterface $request, array $pathArgs) {
        foreach ($this->getControllerMethodNames($request->getMethod(), $pathArgs) as list($methodName, $omit)) {
            if ($callback = $this->classLocator->findMethod($controller, $methodName)) {
                $args = $pathArgs;
                if ($omit !== null) {
                    array_splice($args, $omit, 1);
                }

                $callbackArgs = $this->matchArgs($callback, $request, $args, $controller);

                if ($callbackArgs !== null) {
                    $result = new Action($callback, $callbackArgs);
                    return $result;
                }
            }
        }
        return null;
    }

    /**
     * Try and match the arguments for a callback from a request.
     *
     * How arguments are matched:
     *
     * 1. Parameters with names that match the mappings will be set from the appropriate request item.
     * 2. The {@link $pathArgs} are matched to the callback's parameters in order.
     * 3. If an argument doesn't conform to one of the constraints in the route then the match fails.
     * 4. If the callback has less arguments than the path requires then the match will fail.
     * 5. If the callback is variadic then it will take all of the remaining path arguments.
     *
     * @param callable $callback The callback to match the arguments for.
     * @param RequestInterface $request The request used to match.
     * @param array $pathArgs The current request path.
     * @param object $sender The controller running the request.
     */
    private function matchArgs(callable $callback, RequestInterface $request, array $pathArgs, $sender = null) {
        $method = $this->reflectCallback($callback);

        $result = [];
        $toMap = []; // keys for late mapping.
        $args = []; // reflected $pathArgs
        foreach ($method->getParameters() as $i => $param) {
            /* @var \ReflectionParameter $param */
            $name = $param->getName();

            if ($this->isMapped($name)) {
                // This is a mapped parameter, but map after everything is reflected.
                $toMap[] = $name;
                $result[$name] = null;
            } elseif ($param->getClass() !== null && is_a($request, $param->getClass()->getName())) {
                $result[$name] = $request;
            } elseif ($param->getClass() !== null && is_a($sender, $param->getClass()->getName())) {
                // This is the sender in a callback.
                $result[$name] = $sender;
            } elseif ($param->isVariadic()) {
                // This is the last variadic parameter and will take the rest of the arguments.
                $result[$name] = $pathArgs;
                $pathArgs = [];
            } else {
                // Look at the path arguments for the value.
                $value = array_shift($pathArgs);
                if ($value === null) {
                    if ($param->isDefaultValueAvailable()) {
                        $value = $param->getDefaultValue();
                    } else {
                        // Not enough parameters passed to match the method so bail.
                        return null;
                    }
                }

                if (($param->isDefaultValueAvailable() && $value === $param->getDefaultValue()) || $this->testCondition($name, $value)) {
                    $result[$name] = $value;
                } else {
                    // The condition failed so this callback doesn't match.
                    return null;
                }
            }
        }

        // If the path still has stuff left then it's not a valid match.
        if (!empty($pathArgs)) {
            return null;
        }

        // Fill in all of the mappings now that everything has been reflected.
        foreach ($toMap as $name) {
            $result[$name] = $this->mapParam($name, $request, $args);
        }

        return $result;
    }

    /**
     * Get the reflection object for a callback.
     *
     * @param callable $callback The callback to reflect.
     * @return \ReflectionFunctionAbstract Returns the appropriate reflection object for the type of callback passed.
     */
    private function reflectCallback(callable $callback) {
        if (is_array($callback)) {
            return new \ReflectionMethod(...$callback);
        } else {
            return new \ReflectionFunction($callback);
        }
    }

    /**
     * Get the potential names of controller methods from the path.
     *
     * @param string $method The request method.
     * @param array $pathArgs The current path of the request, minus the controller part.
     * @return array Returns an array of method names and an optional omission index.
     */
    private function getControllerMethodNames($method, $pathArgs) {
        $method = strtolower($method);
        $result = [];

        if (isset($pathArgs[0])) {
            $name = lcfirst($this->filterName($pathArgs[0]));
            $result[] = ["{$method}_{$name}", 0];
            if (!in_array($name, self::$specialMethods)) {
                $result[] = [$name, 0];
            }
        }
        if (isset($pathArgs[1])) {
            $name = lcfirst($this->filterName($pathArgs[1]));
            $result[] = ["{$method}_{$name}", 1];
            if (!in_array($name, self::$specialMethods)) {
                $result[] = [$name, 1];
            }
        }

        $result[] = [$method, null];

        if ($method === 'get') {
            $result[] = ['index', null];
        }

        return $result;
    }



    /**
     * Get the classLocator.
     *
     * @return ClassLocator Returns the classLocator.
     */
    public function getClassLocator() {
        return $this->classLocator;
    }

    /**
     * Set the class locator.
     *
     * @param ClassLocator $classLocator The new class locator.
     * @return $this
     */
    public function setClassLocator(ClassLocator $classLocator) {
        $this->classLocator = $classLocator;
        return $this;
    }

    /**
     * Get the base path.
     *
     * @return string Returns the basePath.
     */
    public function getBasePath() {
        return $this->basePath;
    }

    /**
     * Set the base path.
     *
     * @param string $basePath The new base path.
     * @return $this
     */
    public function setBasePath($basePath) {
        $this->basePath = '/'.ltrim($basePath, '/');
        return $this;
    }

    /**
     * Get the controller pattern.
     *
     * @return string Returns the controllerPattern.
     */
    public function getControllerPattern() {
        return $this->controllerPattern;
    }

    /**
     * Set the controller pattern.
     *
     * The controller pattern is passed to {@link sprintf()} to map a slug in path to a controller name.
     *
     * @param string $controllerPattern The new controller pattern.
     * @return $this
     */
    public function setControllerPattern($controllerPattern) {
        $this->controllerPattern = $controllerPattern;
        return $this;
    }
}
