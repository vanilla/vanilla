<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Garden\Schema;
use Garden\Validation;

class EndpointSchema extends Schema {
    /**
     * @var object
     */
    private $controller;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $type;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var bool
     */
    private $fired = false;

    public function __construct(array $schema = [], $controller, $method, $type = 'in', EventManager $eventManager = null) {
        parent::__construct($schema);
        $this
            ->setController($controller)
            ->setMethod($method)
            ->setType($type);

        $this->eventManager = $eventManager;
    }

    /**
     * Generate an event named based on this object.
     *
     * @param string $suffix
     * @return string
     */
    private function eventName($suffix = '') {
        $basename = EventManager::classBasename($this->getController());
        $result = "{$basename}_{$this->method}_{$this->type}Schema{$suffix}";

        return $result;
    }

    protected function isValidInternal(array &$data, array $schema, Validation &$validation = null, $path = '') {
        // Fire an event that allows the schema to be modified.
        if (!$this->fired && $this->eventManager) {
            $this->eventManager->fire(
                $this->eventName(),
                $this
            );
        }

        return parent::isValidInternal($data, $schema, $validation, $path);
    }

    /**
     * Get the controller.
     *
     * @return object Returns the controller.
     */
    public function getController() {
        return $this->controller;
    }

    /**
     * Set the controller.
     *
     * @param object $controller
     * @return $this
     */
    public function setController($controller) {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Get the method.
     *
     * @return string Returns the method.
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Set the method.
     *
     * @param string $method
     * @return $this
     */
    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    /**
     * Get the type.
     *
     * @return string Returns the type.
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set the type.
     *
     * @param string $type
     * @return $this
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }
}
