<?php if (!defined('APPLICATION')) exit();

/**
 * Singleton interface
 * 
 * A simple interface that all singletons must follow.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

if (!defined('APPLICATION'))
   exit();


/**
 * A simple interface that all singletons must follow.
 *
 * @package Garden
 */
interface ISingleton {
   /**
    * Returns the internal pointer to the in-memory singleton of the class.
    * Instantiates the class if it has not yet been created.
    *
    * @return object
    */
   public static function GetInstance();
}