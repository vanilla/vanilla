<?php if (!defined('APPLICATION')) exit();

/**
 * Plugin base class
 * 
 * A simple framework that all plugins should extend. Aside from the implementation of
 * Gdn_IPlugin, this class provides some convenience methods to make plugin development
 * easier and faster.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

abstract class Gdn_Plugin extends Gdn_Pluggable implements Gdn_IPlugin {
   
   protected $Sender;
   
   public function __construct() {
      parent::__construct();
   }
   
   /**
    * Get an instance of the calling class
    * 
    * WARNING: This method uses Late Static Binding and therefore requires 
    * PHP 5.3+
    * 
    * @return Gdn_Plugin
    */
   public static function Instance() {
      return Gdn::PluginManager()->GetPluginInstance(get_called_class(), Gdn_PluginManager::ACCESS_CLASSNAME);
   }

   public function GetPluginName() {
      return GetValue('Name', Gdn::PluginManager()->GetPluginInfo(get_class($this), Gdn_PluginManager::ACCESS_CLASSNAME));
   }
   
   public function GetPluginIndex() {
      return GetValue('Index', Gdn::PluginManager()->GetPluginInfo(get_class($this), Gdn_PluginManager::ACCESS_CLASSNAME));
   }
   
   public function GetPluginFolder($Absolute = TRUE) {
      $Folder = GetValue('PluginRoot', Gdn::PluginManager()->GetPluginInfo(get_class($this), Gdn_PluginManager::ACCESS_CLASSNAME));
      if (!$Absolute)
         $Folder = str_replace(rtrim(PATH_PLUGINS,'/'), 'plugins', $Folder);
         
      return $Folder;
   }
   
   /**
    * Get a specific keyvalue from the plugin info array
    *
    * @param string $Key Name of the key whose value you wish to retrieve
    * @param mixed $Default Optional value to return if the key cannot be found
    * @return mixed value of the provided key
    */
   public function GetPluginKey($Key, $Default = NULL) {
      return GetValue($Key, Gdn::PluginManager()->GetPluginInfo(get_class($this), Gdn_PluginManager::ACCESS_CLASSNAME), $Default);
   }
   
   /**
    * Gets the path to a file within the plugin's folder (and optionally include it)
    *
    * @param $Filename string relative path to a file within the plugin's folder
    * @param $IncludeFile boolean whether or not to immediately include() the file if it exists
    * @param $AbsolutePath boolean whether or not to prepend the full document root to the path
    * @return string path to the file
    */
   public function GetResource($Filepath, $Include = FALSE, $Absolute = TRUE) {
      $RequiredFilename = implode(DS, array($this->GetPluginFolder($Absolute), $Filepath));
      if ($Include && file_exists($RequiredFilename))
         include($RequiredFilename);
            
      return $RequiredFilename;
   }
   
   /**
    * Converts view files to Render() paths
    * 
    * This method takes a simple filename and, assuming it is located inside <yourplugin>/views/, 
    * converts it into a path that is suitable for $Sender->Render().
    * 
    * @param $ViewName string name of the view file, including extension
    * @return string path to the view file, relative to the document root.
    */
   public function GetView($ViewName) {
      $PluginDirectory = implode(DS, array($this->GetPluginFolder(TRUE), 'views'));
      return $PluginDirectory.DS.$ViewName;
   }
   
   public function GetWebResource($Filepath, $WithDomain = FALSE) {
      $WebResource = $this->GetResource($Filepath, FALSE, FALSE);
      
      if ($WithDomain === '/')
         return $WebResource;
      
      if (Gdn_Url::WebRoot())
         $WebResource = '/'.CombinePaths(array(Gdn_Url::WebRoot(),$WebResource));
      
      if ($WithDomain === '//')
         $WebResource = '//'.Gdn::Request()->HostAndPort().$WebResource;
      elseif ($WithDomain)
         $WebResource = Gdn::Request()->Scheme().'//'.Gdn::Request()->HostAndPort().$WebResource;
      
      return $WebResource;
   }
   
   /** Implementaion of Gdn_IPlugin::Setup().
    */
   public function Setup() {
      // Do nothing...
   }
   
   /**
    * Retries UserMeta information for a UserID / Key pair
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
    * @param $UserID integer UserID or array of UserIDs
    * @param $Key string relative user meta key
    * @param $Default optional default return value if key is not found
    * @param $AutoUnfold optional Automatically return key item for single key queries
    * @return array results or $Default
    */
   protected function GetUserMeta($UserID, $Key, $Default = NULL, $AutoUnfold = FALSE) {
      $MetaKey = $this->MakeMetaKey($Key);
      $R = $this->UserMetaModel()->GetUserMeta($UserID, $MetaKey, $Default);
      if ($AutoUnfold)
         $R = GetValue($MetaKey, $R);
      return $R;
   }
   
   /**
    * Sets UserMeta data to the UserMeta table
    * 
    * This method takes a UserID, Key, and Value, and attempts to set $Key = $Value for $UserID.
    * $Key can be an SQL wildcard, thereby allowing multiple variations of a $Key to be set. $UserID 
    * can be an array, thereby allowing multiple users' $Keys to be set to the same $Value.
    *
    * ++ Before any queries are run, $Key is converted to its fully qualified format (Plugin.<PluginName> prepended)
    * ++ to prevent collisions in the meta table when multiple plugins have similar key names.
    *
    * If $Value == NULL, the matching row(s) are deleted instead of updated.
    * 
    * @param $UserID int UserID or array of UserIDs
    * @param $Key string relative user key
    * @param $Value mixed optional value to set, null to delete
    * @return void
    */
   protected function SetUserMeta($UserID, $Key, $Value = NULL) {
      $MetaKey = $this->MakeMetaKey($Key);
      $this->UserMetaModel()->SetUserMeta($UserID, $MetaKey, $Value);
   }
   
   /**
    * This method trims the plugin prefix from a fully qualified MetaKey.
    *
    * For example, Plugin.Signatures.Sig would become 'Sig'.
    *
    * @param $UserMetaKey string fully qualified meta key
    * @return string relative meta key
    */
   protected function TrimMetaKey($FullyQualifiedUserKey) {
      $Key = explode('.', $FullyQualifiedUserKey);
      if ($Key[0] == 'Plugin' && sizeof($Key) >= 3) {
         return implode('.',array_slice($Key, 2));
      }
         
      return $FullyQualifiedUserKey;
   }
   
   /**
    * This method takes a UserKey (short relative form) and prepends the plugin prefix.
    * 
    * For example, 'Sig' becomes 'Plugin.Signatures.Sig'
    * 
    * @param $UserKey string relative user meta key
    * @return string fully qualified meta key
    */
   protected function MakeMetaKey($RelativeUserKey) {
      return implode('.',array('Plugin',$this->GetPluginIndex(),$this->TrimMetaKey($RelativeUserKey)));
   }
   
   public function Controller_Index($Sender) {
      $Sender->Title($this->GetPluginKey('Name'));
      $Sender->AddSideMenu('plugin/'.$this->GetPluginIndex());
      $Sender->SetData('Description', $this->GetPluginKey('Description'));
      
      $CSSFile = $this->GetResource('css/'.strtolower($this->GetPluginIndex()).'.css',FALSE,FALSE);
      if (file_exists($CSSFile))
         $Sender->AddCssFile($CSSFile);
      
      $ViewFile = $this->GetView(strtolower($this->GetPluginIndex()).'.php');
      $Sender->Render($ViewFile);
   }
   
   /**
    * Automatically handle the toggle effect
    *
    * @param object $Sender Reference to the invoking controller
    * @param mixed $Redirect 
    */
   public function AutoToggle($Sender, $Redirect = NULL) {
      $PluginName = $this->GetPluginIndex();
      $EnabledKey = "Plugins.{$PluginName}.Enabled";
      $CurrentConfig = C($EnabledKey, FALSE);
      $PassedKey = GetValue(1, $Sender->RequestArgs);
      
      if ($Sender->Form->AuthenticatedPostBack() || Gdn::Session()->ValidateTransientKey($PassedKey)) {
         $CurrentConfig = !$CurrentConfig;
         SaveToConfig($EnabledKey, $CurrentConfig);
      }
      
      if ($Sender->Form->AuthenticatedPostBack())
         $this->Controller_Index($Sender);
      else {
         if ($Redirect === FALSE) return $CurrentConfig;
         if (is_null($Redirect))
            Redirect('plugin/'.strtolower($PluginName));
         else
            Redirect($Redirect);
      }            
      return $CurrentConfig;
   }
   
   public function AutoTogglePath($Path = NULL) {
      if (is_null($Path)) {
         $PluginName = $this->GetPluginIndex();
         $Path = '/dashboard/plugin/'.strtolower($PluginName).'/toggle/'.Gdn::Session()->TransientKey();
      }
      return $Path;
   }
   
   /**
    * Convenience method for determining 2nd level activation
    *
    * This method checks the secondary "Plugin.PLUGINNAME.Enabled" setting that has becoming the de-facto
    * standard for keeping plugins enabled but de-activated.
    *
    * @return boolean Status of plugin's 2nd level activation
    */
   public function IsEnabled() {
      $PluginName = $this->GetPluginIndex();
      $EnabledKey = "Plugins.{$PluginName}.Enabled";
      return C($EnabledKey, FALSE);
   }
   
   public function Dispatch($Sender, $RequestArgs = array()) {
      $this->Sender = $Sender;
      $Sender->Form = new Gdn_Form();
      
      $ControllerMethod = 'Controller_Index';
      if (is_array($RequestArgs) && sizeof($Sender->RequestArgs)) {
         list($MethodName) = $Sender->RequestArgs;
         // Account for suffix
         $MethodName = array_shift($Trash = explode('.', $MethodName));
         $TestControllerMethod = 'Controller_'.$MethodName;
         if (method_exists($this, $TestControllerMethod))
            $ControllerMethod = $TestControllerMethod;
      }
      
      if (method_exists($this, $ControllerMethod)) {
         $Sender->Plugin = $this;
         return call_user_func(array($this,$ControllerMethod),$Sender);
      } else {
         $PluginName = get_class($this);
         throw NotFoundException("@{$PluginName}->{$ControllerMethod}()");
      }
   }
   
   /**
    * Passthru render request to sender
    * 
    * This render method automatically adds the correct ApplicationFolder parameter
    * so that $Sender->Render() will first check the plugin's views/ folder.
    * 
    * @param string $ViewName 
    */
   public function Render($ViewName) {
      $PluginFolder = $this->GetPluginFolder(FALSE);
      $this->Sender->Render($ViewName, '', $PluginFolder);
   }
   
   public function UserMetaModel() {
      return Gdn::UserMetaModel();
   }
}