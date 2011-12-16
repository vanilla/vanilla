<?php if (!defined('APPLICATION')) exit();

/**
 * Plugin interface
 * 
 * A simple interface that all plugins must follow. Aside from the Setup
 * method, this is used more to identify plugins than to enforce structure upon
 * them.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */
interface Gdn_IPlugin {

   /**
    * This method can be used to ensure that any setup components of a plugin have been set up before the
    * plugin is "enabled".
    *
    * This method will be called every time a plugin is enabled,
    * so it should check before performing redundant operations like
    * inserting tables or data into the database. If a plugin has no setup to
    * perform, simply declare this method and return TRUE.
    *
    * Returns a boolean value indicating success.
    *
    * @return boolean
    */
   public function Setup();
   
   /**
    * These methods are invoked if present, but are not required and will be silently ignored
    * if they do not exist.
    */
   
   // public function OnLoad()    - Called as the plugin is instantiated (each page load)
   // public function OnDisable() - Called as the plugin is disabled
   // public function CleanUp()   - Called as the plugin is removed
}
