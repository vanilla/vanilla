<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['LocaleDeveloper'] = array(
   'Name' => 'Locale Developer',
   'Description' => 'Contains useful functions for locale developers. When you enable this plugin go to its settings page to change your options. This plugin is maintained at http://github.com/vanillaforums/Addons',
   'Version' => '1.1.1',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/todd',
   'RequiredApplications' => array('Vanilla' => '2.0.11'),
   'SettingsUrl' => '/dashboard/settings/localedeveloper',
   'SettingsPermission' => 'Garden.Site.Manage',
);

class LocaleDeveloperPlugin extends Gdn_Plugin {
   public $LocalePath;

   /**
    * @var Gdn_Form Form
    */
   protected $Form;

   public function  __construct() {
      $this->LocalePath = PATH_UPLOADS.'/LocaleDeveloper';
      $this->Form = new Gdn_Form();
      parent::__construct();
   }

   /**
    * Save the captured definitions.
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function Base_Render_After($Sender, $Args) {
      $Locale = Gdn::Locale();
      if (!is_a($Locale, 'DeveloperLocale'))
         return;

      $Path = $this->LocalePath.'/tmp_'.RandomString(10);
      if (!file_exists(dirname($Path)))
         mkdir(dirname($Path), 0777, TRUE);
      elseif (file_exists($Path)) {
         // Load the existing definitions.
         $Locale->Load($Path);
      }
      
      // Load the core definitions.
      if (file_exists($this->LocalePath.'/captured_site_core.php')) {
         $Definition = array();
         include $this->LocalePath.'/captured_site_core.php';
         $Core = $Definition;
      } else {
         $Core = array();
      }
      
      // Load the admin definitions.
      if (file_exists($this->LocalePath.'/captured_dash_core.php')) {
         $Definition = array();
         include $this->LocalePath.'/captured_dash_core.php';
         $Admin = $Definition;
      } else {
         $Admin = array();
      }

      // Load the ignore file.
      $Definition = array();
      include dirname(__FILE__).'/ignore.php';
      $Ignore = $Definition;
      $Definition = array();
      
      $CapturedDefinitions = $Locale->CapturedDefinitions();
      
//      decho ($CapturedDefinitions);
//      die();
      
      foreach ($CapturedDefinitions as $Prefix => $Definition) {
         $FinalPath = $this->LocalePath."/captured_$Prefix.php";

         // Load the definitions that have already been captured.
         if (file_exists($FinalPath)) {
            include $FinalPath;
         }
         $Definition = array_diff_key($Definition, $Ignore);
         
         // Strip core definitions from the file.
         if ($Prefix != 'site_core') {
            $Definition = array_diff_key($Definition, $Core);
            
            if ($Prefix != 'dash_core') {
               $Definition = array_diff_key($Definition, $Admin);
            }
         }

         // Save the current definitions.
         $fp = fopen($Path, 'wb');
         fwrite($fp, $this->GetFileHeader());
         LocaleModel::WriteDefinitions($fp, $Definition);
         fclose($fp);

         // Copy the file over the existing one.
         $Result = rename($Path, $FinalPath);
      }
   }
   
   public function Gdn_Dispatcher_BeforeDispatch_Handler($Sender) {
      if (C('Plugins.LocaleDeveloper.CaptureDefinitions')) {
         // Install the developer locale.
         $_Locale = new DeveloperLocale(Gdn::Locale()->Current(), C('EnabledApplications'), C('EnabledPlugins'));

         $tmp = Gdn::FactoryOverwrite(TRUE);
         Gdn::FactoryInstall(Gdn::AliasLocale, 'Gdn_Locale', NULL, Gdn::FactorySingleton, $_Locale);
         Gdn::FactoryOverwrite($tmp);
         unset($tmp);
      }
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function SettingsController_Render_Before($Sender, $Args) {
      if (strcasecmp($Sender->RequestMethod, 'locales') != 0)
         return;

      // Add a little pointer to the settings.
      $Text = '<div class="Info">'.
         sprintf(T('Locale Developer Settings %s.'), Anchor(T('here'), '/dashboard/settings/localedeveloper')).
         '</div>';
      $Sender->AddAsset('Content', $Text, 'LocaleDeveloperLink');
   }

   /**
    *
    * @var SettingsController $Sender
    */
   public function SettingsController_LocaleDeveloper_Create($Sender, $Args = array()) {
      $Sender->Permission('Garden.Settings.Manage');

      $Sender->AddSideMenu();
      $Sender->SetData('Title', T('Locale Developer'));

      switch (strtolower(GetValue(0, $Args, ''))) {
         case '':
            $this->_Settings($Sender, $Args);
            break;
         case 'download':
            $this->_Download($Sender, $Args);
            break;
         case 'googletranslate':
            $this->_GoogleTranslate($Sender, $Args);
            break;
      }
   }

   public function _Download($Sender, $Args) {
      try {
      // Create the zip file.
      $Path = $this->CreateZip();

      // Serve the zip file.
      Gdn_FileSystem::ServeFile($Path, basename($Path), 'application/zip');
      } catch (Exception $Ex) {
         $this->Form->AddError($Ex);
         $this->_Settings($Sender, $Args);
      }
   }

   public function EnsureDefinitionFile() {
      $Path = $this->LocalePath.'/definitions.php';
      if (file_exists($Path))
         unlink($Path);
      $Contents = $this->GetFileHeader().self::FormatInfoArray('$LocaleInfo', $this->GetInfoArray());
      Gdn_FileSystem::SaveFile($Path, $Contents);
   }

   public static function FormatInfoArray($VariableName, $Array) {
      $VariableName = '$'.trim($VariableName, '$');

      $Result = '';
      foreach ($Array as $Key => $Value) {
         $Result .= $VariableName."['".addcslashes($Key, "'")."'] = ";
         $Result .= var_export($Value, TRUE);
         $Result .= ";\n\n";
      }

      return $Result;
   }

   public static function FormatValue($Value, $SingleLine = TRUE) {
      if (is_bool($Value)) {
         return $Value ? 'TRUE' : 'FALSE';
      } elseif (is_numeric($Value)) {
         return (string)$Value;
      } elseif (is_string($Value)) {
         if ($SingleLine)
            return var_export($Value, TRUE);
         else
            return "'".addcslashes($Value, "'")."'";
      } elseif (is_array($Value)) {
         $Result = '';
         $ArraySep = $SingleLine ? ', ' : ",\n   ";

         foreach ($Value as $Key => $ArrayValue) {
            if (strlen($Result) > 0)
               $Result .= $ArraySep;

            if ($SingleLine == 'TRUEFALSE')
               $SingleLine = FALSE;

            $Result .= "'".addcslashes($Key, "'")."' => ".self::FormatValue($ArrayValue, $SingleLine);
         }

         $Result = 'array('.$Result.')';
         return $Result;
      } else {
         $Error = print_r($Value);
         $Error = str_replace('*/', '', $Error);

         return "/* Could not format the following value:\n{$Error}\n*/";
      }
   }

   public function GetFileHeader() {
      $Now = Gdn_Format::ToDateTime();

      $Result = "<?php if (!defined('APPLICATION')) exit();
/** This file was generated by the Locale Developer plugin on $Now **/\n\n";

      return $Result;
   }

   public function GetInfoArray() {
      $Info = C('Plugins.LocaleDeveloper');
      foreach ($Info as $Key => $Value) {
         if (!$Value)
            unset($Info[$Key]);
      }

      $InfoArray = array(GetValue('Key', $Info, 'LocaleDeveloper') => array(
          'Locale' => GetValue('Locale', $Info, Gdn::Locale()->Current()),
          'Name' => GetValue('Name', $Info, 'Locale Developer'),
          'Description' => 'Automatically gernerated by the Locale Developer plugin.',
          'Version' => '0.1a',
          'Author' => "Your Name",
          'AuthorEmail' => 'Your Email',
          'AuthorUrl' => 'http://your.domain.com',
          'License' => 'Your choice of license'
      ));

      return $InfoArray;
   }

   public function _GoogleTranslate($Sender, $Args) {
      $Sender->Form = $this->Form;

      if ($this->Form->IsPostBack()) {
         exit('Foo');

      } else {
         // Load all of the definitions.
         //$Definitions = $this->LoadDefinitions();
         //$Sender->SetData('Definitions', $Definitions);
      }

      $Sender->Render('googletranslate', '', 'plugins/LocaleDeveloper');
   }

//   public function LoadDefinitions($Path = NULL) {
//      if ($Path === NULL)
//         $Path = $this->LocalePath;
//
//      $Paths = SafeGlob($Path.'/*.php');
//      $Definition = array();
//      foreach ($Paths as $Path) {
//         // Skip the locale developer's changes file.
//         if ($Path == $this->LocalePath && basename($Path) == 'changes.php')
//            continue;
//         include $Path;
//      }
//      return $Definition;
//   }

   public function _Settings($Sender, $Args) {
      $Sender->Form = $this->Form;

      // Grab the existing locale packs.
      $LocaleModel = new LocaleModel();
      $LocalePacks = $LocaleModel->AvailableLocalePacks();
      $LocalArray = array();
      foreach ($LocalePacks as $Key => $Info) {
         $LocaleArray[$Key] = GetValue('Name', $Info, $Key);
      }
      $Sender->SetData('LocalePacks', $LocaleArray);

      if ($this->Form->IsPostBack()) {
         if ($this->Form->GetFormValue('Save')) {
            $Values = ArrayTranslate($this->Form->FormValues(), array('Key', 'Name', 'Locale', 'CaptureDefinitions'));
            $SaveValues = array();
            foreach ($Values as $Key => $Value) {
               $SaveValues['Plugins.LocaleDeveloper.'.$Key] = $Value;
            }

            // Save the settings.
            SaveToConfig($SaveValues, '', array('RemoveEmpty' => TRUE));

            $Sender->StatusMessage = T('Your changes have been saved.');
         } elseif ($this->Form->GetFormValue('GenerateChanges')) {
            $Key = $this->Form->GetFormValue('LocalePackForChanges');
            if (!$Key)
               $this->Form->AddError('ValidateRequired', 'Locale Pack');
            $Path = PATH_ROOT.'/locales/'.$Key;
            if (!file_exists($Path))
               $this->Form->AddError('Could not find the selected locale pack.');

            if ($this->Form->ErrorCount() == 0) {
               try {
                  $LocaleModel->GenerateChanges($Path, $this->LocalePath);
                  $Sender->StatusMessage = T('Your changes have been saved.');
               } catch (Exception $Ex) {
                  $this->Form->AddError($Ex);
               }
            }
         } elseif ($this->Form->GetFormValue('Copy')) {
            $Key = $this->Form->GetFormValue('LocalePackForCopy');
            if (!$Key)
               $this->Form->AddError('ValidateRequired', 'Locale Pack');
            $Path = PATH_ROOT.'/locales/'.$Key;
            if (!file_exists($Path))
               $this->Form->AddError('Could not find the selected locale pack.');

            if ($this->Form->ErrorCount() == 0) {
               try {
                  $LocaleModel->CopyDefinitions($Path, $this->LocalePath.'/copied.php');
                  $Sender->StatusMessage = T('Your changes have been saved.');
               } catch (Exception $Ex) {
                  $this->Form->AddError($Ex);
               }
            }
         } elseif ($this->Form->GetFormValue('Remove')) {
            $Files = SafeGlob($this->LocalePath.'/*');
            foreach ($Files as $File) {
               $Result = unlink($File);
               if (!$Result) {
                  $this->Form->AddError('@'.sprintf(T('Could not remove %s.'), $File));
               }
            }
            if ($this->Form->ErrorCount() == 0)
               $Sender->StatusMessage = T('Your changes have been saved.');
         }
      } else {
         $Values = C('Plugins.LocaleDeveloper');
         foreach ($Values as $Key => $Value) {
            $this->Form->SetFormValue($Key, $Value);
         }
      }

      $Sender->SetData('LocalePath', $this->LocalePath);

      $Sender->Render('', '', 'plugins/LocaleDeveloper');
   }

   public function WriteInfoArray($fp) {
      $Info = C('Plugins.LocaleDeveloper');

      // Write the info array.
      $InfoArray = $this->GetInfoArray();

      $InfoString = self::FormatInfoArray('$LocaleInfo', $InfoArray);
      fwrite($fp, $InfoString);
   }

   public function CreateZip() {
      if (!class_exists('ZipArchive')) {
         throw new Exception('Your server does not support zipping files.', 400);
      }

      $Info = $this->GetInfoArray();
      $this->EnsureDefinitionFile();

      // Get the basename of the locale.
      $Key = key($Info);

      $ZipPath = PATH_UPLOADS."/$Key.zip";
      $TmpPath = PATH_UPLOADS."/tmp_".RandomString(10);

      $Zip = new ZipArchive();
      $Zip->open($TmpPath, ZIPARCHIVE::CREATE);
      
      // Add all of the files in the locale to the zip.
      $Files = SafeGlob(rtrim($this->LocalePath, '/').'/*.*', array('php', 'txt'));
      foreach ($Files as $File) {
         $LocalPath = $Key.'/'.basename($File);
         $Zip->addFile($File, $LocalPath);
      }

      $Zip->close();

      rename($TmpPath, $ZipPath);

      return $ZipPath;
   }
}
