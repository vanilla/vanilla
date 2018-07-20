<?php
/**
 * Pluggable
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

namespace Vanilla;
use Gdn;
use ArgumentCountError;
use Exception;
use Logger;

/**
 * Event Framework: Pluggable
 *
 * The Pluggable class is extended by other classes to enable the plugins
 * and the custom event model in plugins. Any class that extends this class
 * has the ability to throw custom events at any time, which can then be
 * handled by plugins.
 *
 * @abstract
 */
abstract class Pluggable {

    /**
     * @var string The name of the class that has been instantiated. Typically this will be
     * a class that has extended this class.
     */
    protected $ClassName;

    /**
     * @var array Any arguments that should be passed into a plugin's event handler.
     * Typically these are variables that were defined before the event fired
     * and were local to the method being executed. It is an associative array
     * of Argument_Name => Argument_Value pairs. The EventArguments can be
     * accessed (and changed) in the plugin since the plugin has full
     * access to the object throwing the event (see fireEvent() below).
     */
    public $EventArguments;

    /**
     * @var array When any events are handled by plugins, the return values from those
     * method are saved in $this->Returns array as $EventHandlerName =>
     * array($PluginName => $ReturnValue) pairs.
     * Note: Method overrides and direct method call return values are NOT saved
     * in this array; they are returned as normal.
     *  Example: To get the return value a plugin called "TestPlugin" that
     *  attaches to an "ExampleController->render()" method using the magic
     *  "Before" event, you would reference it like so:
     *  $ReturnVal = $Sender->Returns['ExampleController_BeforeRender_Handler']['TestPlugin'];
     */
    public $Returns = [];


    /**
     * @var string An enumerator indicating what type of handler the method being called is.
     * Options are:
     *  HANDLER_TYPE_NORMAL: Standard call to a method on the object (DEFAULT).
     *  HANDLER_TYPE_OVERRIDE: Call to a method override.
     *  HANDLER_TYPE_NEW: Call to a new object method.
     */
    public $HandlerType;

    /**
     * @var string In some cases it may be desirable to fire an event from a different class
     * than is currently available via $this. If this variable is set, it should
     * contain the name of the class that the next event will fire off.
     */
    public $FireAs;

    /**
     * The public constructor of the class. If any extender of this class
     * overrides this one, parent::__construct(); should be called to ensure
     * interoperability.
     */
    public function __construct() {
        $this->ClassName = get_class($this);
        $this->EventArguments = [];
        $this->Returns = [];
        $this->HandlerType = HANDLER_TYPE_NORMAL;
    }

    /**
     * Get the return values from a an event listener.
     *
     * @param string $pluginName The plugin the handled the event.
     * @param string $handlerName The name of the of the event handler.
     *
     * @return mixed
     */
    public function getReturn($pluginName, $handlerName) {
        return $this->Returns[strtolower($handlerName)][strtolower($pluginName)];
    }

    /**
     * Fire the next event off a custom parent class
     *
     * @param mixed $options Either the parent class, or an option array
     * s
     * @return $this For fluent method chaining.
     */
    public function fireAs($options) {
        if (!is_array($options)) {
            $options = ['FireClass' => $options];
        }

        if (array_key_exists('FireClass', $options)) {
            $this->FireAs = val('FireClass', $options);
        }

        return $this;
    }

    /**
     * Fires an event to be dealt with by plugins. Plugins can create
     * custom methods to handle the event simply by naming their handler method
     * appropriately. The convention is:
     *  public function senderClassName_EventName_Handler($Sender) {}
     *
     * @param string $eventName The name of the event being fired.
     * @param array $arguments An array of arguments for the event.
     *
     * @throws Exception when Pluggable::__construct has not been called.
     * @throws ArgumentCountError When the incorrect number or arguments was passed to the event handler.
     *
     * @return bool Returns **true** if an event was executed.
     */
    public function fireEvent($eventName, $arguments = null) {
        if (!$this->ClassName) {
            $realClassName = get_class($this);
            throw new Exception("Event fired from pluggable class '{$realClassName}', but Pluggable::__construct() was never called.");
        }

        $fireClass = !is_null($this->FireAs) ? $this->FireAs : $this->ClassName;
        $this->FireAs = null;

        // Apply inline arguments to EventArguments
        if (is_array($arguments)) {
            $this->EventArguments = array_merge($this->EventArguments, $arguments);
        }

        // Look to the PluginManager to see if there are related event handlers and call them
        try {
            return Gdn::pluginManager()->callEventHandlers($this, $fireClass, $eventName);
        } catch (ArgumentCountError $ex) {
            if (debug()) {
                throw $ex;
            }
            Logger::event('arg_error', Logger::CRITICAL, $ex->getMessage());
            return false;
        }
    }

    /**
     * Used to extend any method.
     *
     * There are two types of extended method calls:
     *  1. Declared: The method was declared with the lowercase "x" prefix and called without it.
     *     ie. Declaration: public function xMethodName() {}
     *         Call: $Object->methodName();
     *
     *  2. Called: The method was declared without the lowercase "x" prefix and called with it.
     *     ie. Declaration: public function methodName() {}
     *         Call: $Object->xMethodName();
     *
     * Note: Plugins will always refer to the method name without the "x"
     * regardless of the type. So, $referenceMethodName is declared below without
     * the "x".
     *
     *
     * @param string $methodName
     * @param array $arguments
     * @return mixed
     *
     * @throws Exception when Pluggable::__construct has not been called.
     * @throws ArgumentCountError When the incorrect number or arguments was passed to the event handler.
     *
     */
    public function __call($methodName, $arguments) {
        // Define a return variable.
        $return = false;

        // We removed the SliceProvider class, which Pluggable previously extended.
        // If any of these methods are called, send out an error.
        $sliceProviderMethods = ['enableSlicing', 'slice', 'addSliceAsset', 'renderSliceConfig'];

        if (in_array($methodName, $sliceProviderMethods)) {
            $message = 'Slicing has been removed from '.self::class;
            $message .= ' Try using the functionality provided by "js-form" instead.';
            throw new Exception($message);
        }


        // Was this method declared, or called?
        if (substr($methodName, 0, 1) == 'x') {
            // Declared
            $actualMethodName = substr($methodName, 1); // Remove the x prefix
            $referenceMethodName = $actualMethodName; // No x prefix
        } else {
            // Called
            $actualMethodName = 'x'.$methodName; // Add the x prefix
            $referenceMethodName = $methodName; // No x prefix
        }

        $className = \Garden\EventManager::classBasename($this->ClassName ?: get_called_class());

        // Make sure that $ActualMethodName exists before continuing:
        if (!method_exists($this, $actualMethodName)) {
            // Make sure that a plugin is not handling the call
            if (!Gdn::pluginManager()->hasNewMethod($className, $referenceMethodName)) {
                throw new \BadMethodCallException(
                    "The \"$className\" object does not have a \"$actualMethodName\" method.",
                    501
                );
            }
        }

        // Make sure the arguments get passed in the same way whether firing a custom event or a magic one.
        $this->EventArguments = $arguments;

        // Call the "Before" event handlers.
        try {
            Gdn::pluginManager()->callEventHandlers($this, $className, $referenceMethodName, 'Before');
        } catch (ArgumentCountError $ex) {
            if (debug()) {
                throw $ex;
            }
            Logger::event('arg_error', Logger::CRITICAL, $ex->getMessage());
        }

        // Call this object's method
        if (Gdn::pluginManager()->hasMethodOverride($className, $referenceMethodName)) {
            // The method has been overridden
            $this->HandlerType = HANDLER_TYPE_OVERRIDE;
            try {
                $return = Gdn::pluginManager()->callMethodOverride($this, $this->ClassName, $referenceMethodName);
            } catch (ArgumentCountError $ex) {
                if (debug()) {
                    throw $ex;
                }
                Logger::event('arg_error', Logger::CRITICAL, $ex->getMessage());
            }
        } elseif (Gdn::pluginManager()->hasNewMethod($className, $referenceMethodName)) {
            $this->HandlerType = HANDLER_TYPE_NEW;
            try {
                $return = Gdn::pluginManager()->callNewMethod($this, $className, $referenceMethodName);
            } catch (ArgumentCountError $ex) {
                if (debug()) {
                    throw $ex;
                }
                Logger::event('arg_error', Logger::CRITICAL, $ex->getMessage());
            }
        } else {
            // The method has not been overridden.
            $return = call_user_func_array([$this, $actualMethodName], $arguments);
        }

        // Call the "After" event handlers.
        try {
            Gdn::pluginManager()->callEventHandlers($this, $className, $referenceMethodName, 'After');
        } catch (ArgumentCountError $ex) {
            if (debug()) {
                throw $ex;
            }
            Logger::event('arg_error', Logger::CRITICAL, $ex->getMessage());
        }

        return $return;
    }
}
