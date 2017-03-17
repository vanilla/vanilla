<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Garden\Schema;
use Gdn_Session as SessionInterface;
use Vanilla\InjectableInterface;

/**
 * The controller base class.
 */
abstract class Controller implements InjectableInterface {
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var
     */
    private $eventManager;

    /**
     * Set the base dependencies of the controller.
     *
     * This method allows sub-classers to declare their dependencies in their constructor without worrying about these
     * dependencies.
     *
     * @param SessionInterface|null $session The session of the current user.
     * @param EventManager|null $eventManager The event manager dependency.
     */
    public function setDependencies(SessionInterface $session = null, EventManager $eventManager = null) {
        $this->session = $session;
        $this->eventManager = $eventManager;
    }

    /**
     * Enforce the following permission(s) or throw an exception that the dispatcher can handle.
     *
     * When passing several permissions to check the user can have any of the permissions. If you want to force several
     * permissions then make several calls to this method.
     *
     * @param string|array $permission The permissions you are requiring.
     * @param int|null $id The ID of the record we are checking the permission of.
     */
    public function permission($permission, $id = null) {
        if (!$this->session instanceof SessionInterface) {
            throw new \Exception("Session not available.", 500);
        }

        // TODO: See if there is a particular ban so we can throw a particular error.

        if (!$this->session->getPermissions()->hasAny((array)$permission, $id)) {
            // TODO: This should be a different exception.
            throw new \Exception("Access denied.", 401);
        }
    }

    /**
     * Create a schema attached to this controller.
     *
     * @param array $schema The schema definition.
     * @param string $methodName The name of the method to attach this schema to. Usually you just call this with the **__FUNCTION__** constant.
     * @param string $type The type of schema, either "in" or "out".
     * @return EndpointSchema Returns a new schema attached to this controller.
     */
    public function schema(array $schema, $methodName, $type = 'in') {
        return EndpointSchema::parse($schema, $this, $methodName, $type, $this->eventManager);
    }

    /**
     * Determine whether a method on the controller is protected, but should not be dispatched to.
     *
     * The resource router will not route to getters/setters by default so this only returns other protected methods.
     *
     * @param string $method The name of the method to test.
     * @return bool Returns **true** if the method is protected or **false** otherwise.
     */
    public function isProtected($method) {
        return in_array(strtolower($method), ['permission', 'schema']);
    }

    /**
     * Get the session.
     *
     * @return SessionInterface Returns the session.
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * Set the session.
     *
     * @param SessionInterface $session The new session.
     * @return $this
     */
    public function setSession($session) {
        $this->session = $session;
        return $this;
    }

    /**
     * Get the event manager.
     *
     * @return mixed Returns the event manager.
     */
    public function getEventManager() {
        return $this->eventManager;
    }

    /**
     * Set the event manager.
     *
     * @param mixed $eventManager The new event manager.
     * @return $this
     */
    public function setEventManager($eventManager) {
        $this->eventManager = $eventManager;
        return $this;
    }
}
