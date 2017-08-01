<?php
/**
 * Class AbstractDecorator
 *
 * Allow to decorate an object and forward everything that is not defined on the decorator to the base object.
 * Note: Using this class will hide the object's real type.
 */
abstract class AbstractDecorator {

    /** @var mixed decorated object */
	protected $object;

    /**
     * AbstractDecorator constructor.
     *
     * @param mixed $object
     */
	public function __construct($object) {
	    $this->object = $object;
    }

	/**
	 * Gets the original object that all the decorators have wrapped themselves around.
	 * @return Object
	 */
	public function getOriginalObject() {
		$object = $this->object;

		while(is_a($object, get_class())){
			$object = $object->getOriginalObject();
		}

		return $object;

	}

    /**
     * Magic __call will recursively call itself and cascade through all the methods on the decorators.
     * This will work for the child object's methods, and even when methods are missing in between the decorator stack.
     *
     * @throws Exception
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args) {
        if (!($this->object instanceof AbstractDecorator)) {
            if (!is_callable([$this->object, $method]) ){
                throw new Exception('Undefined method - '.get_class($this->object).'::'.$method);
            }
        }
        return call_user_func_array([$this->object, $method], $args);
    }

    /**
	 * Magic __get will return the properties from the original object.
	 * This won't be executed if the current instance has the property defined.
	 *
     * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		$object = $this->getOriginalObject();
		if (property_exists($object, $property)) {
			return $object->$property;
		}
		return null;
	}

    /**
	 * Magic __set will set a property on the original object.
	 * This won't be executed if the current instance has the property defined.
	 *
     * @param string $property
	 * @param mixed $value
	 * @return object $this
	 */
	public function __set($property, $value){
		$object = $this->getOriginalObject();
		$object->$property = $value;
		return $this;
	}
}
