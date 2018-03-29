<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Dashboard\Models;

use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Web\ResourceRoute;
use Garden\Web\Route;
use ReflectionMethod;

/**
 * Maps a PHP controller method to an open API action.
 */
class ReflectionAction {
    /**
     * @var ReflectionMethod
     */
    private $method;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var ResourceRoute $route
     */
    private $route;

    /**
     * @var string The name of the resource (the controller expressed as a path).
     */
    private $resource;

    /**
     * @var string
     */
    private $subpath;

    /**
     * @var string The http method of the resource.
     */
    private $httpMethod;

    /**
     * @var string
     */
    private $idParam;

    /**
     * @var string;
     */
    private $bodyParam;

    /**
     * @var string[]
     */
    private $params;

    /**
     * @var array
     */
    private $args;

    /**
     * @var object Instance to which the method belongs to.
     */
    private $methodInstance;

    /**
     * @var object The controller on which the action will be executed.
     */
    private $controllerInstance;

    /**
     * @var array
     */
    private $operation;

    /**
     * ReflectionAction constructor.
     *
     * @param ReflectionMethod $method The PHP method that the action is meant to represent.
     * @param object $methodInstance An object instance that the method belongs to.
     * @param object $controllerInstance The controller on which the action will be executed.
     * @param ResourceRoute $route The router used to inspect and quasi-reverse route the method.
     * @param EventManager $eventManager An event manager for capturing events.
     */
    public function __construct(
        ReflectionMethod $method,
        $methodInstance,
        $controllerInstance,
        ResourceRoute $route,
        EventManager $eventManager
    ) {
        $this->method = $method;
        $this->eventManager = $eventManager;
        $this->route = $route;
        $this->methodInstance = $methodInstance;
        $this->controllerInstance = $controllerInstance;

        $this->reflectAction();
    }

    /**
     * Reflect a controller action from a callback.
     *
     * @throws \InvalidArgumentException Throws an exception when the method name does not follow the convention used to
     * [map requests to methods](http://docs.vanillaforums.com/developer/framework/apiv2/resource-routing/#methods-names-actions).
     * @throws \InvalidArgumentException Throws an exception if the object in the callback is not named with the *ApiController suffix.
     */
    private function reflectAction() {
        $method = $this->method;
        $controllerPattern = $this->route->getControllerPattern();
        if (is_array($controllerPattern)) {
            $controllerPattern = reset($controllerPattern);
        }
        $resourceRegex = str_replace(['%s', '*\\'], ['([a-z][a-z0-9]*)', '(?:^|\\\\)'], $controllerPattern);

        // Regex the method name against event handler syntax or regular method syntax.
        if (preg_match(
            "`^(?:(?<class>$resourceRegex)_)?(?<method>get|post|patch|put|options|delete|index)(?:_(?<path>[a-z0-9]+?))?$`i",
            $method->getName(),
            $m
        )) {
            $controller = $m['class'] ?: get_class($this->controllerInstance);
            $httpMethod = $m['method'];
            $subpath = isset($m['path']) ? $m['path'] : '';
        } else {
            throw new \InvalidArgumentException("The method name does not match an action's pattern", 500);
        }

        if (strcasecmp($httpMethod, 'index') === 0) {
            $httpMethod = 'GET';
            $subpath = '';
        }

        // Check against the controller pattern.
        if (preg_match("`(?:^|\\\\)$resourceRegex$`i", $controller, $m)) {
            $resource = $m[1];
        } else {
            throw new \InvalidArgumentException("The controller is not an API controller.", 500);
        }

        $this->httpMethod = strtoupper($httpMethod);
        $this->resource = $this->dashCase($resource);
        $this->subpath = ltrim('/'.$this->dashCase($subpath), '/');

        $this->args = [];
        $eventBound = $method->class !== $controller;

        foreach ($method->getParameters() as $param) {
            // The first parameter of eventBounds endpoint has to be the controller.
            if ($eventBound && $param->getPosition() === 0) {
                $this->args[$param->getName()] = $this->controllerInstance;
                continue;
            }

            // Default the call args.
            $this->args[$param->getName()] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : ($param->isArray() ? [] : null);

            $p = null;
            if ($this->route->isMapped($param, Route::MAP_BODY)) {
                $this->bodyParam = $param->getName();
                $p = ['name' => $param->getName(), 'in' => 'body', 'required' => true];
            } elseif (!$param->getClass() && !$this->route->isMapped($param)) {
                $p = ['name' => $param->getName(), 'in' => 'path', 'required' => true];

                $constraint = (array)$this->route->getConstraint($param->getName()) + ['position' => '*'];

                $position = $param->getPosition();
                if ($eventBound) {
                    $position -= 1;
                }

                // Check if the "first" parameter is an idParam.
                if ($position === 0 && $constraint['position'] === $position) {
                    $this->idParam = $param->getName();
                }
            }

            if ($p !== null) {
                if ($param->isDefaultValueAvailable()) {
                    $p['default'] = $param->getDefaultValue();
                }

                $this->params[$p['name']] = $p;
            }
        }
    }

    /**
     * Get the swagger operation array for this action.
     *
     * @return array Returns an operation array.
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#operationObject
     */
    public function getOperation() {
        if ($this->operation === null) {
            $this->operation = $this->makeOperation();
        }
        return $this->operation;
    }

    /**
     * Make the Swagger operation array for this action using a combination of reflection and event trapping.
     *
     * @return array Returns an operation array.
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#operationObject
     */
    private function makeOperation() {
        /* @var Schema $in, $allIn */
        /* @var Schema $out */
        /* @var Schema $allIn */
        $in = $out = $allIn = null;
        $summary = '';
        $other = [];

        // Set up an event handler that will capture the schemas.
        $fn  = function ($controller, Schema $schema, $type) use (&$in, &$out, &$allIn) {
            switch ($type) {
                case 'in':
                    if (empty($this->bodyParam)) {
                        if ($allIn instanceof Schema) {
                            $allIn = $allIn->merge($schema);
                        } else {
                            $allIn = $schema;
                        }
                    } elseif ($in !== null) {
                        $allIn = $in;
                    }
                    $in = $schema;
                    break;
                case 'out':
                    $out = $schema;
                    throw new ShortCircuitException();
            }
        };

        try {
            $this->eventManager->bind('controller_schema', $fn, EventManager::PRIORITY_LOW);

            $r = $this->method->invoke($this->methodInstance, ...array_values($this->args));

        } catch (ShortCircuitException $ex) {
            // We should have everything we need now.
        } catch (\Exception $ex) {
            $other['deprecated'] = true;
            // We shouldn't get here, but let's allow it.
            $summary = "Something happened before the output schema was found. The endpoint most likely didn't define its output properly.";
        } finally {
            $this->eventManager->unbind('controller_schema', $fn);
        }

        // Fill in information about the parameters from the input schema.
        if ($in instanceof Schema) {
            $summary = $summary ?: $in->getDescription();
            if (empty($summary) && $allIn instanceof Schema) {
                $summary = $allIn->getDescription();
            }
            $inArr = $in->jsonSerialize();
            $allInArr = $allIn !== null ? $allIn->jsonSerialize() : [];
            unset($inArr['description']);

            if (!empty($this->bodyParam)) {
                $this->params[$this->bodyParam]['schema'] = $inArr;
                /* @var array $property */
                foreach ($allInArr['properties'] as $name => $property) {
                    if (isset($this->params[$name])) {
                        $this->params[$name] = (array)$this->params[$name] + (array)$property;
                    }
                }
            } else {
                /* @var array $property */
                foreach ($allInArr['properties'] as $name => $property) {
                    if (isset($this->params[$name])) {
                        $this->params[$name] = (array)$this->params[$name] + (array)$property;
                    } else {
                        $this->params[$name] = ['name' => $name, 'in' => 'query'] + $property;
                    }
                    $param = &$this->params[$name];

                    if (isset($property['enum']) && is_array($property['enum'])) {
                        $enumDescription = 'Must be one of: '.implode(', ', array_map('json_encode', $property['enum'])).'.';

                        $param['description'] = (empty($param['description']) ? '' : rtrim($param['description'], '.').".\n").$enumDescription;
                    }

                    if (isset($param['description'])) {
                        $param['description'] = \Gdn_Format::to($param['description'], 'markdown');
                    }

                    if (isset($allInArr['required']) && in_array($name, $allInArr['required'])) {
                        $param['required'] = true;
                    } else if (isset($param['required'])) {
                        unset($param['required']);
                    }
                }
            }
        }

        // Make sure the parameters have a type now.
        foreach ($this->params as $name => &$param) {
            if ($param['in'] === 'path') {
                $param += ['type' => $name === $this->idParam ? 'integer' : 'string'];
            }
        }

        // Fill in the responses.
        $responses = [];
        if ($out instanceof Schema && !empty($out->getSchemaArray())) {
            $status = $this->httpMethod === 'POST' && empty($this->idParam) ? '201' : '200';

            $responses[$status]['description'] = $out->getDescription() ?: 'Success';
            $responses[$status]['schema'] = $out->jsonSerialize();
        } else {
            $status = $this->httpMethod === 'POST' && empty($this->idParam) ? '201' : '204';
            $responses[$status]['description'] = 'Success';
        }

        $r = [
            'tags' => [ucfirst($this->resource)],
            'summary' => $summary,
            'parameters' => array_values($this->params),
            'responses' => $responses
        ] + $other;

        return array_filter($r);
    }

    /**
     * Convert a string from CapitalCase to dash-case.
     *
     * @param string $str The string to convert.
     * @return string Returns a dash-case string.
     */
    private function dashCase($str) {
        $str = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', '-$1', $str);
        $str = preg_replace('`(?<!-)([A-Z0-9])(?=[a-z])`', '-$1', $str);
        $str = trim($str, '-');

        return strtolower($str);
    }

    /**
     * Get the HTTP method for this action.
     *
     * @return string Returns the name of an HTTP method.
     */
    public function getHttpMethod() {
        return $this->httpMethod;
    }

    /**
     * Get the full path of the action.
     *
     * @return string Returns the path as a string.
     */
    public function getPath() {
        $r = '/'.$this->resource.
            ($this->idParam ? '/{'.$this->idParam.'}' : '').
            (empty($this->subpath) ? '' : '/'.$this->subpath);

        foreach ($this->params as $key => $param) {
            if ($param['in'] === 'path' && $key !== $this->idParam) {
                $r .= '/{'.$key.'}';
            }
        }

        return $r;
    }

    /**
     * Get the subpath of the action.
     *
     * The subpath occurs after the resource name and the ID parameter and narrows down an action even more. A general
     * resourceful endpoint would not have a subpath in which case this method will return an empty string.
     *
     * @return string Returns the subpath.
     */
    public function getSubpath() {
        return $this->subpath;
    }
}
