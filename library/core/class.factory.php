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
 * A factory used to create most objects in the core library.
 * If you have your own object that implements some base portion of the library you can install it in the factory
 * make sure your own object has the same properties/methods as the core object and then install it into this factory.
 *
 * @todo Localize the exception in this class.
 */
class Gdn_Factory {
   /** @var array The object definitions for the factory. */
   protected $_Objects = array();
   /** @var array The property dependancies for the factory. */
   protected $_Dependencies = array();
   
   /**
    * Checks whether or not a factory alias exists.
    * 
    * @param string $Alias The alias of the factory to check for.
    * @return boolean Whether or not a factory definintion exists.
    */
   public function Exists($Alias) {
      $Result = array_key_exists($Alias, $this->_Objects);
      return $Result;
   }
   
   /**
    * Creates an object with mapped to the name.
    * 
    * @param $Alias The class code of the object to create.
    * @param $Args The arguments to pass to the constructor of the object.
    */
   public function Factory($Alias, $Args = NULL) {
      //if (!$this->Exists($Alias))
      if (!array_key_exists($Alias, $this->_Objects))
         return NULL;
      
      $Def = &$this->_Objects[$Alias];
      $ClassName = $Def['ClassName'];
      
      // Make sure the class has beeen included.
      if(!class_exists($ClassName, FALSE)) {
         $Path = $Def['Path'];
         if(substr($Path, 0, 1) == '~') {
            // Replace the beginning of the path with the root of the application.
            $Path = PATH_ROOT . substr($Path, 1);
            $Def['Path'] = $Path;
         }
         require_once($Path);
      }
      
      if(!class_exists($ClassName, FALSE)) {
         throw new Exception(sprintf('Class %s not found while trying to get an object for %s. Check the path %s.', $ClassName, $Alias, $Def['Path']));
      }
      
      // Create the object differently depending on the type.
      $Result = NULL;
      $FactoryType = $Def['FactoryType'];
      $FactorySupplimentData = isset($Def[$FactoryType]) ? $Def[$FactoryType] : NULL;
      switch($FactoryType) {
         case Gdn::FactoryInstance:
            // Instantiate a new instance of the class.
            $Result = $this->_InstantiateObject($Alias, $ClassName, $Args);
            break;
         case Gdn::FactoryPrototype:
            $Prototype = $FactorySupplimentData;
            $Result = clone $Prototype;
            break;
         case Gdn::FactorySingleton:
            $SingletonDef = $FactorySupplimentData;
            if(is_array($SingletonDef)) {
               // The singleton has arguments for instantiation.
               $Singleton = NULL;
               $Args = $SingletonDef;
            } else {
               $Singleton = $SingletonDef;
            }
            
            if(is_null($Singleton)) {
               // Lazy create the singleton instance.
               $Singleton = $this->_InstantiateObject($Alias, $ClassName, $Args);
               $Def[$FactoryType] = $Singleton;
            }
            $Result = $Def[$FactoryType];
            break;
         case Gdn::FactoryRealSingleton:
            $RealSingletonDef = $FactorySupplimentData;
            
            // Not yet stored as an object... need to instantiate
            if (!is_object($RealSingletonDef)) {
               $RealSingleton = NULL;
            } else {
               $RealSingleton = $RealSingletonDef;
            }
            
            if (is_null($RealSingleton)) {
               // Lazy create the singleton instance.
               $RealSingleton = call_user_func_array(array($ClassName,$RealSingletonDef), $Args);
               $this->_SetDependancies($Alias, $RealSingleton);
               $Def[$FactoryType] = $RealSingleton;
            }
            $Result = $Def[$FactoryType];
            break;
         default:
            /** @todo Throw an exception. */
            throw new Exception();
            break;
      }
      return $Result;
   }
   
   /**
    * Install a class to the factory.
    * 
    * @param string $Alias An alias for the class that will be used to retreive instances of it.
    * @param string $ClassName The actual name of the class.
    * @param string $Path The path to the class' file. You can prefix the path with ~ to start at the application root (PATH_ROOT).
    * @param string $FactoryType The way objects will be instantiated for the class. One of (Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton).
    * <ul>
    *  <li><b>Gdn::FactoryInstance</b>: A new instance of the class will be created when the factory is called.</li>
    *  <li><b>Gdn::FactoryPrototype</b>: A clone of a prototype will be created when the factory is called.
    *   The prototype must be passed into the $Data argument.</li>
    *  <li><b>Gdn::FactorySingleton</b>: A singleton instance, stored in the factory will be returned when the factory is called.
    *   The instance can be passed to the $Data argument on installation, or it will be lazy created when first accessed.
    *   You can also pass an array to $Data and it will be used as the arguments for the lazy construction.</li>
    * </ul>
    */
   public function Install($Alias, $ClassName, $Path, $FactoryType = Gdn::FactorySingleton, $Data = NULL) {
      $FactoryType = ucfirst($FactoryType);
      if(!in_array($FactoryType, array(Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton, Gdn::FactoryRealSingleton))) {
         throw new Exception(sprintf('$FactoryType must be one of %s, %s, %s, %s.', Gdn::FactoryInstance, Gdn::FactoryPrototype, Gdn::FactorySingleton, Gdn::FactoryRealSingleton));
      }
      
      // Set the initial definition of the object.
      $Def = array('ClassName' => $ClassName, 'Path' => $Path, 'FactoryType' => $FactoryType);
      
      // Set the other data of the object.
      switch($FactoryType) {
         case Gdn::FactoryInstance:
            break;
         case Gdn::FactoryPrototype:
            if(is_null($Data)) {
               throw new Exception('You must supply a prototype object when installing an object of type Prototype.');
            }
         case Gdn::FactorySingleton:
         case Gdn::FactoryRealSingleton:
            $Def[$FactoryType] = $Data;
            break;
         default:
            throw Exception();
      }
      
      $this->_Objects[$Alias] = $Def;
   }
   
   /**
    * Install a dependency for the factory.
    * 
    * This method provides support for simple dependency injection.
    * When an object with dependencies is created then the factory will call inline{@link Gdn_Factory::Factory()}
    * for each dependency and set the object properties before returning it.
    * Those dependencies can also have their own dependencies which will all be set when the object is returned.
    * 
    * @param string $Alias The alias of the class that will have the dependency.
    * @param string $PropertyName The name of the property on the class that will have the dependency.
    * @param string $SourceAlias The alias of the class that will provide the value of the property when objects are instantiated.
    *
    */
   public function InstallDependency($Alias, $PropertyName, $SourceAlias) {
      if(!array_key_exists($Alias, $this->_Dependencies)) {
         $this->_Dependencies[$Alias] = array($PropertyName => $SourceAlias);
      } else {
         $this->_Dependencies[$Alias][$PropertyName] = $SourceAlias;
      }
   }
   
   /** 
    * Instantiate a new object.
    *
    * @param string $ClassName The name of the class to instantiate.
    * @param array $Args The arguments to pass to the constructor.
    * Note: This function currently only supports a maximum of 8 arguments.
    */
   protected function _InstantiateObject($Alias, $ClassName, $Args = NULL) {
      if(is_null($Args)) $Args = array();
      $Result = NULL;

      // Instantiate the object with the correct arguments.
      // This odd looking case statement is purely for speed optimization.
      switch(count($Args)) {
         case 0:
            $Result = new $ClassName; break;
         case 1:
            $Result = new $ClassName($Args[0]); break;
         case 2:
            $Result = new $ClassName($Args[0], $Args[1]); break;
         case 3:
            $Result = new $ClassName($Args[0], $Args[1], $Args[2]); break;
         case 4:
            $Result = new $ClassName($Args[0], $Args[1], $Args[2], $Args[3]); break;
         case 5:
            $Result = new $ClassName($Args[0], $Args[1], $Args[2], $Args[3], $Args[4]); break;
         case 6:
            $Result = new $ClassName($Args[0], $Args[1], $Args[2], $Args[3], $Args[4], $Args[5]); break;
         case 7:
            $Result = new $ClassName($Args[0], $Args[1], $Args[2], $Args[3], $Args[4], $Args[5], $Args[6]); break;
         case 8:
            $Result = new $ClassName($Args[0], $Args[1], $Args[2], $Args[3], $Args[4], $Args[5], $Args[6], $Args[7]); break;
         default:
            throw new Exception();
      }

      $this->_SetDependancies($Alias, $Result);
      return $Result;
   }
   
   private function _SetDependancies($Alias, $Object) {
      // Set any dependancies for the object.
      if(array_key_exists($Alias, $this->_Dependencies)) {
         $Dependencies = $this->_Dependencies[$Alias];
         foreach($Dependencies as $PropertyName => $SourceAlias) {
            $PropertyValue = $this->Factory($SourceAlias);
            $Object->$PropertyName = $PropertyValue;
         }
      }
   }
   
   /** 
    * Uninstall a factory definition.
    *
    * @param string $Alias The object alias to uninstall.
    */
   public function Uninstall($Alias) {
      if(array_key_exists($this->_Objects, $Alias))
         unset($this->_Objects[$Alias]);
   }
   
   /** 
    * Uninstall a dependency definition.
    * 
    * @param string $Alias The object alias to uninstall the dependency for.
    * @param string $PropertyName The name of the property dependency to uninstall.
    * Note: If $PropertyName is null then all of the dependencies will be uninstalled for $Alias.
    */
   public function UninstallDependency($Alias, $PropertyName = NULL) {
      if(array_key_exists($this->_Dependencies, $Alias)) {
         if(is_null($PropertyName))
            unset($this->_Dependencies[$Alias]);
         elseif(array_key_exists($PropertyName, $this->_Dependencies[$Alias]))
               unset($this->_Dependencies[$Alias][$PropertyName]);
      }
   }
}
