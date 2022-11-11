<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web;

/**
 * Event fired when a controller is dispatched.
 */
final class ControllerDispatchedEvent
{
    /** @var callable */
    private $controllerCallable;

    /**
     * DI.
     *
     * @param callable $controllerCallable
     */
    public function __construct(callable $controllerCallable)
    {
        $this->controllerCallable = $controllerCallable;
    }

    /**
     * Get the class that was dispatched.
     *
     * @return string
     */
    public function getDispatchedClass(): string
    {
        $firstPart = $this->controllerCallable[0];
        if (!is_string($firstPart)) {
            return get_class($firstPart);
        } else {
            return $firstPart;
        }
    }

    /**
     * Get the method that was dispatched.
     *
     * @return string
     */
    public function getDispatchedMethod(): string
    {
        return $this->controllerCallable[1];
    }
}
