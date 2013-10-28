<?php if (!defined('APPLICATION')) exit();

/**
 * A module for a list of links.
 * 
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.3
 */

/**
 * 
 */
class SiteNavModule extends NavModule {
   /// Properties ///
   
   
   /// Methods ///
   
   public function render() {
      // The module contains different links depending on its section.
      if (InSection('Profile')) {
         $this->FireEvent('Profile');
      } else {
         $this->FireEvent('Site');
      }
      
      parent::render();
   }
}