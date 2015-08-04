<?php
/**
 * Gdn_Module.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Module base class
 *
 * Provides basic functionality when extended by real modules.
 */
class Gdn_Module extends Gdn_Pluggable implements Gdn_IModule {

    /** @var string The name of the current asset that is being rendered. */
    public $AssetName = '';

    /** @var string The name of the application folder that this module resides within. */
    protected $_ApplicationFolder;

    /** @var array Data that is passed into the view. */
    public $Data = array();

    /** @var Gdn_Controller The object that constructed this object. Typically this should be a Controller object. */
    protected $_Sender;

    /** @var string The name of the theme folder that the application is currently using. */
    protected $_ThemeFolder;

    /** @var bool  */
    public $Visible = true;

    /**
     * Class constructor
     *
     * @param object $Sender
     */
    public function __construct($Sender = '', $ApplicationFolder = false) {
        if (!$Sender) {
            $Sender = Gdn::controller();
        }

        if (is_object($Sender)) {
            $this->_ApplicationFolder = $Sender->ApplicationFolder;
            $this->_ThemeFolder = $Sender->Theme;
        } else {
            $this->_ApplicationFolder = 'dashboard';
            $this->_ThemeFolder = Gdn::config('Garden.Theme');
        }
        if ($ApplicationFolder !== false) {
            $this->_ApplicationFolder = $ApplicationFolder;
        }

        if (is_object($Sender)) {
            $this->_Sender = $Sender;
        }

        parent::__construct();
    }

    /**
     * Returns the name of the asset where this component should be rendered.
     */
    public function assetTarget() {
        trigger_error(ErrorMessage("Any class extended from the Module class must implement it's own AssetTarget method.", get_class($this), 'AssetTarget'), E_USER_ERROR);
    }

    /**
     *
     *
     * @param null $Name
     * @param string $Default
     * @return array|mixed
     */
    public function data($Name = null, $Default = '') {
        if ($Name == null) {
            $Result = $this->Data;
        } else {
            $Result = GetValueR($Name, $this->Data, $Default);
        }
        return $Result;
    }

    /**
     * Returns the xhtml for this module as a fully parsed and rendered string.
     *
     * @return string
     */
    public function fetchView($View = '') {
        $ViewPath = $this->fetchViewLocation($View);
        $String = '';
        ob_start();
        if (is_object($this->_Sender) && isset($this->_Sender->Data)) {
            $Data = $this->_Sender->Data;
        } else {
            $Data = array();
        }
        include($ViewPath);
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
    public function fetchViewLocation($View = '', $ApplicationFolder = '') {
        if ($View == '') {
            $View = strtolower($this->name());
        }

        if (substr($View, -6) == 'module') {
            $View = substr($View, 0, -6);
        }

        if (substr($View, 0, 4) == 'gdn_') {
            $View = substr($View, 4);
        }

        if ($ApplicationFolder == '') {
            $ApplicationFolder = strpos($this->_ApplicationFolder, '/') ? $this->_ApplicationFolder : strtolower($this->_ApplicationFolder);
        }

        $ThemeFolder = $this->_ThemeFolder;

        $ViewPath = null;

        // Try to use Gdn_Controller's FetchViewLocation
        if (Gdn::controller() instanceof Gdn_Controller) {
            try {
                $ViewPath = Gdn::controller()->fetchViewLocation($View, 'modules', $ApplicationFolder);
            } catch (Exception $Ex) {
            }
        }

        if (!$ViewPath) {
            $ViewPaths = array();
            // 1. An explicitly defined path to a view
            if (strpos($View, '/') !== false) {
                $ViewPaths[] = $View;
            }

            // 2. A theme
            if ($ThemeFolder != '') {
                // a. Application-specific theme view. eg. /path/to/application/themes/theme_name/app_name/views/modules/
                $ViewPaths[] = CombinePaths(array(PATH_THEMES, $ThemeFolder, $ApplicationFolder, 'views', 'modules', $View.'.php'));

                // b. Garden-wide theme view. eg. /path/to/application/themes/theme_name/views/modules/
                $ViewPaths[] = CombinePaths(array(PATH_THEMES, $ThemeFolder, 'views', 'modules', $View.'.php'));
            }

            // 3. Application default. eg. /path/to/application/app_name/views/controller_name/
            if ($this->_ApplicationFolder) {
                $ViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, $ApplicationFolder, 'views', 'modules', $View.'.php'));
            } else {
                $ViewPaths[] = dirname($this->path())."/../views/modules/$View.php";
            }

            // 4. Garden default. eg. /path/to/application/dashboard/views/modules/
            $ViewPaths[] = CombinePaths(array(PATH_APPLICATIONS, 'dashboard', 'views', 'modules', $View.'.php'));

            $ViewPath = Gdn_FileSystem::exists($ViewPaths);
        }

        if ($ViewPath === false) {
            throw new Exception(ErrorMessage('Could not find a `'.$View.'` view for the `'.$this->Name().'` module in the `'.$ApplicationFolder.'` application.', get_class($this), 'FetchView'), E_USER_ERROR);
        }

        return $ViewPath;
    }


    /**
     * Returns the name of this module. Unless it is overridden, it will simply
     * return the class name.
     *
     * @return string
     */
    public function name() {
        return get_class($this);
    }

    /**
     *
     *
     * @param bool $NewValue
     * @return bool|string
     */
    public function path($NewValue = false) {
        static $Path = false;
        if ($NewValue !== false) {
            $Path = $NewValue;
        } elseif ($Path === false) {
            $RO = new ReflectionObject($this);
            $Path = $RO->getFileName();
        }
        return $Path;
    }

    /**
     * Output HTML.
     */
    public function render() {
        echo $this->toString();
    }

    /**
     *
     *
     * @param $Name
     * @param $Value
     */
    public function setData($Name, $Value) {
        $this->Data[$Name] = $Value;
    }

    /**
     * Returns the component as a string to be rendered to the screen.
     *
     * Unless this method is overridden, it will attempt to find and return a view
     * related to this module automatically.
     *
     * @return string
     */
    public function toString() {
        if ($this->Visible) {
            return $this->fetchView();
        } else {
            return '';
        }
    }

    /**
     * Magic method for type casting to string.
     *
     * @return string
     */
    public function __toString() {
        return $this->toString();
    }
}
