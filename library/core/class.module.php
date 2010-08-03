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
 * Base module object
 *
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */


/**
 * Base module object
 * @package Garden
 */
class Gdn_Module extends Gdn_Pluggable implements Gdn_IModule {

   /** The name of the current asset that is being rendered.
    *
    * @var string
    */
   public $AssetName = '';


   /**
    * The name of the application folder that this module resides within.
    *
    * @var string
    */
   protected $_ApplicationFolder;


   /**
    * The object that constructed this object. Typically this should be a
    * Controller object.
    *
    * @var object
    */
   protected $_Sender;


   /**
    * The name of the theme folder that the application is currently using.
    *
    * @var string
    */
   protected $_ThemeFolder;


   /**
    * Class constructor
    *
    * @param object $Sender
    */
   public function __construct(&$Sender = '') {
      if (is_object($Sender)) {
         $this->_ApplicationFolder = $Sender->Application;
         $this->_ThemeFolder = $Sender->Theme;
      } else {
         $this->_ApplicationFolder = 'dashboard';
         $this->_ThemeFolder = Gdn::Config('Garden.Theme');
      }
      if (is_object($Sender))
         $this->_Sender = &$Sender;
         
      parent::__construct();
   }


   /**
    * Returns the name of the asset where this component should be rendered.
    */
   public function AssetTarget() {
      trigger_error(ErrorMessage("Any class extended from the Module class must implement it's own AssetTarget method.", get_class($this), 'AssetTarget'), E_USER_ERROR);
   }


   /**
    * Returns the xhtml for this module as a fully parsed and rendered string.
    *
    * @return string
    */
   public function FetchView() {
      $ViewPath = $this->FetchViewLocation();
      $String = '';
      ob_start();
      if(is_object($this->_Sender) && isset($this->_Sender->Data)) {
         $Data = $this->_Sender->Data;
      } else {
         $Data = array();
      }
      include ($ViewPath);
      $String = ob_get_contents();
      @ob_end_clean();
      return $String;
   }


   /**
    * Returns the location of the view for this module in the filesystem.
    *
    * @param string $View
    * @param string $ApplicationFolder
    * @return array
    */
   public function FetchViewLocation($View = '', $ApplicationFolder = '') {
      if ($View == '')
         $View = strtolower($this->Name());

      if (substr($View, -6) == 'module')
         $View = substr($View, 0, -6);
         
      if (substr($View, 0, 4) == 'gdn_')
         $View = substr($View, 4);

      if ($ApplicationFolder == '')
         $ApplicationFolder = strtolower($this->_ApplicationFolder);

      $ThemeFolder = strtolower($this->_ThemeFolder);

      // Views come from one of four places:
      $ViewPaths = array();
      // 1. An explicitly defined path to a view
      if (strpos($View, DS) !== FALSE)
         $ViewPaths[] = $View;

      if ($ThemeFolder != '') {
         // 1. Application-specific theme view. eg. /path/to/application/themes/theme_name/app_name/views/modules/
         $ViewPaths[] = CombinePaths(array(PATH_THEMES, $ThemeFolder, $ApplicationFolder, 'views', 'modules', $View . '.php'));
         // 2. Garden-wide theme view. eg. /path/to/application/themes/theme_name/views/modules/
         $ViewPaths[] = CombinePaths(array(PATH_THEMES, $ThemeFolder, 'views', 'modules', $View . '.php'));
      }
      // 3. Application default. eg. /path/to/application/app_name/views/controller_name/
      $ViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, $ApplicationFolder, 'views', 'modules', $View . '.php'));
      // 4. Garden default. eg. /path/to/application/dashboard/views/modules/
      $ViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, 'dashboard', 'views', 'modules', $View . '.php'));

      $ViewPath = Gdn_FileSystem::Exists($ViewPaths);
      if ($ViewPath === FALSE)
         trigger_error(ErrorMessage('Could not find a `' . $View . '` view for the `' . $this->Name() . '` module in the `' . $ApplicationFolder . '` application.', get_class($this), 'FetchView'), E_USER_ERROR);

      return $ViewPath;
   }


   /**
    * Returns the name of this module. Unless it is overridden, it will simply
    * return the class name.
    *
    * @return string
    */
   public function Name() {
      return get_class($this);
   }
   
   public function Render() {
      echo $this->ToString();
   }

   /**
    * Returns the component as a string to be rendered to the screen. Unless
    * this method is overridden, it will attempt to find and return a view
    * related to this module automatically.
    *
    * @return string
    */
   public function ToString() {
      return $this->FetchView();
   }

   /**
    * Magic method for type casting to string.
    *
    * @todo check if you want to keep this.
    * @return string
    */
   public function __toString() {
      return $this->ToString();
   }
}
