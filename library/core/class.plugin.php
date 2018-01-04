<?php
/**
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

use Vanilla\Addon;

/**
 * Plugin base class.
 *
 * A simple framework that all plugins should extend. Aside from the implementation of
 * Gdn_IPlugin, this class provides some convenience methods to make plugin development
 * easier and faster.
 */
abstract class Gdn_Plugin extends Gdn_Pluggable implements Gdn_IPlugin {

    /** @var object */
    protected $Sender;

    /**
     * @var Addon The addon that this plugin belongs to.
     */
    private $addon;

    /**
     * Initialize a new instance of the {@link Gdn_Plugin} class.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get an instance of the calling class.
     *
     * @return Gdn_Plugin
     */
    public static function instance() {
        return Gdn::pluginManager()->getPluginInstance(get_called_class(), Gdn_PluginManager::ACCESS_CLASSNAME);
    }

    /**
     * Get the human-readable name of the plugin.
     *
     * @return string Returns the name of the plugin.
     */
    public function getPluginName() {
        return $this->getPluginKey('name', "Unknown Plugin");
    }

    /**
     * Get the addon that this plugin belongs to.
     *
     * @return Addon Returns the addon.
     */
    public function getAddon() {
        return $this->addon;
    }

    /**
     * Set the addon that this plugin belongs to.
     *
     * @param Addon $addon The new addon.
     *
     * @return Gdn_Plugin Returns `$this` for fluent calls.
     */
    public function setAddon(Addon $addon) {
        $this->addon = $addon;
        return $this;
    }

    /**
     * Set the addon associated with the plugin from the given {@link \Vanilla\AddonManager}.
     *
     * @param \Vanilla\AddonManager $addonManager The addon manager to search.
     */
    public function setAddonFromManager(\Vanilla\AddonManager $addonManager) {
        $addon = $addonManager->lookupByClassname(get_called_class());
        if ($addon instanceof Addon) {
            $this->setAddon($addon);
        }
    }

    /**
     * Gets the case-sensitive plugin key.
     *
     * @return string
     */
    public function getPluginIndex() {
        if ($this->addon !== null) {
            return $this->addon->getInfoValue('keyRaw', $this->addon->getKey());
        } else {
            return '';
        }
    }

    /**
     * Get the plugin's folder.
     *
     * @param bool $absolute Whether or not the folder should be absolute.
     *
     * @return string Returns the folder or an empty string if there is no addon associated with this object.
     */
    public function getPluginFolder($absolute = true) {
        if ($this->addon === null) {
            return '';
        }
        $folder = $this->addon->path('', $absolute ? Addon::PATH_FULL : Addon::PATH_ADDON);
        return $folder;
    }

    /**
     * Get a specific value from the plugin info array.
     *
     * @param string $key Name of the key whose value you wish to retrieve.
     * @param mixed $default Optional value to return if the key cannot be found.
     * @return mixed value of the provided key or {@link $default} if it isn't found.
     */
    public function getPluginKey($key, $default = null) {
        if ($this->addon !== null) {
            return $this->addon->getInfoValue($key, $default);
        }
        return $default;
    }

    /**
     * Gets the path to a file within the plugin's folder (and optionally include it).
     *
     * @param string $filePath A relative path to a file within the plugin's folder.
     * @param bool $include  Whether or not to immediately include() the file if it exists.
     * @param bool $absolute Whether or not to prepend the full document root to the path.
     * @return string path to the file
     */
    public function getResource($filePath, $include = false, $absolute = true) {
        if ($this->addon === null) {
            return '';
        }
        $subPath = $this->addon->path($filePath, Addon::PATH_ADDON);
        $fullPath = PATH_ROOT.$subPath;

        if ($include && file_exists($fullPath)) {
            include $fullPath;
        }

        $RequiredFilename = implode(DS, [$this->getPluginFolder($absolute), $filePath]);
        if ($include && file_exists($RequiredFilename)) {
            include($RequiredFilename);
        }

        return $absolute ? $fullPath : $subPath;
    }

    /**
     * Converts view files to render() paths.
     *
     * This method takes a simple filename and, assuming it is located inside <plugin>/views/,
     * converts it into a path that is suitable for $Sender->render().
     *
     * @param string $viewName The name of the view file, including extension.
     * @return string Returns the path to the view file, relative to the document root.
     * @deprecated This method is not themeable and thus not advisable.
     */
    public function getView($viewName) {
        deprecated('Gdn_Plugin->getView()');
        $pluginDirectory = implode(DS, [$this->getPluginFolder(true), 'views']);
        return $pluginDirectory.DS.$viewName;
    }

    /**
     * Get a static resource for this addon suitable to be served from the browser.
     *
     * @param string $filePath The plugin-relative path of the resource.
     * @param bool $withDomain Whether or not to include the domain.
     * @return string Returns the URL of the resource.
     */
    public function getWebResource($filePath, $withDomain = false) {
        $assetPath = $this->getResource($filePath, false, false);
        $result = asset($assetPath, $withDomain);

        return $result;
    }

    /**
     * Implementation of {@link Gdn_IPlugin::setup()}.
     */
    public function setup() {
        // Do nothing...
    }

    /**
     * Retries UserMeta information for a UserID/Key pair.
     *
     * This method takes a $UserID or array of $UserIDs, and a $Key. It converts the
     * $Key to fully qualified format and then queries for the associated value(s). $Key
     * can contain SQL wildcards, in which case multiple results can be returned.
     *
     * If $UserID is an array, the return value will be a multi dimensional array with the first
     * axis containing UserIDs and the second containing fully qualified UserMetaKeys, associated with
     * their values.
     *
     * If $UserID is a scalar, the return value will be a single dimensional array of $UserMetaKey => $Value
     * pairs.
     *
     * @param int|array $userID The UserID or array of UserIDs.
     * @param string $key The relative user meta key.
     * @param mixed $default An optional default return value if key is not found.
     * @param bool $autoUnfold Automatically return key item for single key queries.
     * @return array Return the results or $Default.
     */
    protected function getUserMeta($userID, $key, $default = null, $autoUnfold = false) {
        $metaKey = $this->makeMetaKey($key);
        $r = $this->userMetaModel()->getUserMeta($userID, $metaKey, $default);
        if ($autoUnfold) {
            $r = val($metaKey, $r, $default);
        }
        return $r;
    }

    /**
     * Sets UserMeta data to the UserMeta table
     *
     * This method takes a UserID, Key, and Value, and attempts to set $key = $value for $userID.
     * $key can be an SQL wildcard, thereby allowing multiple variations of a $key to be set. $userID
     * can be an array, thereby allowing multiple users' $keys to be set to the same $value.
     *
     * ++ Before any queries are run, $key is converted to its fully qualified format (Plugin.<PluginName> prepended)
     * ++ to prevent collisions in the meta table when multiple plugins have similar key names.
     *
     * If $value == NULL, the matching row(s) are deleted instead of updated.
     *
     * @param $userID int UserID or array of UserIDs
     * @param $key string relative user key
     * @param $value mixed optional value to set, null to delete
     * @return void
     */
    protected function setUserMeta($userID, $key, $value = null) {
        $metaKey = $this->makeMetaKey($key);
        $this->userMetaModel()->setUserMeta($userID, $metaKey, $value);
    }

    /**
     * This method trims the plugin prefix from a fully qualified MetaKey.
     *
     * For example, Plugin.Signatures.Sig would become 'Sig'.
     *
     * @param $UserMetaKey string fully qualified meta key
     * @return string relative meta key
     */
    protected function trimMetaKey($fullyQualifiedUserKey) {
        $key = explode('.', $fullyQualifiedUserKey);
        if ($key[0] == 'Plugin' && sizeof($key) >= 3) {
            return implode('.', array_slice($key, 2));
        }

        return $fullyQualifiedUserKey;
    }

    /**
     * This method takes a UserKey (short relative form) and prepends the plugin prefix.
     *
     * For example, 'Sig' becomes 'Plugin.Signatures.Sig'
     *
     * @param string $relativeUserKey The relative user meta key.
     * @return string Returns a fully qualified meta key.
     */
    protected function makeMetaKey($relativeUserKey) {
        $result = implode(
            '.',
            [
                'Plugin',
                $this->getPluginIndex(),
                $this->trimMetaKey($relativeUserKey)
            ]
        );

        return $result;
    }

    /**
     *
     *
     * @param $sender
     */
    public function controller_index($sender) {
        $pluginIndex = $this->getPluginIndex();
        $sender->title($this->getPluginKey('Name'));
        $sender->setHighlightRoute('plugin/'.$pluginIndex);
        $sender->setData('Description', $this->getPluginKey('Description'));

        $cSSFile = $this->getResource('css/'.strtolower($pluginIndex).'.css', false, false);
        if (file_exists($cSSFile)) {
            $sender->addCssFile($cSSFile);
        }

        $viewFile = $sender->fetchViewLocation(
            strtolower($pluginIndex),
            '',
            'plugins/'.$pluginIndex
        );
        $sender->render($viewFile);
    }

    /**
     * Automatically handle the toggle effect.
     *
     * @param object $sender Reference to the invoking controller
     * @param mixed $redirect
     * @deprecated
     * @todo Remove this.
     */
    public function autoToggle($sender, $redirect = null) {
        deprecated('Gdn_Plugin->autoToggle()');
        $pluginName = $this->getPluginIndex();
        $enabledKey = "Plugins.{$pluginName}.Enabled";
        $currentConfig = c($enabledKey, false);
        $passedKey = val(1, $sender->RequestArgs);

        if ($sender->Form->authenticatedPostBack() || Gdn::session()->validateTransientKey($passedKey)) {
            $currentConfig = !$currentConfig;
            saveToConfig($enabledKey, $currentConfig);
        }

        if ($sender->Form->authenticatedPostBack()) {
            $this->controller_index($sender);
        } else {
            if ($redirect === false) {
                return $currentConfig;
            }
            if (is_null($redirect)) {
                redirectTo('plugin/'.strtolower($pluginName));
            } else {
                redirectTo($redirect);
            }
        }
        return $currentConfig;
    }

    /**
     *
     *
     * @param null $path
     * @return null|string
     * @todo Remove this.
     */
    public function autoTogglePath($path = null) {
        deprecated('Gdn_Plugin->autoTogglePath()');
        if (is_null($path)) {
            $pluginName = $this->getPluginIndex();
            $path = '/dashboard/plugin/'.strtolower($pluginName).'/toggle/'.Gdn::session()->transientKey();
        }
        return $path;
    }

    /**
     * Convenience method for determining 2nd level activation.
     *
     * This method checks the secondary "Plugin.PLUGINNAME.Enabled" setting that has becoming the de-facto
     * standard for keeping plugins enabled but de-activated.
     *
     * @return boolean Status of plugin's 2nd level activation
     */
    public function isEnabled() {
        $pluginName = $this->getPluginIndex();
        $enabledKey = "Plugins.{$pluginName}.Enabled";
        return (bool)c($enabledKey, false);
    }

    /**
     *
     *
     * @param $sender
     * @param array $requestArgs
     * @return mixed
     * @throws Exception
     */
    public function dispatch($sender, $requestArgs = []) {
        $this->Sender = $sender;
        $sender->Form = new Gdn_Form();

        $controllerMethod = 'Controller_Index';
        if (is_array($requestArgs) && sizeof($sender->RequestArgs)) {
            list($methodName) = $sender->RequestArgs;
            // Account for suffix
            $methodName = array_shift($trash = explode('.', $methodName));
            $testControllerMethod = 'Controller_'.$methodName;
            if (method_exists($this, $testControllerMethod)) {
                $controllerMethod = $testControllerMethod;
                array_shift($requestArgs);
            }
        }

        if (method_exists($this, $controllerMethod)) {
            $sender->Plugin = $this;
            return call_user_func([$this, $controllerMethod], $sender, $requestArgs);
        } else {
            $pluginName = get_class($this);
            throw notFoundException("@{$pluginName}->{$controllerMethod}()");
        }
    }

    /**
     * Passthru render request to sender.
     *
     * This render method automatically adds the correct ApplicationFolder parameter
     * so that $Sender->render() will first check the plugin's views/ folder.
     *
     * @param string $view The name of the view to render.
     */
    public function render($view) {
        $pluginFolder = $this->getPluginFolder(false);
        Gdn::controller()->render($view, '', $pluginFolder);
    }

    /**
     * Instance of UserMetaModel.
     *
     * @return UserMetaModel
     */
    public function userMetaModel() {
        return Gdn::userMetaModel();
    }
}
