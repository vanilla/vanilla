<?php if (!defined('APPLICATION')) exit();

/**
 * Module interface
 * 
 * An interface for in-page modules.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

interface Gdn_IModule {
//   public function __construct($Sender);

   /**
    * Returns the name of the asset where this component should be rendered.
    */
   public function AssetTarget();


   /**
    * Returns the xhtml for this module as a fully parsed and rendered string.
    */
   public function FetchView();


   /**
    * Returns the location of the view for this module in the filesystem.
    *
    * @param unknown_type $View
    * @param unknown_type $ApplicationFolder
    * @todo update doc with arguments type
    */
   public function FetchViewLocation($View = '', $ApplicationFolder = '');


   /**
    * Returns the name of the module.
    */
   public function Name();

   /**
    * Renders the module.
    */
   public function Render();
}