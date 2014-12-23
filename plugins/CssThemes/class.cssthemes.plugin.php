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
$PluginInfo['CssThemes'] = array(
   'Description' => 'The Css Theme plugin caches css files and allows the colors to be themed in the application.',
   'Version' => '1.0',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'SettingsUrl' => '/dashboard/plugin/cssthemes', // Url of the plugin's settings page.
   'SettingsPermission' => 'Garden.Themes.Manage', // The permission required to view the SettingsUrl.
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://toddburry.com'
);

$tmp = Gdn::FactoryOverwrite(TRUE);
Gdn::FactoryInstall('CssCacher', 'Gdn_CssThemes', __FILE__);
Gdn::FactoryOverwrite($tmp);
unset($tmp);


class CssThemes extends Gdn_Plugin {
	/// Constants ///
	/**
	 * The Regex to capture themeable colors.
	 *
	 * This will capture a css color in four groups:
	 * (Color)(CommentStart)(Name)(CommentEnd)
	 *
	 * @var string
	 */
	const RegEx = '/(#[0-9a-fA-F]{3,6})(\s*\/\*\s*)([^\*]*?)(\s*\*\/)/';
	const RegEx2 = '/#([0-9a-fA-F]{3,6})/';
	const UrlRegEx = '/(url\s*\([\'"]?)([\w\.]+?)(\.\w+)([\'"]?\s*\)\s*)(\/\*\s*NoFollow\s*\*\/\s*)?/';
	
	/// Properties ///
	
	public $MissingSettings = array();
	
	protected $_OrignialPath;
	protected $_AppName;
	
	public $ThemeSettings = NULL;
	
	/// Methods ///
	
	public function ApplyTheme($OriginalPath, $CachePath, $InsertNames = TRUE) {		
		// Get the theme settings.
		$SQL = Gdn::SQL();
		if(is_null($this->ThemeSettings)) {
			$Data = $SQL->Get('ThemeSetting')->ResultArray();
			$Data = ConsolidateArrayValuesByKey($Data, 'Name', 'Setting', '');
			$this->ThemeSettings = $Data;
		}
		
		$Css = file_get_contents($OriginalPath);
		// Process the urls.
		$Css = preg_replace_callback(self::UrlRegEx, array($this, '_ApplyUrl'), $Css);
		
		// Go through the css and replace its colors with the theme colors.
		$Css = preg_replace_callback(self::RegEx, array($this, '_ApplyThemeSetting'), $Css);
		
		// Insert the missing settings into the database.
		if($InsertNames) {
			foreach($this->MissingSettings as $Name => $Setting) {
				$SQL->Insert('ThemeSetting', array('Name' => $Name, 'Setting' => $Setting));
			}
			$this->MissingSettings = array();
		}
		
		// Save the theme to the cache path.
		file_put_contents($CachePath, $Css);
		return $CachePath;
	}
	
	protected function _ApplyThemeSetting($Match) {
		$Setting = $Match[1];
		$Name = $Match[3];
		
		if(array_key_exists($Name, $this->ThemeSettings)) {
			$Setting = $this->ThemeSettings[$Name];
		} else {
			$this->ThemeSettings[$Name] = $Setting;
			$this->MissingSettings[$Name] = $Setting;
		}
		
		$Result = $Setting.$Match[2].$Name.$Match[4];
		
		return $Result;
	}
	
	protected function _ApplyImport($Match) {
		$NoFollow = ArrayValue(4, $Match);
		$Url = $Match[2];
		
		if($NoFollow !== FALSE) {
			// Don't apply the theme to this import.
			$OriginalAssetPath = str_replace(array(PATH_ROOT, DS), array('', '/'), $this->_OrignialPath);
			$Url = Asset(CombinePaths(array(dirname($OriginalAssetPath), $Url), '/'));
		} else {
			// Also parse the import.
			$OrignalPath = $this->_OrignialPath;
			$ImportPath = CombinePaths(array(dirname($OrignalPath), $Url));
			$Url = $this->Get($ImportPath, $this->_AppName);
			$Url = str_replace(array(PATH_ROOT, DS), array('', '/'), $Url);
			$Url = Asset($Url);
			
			$this->_OrignalPath = $OrignalPath;
		}
		
		
		$Result = $Match[1].$Url.$Match[3];
		return $Result;
	}
	
	protected function _ApplyUrl($Match) {
		$NoFollow = ArrayValue(5, $Match);
		$Url = $Match[2];
		$Extension = $Match[3];
		
		if($NoFollow !== FALSE || strcasecmp($Extension, '.css') != 0) {
			// Don't apply the theme to this import.
			$OriginalAssetPath = str_replace(array(PATH_ROOT, DS), array('', '/'), $this->_OrignialPath);
			$Url = Asset(CombinePaths(array(dirname($OriginalAssetPath), $Url.$Extension), '/'));
		} else {
			// Cache the css too.
			$OrignalPath = $this->_OrignialPath;
			$ImportPath = CombinePaths(array(dirname($OrignalPath), $Url.$Extension));
			$Url = $this->Get($ImportPath, $this->_AppName);
			$Url = str_replace(array(PATH_ROOT, DS), array('', '/'), $Url);
			$Url = Asset($Url);
			
			$this->_OrignalPath = $OrignalPath;
		}
		
		$Result = $Match[1].$Url.$Match[4];
		return $Result;
	}
	
	public function Get($OriginalPath, $AppName) {
		if(!file_exists($OriginalPath))
			return FALSE;
		
		$this->_OrignialPath = $OriginalPath;
		$this->_AppName = $AppName;
		
		$Result = $OriginalPath;
		
		$Filename = basename($OriginalPath);
		$CachePath = PATH_CACHE.DS.'css'.DS.$AppName.'_'.$Filename;
		
		if(!file_exists($CachePath) || filemtime($OriginalPath) > filemtime($CachePath)) {
			$Css = file_get_contents($OriginalPath);
			
			$Result = $this->ApplyTheme($OriginalPath, $CachePath);
		} else {
			$Result = $CachePath;
		}
	
		return $Result;
	}
	
	public function GetNames($Css, $InsertNames = FALSE) {
		$Result = array();
		
		if(preg_match_all(self::RegEx, $Css, $Matches)) {
			foreach($Matches as $Match) {
				$Result[$Match[1]] = $Match[0];
			}
		}
		// Insert all of the names into the database.
		if(count($Result) > 0) {
			$SQL = Gdn::SQL();
			// Get the existing names.
			$Insert = $Result;
			
			
			// Insert the necessary settings.
			if($InsertNames) {
				foreach($Insert as $Name => $Setting) {
					$SQL->Insert('ThemeSetting', array('Name' => $Name, 'Setting' => $Setting));
				}
			}
		}
		
		return $Result;
	}
	
	public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
		$Menu->AddLink('Add-ons', 'Colors', 'plugin/cssthemes', 'Garden.Themes.Manage');
	}
	
	public function PluginController_Colors_Create($Sender) {
		$Sender->Form = Gdn::Factory('Form');
		
		$this->Colors = array();
		
		$this->ParseCss(PATH_APPLICATIONS);
		//$this->ParseCss(PATH_THEMES);
		
		asort($this->Colors);
		$Sender->Colors = $this->Colors;
		
		// Add the javascript & css.
		//$Sender->Head->AddScript('/plugins/cssthemes/colorpicker.js');
		//$Sender->Head->AddScript('/plugins/cssthemes/cssthemes.js');
		$Sender->Head->AddCss('/plugins/cssthemes/colorpicker.css');
		$Sender->Head->AddCss('/plugins/cssthemes/cssthemes.css');
		
		$Sender->View = $this->GetView('colors.php');
		$Sender->Render();
	}
	
	public function ParseCss($Path) {
		// Look for all of the css files in the path.
		$CssPaths = glob($Path.DS.'*.css');
		if($CssPaths) {
			foreach($CssPaths as $CssPath) {
				//echo $CssPath, "<br />\n";
				$Css = file_get_contents($CssPath);
				// Process the urls.
				//$Css = preg_replace_callback(self::UrlRegEx, array($this, '_ApplyUrl'), $Css);
		
				// Go through the css and replace its colors with the theme colors.
				$Css = preg_replace_callback(self::RegEx2, array($this, 'GetColors'), $Css);
		
			}
		}
		
		// Look for all of the subdirectories.
		$Paths = glob($Path.DS.'*', GLOB_ONLYDIR);
		if($Paths) {
			foreach($Paths as $Path) {
				if(in_array(strrchr($Path, DS), array(DS.'vforg', DS.'vfcom')))
					continue;
				$this->ParseCss($Path);
			}
		}
	}
	
	public function RGB($Color) {
		return array(hexdec(substr($Color, 0, 2)), hexdec(substr($Color, 2, 2)), hexdec(substr($Color, 4, 2)));
	}
	
	public function GetColors($Match) {
		$Color = strtolower($Match[1]);
		if(strlen($Color) == 3)
			$Color = str_repeat(substr($Color, 0, 1), 2).str_repeat(substr($Color, 1, 1), 2).str_repeat(substr($Color, 2, 1), 2);
		
		list($H, $S, $V) = $this->RGB2HSB(hexdec(substr($Color, 0, 2)), hexdec(substr($Color, 2, 2)), hexdec(substr($Color, 4, 2)));
		
		if($S < .2) {
			$S = 0;
			$H = 1000;
		}
		$H2 = $H / 72.0;
		
		$HSV = sprintf('%04d,%04d,%04d', round($H2), $V * 1000, $S * 1000);
		
		$this->Colors[$Color] = $HSV;
		
		return implode($Match);
	}
	
	function RGB2HSB($R, $G = NULL, $B = NULL) {
		if(is_null($G)) {
			list($R, $G, $B) = (array)$R;
		}
		
		$R /= 255;
		$G /= 255;
		$B /= 255;
		
		$H = $S = $V = 0;
		$Min = min($R, $G, $B);
		$Max = max($R, $G, $B);
		
		$V = $Max;
		if($V == 0)
			return array(1000, $S, $V);
		
		$R /= $V;
		$G /= $V;
		$B /= $V;
		$Min = min($R, $G, $B);
		$Max = max($R, $G, $B);
		
		$S = $Max - $Min;
		if($S == 0)
			return array(1000, $S, $V);
			
		$R = ($R - $Min) / ($Max - $Min);
		$G = ($G - $Min) / ($Max - $Min);
		$B = ($B - $Min) / ($Max - $Min);
			
		if($Max == $R) {
			$H = 60 * ($G - $B);
			if($H < 0) $H += 360;
		} elseif($Max == $G)
			$H = 120 + 60 * ($B - $R);
		else
			$H = 240 + 60 * ($R - $G);
		
		return array($H, $S, $V);
	}
	
	/**
	 * @package $Sender Gdn_Controller
	 */
	public function PluginController_CssThemes_Create($Sender) {
		$Sender->Form = Gdn::Factory('Form');
		$Model = new Gdn_Model('ThemeSetting');
		$Sender->Form->SetModel($Model);
		
		if($Sender->Form->AuthenticatedPostBack() === FALSE) {
			// Grab the colors.
			$Data = $Model->Get();
			//$Data = ConsolidateArrayValuesByKey($Data->ResultArray(), 'Name', 'Setting');
			$Sender->SetData('ThemeSettings', $Data->ResultArray());
			//$Sender->Form->SetData($Data);
		} else {
			$Data = $Sender->Form->FormDataSet();
			
			// Update the database.
			$SQL = Gdn::SQL();
			foreach($Data as $Row) {
				$SQL->Put(
					'ThemeSetting',
					array('Setting' => $Row['Setting']),
					array('Name' => $Row['Name']));
			}
			
			// Clear out the css cache.
			$Files = SafeGlob(PATH_CACHE.DS.'css'.DS.'*.css');
			foreach($Files as $File) {
				unlink($File);
			}
			
			$Sender->SetData('ThemeSettings', $Data);
			$Sender->StatusMessage = T('Your changes have been saved.');
		}
		
		// Add the javascript & css.
		$Sender->Head->AddScript('/plugins/cssthemes/colorpicker.js');
		$Sender->Head->AddScript('/plugins/cssthemes/cssthemes.js');
		$Sender->Head->AddCss('/plugins/cssthemes/colorpicker.css');
		$Sender->Head->AddCss('/plugins/cssthemes/cssthemes.css');
		
		// Add the side module.
      $Sender->AddSideMenu('/plugin/cssthemes');
		
		$Sender->View = $this->GetView('cssthemes.php');
		$Sender->Render();
	}
	
	public function Setup() {
		if (!file_exists(PATH_CACHE.DS.'css')) mkdir(PATH_CACHE.DS.'css');
		
		// Setup the theme table.
		$St = Gdn::Structure();
		$St->Table('bThemeSetting')
			->Column('Name', 'varchar(50)', FALSE, 'primary')
			->Column('Setting', 'varchar(50)')
			->Set(FALSE, FALSE);
			
		// Insert default values.
		$St->Database->Query('insert '.$St->Database->DatabasePrefix.'bThemeSetting (Name, Setting) values '.
		"('Banner Background Color', '#44c7f4'),
		('Banner Font Color', '#fff'),
		('Banner Font Shadow Color', '#30ACD6'),
		('Banner Hover Font Color', '#f3fcff'),
		('Body Alt Background Color', '#f8f8f8'),
		('Body Background Color', '#ffffff'),
		('Body Default Font Color', '#000'),
		('Body Heading Font Color', '#000'),
		('Body Hover Font Color', '#ff0084'),
		('Body Line Color', '#eee'),
		('Body Link Font Color', '#2786c2'),
		('Body Subheading Font Color', '#6C6C6C'),
		('Body Text Font Color', '#555'),
		('Discussion My Background Color', '#F5FCFF'),
		('Discussion New Background Color', '#ffd'),
		('Menu Background Color', '#44c7f4'),
		('Menu Font Color', '#fff'),
		('Menu Hover Background Color', '#28bcef'),
		('Menu Hover Font Color', '#fff'),
		('Meta Font Color', '#2b2d33'),
		('Meta Label Font Color', '#80828c'),
		('Panel Background Color', '#E9F9FF'),
		('Panel Font Color', '#2786C2'),
		('Panel Hover Font Color', '#e9f9ff'),
		('Panel Inlay Background Color', '#f0fbff'),
		('Panel Inlay Border Color', '#caf0fe'),
		('Panel Inlay Font Color', '#0766a2'),
		('Panel Selected Background Color', '#fff'),
		('Panel Selected Font Color', '#ff0084')");
	}
	
	public function CleanUp() {
	   Gdn::Structure()->Table('bThemeSetting')->Drop();
	}
}