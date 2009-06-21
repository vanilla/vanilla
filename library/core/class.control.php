<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/**
 * A base class that all classes can inherit for error handling and other
 * common methods/properties.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Lussumo.Garden.Core
 */

class Control extends Pluggable {

   /**
    * An array of class names to instantiate and make properties of this class.
    * Typically used by controllers that need helper objects.
    * ie. $this->Uses = array('Form', 'Database');
    *
    * @var array
    */
   public $Uses;

   /**
    * The constructor for this class. Automatically fills $Control->ClassName
    * and includes/instantiates classes in the $this->Uses collection as
    * properties of this class.
    */
   public function __construct() {
      parent::__construct();
   }

   /**
    * Undocumented method.
    *
    * @todo Method GetImports() needs a description.
    */
   public function GetImports() {
      if(!is_array($this->Uses))
         return;
      
      // Load any classes in the uses array and make them properties of this class
      foreach ($this->Uses as $Class) {
         if(strlen($Class) >= 4 && substr_compare($Class, 'Gdn_', 0, 4) == 0) {
            $Property = substr($Class, 4);
         } else {
            $Property = $Class;
         }
         
         // Find the class and instantiate an instance..
         if(Gdn::FactoryExists($Property)) {
            $this->$Property = Gdn::Factory($Property);
         } if(Gdn::FactoryExists($Class)) {
            // Instantiate from the factory.
            $this->$Property = Gdn::Factory($Class);
         } elseif(class_exists($Class)) {               
            // Instantiate as an object.
            $ReflectionClass = new ReflectionClass($Class);
            // Is this class a singleton?
            if ($ReflectionClass->implementsInterface("ISingleton")) {
               eval('$this->'.$Property.' = '.$Class.'::GetInstance();');
            } else {
               $this->$Property = new $Class();
            }
         } else {
            trigger_error(ErrorMessage('The "'.$Class.'" class could not be found.', $this->ClassName, '__construct'), E_USER_ERROR);
         }
      }
   }
}