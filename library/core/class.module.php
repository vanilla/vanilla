<?php if (!defined('APPLICATION')) exit();

/**
 * Module base class
 *
 * Provides basic functionality when extended by real modules.
 * 
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
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
    * Data that is passed into the view.
    * 
    * @var array
    */
   public $Data = array();


   /**
    * The object that constructed this object. Typically this should be a
    * Controller object.
    *
    * @var Gdn_Controller
    */
   protected $_Sender;


   /**
    * The name of the theme folder that the application is currently using.
    *
    * @var string
    */
   protected $_ThemeFolder;
   
   public $Visible = TRUE;


   /**
    * Class constructor
    *
    * @param object $Sender
    */
   public function __construct($Sender = '', $ApplicationFolder = FALSE) {
      if (!$Sender)
         $Sender = Gdn::Controller();
      
      if (is_object($Sender)) {
         $this->_ApplicationFolder = $Sender->ApplicationFolder;
         $this->_ThemeFolder = $Sender->Theme;
      } else {
         $this->_ApplicationFolder = 'dashboard';
         $this->_ThemeFolder = Gdn::Config('Garden.Theme');
      }
      if ($ApplicationFolder !== FALSE)
         $this->_ApplicationFolder = $ApplicationFolder;
      
      if (is_object($Sender))
         $this->_Sender = $Sender;
         
      parent::__construct();
   }


   /**
    * Returns the name of the asset where this component should be rendered.
    */
   public function AssetTarget() {
      trigger_error(ErrorMessage("Any class extended from the Module class must implement it's own AssetTarget method.", get_class($this), 'AssetTarget'), E_USER_ERROR);
   }
   
   public function Data($Name = NULL, $Default = '') {
      if ($Name == NULL)
         $Result = $this->Data;
      else
         $Result = GetValueR($Name, $this->Data, $Default);
      return $Result;
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
         $ApplicationFolder = strpos($this->_ApplicationFolder, '/') ? $this->_ApplicationFolder : strtolower($this->_ApplicationFolder);

      $ThemeFolder = $this->_ThemeFolder;
      
      $ViewPath = NULL;
      
      // Try to use Gdn_Controller's FetchViewLocation
      if (Gdn::Controller() instanceof Gdn_Controller) {
         try {
            $ViewPath = Gdn::Controller()->FetchViewLocation($View, 'modules', $ApplicationFolder);
         } catch (Exception $Ex) {}
      }
      
      if (!$ViewPath) {
         
         $ViewPaths = array();
         // 1. An explicitly defined path to a view
         if (strpos($View, '/') !== FALSE)
            $ViewPaths[] = $View;

         // 2. A theme
         if ($ThemeFolder != '') {
            // a. Application-specific theme view. eg. /path/to/application/themes/theme_name/app_name/views/modules/
            $ViewPaths[] = CombinePaths(array(PATH_THEMES, $ThemeFolder, $ApplicationFolder, 'views', 'modules', $View . '.php'));
            
            // b. Garden-wide theme view. eg. /path/to/application/themes/theme_name/views/modules/
            $ViewPaths[] = CombinePaths(array(PATH_THEMES, $ThemeFolder, 'views', 'modules', $View . '.php'));
         }

         // 3. Application default. eg. /path/to/application/app_name/views/controller_name/
         if ($this->_ApplicationFolder)
            $ViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, $ApplicationFolder, 'views', 'modules', $View . '.php'));
         else
            $ViewPaths[] = dirname($this->Path())."/../views/modules/$View.php";

         // 4. Garden default. eg. /path/to/application/dashboard/views/modules/
         $ViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, 'dashboard', 'views', 'modules', $View . '.php'));

         $ViewPath = Gdn_FileSystem::Exists($ViewPaths);
      }
      
      if ($ViewPath === FALSE)
         throw new Exception(ErrorMessage('Could not find a `' . $View . '` view for the `' . $this->Name() . '` module in the `' . $ApplicationFolder . '` application.', get_class($this), 'FetchView'), E_USER_ERROR);

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

   public function Path($NewValue = FALSE) {
      static $Path = FALSE;
      if ($NewValue !== FALSE)
         $Path = $NewValue;
      elseif ($Path === FALSE) {
         $RO = new ReflectionObject($this);
         $Path = $RO->getFileName();
      }
      return $Path;
   }
   
   public function Render() {
      echo $this->ToString();
   }
   
   public function SetData($Name, $Value) {
      $this->Data[$Name] = $Value;
   }

   /**
    * Returns the component as a string to be rendered to the screen. Unless
    * this method is overridden, it will attempt to find and return a view
    * related to this module automatically.
    *
    * @return string
    */
   public function ToString() {
      if ($this->Visible)
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
