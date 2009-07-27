<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

function __autoload($ClassName) {
   // echo $ClassName;
   if (class_exists('HTMLPurifier_Bootstrap', FALSE) && HTMLPurifier_Bootstrap::autoload($ClassName))
      return true;
   if(!class_exists('Gdn_FileSystem', FALSE))
      return false;
   
   if(substr($ClassName, 0, 4) === 'Gdn_')
      $LibraryFileName = 'class.' . strtolower(substr($ClassName, 4)) . '.php';
   else
      $LibraryFileName = 'class.' . strtolower($ClassName) . '.php';
   
   if(!is_null($ApplicationManager = Gdn::Factory('ApplicationManager')))
      $ApplicationWhiteList = Gdn::Factory('ApplicationManager')->EnabledApplicationFolders();
   else
      $ApplicationWhiteList = NULL;
   
   $LibraryPath = FALSE;

   // If this is a model, look in the models folder(s)
   if (strtolower(substr($ClassName, -5)) == 'model')
      $LibraryPath = Gdn_FileSystem::FindByMapping('library_mappings.php', 'Library', PATH_APPLICATIONS, $ApplicationWhiteList, 'models' . DS . $LibraryFileName);

   if ($LibraryPath === FALSE)
      $LibraryPath = Gdn_FileSystem::FindByMapping(
         'library_mappings.php',
         'Library',
         PATH_LIBRARY,
         array(
            'core',
            'database',
            'vendors'. DS . 'phpmailer',
            'vendors' . DS . 'htmlpurifier'
         ),
         $LibraryFileName
      );

   // If it still hasn't been found, check for modules
   if ($LibraryPath === FALSE)
      $LibraryPath = Gdn_FileSystem::FindByMapping('library_mappings.php', 'Library', PATH_APPLICATIONS, $ApplicationWhiteList, 'modules' . DS . $LibraryFileName);

   if ($LibraryPath !== FALSE)
      include_once($LibraryPath);
}

// Functions relating to data/variable types and type casting
if (!function_exists('RemoveKeyFromArray')) {
   function RemoveKeyFromArray($Array, $Key) {
      if (!is_array($Key))
         $Key = array($Key);

      $Count = count($Key);
      for ($i = 0; $i < $Count; $i++) {
         $KeyIndex = array_keys(array_keys($Array), $Key[$i]);
         if (count($KeyIndex) > 0) array_splice($Array, $KeyIndex[0], 1);
      }
      return $Array;
   }
}
if (!function_exists('Img')) {
   function Img($Image, $Attributes = '') {
      if ($Attributes == '')
         $Attributes = array();

      if (substr($Image, 0, 7) != 'http://' && $Image != '')
         $Image = Url($Image);

      return '<img src="'.$Image.'"'.Attribute($Attributes).' />';
   }
}
if (!function_exists('Anchor')) {
   function Anchor($Text, $Destination = '', $CssClass = '', $Attributes = '', $ForceAnchor = FALSE) {
      if (!is_array($CssClass) && $CssClass != '')
         $CssClass = array('class' => $CssClass);

      if ($Destination == '' && $ForceAnchor === FALSE)
         return $Text;
      
      if ($Attributes == '')
         $Attributes = array();

      if (substr($Destination, 0, 7) != 'http://' && ($Destination != '' || $ForceAnchor === FALSE))
         $Destination = Url($Destination);

      return '<a href="'.$Destination.'"'.Attribute($CssClass).Attribute($Attributes).'>'.$Text.'</a>';
   }
}
if (!function_exists('UserAnchor')) {
   function UserAnchor($User, $CssClass = '') {
      if ($CssClass != '')
         $CssClass = ' class="'.$CssClass.'"';

      $Name = is_object($User) ? $User->Name : $User;
      return '<a href="'.Url('/profile/'.urlencode($Name)).'"'.$CssClass.'>'.$Name.'</a>';
   }
}
if (!function_exists('UserPhoto')) {
   function UserPhoto($User, $Photo, $CssClass = '') {
      $Name = is_object($User) ? $User->Name : $User;
      if ($Photo != '') {
         return '<a href="'.Url('/profile/'.urlencode($Name)).'"'.$CssClass.'><img src="'.Url('uploads/n'.$Photo).'" /></a>';
      } else {
         return ''; // Anchor($Name, '/profile/'.Format::Url($Name), $CssClass);
      }
   }
}
if (!function_exists('Plural')) {
   function Plural($Number, $Singular, $Plural) {
      return Gdn::Translate($Number == 1 ? $Singular : $Plural);
   }
}

if (!function_exists('Translate')) {
   /**
	 * Translates a code into the selected locale's definition.
	 *
	 * @param string $Code The code related to the language-specific definition.
	 * @param string $Default The default value to be displayed if the translation code is not found.
	 * @return string The translated string or $Code if there is no value in $Default.
	 * @deprecated
	 * @see Gdn::Translate()
	 */
   function Translate($Code, $Default = '') {
      $Result = Gdn::Translate($Code, $Default);
      return $Result;
   }
}

//if (!function_exists('Config')) {
//   /**
//    * Gets a configuration setting.
//    * @deprecated
//    * @see Gdn::Config()
//    */
//   function Config($Name, $Default = FALSE, $Group = '') {
//      if(strlen($Group) > 0 && $Group != 'Configuration')
//         $Result = Gdn::Config($Group . '.' . $Name, $Default);
//      else
//         $Result = Gdn::Config($Name, $Default);
//      
//      return $Result;
//   }
//}

if (!function_exists('MergeArrays')) {
   /**
    * Merge two associative arrays into a single array.
    *
    * @param array The "dominant" array, who's values will be chosen over those of the subservient.
    * @param array The "subservient" array, who's values will be disregarded over those of the dominant.
    */
   function MergeArrays(&$Dominant, $Subservient) {
      foreach ($Subservient as $Key => $Value) {
         if (!array_key_exists($Key, $Dominant)) {
            // Add the key from the subservient array if it doesn't exist in the
            // dominant array.
            $Dominant[$Key] = $Value;
         } else {
            // If the key already exists in the dominant array, only continue if
            // both values are also arrays - because we don't want to overwrite
            // values in the dominant array with ones from the subservient array.
            if (is_array($Dominant[$Key]) && is_array($Value)) {
               $Dominant[$Key] = MergeArrays($Dominant[$Key], $Value);
            }
         }
      }
      return $Dominant;
   }
}

if (!function_exists('CombinePaths')) {
   // filesystem input/output functions that deal with loading libraries, application paths, etc.
   function CombinePaths($Paths, $Delimiter = DS) {
      if (is_array($Paths)) {
         $MungedPath = implode($Delimiter, $Paths);
         $MungedPath = str_replace(array($Delimiter.$Delimiter.$Delimiter, $Delimiter.$Delimiter), array($Delimiter, $Delimiter), $MungedPath);
         return str_replace('http:/', 'http://', $MungedPath);
         /*
         $Count = count($Paths);
         for ($i = 0; $i < $Count; ++$i) {
            $Path = trim($Paths[$i]);

            // Remove $Delimiter from the begining of each path portion
            $Len = strlen($Path);
            if ($Len > 0 && substr($Path, 0, 1) == $Delimiter) {
               $Path = substr($Path, 1);
               $Len--;
            }

            // Remove $Delimiter from the end of each path portion
            if ($Len > 0 && substr($Path, $Len - 1) == $Delimiter) {
               $Path = substr($Path, 0, $Len - 1);
               $Len--;
            }

            $Paths[$i] = $Path;
         }
         return implode($Delimiter, $Paths);
         */
      } else {
         return $Paths;
      }
   }
}

if (!function_exists('ArrayValue')) {
   /**
    * Returns the value associated with the $Needle key in the $Haystack
    * associative array or FALSE if not found. This is a CASE-SENSITIVE search.
    *
    * @param string The key to look for in the $Haystack associative array.
    * @param array The associative array in which to search for the $Needle key.
    * @param string The default value to return if the requested value is not found. Default is FALSE.
    */
   function ArrayValue($Needle, $Haystack, $Default = FALSE) {
      $Return = $Default;
      if (is_array($Haystack) === TRUE && array_key_exists($Needle, $Haystack) === TRUE) {
         $Return = $Haystack[$Needle];
      }
      return $Return;
   }
}

if (!function_exists('ArrayValueI')) {
   /**
    * Returns the value associated with the $Needle key in the $Haystack
    * associative array or FALSE if not found. This is a CASE-INSENSITIVE
    * search.
    *
    * @param string The key to look for in the $Haystack associative array.
    * @param array The associative array in which to search for the $Needle key.
    * @param string The default value to return if the requested value is not found. Default is FALSE.
    */
   function ArrayValueI($Needle, $Haystack, $Default = FALSE) {
      $Return = $Default;
      if (is_array($Haystack)) {
         foreach ($Haystack as $Key => $Value) {
            if (strtolower($Needle) == strtolower($Key)) {
               $Return = $Value;
               break;
            }
         }
      }
      return $Return;
   }
}

if (!function_exists('InArrayI')) {
   /**
    * Case-insensitive version of php's native in_array function.
    */
   function InArrayI($Needle, $Haystack) {
      $Needle = strtolower($Needle);
      foreach ($Haystack as $Item) {
         if (strtolower($Item) == $Needle)
            return TRUE;
      }
      return FALSE;
   }
}

if(!function_exists('TrueStripSlashes')) {
   if(get_magic_quotes_gpc()) {
      function TrueStripSlashes($String) {
         return stripslashes($String);
      }
   } else {
      function TrueStripSlashes($String) {
         return $String;
      }
   }
}

// Takes a route and prepends the web root (expects "/controller/action/params" as $Destination)
if (!function_exists('Url')) {   
   function Url($Destination = '', $WithDomain = FALSE, $RemoveSyndication = FALSE) {
      // Cache the rewrite urls config setting in this object.
      static $RewriteUrls = NULL;
      if(is_null($RewriteUrls)) $RewriteUrls = ForceBool(Gdn::Config('Garden.RewriteUrls', FALSE));
      
      if (substr($Destination, 0, 7) == 'http://') {
         return $Destination;
      } else if ($Destination == '#' || $Destination == '') {
         if ($WithDomain)
            return Gdn_Url::Request(TRUE, TRUE, $RemoveSyndication).$Destination;
         else
            return '/'.Gdn_Url::Request(TRUE, FALSE, $RemoveSyndication).$Destination;
      } else {
         $Paths = array();
         if (!$WithDomain)
            $Paths[] = '/';
            
         $Paths[] = Gdn_Url::WebRoot($WithDomain);
         if (!$RewriteUrls)
            $Paths[] = 'index.php';
            
         $Paths[] = $Destination;
         return CombinePaths($Paths, '/');
      }
   }
}

// Takes the path to an asset (image, js file, css file, etc) and prepends the webroot.
if (!function_exists('Asset')) {
   function Asset($Destination = '', $WithDomain = FALSE) {
      if (substr($Destination, 0, 7) == 'http://') {
         return $Destination;
      } else {
         return CombinePaths(array('/', Gdn_Url::WebRoot($WithDomain), $Destination), '/');
      }
   }
}


if (!function_exists('ForceBool')) {
   function ForceBool($Value, $DefaultValue = FALSE, $True = TRUE, $False = FALSE) {
      if (is_bool($Value)) {
         return $Value ? $True : $False;
      } else if (is_numeric($Value)) {
         return $Value == 0 ? $False : $True;
      } else if (is_string($Value)) {
         return strtolower($Value) == 'true' ? $True : $False;
      } else {
         return $DefaultValue;
      }
   }
}

if (!function_exists('Redirect')) {
   function Redirect($Destination) {
      @ob_end_clean();
      header("location: ".Url($Destination));
      // Close any db connections before exit
      $Database = Gdn::Database();
      $Database->CloseConnection();
      // Exit
      exit();
   }
}

if (!function_exists('CalculateNumberOfPages')) {
   /**
    * Based on the total number of items and the number of items per page,
    * this function will calculate how many pages there are.
    * Returns the number of pages available
    */
   function CalculateNumberOfPages($ItemCount, $ItemsPerPage) {
      $TmpCount = ($ItemCount/$ItemsPerPage);
      $RoundedCount = intval($TmpCount);
      $PageCount = 0;
      if ($TmpCount > 1) {
         if ($TmpCount > $RoundedCount) {
            $PageCount = $RoundedCount + 1;
         } else {
            $PageCount = $RoundedCount;
         }
      } else {
         $PageCount = 1;
      }
      return $PageCount;
   }
}

if (!function_exists('GetPostValue')) {
   // Return the value for $FieldName from the $_POST collection
   function GetPostValue($FieldName, $Default = FALSE) {
      return array_key_exists($FieldName, $_POST) ? $_POST[$FieldName] : $Default;
   }
}

if (!function_exists('GetIncomingValue')) {
   function GetIncomingValue($FieldName, $Default = FALSE) {
      if (array_key_exists($FieldName, $_POST) === TRUE) {
         return $_POST[$FieldName];
      } else if (array_key_exists($FieldName, $_GET) === TRUE) {
         return $_GET[$FieldName];
      } else {
         return $Default;
      }
   }
}

if (!function_exists('ArrayValuesToKeys')) {
   // Takes an array's values and applies them to a new array as both the keys and
   // values.
   function ArrayValuesToKeys($Array) {
      return array_combine(array_values($Array), $Array);
   }
}

if (!function_exists('ConsolidateArrayValuesByKey')) {
   /**
    * Takes an array of associative arrays (ie. a dataset array), a $Key, and
    * merges all of the values for that key into a single array, returning it.
    */
   function ConsolidateArrayValuesByKey($Array, $Key, $ValueKey = '', $DefaultValue = NULL) {
      $Return = array();
      foreach ($Array as $Index => $AssociativeArray) {
         if (array_key_exists($Key, $AssociativeArray)) {
            if($ValueKey === '') {
               $Return[] = $AssociativeArray[$Key];
            } elseif (array_key_exists($ValueKey, $AssociativeArray)) {
               $Return[$AssociativeArray[$Key]] = $AssociativeArray[$ValueKey];
            } else {
               $Return[$AssociativeArray[$Key]] = $DefaultValue;
            }
         }
      }
      return $Return;
   }
}

if (!function_exists('Now')) {
   function Now() {
      list($usec, $sec) = explode(" ", microtime());
      return ((float)$usec + (float)$sec);
   }
}

if (!function_exists('ArrayInArray')) {
   /**
    * Searches Haystack array for items in Needle array. If FullMatch is TRUE,
    * all items in Needle must also be in Haystack. If FullMatch is FALSE, only
    * one-or-more items in Needle must be in Haystack.
    *
    * @param array The array containing items to match to Haystack.
    * @param array The array to search in for Needle items.
    * @param bool Should all items in Needle be found in Haystack to return TRUE?
    */
   function ArrayInArray($Needle, $Haystack, $FullMatch = TRUE) {
      $Count = count($Needle);
      $Return = $FullMatch ? TRUE : FALSE;
      for ($i = 0; $i < $Count; ++$i) {
         if ($FullMatch === TRUE) {
            if (in_array($Needle[$i], $Haystack) === FALSE)
               $Return = FALSE;
         } else {
            if (in_array($Needle[$i], $Haystack) === TRUE) {
               $Return = TRUE;
               break;
            }
         }
      }
      return $Return;
   }
}

if (!function_exists('GetConnectionString')) {
   function GetConnectionString($DatabaseName, $HostName = 'localhost', $ServerType = 'mysql') {
      $HostName = explode(':', $HostName);
      $Port = count($HostName) == 2 ? $HostName[1] : '';
      $HostName = $HostName[0];
      $String = $ServerType.':host='.$HostName;
      if ($Port != '')
         $String .= ';port='.$Port;
      return $String .= ';dbname='.$DatabaseName;
   }
}

if (!function_exists('StringIsNullOrEmpty')) {
   function StringIsNullOrEmpty($String) {
      return is_null($String) === TRUE || (is_string($String) && trim($String) == '');
   }
}

if (!function_exists('RandomString')) {
   function RandomString($Length) {
      $Characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
      $String = '' ;
      for ($i = 0; $i < $Length; ++$i) {
        $Offset = rand() % 35;
        $String .= substr($Characters, $Offset, 1);
      }
      return $String;
   }
}
if (!function_exists('SliceString')) {
   function SliceString($String, $Length) {
      if (strlen($String) > $Length) {
         $Return = substr(trim($String), 0, $Length);
         return substr($Return, 0, strlen($Return) - strpos(strrev($Return), ' ')) . '...';
      } else {
         return $String;
      }
   }
}

if (!function_exists('AddActivity')) {
   /**
    * A convenience function that allows adding to the activity table with a single line.
    */
   function AddActivity($ActivityUserID, $ActivityType, $Story = '', $RegardingUserID = '', $Route = '') {
      $ActivityModel = new Gdn_ActivityModel();
      $ActivityModel->Add($ActivityUserID, $ActivityType, $Story, $RegardingUserID, '', $Route);
   }
}

if (!function_exists('Attribute')) {
   function Attribute($Name, $Value = '') {
      $Return = '';
      if (!is_array($Name)) {
         $Name = array($Name => $Value);
      }
      foreach ($Name as $Attribute => $Val) {
         if ($Val != '') {
            $Return .= ' '.$Attribute.'="'.$Val.'"';
         }
      }
      return $Return;
   }
}

if (!function_exists('GetApplicationMenus')) {
   function GetApplicationMenus(&$Menu) {
      $ApplicationManager = new Gdn_ApplicationManager();
      $EnabledApplications = $ApplicationManager->EnabledApplications();

      // Get all settings pages from the specified applications:
      foreach ($EnabledApplications as $ApplicationName => $ApplicationFolder) {
         $AppController = $ApplicationName.'Controller';
         // Attempt to include the app controller if it isn't already loaded
         $ControllerPath = PATH_APPLICATIONS . DS . $ApplicationFolder . DS . 'controllers' . DS . 'appcontroller.php';
         if (!class_exists($AppController) && file_exists($ControllerPath))
            include_once($ControllerPath);

         // Attempt to instantiate the app controller to get the settings pages
         if (class_exists($AppController)) {
            $AppController = new $AppController();
            if (method_exists($AppController, 'GetSettingsPages'))
               $AppController->GetSettingsPages($Menu);

         }
      }

      // Get all settings pages from plugins
      $Plugins = array();
      $PluginManager = Gdn::Factory('PluginManager');
      $PluginMenuAdded = FALSE;
      foreach ($PluginManager->EnabledPlugins as $PluginName => $PluginInfo) {
         if (array_key_exists('SettingsUrl', $PluginInfo)) {
            if (!$PluginMenuAdded) {
               $Menu->AddItem('Plugins', 'Plugins');
               $PluginMenuAdded = TRUE;
            }
            // TODO: DOES A PERMISSION NEED TO APPLY TO THE PLUGIN SETTINGS URL?
            $Menu->AddLink('Plugins', $PluginName, ArrayValue('SettingsUrl', $PluginInfo, ''));
         }
      }
   }
}

if (!function_exists('CheckRequirements')) {
   function CheckRequirements($ItemName, $RequiredItems, $EnabledItems, $RequiredItemTypeCode) {
      // 1. Make sure that $RequiredItems are present
      if (is_array($RequiredItems)) {
         foreach ($RequiredItems as $RequiredItemName => $RequiredVersion) {
            if (array_key_exists($RequiredItemName, $EnabledItems) === FALSE) {
               throw new Exception(
                  sprintf(
                     Gdn::Translate('%1$s requires the %2$s %3$s version %4$s.'),
                     $ItemName,
                     $RequiredItemName,
                     $RequiredItemTypeCode,
                     $RequiredVersion
                  )
               );
            } else if (StringIsNullOrEmpty($RequiredVersion) === FALSE) {
                // If the item exists and is enabled, check the version
               $EnabledVersion = ArrayValue('Version', ArrayValue($RequiredItemName, $EnabledItems, array()), '');
               if ($EnabledVersion !== $RequiredVersion) {
                  // Check for version ranges (<, <=, >, >=)
                  $Matches = FALSE;
                  preg_match_all('/(>|>=|<|<=){1}([\d\.]+)/', $RequiredVersion, $Matches);
                  if (is_array($Matches) && count($Matches) == 3 && count($Matches[1]) > 0) {
                     // The matches array should contain a three parts:
                     /*
                      eg. The following $RequiredVersion string:
                        >1.33<=4.1
                     would result in:
                        Array (
                              [0] => Array
                                  (
                                      [0] => >1.33
                                      [1] => <=4.1
                                  )
                              [1] => Array
                                  (
                                      [0] => >
                                      [1] => <=
                                  )
                              [2] => Array
                                  (
                                      [0] => 1.33
                                      [1] => 4.1
                                  )
                          )
                     */

                     $Operators = $Matches[1];
                     $Versions = $Matches[2];
                     $Count = count($Operators);
                     for ($i = 0; $i < $Count; ++$i) {
                        $Operator = $Operators[$i];
                        $MatchVersion = $Versions[$i];
                        if (!version_compare($EnabledVersion, $MatchVersion, $Operator)) {
                           throw new Exception(
                              sprintf(
                                 Gdn::Translate('%1$s requires the %2$s %3$s version %4$s %5$s'),
                                 $ItemName,
                                 $RequiredItemName,
                                 $RequiredItemTypeCode,
                                 $Operator,
                                 $MatchVersion
                              )
                           );
                        }
                     }
                  } else if ($RequiredVersion != '*' && $RequiredVersion != '') {
                     throw new Exception(
                        sprintf(
                           Gdn::Translate('%1$s requires the %2$s %3$s version %4$s'),
                           $ItemName,
                           $RequiredItemName,
                           $RequiredItemTypeCode,
                           $RequiredVersion
                        )
                     );
                  }
               }
            }
         }
      }
   }
}

if (!function_exists('RemoveQuoteSlashes')) {
 	function RemoveQuoteSlashes($String) {
		return str_replace("\\\"", '"', $String);
	}
}

if (!function_exists('GetMentions')) {
   function GetMentions($String) {
      $Mentions = array();
      
      // This one grabs mentions that start at the beginning of $String
      preg_match(
         '/^(@([\d\w_]{1,20}))/si',
         $String,
         $Matches
      );
      if (count($Matches) == 3)
         $Mentions[] = $Matches[2];
      
      // This one handles all other mentions
      preg_match_all(
         '/([\s]+)(@([\d\w_]{3,20}))/si',
         $String,
         $Matches
      );
      if (count($Matches) == 4) {
         for ($i = 0; $i < count($Matches[3]); ++$i) {
            $Mentions[] = $Matches[3][$i];
         }
      }
      return array_unique($Mentions);
   }
}

// Needed this to fix a bug: http://github.com/lussumo/Garden/issues/closed#issue/3/comment/19938
if (!function_exists('getallheaders')) {
   function getallheaders() {
      foreach($_SERVER as $name => $value)
          if(substr($name, 0, 5) == 'HTTP_')
              $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
      return $headers;
   }
}

if (!function_exists('ArrayCombine')) {
   // PHP has a limitation that doesn't allow array_combine to work if either of
   // the arrays are empty
   function ArrayCombine($Array1, $Array2) {
      if (!is_array($Array1))
         $Array1 = array();
         
      if (!is_array($Array2))
         $Array2 = array();
         
      if (count($Array1) > 0 && count($Array2) > 0)
         return array_combine($Array1, $Array2);
      elseif (count($Array1) == 0)
         return $Array2;
      else
         return $Array1;
   }
}

if (!function_exists('filter_input')) {
   if (!defined('INPUT_GET')) define('INPUT_GET', 'INPUT_GET');
   if (!defined('INPUT_POST')) define('INPUT_POST', 'INPUT_POST');
   if (!defined('FILTER_SANITIZE_STRING')) define('FILTER_SANITIZE_STRING', 'FILTER_SANITIZE_STRING');
   if (!defined('FILTER_REQUIRE_ARRAY')) define('FILTER_REQUIRE_ARRAY', 'FILTER_REQUIRE_ARRAY');
   function filter_input($InputType, $FieldName, $Filter = '', $Options = '') {
      $Collection = $InputType == INPUT_GET ? $_GET : $_POST;
      $Value = ArrayValue($FieldName, $Collection, '');
      if (get_magic_quotes_gpc()) {
         if (is_array($Value)) {
            $Count = count($Value);
            for ($i = 0; $i < $Count; ++$i) {
               $Value[$i] = stripslashes($Value[$i]);
            }
         } else {
            $Value = stripslashes($Value);
         }
      }
      return $Value;     
   }
}

if (!function_exists('json_encode')) {
   require_once PATH_LIBRARY . DS . 'vendors' . DS . 'JSON' . DS . 'JSON.php';
   
   function json_encode($arg) {
      global $services_json;
      if (!isset($services_json)) {
         $services_json = new Services_JSON();
      }
      return $services_json->encode($arg);
   }
   
   function json_decode($arg, $assoc = FALSE) {
      global $services_json;
      if (!isset($services_json)) {
         $services_json = new Services_JSON();
      }
      $obj = $services_json->decode($arg);
      if ($assoc)
         return Format::ObjectAsArray($obj);
      else
         return $obj;
   }
}
if (!function_exists('array_fill_keys')) {
   function array_fill_keys($Keys, $Val) {
      return array_combine($Keys,array_fill(0,count($Keys),$Val));
   }
}

if (!function_exists('parse_ini_string')) {
   function parse_ini_string ($Ini) {
      $Lines = split("\n", $Ini);
      $Result = array();
      foreach($Lines as $Line) {
         $Parts = split('=', $Line, 2);
         if(count($Parts) == 1) {
            $Result[trim($Parts[0])] = '';
         } elseif(count($Parts) >= 2) {
            $Result[trim($Parts[0])] = trim($Parts[1]);
         }
      }
      return $Result;
   }
}