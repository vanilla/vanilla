<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

/**
 * This is a placeholder interface that allows base classes to declare dependencies in a a method other than the constructor
 * so that they won't pollute the constructor for subclasses. This
 *
 * This interface doesn't specify a specific method because each implementor will need to declare different parameters. It
 * is recommended a method name of **setDependencies()** is used.
 *
 * This is somewhat of an anti-pattern, but considering the trade off of classes that are frequently sub-classed such as
 * controllers and models this is a worthwhile interface. This also allows base classes to add additional dependencies
 * after the fact without breaking child constructors. It is recommended that classes that use this interface
 */
interface InjectableInterface {
    // public function setDependencies(...)
}
