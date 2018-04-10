<?php
/**
 * Gdn_Module.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
    public $Data = [];

    /** @var Gdn_Controller The object that constructed this object. Typically this should be a Controller object. */
    protected $_Sender;

    /** @var string The name of the theme folder that the application is currently using. */
    protected $_ThemeFolder;

    /** @var bool  */
    public $Visible = true;

    /**  @var string The filename of view to render, excluding the extension. */
    protected $view;

    /**
     * Class constructor
     *
     * @param object $sender
     */
    public function __construct($sender = '', $applicationFolder = false) {
        if (!$sender) {
            $sender = Gdn::controller();
        }

        if (is_object($sender)) {
            $this->_ApplicationFolder = $sender->ApplicationFolder;
            $this->_ThemeFolder = $sender->Theme;
        } else {
            $this->_ApplicationFolder = 'dashboard';
            $this->_ThemeFolder = Gdn::config('Garden.Theme');
        }
        if ($applicationFolder !== false) {
            $this->_ApplicationFolder = $applicationFolder;
        }

        if (is_object($sender)) {
            $this->_Sender = $sender;
        }

        parent::__construct();
    }

    /**
     * @return string The filename of view to render, excluding the extension.
     */
    public function getView() {
        return $this->view;
    }

    /**
     * @param string $view The filename of view to render, excluding the extension.
     * @return $this The calling module.
     */
    public function setView($view) {
        $this->view = $view;
        return $this;
    }

    /**
     * Returns the name of the asset where this component should be rendered.
     */
    public function assetTarget() {
        trigger_error(errorMessage("Any class extended from the Module class must implement it's own AssetTarget method.", get_class($this), 'AssetTarget'), E_USER_ERROR);
    }

    /**
     *
     *
     * @param null $name
     * @param string $default
     * @return array|mixed
     */
    public function data($name = null, $default = '') {
        if ($name == null) {
            $result = $this->Data;
        } else {
            $result = getValueR($name, $this->Data, $default);
        }
        return $result;
    }

    /**
     * Returns the xhtml for this module as a fully parsed and rendered string.
     *
     * @return string
     */
    public function fetchView($view = '') {
        if ($view) {
            $this->view = $view;
        }
        if (method_exists($this, 'prepare')) {
            if (!$this->prepare()) {
                return '';
            }
        }
        $viewPath = $this->fetchViewLocation($this->view);
        $String = '';
        ob_start();
        if (is_object($this->_Sender) && isset($this->_Sender->Data)) {
            $Data = $this->_Sender->Data;
        } else {
            $Data = [];
        }
        include($viewPath);
        $String = ob_get_contents();
        @ob_end_clean();
        return $String;
    }

    /**
     * Checks whether an item is allowed by returning it if it is already a boolean,
     * or checking the permission if it is a string or array.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @return bool Whether the item has permission to be added to the items list.
     */
    public function allowed($isAllowed) {
        if (is_bool($isAllowed)) {
            return $isAllowed;
        }
        if (is_string($isAllowed) || is_array($isAllowed)) {
            return Gdn::session()->checkPermission($isAllowed);
        }
        return false;
    }

    /**
     * Returns the location of the view for this module in the filesystem.
     *
     * @param string $view
     * @param string $applicationFolder
     * @return array
     */
    public function fetchViewLocation($view = '', $applicationFolder = '') {
        if ($view == '') {
            $view = strtolower($this->name());
        }

        if (substr($view, -6) == 'module') {
            $view = substr($view, 0, -6);
        }

        if (substr($view, 0, 4) == 'gdn_') {
            $view = substr($view, 4);
        }

        if ($applicationFolder == '') {
            $applicationFolder = strpos($this->_ApplicationFolder, '/') ? $this->_ApplicationFolder : strtolower($this->_ApplicationFolder);
        }

        $themeFolder = $this->_ThemeFolder;

        $viewPath = null;

        // Try to use Gdn_Controller's FetchViewLocation
        if (Gdn::controller() instanceof Gdn_Controller) {
            try {
                $viewPath = Gdn::controller()->fetchViewLocation($view, 'modules', $applicationFolder);
            } catch (Exception $ex) {
            }
        }

        if (!$viewPath) {
            $viewPaths = [];
            // 1. An explicitly defined path to a view
            if (strpos($view, '/') !== false) {
                $viewPaths[] = $view;
            }

            // 2. A theme
            if ($themeFolder != '') {
                // a. Application-specific theme view. eg. /path/to/application/themes/theme_name/app_name/views/modules/
                $viewPaths[] = combinePaths([PATH_THEMES, $themeFolder, $applicationFolder, 'views', 'modules', $view.'.php']);

                // b. Garden-wide theme view. eg. /path/to/application/themes/theme_name/views/modules/
                $viewPaths[] = combinePaths([PATH_THEMES, $themeFolder, 'views', 'modules', $view.'.php']);
            }

            // 3. Application default. eg. /path/to/application/app_name/views/controller_name/
            if ($this->_ApplicationFolder) {
                $viewPaths[] = combinePaths([PATH_APPLICATIONS, $applicationFolder, 'views', 'modules', $view.'.php']);
            } else {
                $viewPaths[] = dirname($this->path())."/../views/modules/$view.php";
            }

            // 4. Garden default. eg. /path/to/application/dashboard/views/modules/
            $viewPaths[] = combinePaths([PATH_APPLICATIONS, 'dashboard', 'views', 'modules', $view.'.php']);

            $viewPath = Gdn_FileSystem::exists($viewPaths);
        }

        if ($viewPath === false) {
            throw new Exception(errorMessage('Could not find a `'.$view.'` view for the `'.$this->name().'` module in the `'.$applicationFolder.'` application.', get_class($this), 'FetchView'), E_USER_ERROR);
        }

        return $viewPath;
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
     * @param bool $newValue
     * @return bool|string
     */
    public function path($newValue = false) {
        static $path = false;
        if ($newValue !== false) {
            $path = $newValue;
        } elseif ($path === false) {
            $rO = new ReflectionObject($this);
            $path = $rO->getFileName();
        }
        return $path;
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
     * @param $name
     * @param $value
     */
    public function setData($name, $value) {
        $this->Data[$name] = $value;
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
