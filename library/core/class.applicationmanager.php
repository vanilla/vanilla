<?php if (!defined('APPLICATION')) exit();

/**
 * Application Manager
 * 
 * Manages available applications, enabling and disabling them.
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_ApplicationManager {
   
   /**
    * An array of available applications. Never access this directly, instead
    * use $this->AvailableApplications();
    *
    * @var array
    */
   private $_AvailableApplications = NULL;

   /**
    * An array of enabled applications. Never access this directly, instead
    * use $this->EnabledApplications();
    *
    * @var array
    */
   private $_EnabledApplications = NULL;
   
   /**
    * The valid paths to search for applications.
    *
    * @var array
    */
   public $Paths = array(PATH_APPLICATIONS);

   /**
    * Looks through the root Garden directory for valid applications and
    * returns them as an associative array of "Application Name" =>
    * "Application Info Array". It also adds a "Folder" definition to the
    * Application Info Array for each application.
    */
   public function AvailableApplications() {
      if (!is_array($this->_AvailableApplications)) {
         $ApplicationInfo = array();
         
         $AppFolders = Gdn_FileSystem::Folders(PATH_APPLICATIONS); // Get an array of all application folders
         $ApplicationAboutFiles = Gdn_FileSystem::FindAll(PATH_APPLICATIONS, 'settings' . DS . 'about.php', $AppFolders); // Now look for about files within them.
         // Include them all right here and fill the application info array
         $ApplicationCount = count($ApplicationAboutFiles);
         for ($i = 0; $i < $ApplicationCount; ++$i) {
            include($ApplicationAboutFiles[$i]);

            // Define the folder name for the newly added item
            foreach ($ApplicationInfo as $ApplicationName => $Info) {
               if (array_key_exists('Folder', $ApplicationInfo[$ApplicationName]) === FALSE) {
                  $Folder = substr($ApplicationAboutFiles[$i], strlen(PATH_APPLICATIONS));
                  if (substr($Folder, 0, 1) == DS)
                     $Folder = substr($Folder, 1);

                  $Folder = substr($Folder, 0, strpos($Folder, DS));
                  $ApplicationInfo[$ApplicationName]['Folder'] = $Folder;
               }
            }
         }
         // Add all of the indexes to the applications.
         foreach ($ApplicationInfo as $Index => &$Info) {
            $Info['Index'] = $Index;
         }

         $this->_AvailableApplications = $ApplicationInfo;
      }

      return $this->_AvailableApplications;
   }

   /**
    * Gets an array of all of the enabled applications.
    * @return array
    */
   public function EnabledApplications() {
      if (!is_array($this->_EnabledApplications)) {
         $EnabledApplications = Gdn::Config('EnabledApplications', array('Dashboard' => 'dashboard'));
         // Add some information about the applications to the array.
         foreach($EnabledApplications as $Name => $Folder) {
            $EnabledApplications[$Name] = array('Folder' => $Folder);
            //$EnabledApplications[$Name]['Version'] = Gdn::Config($Name.'.Version', '');
            $EnabledApplications[$Name]['Version'] = '';
            $EnabledApplications[$Name]['Index'] = $Name;
            // Get the application version from it's about file.
            $AboutPath = PATH_APPLICATIONS.'/'.strtolower($Name).'/settings/about.php';
            if (file_exists($AboutPath)) {
               $ApplicationInfo = array();
               include $AboutPath;
               $EnabledApplications[$Name]['Version'] = GetValueR("$Name.Version", $ApplicationInfo, '');
            }
         }
         $this->_EnabledApplications = $EnabledApplications;
      }

      return $this->_EnabledApplications;
   }
   
   public function CheckApplication($ApplicationName) {
      if (array_key_exists($ApplicationName, $this->EnabledApplications()))
         return TRUE;
         
      return FALSE;
   }
   
   public function GetApplicationInfo($ApplicationName, $Target = NULL) {
      $ApplicationInfo = GetValue($ApplicationName, $this->AvailableApplications(), NULL);
      if (is_null($ApplicationInfo)) return FALSE;
      
      if (!is_null($Target))
         return GetValueR($Target, $ApplicationInfo, FALSE);
      return $ApplicationInfo;
   }
   
   public function AvailableVisibleApplications() {
      $AvailableApplications = $this->AvailableApplications();
      foreach ($AvailableApplications as $ApplicationName => $Info) {
         if (!ArrayValue('AllowEnable', $Info, TRUE) || !ArrayValue('AllowDisable', $Info, TRUE))
            unset($AvailableApplications[$ApplicationName]);
      }
      return $AvailableApplications;
   }

   public function EnabledVisibleApplications() {
      $AvailableApplications = $this->AvailableApplications();
      $EnabledApplications = $this->EnabledApplications();
      foreach ($AvailableApplications as $ApplicationName => $Info) {
         if (array_key_exists($ApplicationName, $EnabledApplications)) {
            if (!ArrayValue('AllowEnable', $Info, TRUE) || !ArrayValue('AllowDisable', $Info, TRUE)) {
               unset($AvailableApplications[$ApplicationName]);
            }
         } else {
            unset($AvailableApplications[$ApplicationName]);
         }
      }
      return $AvailableApplications;
   }

   /**
    * @todo Undocumented method.
    */
   public function EnabledApplicationFolders() {
      $EnabledApplications = C('EnabledApplications', array());
      $EnabledApplications['Dashboard'] = 'dashboard';
      return array_values($EnabledApplications);
   }

   /**
    * Undocumented method.
    *
    * @param string $ApplicationName Undocumented variable.
    * @todo Document CheckRequirements() method.
    */
   public function CheckRequirements($ApplicationName) {
      $AvailableApplications = $this->AvailableApplications();
      $RequiredApplications = ArrayValue('RequiredApplications', ArrayValue($ApplicationName, $AvailableApplications, array()), FALSE);
      $EnabledApplications = $this->EnabledApplications();
      CheckRequirements($ApplicationName, $RequiredApplications, $EnabledApplications, 'application');
   }

   /**
    * Undocumented method.
    *
    * @param string $ApplicationName Undocumented variable.
    * @param string $Validation Undocumented variable.
    * @todo Document EnableApplication() method.
    */
   public function EnableApplication($ApplicationName, $Validation) {
      $this->TestApplication($ApplicationName, $Validation);
      $ApplicationInfo = ArrayValueI($ApplicationName, $this->AvailableApplications(), array());
      $ApplicationName = $ApplicationInfo['Index'];
      $ApplicationFolder = ArrayValue('Folder', $ApplicationInfo, '');

      SaveToConfig('EnabledApplications'.'.'.$ApplicationName, $ApplicationFolder);
      
      // Redefine the locale manager's settings $Locale->Set($CurrentLocale, $EnabledApps, $EnabledPlugins, TRUE);
      $Locale = Gdn::Locale();
      $Locale->Set($Locale->Current(), $this->EnabledApplicationFolders(), Gdn::PluginManager()->EnabledPluginFolders(), TRUE);
      
      return TRUE;
   }

   public function TestApplication($ApplicationName, &$Validation) {
      // Add the application to the $EnabledApplications array in conf/applications.php
      $ApplicationInfo = ArrayValueI($ApplicationName, $this->AvailableApplications(), array());
      $ApplicationName = $ApplicationInfo['Index'];
      $ApplicationFolder = ArrayValue('Folder', $ApplicationInfo, '');
      if ($ApplicationFolder == '')
         throw new Exception(T('The application folder was not properly defined.'));
      
      // Hook directly into the autoloader and force it to load the newly tested application
      Gdn_Autoloader::AttachApplication($ApplicationFolder);
      
      // Call the application's setup method
      $Hooks = $ApplicationName.'Hooks';
      if (!class_exists($Hooks)) {
         $HooksFile = PATH_APPLICATIONS.DS.$ApplicationFolder.'/settings/class.hooks.php';
         if (file_exists($HooksFile))
            include($HooksFile);
      }
      if (class_exists($Hooks)) {
         $Hooks = new $Hooks();
         $Hooks->Setup();
      }
      
      return TRUE;
   }

   /**
    * Undocumented method.
    *
    * @param string $ApplicationName Undocumented variable.
    * @todo Document DisableApplication() method.
    */
   public function DisableApplication($ApplicationName) {
      // 1. Check to make sure that this application is allowed to be disabled
      $ApplicationInfo = ArrayValueI($ApplicationName, $this->AvailableApplications(), array());
      $ApplicationName = $ApplicationInfo['Index'];
      if (!ArrayValue('AllowDisable', $ApplicationInfo, TRUE))
         throw new Exception(sprintf(T('You cannot disable the %s application.'), $ApplicationName));

      // 2. Check to make sure that no other enabled applications rely on this one
      foreach ($this->EnabledApplications() as $CheckingName => $CheckingInfo) {
         $RequiredApplications = ArrayValue('RequiredApplications', $CheckingInfo, FALSE);
         if (is_array($RequiredApplications) && array_key_exists($ApplicationName, $RequiredApplications) === TRUE) {
            throw new Exception(sprintf(T('You cannot disable the %1$s application because the %2$s application requires it in order to function.'), $ApplicationName, $CheckingName));
         }
      }

      // 2. Disable it
      RemoveFromConfig("EnabledApplications.{$ApplicationName}");

      // Clear the object caches.
      Gdn_Autoloader::SmartFree(Gdn_Autoloader::CONTEXT_APPLICATION, $ApplicationInfo);

      // Redefine the locale manager's settings $Locale->Set($CurrentLocale, $EnabledApps, $EnabledPlugins, TRUE);
      $Locale = Gdn::Locale();
      $Locale->Set($Locale->Current(), $this->EnabledApplicationFolders(), Gdn::PluginManager()->EnabledPluginFolders(), TRUE);
   }

   /**
    * Undocumented method.
    *
    * @param string $ApplicationName Undocumented variable.
    * @param string $Validation Undocumented variable.
    * @todo Document RegisterPermissions() method.
    */
   public function RegisterPermissions($ApplicationName, &$Validation) {
      $ApplicationInfo = ArrayValue($ApplicationName, $this->AvailableApplications(), array());
      $PermissionName = ArrayValue('RegisterPermissions', $ApplicationInfo, FALSE);
      if ($PermissionName != FALSE) {
         $PermissionModel = Gdn::PermissionModel();
         $PermissionModel->Define($PermissionName);
      }
   }
}
