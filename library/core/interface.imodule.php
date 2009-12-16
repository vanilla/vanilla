<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * An interface for in-page modules.
 *
 *
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */


/**
 * An interface for in-page modules.
 *
 * @package Garden
 */
interface Gdn_IModule {


   /**
    * Class constructor, requires the object that is constructing the module.
    *
    * @param object $Sender The controller that is building the module.
    */
   public function __construct(&$Sender = '');


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