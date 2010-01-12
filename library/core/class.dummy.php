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
 * A dummy class that returns itself on all method and property calls.
 * This class is useful for partial deliveries where parts of the page are not necessary,
 * but you don't want to have to check for them on every use.
 *
 * @author Todd Burry
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

class Gdn_Dummy {
   public function __call ($Name, $Arguments ) {
      return $this;
   }
   
   public function __get($Name) {
      return $this;
   }
   
   public function __set($Name, $Value) {
      return $this;
   }
   
   /**
    * Holds a static instance of this class.
    *
    * @var Dummy
    */
   private static $_Instance;
   
   /**
    * Return the singleton instance of this object.
    *
    * @static
    * @return Dummy The singleton instance of this class.
    */
   public static function GetInstance() {
      if (!isset(self::$_Instance)) {
         self::$_Instance = new Gdn_Dummy();
      }
      return self::$_Instance;
   }
}