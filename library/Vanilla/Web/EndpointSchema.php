<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Schema\Validation;

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
    private $schemaType;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var bool
     */
    private $fired = false;

    public function __construct(array $schema = [], $controller, $method, $schemaType = 'in', EventManager $eventManager = null) {
        parent::__construct($schema);
        $this
            ->setController($controller)
            ->setMethod($method)
            ->setSchemaType($schemaType);

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
        $result = "{$basename}_{$this->method}_{$this->schemaType}Schema{$suffix}";

        return $result;
    }

    /**
     * Validate data against the schema.
     *
     * @param mixed $data The data to validate.
     * @param bool $sparse Whether or not this is a sparse validation.
     * @return mixed Returns a cleaned version of the data.
     * @throws \Garden\Schema\ValidationException when the data does not validate against the schema.
     */
    public function validate($data, $sparse = false) {
        // Fire an event that allows the schema to be modified.
        if (!$this->fired && $this->eventManager) {
            $this->eventManager->fire(
                $this->eventName(),
                $this
            );
        }

        return parent::validate($data, $sparse);
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
    public function getSchemaType() {
        return $this->schemaType;
    }

    /**
     * Set the type.
     *
     * @param string $schemaType
     * @return $this
     */
    public function setSchemaType($schemaType) {
        $this->schemaType = $schemaType;
        return $this;
    }
}
