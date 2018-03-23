<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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


        $this
            ->setConstraint('id', ['regex' => '`^\d+$`', 'position' => 0])
            ->setConstraint('page', '`^p\d+$`');
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
        $resource = array_shift($pathArgs);
        $controllerSlug = $this->filterName($resource);
        foreach ((array)$this->controllerPattern as $controllerPattern) {
            $controllerClass = $this->classLocator->findClass(sprintf($controllerPattern, $controllerSlug));
            if ($controllerClass) {
                break;
            }
        }
        if (!isset($controllerClass)) {
            return null;
        }

        // Now look for a method.
        $controller = $this->createInstance($controllerClass);
        $result = $this->findAction($controller, $request, $pathArgs);

        if ($result !== null) {
            $result->setMeta('resource', $resource);
        }

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
        $methodNames = $this->getControllerMethodNames($request->getMethod(), $pathArgs);
        foreach ($methodNames as list($methodName, $omit)) {
            if ($callback = $this->findMethod($controller, $methodName)) {
                $args = $pathArgs;
                if ($omit !== null) {
                    unset($args[$omit]);
                }
                $method = $this->reflectCallback($callback);

                if (!$this->checkMethodCase($method->getName(), $methodName, $controller, true)) {
                    continue;
                }

                $callbackArgs = $this->matchArgs($method, $request, $args, $controller);

                if ($callbackArgs !== null) {
                    $result = new Action($callback, $callbackArgs);
                    $result->setMeta('method', $request->getMethod());
                    $result->setMeta('action', $result->getCallback()[1]);
                    return $result;
                }
            }
        }
        return null;
    }

    /**
     * Double check to make sure the case of the method matches.
     *
     * This really shouldn't be done here, but I want to make sure we are strict before bad URLs get out.
     *
     * @param string $method The actual object method name.
     * @param string $compare The method to compare to.
     * @param object $obj The object the method belongs to.
     * @param bool $notice Whether to trigger a notice when the check doesn't work.
     * @return bool Returns **true** if the name is correct or **false** otherwise.
     */
    private function checkMethodCase($method, $compare, $obj, $notice = false) {
        $methodSx = trim(strrchr($method, '_'), '_');
        $trySx = trim(strrchr($compare, '_'), '_');

        if ($methodSx !== $trySx) {
            if ($notice) {
                $expected = get_class($obj).'::'.substr($method, 0, -strlen($methodSx)).$trySx.'()';
                trigger_error("Method name has incorrect case. Expecting $expected.");
            }
            return false;
        }
        return true;
    }

    /**
     * Determine whether a method exists on a controller.
     *
     * @param object $controller The controller to examine.
     * @param string $methodName The name of the method.
     * @return callable|null Returns the method callback or null if it doesn't.
     */
    private function findMethod($controller, $methodName) {
        $regex = '`^(get|index|post|patch|put|options|delete)(_|$)`i';

        // Getters and setters aren't found.
        if (!(preg_match($regex, $methodName) || strcasecmp($methodName, 'index') === 0)) {
            return null;
        }

        return $this->classLocator->findMethod($controller, $methodName);
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
     * @param \ReflectionFunctionAbstract $method The callback to match the arguments for.
     * @param RequestInterface $request The request used to match.
     * @param array $pathArgs The current request path.
     * @param object $sender The controller running the request.
     * @return array|mixed|null
     * @internal param int|null $namePos The position of the name in the path.
     */
    private function matchArgs(\ReflectionFunctionAbstract $method, RequestInterface $request, array $pathArgs, $sender = null) {
        list($defaults, $params, $mapped, $pathParam) = $this->splitMappedParameters($method);

        $args = []; // reflected $pathArgs without mappings.
        $i = 0;
        $pathCapture = false;
        foreach ($params as $param) {
            /* @var \ReflectionParameter $param */
            $name = $param->getName();

            if ($param->isVariadic()) {
                // This is the last variadic parameter and will take the rest of the arguments.
                $args[$name] = $pathArgs;

                // Variadic args are a little different. They have to be merged separately.
                if (empty($pathArgs)) {
                    unset($defaults[$name]);
                } else {
                    $defaults[$name] = array_shift($pathArgs);
                    $defaults = array_merge($defaults, $pathArgs);
                    $pathArgs = [];
                }
            } else {
                // Look at the path arguments for the value.
                $pos = key($pathArgs);
                $value = array_shift($pathArgs);
                if ($value === null) {
                    if ($param->isDefaultValueAvailable()) {
                        $value = $param->getDefaultValue();
                    } else {
                        // Not enough parameters passed to match the method so bail.
                        return null;
                    }
                }

                if ($param === $pathParam) {
                    // If this is the path parameter then it will eat up at least itself up to the remaining parameters.
                    // We do this here so that further parameters don't get set to the wrong args.
                    $extraPathArgs = [$value];
                    for ($c = count($pathArgs) - count($params) + $i; $c >= 0; $c--) {
                        $extraPathArgs[] = array_shift($pathArgs);
                    }

                    $defaults[$name] = $args[$name] = $extraPathArgs;
                    $pathCapture = true;
                } elseif ($this->testConstraint($param, $value, ['position' => $pos])) {
                    $defaults[$name] = $args[$name] = $value;
                    $pathCapture = false;
                } elseif ($pathCapture === true && $param->isDefaultValueAvailable()) {
                    // The path argument can take the bad value.
                    $defaults[$pathParam->getName()][] = $args[$pathParam->getName()][] = $value;
                    $defaults[$name] = $args[$name] = $param->getDefaultValue();
                } else {
                    // The condition failed so this callback doesn't match.
                    return null;
                }
            }
            $i++;
        }

        // If the path still has stuff left then it's not a valid match.
        if (!empty($pathArgs)) {
            return null;
        }

        // Fix the path.
        if ($pathParam !== null && !$pathParam->isArray()) {
            $args[$pathParam->getName()] = $defaults[$pathParam->getName()] = '/'.implode('/', $defaults[$pathParam->getName()]);
        }

        // Fill in all of the mappings now that everything has been reflected.
        foreach ($mapped as $name => $param) {
            if ($param->getClass() !== null && is_a($sender, $param->getClass()->getName(), true)) {
                $defaults[$name] = $sender;
            } else {
                $defaults[$name] = $this->mapParam($param, $request, $args);
            }
        }

        return $defaults;
    }

    /**
     * Split a function into its regular parameters and mapped parameters.
     *
     * This method returns several {@link \ReflectionParameter} objects:
     *
     * - **$defaults[]**: An array of all method parameters with default values or **null** if there is none.
     * - **$mapped[]**: All mapped parameters.
     * - **$params[]**: All path parameters.
     * - **$path**: The path parameter because it requires special handling.
     *
     * @param \ReflectionFunctionAbstract $method The method to split.
     * @return array Returns an array in the form `[$defaults[], $mapped[], $params[], $path]`.
     */
    private function splitMappedParameters(\ReflectionFunctionAbstract $method) {
        $defaults = [];
        $mapped = [];
        $params = [];
        $path = null;

        foreach ($method->getParameters() as $param) {
            $name = $param->getName();

            $defaults[$name] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            if ($this->isMapped($param, Route::MAP_PATH)) {
                // The path can eat other parameters so keep track of it.
                $params[$name] = $param;
                $path = $param;
            } elseif ($this->isMapped($param)) {
                $mapped[$name] = $param;
            } elseif ($param->getClass() !== null) {
                // Type-hinted parameters can't be mapped from the path.
                $mapped[$name] = $param;
            } else {
                $params[$name] = $param;
            }
        }

        return [$defaults, $params, $mapped, $path];
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

            if ($method === 'get') {
                $result[] = ["index_{$name}", 0];
            }
        }
        if (isset($pathArgs[1])) {
            $name = lcfirst($this->filterName($pathArgs[1]));
            $result[] = ["{$method}_{$name}", 1];

            if ($method === 'get') {
                $result[] = ["index_{$name}", 1];
            }
        }

        $result[] = [$method, null];

        if ($method === 'get') {
            $result[] = ['index', null];
        } elseif ($method === 'post' && !empty($pathArgs)) {
            // This is a bit of a kludge to allow POST to be used against the usual PATCH method to allow for
            // multipart/form-data on PATCH (edit) endpoints.
            $result[] = ['patch', null];
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
