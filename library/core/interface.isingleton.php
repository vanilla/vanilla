<?php
/**
 * Singleton interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * A simple interface that all singletons must follow.
 */
interface ISingleton {
    /**
     * Return the internal pointer to the in-memory singleton of the class.
     *
     * Instantiates the class if it has not yet been created.
     *
     * @return object
     */
    public static function getInstance();
}
