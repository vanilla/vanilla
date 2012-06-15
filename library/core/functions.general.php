<?php if (!defined('APPLICATION')) exit();

/**
 * General functions
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

include PATH_LIBRARY.'/vendors/wordpress/functions.wordpress.php';

if (!function_exists('AbsoluteSource')) {
   /**
    * Takes a source path (ie. an image src from an html page), and an
    * associated URL (ie. the page that the image appears on), and returns the
    * absolute source (including url & protocol) path.
    * @param string $SrcPath The source path to make absolute (if not absolute already).
    * @param string $Url The full url to the page containing the src reference.
    * @return string Absolute source path.
    */
   function AbsoluteSource($SrcPath, $Url) {
      // If there is a scheme in the srcpath already, just return it.
      if (!is_null(parse_url($SrcPath, PHP_URL_SCHEME)))
         return $SrcPath;
      
      // Does SrcPath assume root?
      if (in_array(substr($SrcPath, 0, 1), array('/', '\\')))
         return parse_url($Url, PHP_URL_SCHEME)
         .'://'
         .parse_url($Url, PHP_URL_HOST)
         .$SrcPath;
   
      // Work with the path in the url & the provided src path to backtrace if necessary
      $UrlPathParts = explode('/', str_replace('\\', '/', parse_url($Url, PHP_URL_PATH)));
      $SrcParts = explode('/', str_replace('\\', '/', $SrcPath));
      $Result = array();
      foreach ($SrcParts as $Part) {
         if (!$Part || $Part == '.')
            continue;
         
         if ($Part == '..')
            array_pop($UrlPathParts);
         else
            $Result[] = $Part;
      }
      // Put it all together & return
      return parse_url($Url, PHP_URL_SCHEME)
         .'://'
         .parse_url($Url, PHP_URL_HOST)
         .'/'.implode('/', array_filter(array_merge($UrlPathParts, $Result)));
   }
}

if (!function_exists('AddActivity')) {
   /**
    * A convenience function that allows adding to the activity table with a single line.
    */
   function AddActivity($ActivityUserID, $ActivityType, $Story = '', $RegardingUserID = '', $Route = '', $SendEmail = '') {
      $ActivityModel = new ActivityModel();
      return $ActivityModel->Add($ActivityUserID, $ActivityType, $Story, $RegardingUserID, '', $Route, $SendEmail);
   }
}

if (!function_exists('ArrayCombine')) {
   /**
    * PHP's array_combine has a limitation that doesn't allow array_combine to
    * work if either of the arrays are empty.
    */
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
/*
 We now support PHP 5.2.0 - Which should make this declaration unnecessary.
if (!function_exists('array_fill_keys')) {
   function array_fill_keys($Keys, $Val) {
      return array_combine($Keys,array_fill(0,count($Keys),$Val));
   }
}
*/
if (!function_exists('ArrayHasValue')) {
   /**
    * Searches $Array (and all arrays it contains) for $Value.
    */ 
   function ArrayHasValue($Array, $Value) {
      if (in_array($Value, $Array)) {
         return TRUE;
      } else {
         foreach ($Array as $k => $v) {
            if (is_array($v) && ArrayHasValue($v, $Value) === TRUE) return TRUE;
         }
         return FALSE;
      }
   }
}

if (!function_exists('ArrayKeyExistsI')) {
   /**
    * Case-insensitive ArrayKeyExists search.
    */
   function ArrayKeyExistsI($Key, $Search) {
      if (is_array($Search)) {
         foreach ($Search as $k => $v) {
            if (strtolower($Key) == strtolower($k))
               return TRUE;
         }
      }
      return FALSE;
   }
}

if (!function_exists('ArrayInArray')) {
   /**
    * Searches Haystack array for items in Needle array. If FullMatch is TRUE,
    * all items in Needle must also be in Haystack. If FullMatch is FALSE, only
    * one-or-more items in Needle must be in Haystack.
    *
    * @param array $Needle The array containing items to match to Haystack.
    * @param array $Haystack The array to search in for Needle items.
    * @param bool $FullMatch Should all items in Needle be found in Haystack to return TRUE?
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

if (!function_exists('ArraySearchI')) {
   /**
    * Case-insensitive version of array_search.
    *
    * @param array $Value The value to find in array.
    * @param array $Search The array to search in for $Value.
    * @return mixed Key of $Value in the $Search array.
    */
   function ArraySearchI($Value, $Search) {
      return array_search(strtolower($Value), array_map('strtolower', $Search)); 
   }
}

if (!function_exists('ArrayTranslate')) {
   /**
    * Take all of the items specified in an array and make a new array with them specified by mappings.
    *
    *
    * @param array $Array The input array to translate.
    * @param array $Mappings The mappings to translate the array.
    * @return array
    */
   function ArrayTranslate($Array, $Mappings) {
      $Array = (array)$Array;
      $Result = array();
      foreach ($Mappings as $Index => $Value) {
         if (is_numeric($Index)) {
            $Key = $Value;
            $NewKey = $Value;
         } else {
            $Key = $Index;
            $NewKey = $Value;
         }
         if (isset($Array[$Key]))
            $Result[$NewKey] = $Array[$Key];
         else
            $Result[$NewKey] = NULL;
      }
      return $Result;
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
      $Result = GetValue($Needle, $Haystack, $Default);
		return $Result;
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

if (!function_exists('ArrayValuesToKeys')) {
   /** Takes an array's values and applies them to a new array as both the keys
    * and values.
    */
   function ArrayValuesToKeys($Array) {
      return array_combine(array_values($Array), $Array);
   }
}

if (!function_exists('Asset')) {
   /**
    * Takes the path to an asset (image, js file, css file, etc) and prepends the webroot.
    */
   function Asset($Destination = '', $WithDomain = FALSE, $AddVersion = FALSE) {
      $Destination = str_replace('\\', '/', $Destination);
      if (substr($Destination, 0, 7) == 'http://' || substr($Destination, 0, 8) == 'https://') {
         $Result = $Destination;
      } else {
         $Parts = array(Gdn_Url::WebRoot($WithDomain), $Destination);
         if (!$WithDomain)
            array_unshift($Parts, '/');
            
         $Result = CombinePaths($Parts, '/');
      }

      if ($AddVersion) {
         if (strpos($Result, '?') === FALSE)
            $Result .= '?';
         else
            $Result .= '&';

         // Figure out which version to put after the asset.
         $Version = APPLICATION_VERSION;
         if (preg_match('`^/([^/]+)/([^/]+)/`', $Destination, $Matches)) {
            $Type = $Matches[1];
            $Key = $Matches[2];
            static $ThemeVersion = NULL;

            switch ($Type) {
               case 'plugins':
                  $PluginInfo = Gdn::PluginManager()->GetPluginInfo($Key);
                  $Version = GetValue('Version', $PluginInfo, $Version);
                  break;
               case 'themes':
                  if ($ThemeVersion === NULL) {
                     $ThemeInfo = Gdn::ThemeManager()->GetThemeInfo(Theme());
                     if ($ThemeInfo !== FALSE) {
                        $ThemeVersion = GetValue('Version', $ThemeInfo, $Version);
                     } else {
                        $ThemeVersion = $Version;
                     }
                  }
                  $Version = $ThemeVersion;
                  break;
            }
         }

         $Result.= 'v='.urlencode($Version);
      }
      return $Result;
   }
}

if (!function_exists('Attribute')) {
   /**
    * Takes an attribute (or array of attributes) and formats them in
    * attribute="value" format.
    */
   function Attribute($Name, $ValueOrExclude = '') {
      $Return = '';
      if (!is_array($Name)) {
         $Name = array($Name => $ValueOrExclude);
         $Exclude = '';
      } else {
         $Exclude = $ValueOrExclude;
      }
      foreach ($Name as $Attribute => $Val) {
         if ($Exclude && StringBeginsWith($Attribute, $Exclude))
            continue;
         
         if ($Val != '' && $Attribute != 'Standard') {
            $Return .= ' '.$Attribute.'="'.htmlspecialchars($Val, ENT_COMPAT, 'UTF-8').'"';
         }
      }
      return $Return;
   }
}

if (!function_exists('C')) {
   /**
    * Retrieves a configuration setting.
    * @param string $Name The name of the configuration setting. Settings in different sections are seperated by a dot ('.')
    * @param mixed $Default The result to return if the configuration setting is not found.
    * @return mixed The configuration setting.
    * @see Gdn::Config()
    */
   function C($Name = FALSE, $Default = FALSE) {
      return Gdn::Config($Name, $Default);
   }
}

if (!function_exists('CTo')) {
   function CTo(&$Data, $Name, $Value) {
      $Name = explode('.', $Name);
      $LastKey = array_pop($Name);
      $Current =& $Data;
      
      foreach ($Name as $Key) {
         if (!isset($Current[$Key]))
            $Current[$Key] = array();
         
         $Current =& $Current[$Key];
      }
      $Current[$LastKey] = $Value;
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

if (!function_exists('ChangeBasename')) {
   /** Change the basename part of a filename for a given path.
    *
    * @param string $Path The path to alter.
    * @param string $NewBasename The new basename. A %s will be replaced by the old basename.
    * @return string
    */
   function ChangeBasename($Path, $NewBasename) {
      $NewBasename = str_replace('%s', '$2', $NewBasename);
      $Result = preg_replace('/^(.*\/)?(.*?)(\.[^.]+)$/', '$1'.$NewBasename.'$3', $Path);
      
      return $Result;
   }
}

// Smarty
if (!function_exists('CheckPermission')) {
   function CheckPermission($PermissionName) {
      $Result = Gdn::Session()->CheckPermission($PermissionName);
      return $Result;
   }
}

// Smarty sux
if (!function_exists('MultiCheckPermission')) {
   function MultiCheckPermission($PermissionName) {
      $Result = Gdn::Session()->CheckPermission($PermissionName, FALSE);
      return $Result;
   }
}

if (!function_exists('CheckRequirements')) {
   function CheckRequirements($ItemName, $RequiredItems, $EnabledItems, $RequiredItemTypeCode) {
      // 1. Make sure that $RequiredItems are present
      if (is_array($RequiredItems)) {
         $MissingRequirements = array();

         foreach ($RequiredItems as $RequiredItemName => $RequiredVersion) {
            if (!array_key_exists($RequiredItemName, $EnabledItems)) {
               $MissingRequirements[] = "$RequiredItemName $RequiredVersion";
            } else if ($RequiredVersion && $RequiredVersion != '*') { // * means any version
               $EnabledItems;

                // If the item exists and is enabled, check the version
               $EnabledVersion = ArrayValue('Version', ArrayValue($RequiredItemName, $EnabledItems, array()), '');
               // Compare the versions.
               if (version_compare($EnabledVersion, $RequiredVersion, '<')) {
                  $MissingRequirements[] = "$RequiredItemName $RequiredVersion";
               }
            }
         }
         if (count($MissingRequirements) > 0) {
            $Msg = sprintf("%s is missing the following requirement(s): %s.",
               $ItemName,
               implode(', ', $MissingRequirements));
            throw new Gdn_UserException($Msg);
         }
      }
   }
}

if (!function_exists('check_utf8')){
   function check_utf8($str) {
       $len = strlen($str);
       for($i = 0; $i < $len; $i++){
           $c = ord($str[$i]);
           if ($c > 128) {
               if (($c > 247)) return false;
               elseif ($c > 239) $bytes = 4;
               elseif ($c > 223) $bytes = 3;
               elseif ($c > 191) $bytes = 2;
               else return false;
               if (($i + $bytes) > $len) return false;
               while ($bytes > 1) {
                   $i++;
                   $b = ord($str[$i]);
                   if ($b < 128 || $b > 191) return false;
                   $bytes--;
               }
           }
       }
       return true;
   }
}

if (!function_exists('CombinePaths')) {
   /**
    * Takes an array of path parts and concatenates them using the specified
    * delimiter. Delimiters will not be duplicated. Example: all of the
    * following arrays will generate the path "/path/to/vanilla/applications/dashboard"
    * array('/path/to/vanilla', 'applications/dashboard')
    * array('/path/to/vanilla/', '/applications/dashboard')
    * array('/path', 'to', 'vanilla', 'applications', 'dashboard')
    * array('/path/', '/to/', '/vanilla/', '/applications/', '/dashboard')
    * 
    * @param array $Paths The array of paths to concatenate.
    * @param string $Delimiter The delimiter to use when concatenating. Defaults to system-defined directory separator.
    * @returns The concatentated path.
    */
   function CombinePaths($Paths, $Delimiter = DS) {
      if (is_array($Paths)) {
         $MungedPath = implode($Delimiter, $Paths);
         $MungedPath = str_replace(array($Delimiter.$Delimiter.$Delimiter, $Delimiter.$Delimiter), array($Delimiter, $Delimiter), $MungedPath);
         return str_replace(array('http:/', 'https:/'), array('http://', 'https://'), $MungedPath);
      } else {
         return $Paths;
      }
   }
}

if (!function_exists('CompareHashDigest')) {
    /**
     * Returns True if the two strings are equal, False otherwise.
     * The time taken is independent of the number of characters that match.
     *
     * This snippet prevents HMAC Timing attacks ( http://codahale.com/a-lesson-in-timing-attacks/ )
     * Thanks to Eric Karulf (ekarulf @ github) for this fix.
     */
   function CompareHashDigest($Digest1, $Digest2) {
        if (strlen($Digest1) !== strlen($Digest2)) {
            return false;
        }

        $Result = 0;
        for ($i = strlen($Digest1) - 1; $i >= 0; $i--) {
            $Result |= ord($Digest1[$i]) ^ ord($Digest2[$i]);
        }

        return 0 === $Result;
    }
}

if (!function_exists('ConcatSep')) {
   /** Concatenate a string to another string with a seperator.
    *
    * @param string $Sep The seperator string to use between the concatenated strings.
    * @param string $Str1 The first string in the concatenation chain.
    * @param mixed $Str2 The second string in the concatenation chain.
    *  - This parameter can be an array in which case all of its elements will be concatenated.
    *  - If this parameter is a string then the function will look for more arguments to concatenate.
    * @return string
    */
   function ConcatSep($Sep, $Str1, $Str2) {
      if(is_array($Str2)) {
         $Strings = array_merge((array)$Str1, $Str2);
      } else {
         $Strings = func_get_args();
         array_shift($Strings);
      }

      $Result = '';
      foreach($Strings as $String) {
         if(!$String)
            continue;

         if($Result)
            $Result .= $Sep;
         $Result .= $String;
      }
      return $Result;
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
         
			if (is_object($AssociativeArray)) {
				if($ValueKey === '') {
					$Return[] = $AssociativeArray->$Key;
				} elseif(property_exists($AssociativeArray, $ValueKey)) {
					$Return[$AssociativeArray[$Key]] = $AssociativeArray->$ValueKey;
				} else {
					$Return[$AssociativeArray->$Key] = $DefaultValue;
				}
			} elseif (is_array($AssociativeArray) && array_key_exists($Key, $AssociativeArray)) {
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

if (!function_exists('decho')) {
   /**
    * Echo's debug variables if user is root admin.
    */
   function decho($Mixed, $Prefix = 'DEBUG', $Permission = FALSE) {
      $Prefix = StringEndsWith($Prefix, ': ', TRUE, TRUE).': ';
      
      if (!$Permission || Gdn::Session()->CheckPermission('Garden.Debug.Allow')) {
         echo '<pre style="text-align: left; padding: 0 4px;">'.$Prefix;
         if (is_string($Mixed))
            echo $Mixed;
         else
            echo htmlspecialchars(print_r($Mixed, TRUE));
      
         echo '</pre>';
      }
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

if (!function_exists('Debug')) {
   function Debug($Value = NULL) {
      static $Debug = FALSE;
      if ($Value === NULL)
         return $Debug;
      
      $Debug = $Value;
      if ($Debug)
         error_reporting(E_ALL);
      else
         error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
   }
}

if (!function_exists('DebugMethod')) {
   function DebugMethod($MethodName, $MethodArgs = array()) {
      echo $MethodName."(";
      $SA = array();
      foreach ($MethodArgs as $FuncArg) {
         if (is_null($FuncArg))
            $SA[] = 'NULL';
         elseif (!is_array($FuncArg) && !is_object($FuncArg))
            $SA[] = "'{$FuncArg}'";
         elseif (is_array($FuncArg))
            $SA[] = "'Array(".sizeof($FuncArg).")'";
         else
            $SA[] = gettype($FuncArg)."/".get_class($FuncArg);
      }
      echo implode(', ', $SA);
      echo ")\n";
   }
}

if (!function_exists('Deprecated')) {
   /**
    * Mark a function deprecated.
    *
    * @param string $Name The name of the deprecated function.
    * @param string $NewName The name of the new function that should be used instead.
    */
   function Deprecated($Name, $NewName = FALSE) {
      $Msg = $Name.' is deprecated.';
      if ($NewName)
         $Msg .= " Use $NewName instead.";

      trigger_error($Msg, E_USER_DEPRECATED);
   }
}

if (!function_exists('ExternalUrl')) {
   function ExternalUrl($Path) {
      $Format = C('Garden.ExternalUrlFormat');

      if ($Format && !StringBeginsWith($Path, 'http'))
         $Result = sprintf($Format, ltrim($Path, '/'));
      else
         $Result = Url($Path, TRUE);

      return $Result;
   }
}


if (!function_exists('FetchPageInfo')) {
   /**
    * Examines the page at $Url for title, description & images. Be sure to check the resultant array for any Exceptions that occurred while retrieving the page. 
    * @param string $Url The url to examine.
    * @param integer $Timeout How long to allow for this request. Default Garden.SocketTimeout or 1, 0 to never timeout. Default is 0.
    * @return array an array containing Url, Title, Description, Images (array) and Exception (if there were problems retrieving the page).
    */
   function FetchPageInfo($Url, $Timeout = 0) {
      $PageInfo = array(
         'Url' => $Url,
         'Title' => '',
         'Description' => '',
         'Images' => array(),
         'Exception' => FALSE
      );
      try {
         if (!defined('HDOM_TYPE_ELEMENT'))
            require_once(PATH_LIBRARY.'/vendors/simplehtmldom/simple_html_dom.php');
            
         $PageHtml = ProxyRequest($Url, $Timeout, TRUE);
         $Dom = str_get_html($PageHtml);
         if (!$Dom)
            throw new Exception('Failed to load page for parsing.');
         
         /* Sample Facebook Open Graph code:

<meta property="og:title" content="60 degrees in&nbsp;February" />
<meta property="og:url" content="http://karinemily.wordpress.com/2012/02/02/60-degrees-in-february/" />
<meta property="og:description" content="and Philadelphia explodes with babies, puppies, and hipsters." />
<meta property="og:site_name" content="K a r i &#039; s" />
<meta property="og:image" content="http://karinemily.files.wordpress.com/2012/02/dsc_0132.jpg?w=300&amp;h=300" />
<meta property="og:image" content="http://karinemily.files.wordpress.com/2012/02/dsc_0214.jpg?w=300&amp;h=300" />
<meta property="og:image" content="http://karinemily.files.wordpress.com/2012/02/dsc_0213.jpg?w=300&amp;h=300" />
<meta property="og:image" content="http://karinemily.files.wordpress.com/2012/02/dsc_0221-version-2.jpg?w=300&amp;h=300" />

          */
         
         // FIRST PASS: Look for open graph title, desc, images
         $PageInfo['Title'] = DomGetContent($Dom, 'meta[property=og:title]');
         
         Trace('Getting og:description');
         $PageInfo['Description'] = DomGetContent($Dom, 'meta[property=og:description]');
         foreach ($Dom->find('meta[property=og:image]') as $Image) {
            if (isset($Image->content))
               $PageInfo['Images'][] = $Image->content;
         }

         // SECOND PASS: Look in the page for title, desc, images
         if ($PageInfo['Title'] == '')
            $PageInfo['Title'] = $Dom->find('title', 0)->plaintext;
         
         if ($PageInfo['Description'] == '') {
            Trace('Getting meta description');
            $PageInfo['Description'] = DomGetContent($Dom, 'meta[name=description]');
         }

         // THIRD PASS: Look in the page contents
         if ($PageInfo['Description'] == '') {
            foreach($Dom->find('p') as $element) {
               Trace('Looking at p for description.');
               
               if (strlen($element->plaintext) > 150) {
                  $PageInfo['Description'] = $element->plaintext;
                  break;
               }
            }
            if (strlen($PageInfo['Description']) > 400)
               $PageInfo['Description'] = SliceParagraph($PageInfo['Description'], 400);
         }
         
         // Final: Still nothing? remove limitations
         if ($PageInfo['Description'] == '') {
            foreach($Dom->find('p') as $element) {
               Trace('Looking at p for description (no restrictions)');
               if (trim($element->plaintext) != '') {
                  $PageInfo['Description'] = $element->plaintext;
                  break;
               }
            }
         }
            
         // Page Images
         if (count($PageInfo['Images']) == 0) {
            $Images = DomGetImages($Dom, $Url);
            $PageInfo['Images'] = array_values($Images);
         }
         
         $PageInfo['Title'] = HtmlEntityDecode($PageInfo['Title']);
         $PageInfo['Description'] = HtmlEntityDecode($PageInfo['Description']);

      } catch (Exception $ex) {
         $PageInfo['Exception'] = $ex->getMessage();
      }
      return $PageInfo;
   }
}

if (!function_exists('DomGetContent')) {
   function DomGetContent($Dom, $Selector, $Default = '') {
      $Element = $Dom->getElementsByTagName($Selector);
      return isset($Element->content) ? $Element->content : $Default;
   }
}

if (!function_exists('DomGetImages')) {
   function DomGetImages($Dom, $Url, $MaxImages = 4) {
      $Images = array();
      foreach ($Dom->find('img') as $element) {
         $Images[] = array('Src' => AbsoluteSource($element->src, $Url), 'Width' => $element->width, 'Height' => $element->height);
      }
      
//      Gdn::Controller()->Data['AllImages'] = $Images;

      // Sort by size, biggest one first
      $ImageSort = array();
      // Only look at first 4 images (speed!)
      $i = 0;
      foreach ($Images as $ImageInfo) {
         $Image = $ImageInfo['Src'];
         
         if (strpos($Image, 'doubleclick.') != FALSE)
            continue;
         
         try {
            if ($ImageInfo['Height'] && $ImageInfo['Width']) {
               $Height = $ImageInfo['Height'];
               $Width = $ImageInfo['Width'];
            } else {
               list($Width, $Height) = getimagesize($Image);
            }
            
            $Diag = (int)floor(sqrt(($Width*$Width) + ($Height*$Height)));
            
//            Gdn::Controller()->Data['Foo'][] = array($Image, $Width, $Height, $Diag);
            
            if (!$Width || !$Height)
               continue;
            
            // Require min 100x100 dimension image.
            if ($Width < 100 && $Height < 100)
               continue;
            
            // Don't take a banner-shaped image.
            if ($Height * 5 < $Width)
               continue;
            
            // Prefer images that are less than 800px wide (banners?)
//            if ($Diag > 141 && $Width < 800) { }
               
            if (!array_key_exists($Diag, $ImageSort)) {
               $ImageSort[$Diag] = array($Image);
            } else {
               $ImageSort[$Diag][] = $Image;
            }
            
            
            $i++;

            if ($i > $MaxImages)
               break;
         } catch(Exception $ex) {
            // do nothing
         }
      }
      
      krsort($ImageSort);
      $GoodImages = array();
      foreach ($ImageSort as $Diag => $Arr) {
         $GoodImages = array_merge($GoodImages, $Arr);
      }
      return $GoodImages;
   }
}

/**
 * Replace missing 'fnmatch' on Windows + PHP <5.3
 */
if (!function_exists('fnmatch')) { 
   define('FNM_PATHNAME', 1); 
   define('FNM_NOESCAPE', 2); 
   define('FNM_PERIOD', 4); 
   define('FNM_CASEFOLD', 16); 

   function fnmatch($pattern, $string, $flags = 0) { 
      return pcre_fnmatch($pattern, $string, $flags); 
   } 

   function pcre_fnmatch($pattern, $string, $flags = 0) { 
      $modifiers = null; 
      $transforms = array( 
         '\*'    => '.*', 
         '\?'    => '.', 
         '\[\!'    => '[^', 
         '\['    => '[', 
         '\]'    => ']', 
         '\.'    => '\.', 
         '\\'    => '\\\\' 
      );

      // Forward slash in string must be in pattern: 
      if ($flags & FNM_PATHNAME) { 
         $transforms['\*'] = '[^/]*'; 
      } 

      // Back slash should not be escaped: 
      if ($flags & FNM_NOESCAPE) { 
         unset($transforms['\\']); 
      } 

      // Perform case insensitive match: 
      if ($flags & FNM_CASEFOLD) { 
         $modifiers .= 'i'; 
      } 

      // Period at start must be the same as pattern: 
      if ($flags & FNM_PERIOD) { 
         if (strpos($string, '.') === 0 && strpos($pattern, '.') !== 0) return false; 
      } 

      $pattern = '#^' 
         . strtr(preg_quote($pattern, '#'), $transforms) 
         . '$#' 
         . $modifiers; 

      return (boolean)preg_match($pattern, $string); 
   }
}

/**
 * Formats a string by inserting data from its arguments, similar to sprintf, but with a richer syntax.
 *
 * @param string $String The string to format with fields from its args enclosed in curly braces. The format of fields is in the form {Field,Format,Arg1,Arg2}. The following formats are the following:
 *  - date: Formats the value as a date. Valid arguments are short, medium, long.
 *  - number: Formats the value as a number. Valid arguments are currency, integer, percent.
 *  - time: Formats the valud as a time. This format has no additional arguments.
 *  - url: Calls Url() function around the value to show a valid url with the site. You can pass a domain to include the domain.
 *  - urlencode, rawurlencode: Calls urlencode/rawurlencode respectively.
 *  - html: Calls htmlspecialchars.
 * @param array $Args The array of arguments. If you want to nest arrays then the keys to the nested values can be seperated by dots.
 * @return string The formatted string.
 * <code>
 * echo FormatString("Hello {Name}, It's {Now,time}.", array('Name' => 'Frank', 'Now' => '1999-12-31 23:59'));
 * // This would output the following string:
 * // Hello Frank, It's 12:59PM.
 * </code>
 */
function FormatString($String, $Args = array()) {
   _FormatStringCallback($Args, TRUE);
   $Result = preg_replace_callback('/{([^}]+?)}/', '_FormatStringCallback', $String);

   return $Result;
}

function _FormatStringCallback($Match, $SetArgs = FALSE) {
   static $Args = array();
   if ($SetArgs) {
      $Args = $Match;
      return;
   }

   $Match = $Match[1];
   if ($Match == '{')
      return $Match;

   // Parse out the field and format.
   $Parts = explode(',', $Match);
   $Field = trim($Parts[0]);
   $Format = trim(GetValue(1, $Parts, ''));
   $SubFormat = strtolower(trim(GetValue(2, $Parts, '')));
   $FomatArgs = GetValue(3, $Parts, '');

   if (in_array($Format, array('currency', 'integer', 'percent'))) {
      $FormatArgs = $SubFormat;
      $SubFormat = $Format;
      $Format = 'number';
   } elseif(is_numeric($SubFormat)) {
      $FormatArgs = $SubFormat;
      $SubFormat = '';
   }

   $Value = GetValueR($Field, $Args, '');
   if ($Value == '' && !in_array($Format, array('url', 'exurl'))) {
      $Result = '';
   } else {
      switch(strtolower($Format)) {
         case 'date':
            switch($SubFormat) {
               case 'short':
                  $Result = Gdn_Format::Date($Value, '%d/%m/%Y');
                  break;
               case 'medium':
                  $Result = Gdn_Format::Date($Value, '%e %b %Y');
                  break;
               case 'long':
                  $Result = Gdn_Format::Date($Value, '%e %B %Y');
                  break;
               default:
                  $Result = Gdn_Format::Date($Value);
                  break;
            }
            break;
         case 'html':
         case 'htmlspecialchars':
            $Result = htmlspecialchars($Value);
            break;
         case 'number':
            if(!is_numeric($Value)) {
               $Result = $Value;
            } else {
               switch($SubFormat) {
                  case 'currency':
                     $Result = '$'.number_format($Value, is_numeric($FormatArgs) ? $FormatArgs : 2);
                  case 'integer':
                     $Result = (string)round($Value);
                     if(is_numeric($FormatArgs) && strlen($Result) < $FormatArgs) {
                           $Result = str_repeat('0', $FormatArgs - strlen($Result)).$Result;
                     }
                     break;
                  case 'percent':
                     $Result = round($Value * 100, is_numeric($FormatArgs) ? $FormatArgs : 0);
                     break;
                  default:
                     $Result = number_format($Value, is_numeric($FormatArgs) ? $FormatArgs : 0);
                     break;
               }
            }
            break;
         case 'rawurlencode':
            $Result = rawurlencode($Value);
            break;
         case 'text':
            $Result = Gdn_Format::Text($Value, FALSE);
            break;
         case 'time':
            $Result = Gdn_Format::Date($Value, '%l:%M%p');
            break;
         case 'url':
            if (strpos($Field, '/') !== FALSE)
               $Value = $Field;
            $Result = Url($Value, $SubFormat == 'domain');
            break;
         case 'exurl':
            if (strpos($Field, '/') !== FALSE)
               $Value = $Field;
            $Result = ExternalUrl($Value);
            break;
         case 'urlencode':
            $Result = urlencode($Value);
            break;
         case 'user':
         case 'you':
         case 'his':
         case 'her':
         case 'your':
            $Result = print_r($Value, TRUE);
            $ArgsBak = $Args;
            if (is_array($Value) && count($Value) == 1)
               $Value = array_shift($Value);
            
            if (is_array($Value)) {
               $Max = C('Garden.FormatUsername.Max', 10);
               
               $Count = count($Value);
               $Result = '';
               for ($i = 0; $i < $Count; $i++) {
                  if ($i >= $Max && $Count > $Max + 1) {
                     $Others = $Count - $i;
                     $Result .= ' '.T('sep and', 'and').' '
                        .Plural($Others, '%s other', '%s others');
                     break;
                  }
                  
                  $ID = $Value[$i];
                  if (is_array($ID)) {
                     continue;
                  }
                  $User = Gdn::UserModel()->GetID($ID);
                  $User->Name = FormatUsername($User, $Format, Gdn::Session()->UserID);
                  
                  if ($i == $Count - 1)
                     $Result .= ' '.T('sep and', 'and').' ';
                  elseif ($i > 0)
                     $Result .= ', ';
                  $Result .= UserAnchor($User);
               }
            } else {
               $User = Gdn::UserModel()->GetID($Value);
               $User->Name = FormatUsername($User, $Format, Gdn::Session()->UserID);
               
               $Result = UserAnchor($User);
            }
               
            $Args = $ArgsBak;
            break;
         default:
            $Result = $Value;
            break;
      }
   }
   return $Result;
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

if (!function_exists('ForceSSL')) {
   /**
    * Checks the current url for SSL and redirects to SSL version if not
    * currently on it. Call at the beginning of any method you want forced to
    * be in SSL. Garden.AllowSSL must be TRUE in order for this function to
    * work.
    */
   function ForceSSL() {
      if (C('Garden.AllowSSL')) {
         if (Gdn::Request()->Scheme() != 'https')
            Redirect(Gdn::Request()->Url('', TRUE, TRUE));
      }
   }
}

if (!function_exists('ForceNoSSL')) {
   /**
    * Checks the current url for SSL and redirects to SSL version if not
    * currently on it. Call at the beginning of any method you want forced to
    * be in SSL. Garden.AllowSSL must be TRUE in order for this function to
    * work.
    */
   function ForceNoSSL() {
      if (Gdn::Request()->Scheme() != 'http')
         Redirect(Gdn::Request()->Url('', TRUE, FALSE));
   }
}

// Formats values to be saved as PHP arrays.
if (!function_exists('FormatArrayAssignment')) {
   function FormatArrayAssignment(&$Array, $Prefix, $Value) {
      if (is_array($Value)) {
         // If $Value doesn't contain a key of "0" OR it does and it's value IS
         // an array, this should be treated as an associative array.
         $IsAssociativeArray = array_key_exists(0, $Value) === FALSE || is_array($Value[0]) === TRUE ? TRUE : FALSE;
         if ($IsAssociativeArray === TRUE) {
            foreach ($Value as $k => $v) {
               FormatArrayAssignment($Array, $Prefix."['$k']", $v);
            }
         } else {
            // If $Value is not an associative array, just write it like a simple array definition.
            $FormattedValue = array_map(array('Gdn_Format', 'ArrayValueForPhp'), $Value);
            $Array[] = $Prefix .= " = array('".implode("', '", $FormattedValue)."');";
         }
      } elseif (is_int($Value)) {
			$Array[] = $Prefix .= ' = '.$Value.';';
		} elseif (is_bool($Value)) {
         $Array[] = $Prefix .= ' = '.($Value ? 'TRUE' : 'FALSE').';';
      } elseif (in_array($Value, array('TRUE', 'FALSE'))) {
         $Array[] = $Prefix .= ' = '.($Value == 'TRUE' ? 'TRUE' : 'FALSE').';';
      } else {
         $Array[] = $Prefix .= ' = '.var_export($Value, TRUE).';';
      }
   }
}

// Formats values to be saved in dotted notation
if (!function_exists('FormatDottedAssignment')) {
   function FormatDottedAssignment(&$Array, $Prefix, $Value) {
      if (is_array($Value)) {
         // If $Value doesn't contain a key of "0" OR it does and it's value IS
         // an array, this should be treated as an associative array.
         $IsAssociativeArray = array_key_exists(0, $Value) === FALSE || is_array($Value[0]) === TRUE ? TRUE : FALSE;
         if ($IsAssociativeArray === TRUE) {
            foreach ($Value as $k => $v) {
               FormatDottedAssignment($Array, "{$Prefix}.{$k}", $v);
            }
         } else {
            // If $Value is not an associative array, just write it like a simple array definition.
            $FormattedValue = array_map(array('Gdn_Format', 'ArrayValueForPhp'), $Value);
            $Prefix .= "']";
            $Array[] = $Prefix .= " = array('".implode("', '", $FormattedValue)."');";
         }
      } else {
         $Prefix .= "']";
         if (is_int($Value)) {
            $Array[] = $Prefix .= ' = '.$Value.';';
         } elseif (is_bool($Value)) {
            $Array[] = $Prefix .= ' = '.($Value ? 'TRUE' : 'FALSE').';';
         } elseif (in_array($Value, array('TRUE', 'FALSE'))) {
            $Array[] = $Prefix .= ' = '.($Value == 'TRUE' ? 'TRUE' : 'FALSE').';';
         } else {
            $Array[] = $Prefix .= ' = '.var_export($Value, TRUE).';';
         }
      }
   }
}

if (!function_exists('getallheaders')) {
   /**
    * If PHP isn't running as an apache module, getallheaders doesn't exist in
    * some systems.
    * Ref: http://github.com/lussumo/Garden/issues/closed#issue/3/comment/19938
    */
   function getallheaders() {
      foreach($_SERVER as $name => $value)
          if(substr($name, 0, 5) == 'HTTP_')
              $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
      return $headers;
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

if (!function_exists('GetIncomingValue')) {
   /**
    * Grabs $FieldName from either the GET or POST collections (whichever one it
    * is present in. Checks $_POST first).
    */
   function GetIncomingValue($FieldName, $Default = FALSE) {
      if (array_key_exists($FieldName, $_POST) === TRUE) {
         $Result = filter_input(INPUT_POST, $FieldName, FILTER_SANITIZE_STRING); //FILTER_REQUIRE_ARRAY);
      } else if (array_key_exists($FieldName, $_GET) === TRUE) {
         $Result = filter_input(INPUT_GET, $FieldName, FILTER_SANITIZE_STRING); //, FILTER_REQUIRE_ARRAY);
      } else {
         $Result = $Default;
      }
      return $Result;
   }
}

if (!function_exists('GetMentions')) {
   function GetMentions($String) {
      // Check for a custom mentions formatter and use it.
      $Formatter = Gdn::Factory('MentionsFormatter');
      if (is_object($Formatter)) {
         return $Formatter->GetMentions($String);
      }

      $Mentions = array();
      
      // This one grabs mentions that start at the beginning of $String
      preg_match_all(
         '/(?:^|[\s,\.>])@(\w{3,20})\b/i',
         $String,
         $Matches
      );
      if (count($Matches) > 1) {
         $Result = array_unique($Matches[1]);
         return $Result;
      }
      return array();
   }
}

if (!function_exists('GetObject')) {
   /**
    * Get a value off of an object.
    *
    * @deprecated GetObject() is deprecated. Use GetValue() instead.
    * @param string $Property The name of the property on the object.
    * @param object $Object The object that contains the value.
    * @param mixed $Default The default to return if the object doesn't contain the property.
    * @return mixed
    */
   function GetObject($Property, $Object, $Default) {
      trigger_error('GetObject() is deprecated. Use GetValue() instead.', E_USER_DEPRECATED);
      $Result = GetValue($Property, $Object, $Default);
      return $Result;
   }
}

if (!function_exists('GetPostValue')) {
   /**
    * Return the value for $FieldName from the $_POST collection.
    */
   function GetPostValue($FieldName, $Default = FALSE) {
      return array_key_exists($FieldName, $_POST) ? $_POST[$FieldName] : $Default;
   }
}

if (!function_exists('GetValue')) {
	/**
	 * Return the value from an associative array or an object.
	 *
	 * @param string $Key The key or property name of the value.
	 * @param mixed $Collection The array or object to search.
	 * @param mixed $Default The value to return if the key does not exist.
    * @param bool $Remove Whether or not to remove the item from the collection.
	 * @return mixed The value from the array or object.
	 */
	function GetValue($Key, &$Collection, $Default = FALSE, $Remove = FALSE) {
		$Result = $Default;
		if(is_array($Collection) && array_key_exists($Key, $Collection)) {
			$Result = $Collection[$Key];
         if($Remove)
            unset($Collection[$Key]);
		} elseif(is_object($Collection) && property_exists($Collection, $Key)) {
			$Result = $Collection->$Key;
         if($Remove)
            unset($Collection->$Key);
      }
			
      return $Result;
	}
}

if (!function_exists('GetValueR')) {
   /**
	 * Return the value from an associative array or an object.
    * This function differs from GetValue() in that $Key can be a string consisting of dot notation that will be used to recursivly traverse the collection.
	 *
	 * @param string $Key The key or property name of the value.
	 * @param mixed $Collection The array or object to search.
	 * @param mixed $Default The value to return if the key does not exist.
	 * @return mixed The value from the array or object.
	 */
   function GetValueR($Key, $Collection, $Default = FALSE) {
      $Path = explode('.', $Key);

      $Value = $Collection;
      for($i = 0; $i < count($Path); ++$i) {
         $SubKey = $Path[$i];

         if(is_array($Value) && isset($Value[$SubKey])) {
            $Value = $Value[$SubKey];
         } elseif(is_object($Value) && isset($Value->$SubKey)) {
            $Value = $Value->$SubKey;
         } else {
            return $Default;
         }
      }
      return $Value;
   }
}

if (!function_exists('HtmlEntityDecode')):
   
/**
 * Decode ALL of the entities out of an html string.
 * 
 * @param string $string The string to decode.
 * @param constant $quote_style
 * @param string $charset
 * @return string 
 * @since 2.1
 */
function HtmlEntityDecode($string, $quote_style = ENT_QUOTES, $charset = "utf-8") {
   $string = html_entity_decode($string, $quote_style, $charset);
   $string = str_ireplace('&apos;', "'", $string);
   $string = preg_replace_callback('~&#x([0-9a-fA-F]+);~i', "chr_utf8_callback", $string);
   $string = preg_replace('~&#([0-9]+);~e', 'chr_utf8("\\1")', $string);
   return $string; 
}

/** 
 * Callback helper 
 */

function chr_utf8_callback($matches) { 
   return chr_utf8(hexdec($matches[1])); 
}

/**
* Multi-byte chr(): Will turn a numeric argument into a UTF-8 string.
* 
* @param mixed $num
* @return string
*/

function chr_utf8($num) {
   if ($num < 128) return chr($num);
   if ($num < 2048) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
   if ($num < 65536) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
   if ($num < 2097152) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
   return '';
}

endif;

if (!function_exists('ImplodeAssoc')) {
   /**
    * A version of implode() that operates on array keys and values.
    *
    * @param string $KeyGlue The glue between keys and values.
    * @param string $ElementGlue The glue between array elements.
    * @param array $Array The array to implode.
    * @return string The imploded array.
    */
   function ImplodeAssoc($KeyGlue, $ElementGlue, $Array) {
      $Result = '';

      foreach ($Array as $Key => $Value) {
         if (strlen($Result) > 0)
            $Result .= $ElementGlue;

         $Result .= $Key.$KeyGlue.$Value;
      }
      return $Result;
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

if (!function_exists('InSubArray')) {
   /**
    * Loop through $Haystack looking for subarrays that contain $Needle.
    */
   function InSubArray($Needle, $Haystack) {
      foreach ($Haystack as $Key => $Val) {
         if (is_array($Val) && in_array($Needle, $Val))
            return TRUE;
      }
      return FALSE;
   }
}

if (!function_exists('IsMobile')) {
   function IsMobile() {
      static $IsMobile = 'unset';
      
      // Short circuit so we only do this work once per pageload
      if ($IsMobile != 'unset') return $IsMobile;
      
      // Start out assuming not mobile
      $Mobile = 0;
      
      $AllHttp = strtolower(GetValue('ALL_HTTP', $_SERVER));
      $HttpAccept = strtolower(GetValue('HTTP_ACCEPT', $_SERVER));
      $UserAgent = strtolower(GetValue('HTTP_USER_AGENT', $_SERVER));
      if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|opera m|kindle|webos)/i', $UserAgent))
         $Mobile++;
 
      if(
         (strpos($HttpAccept,'application/vnd.wap.xhtml+xml') > 0)
         || (
            (isset($_SERVER['HTTP_X_WAP_PROFILE'])
            || isset($_SERVER['HTTP_PROFILE'])))
         )
         $Mobile++;
      
      if(strpos($UserAgent,'android') > 0 && strpos($UserAgent,'mobile') > 0)
         $Mobile++;
 
      $MobileUserAgent = substr($UserAgent, 0, 4);
      $MobileUserAgents = array(
          'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
          'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
          'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
          'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
          'newt','noki','palm','pana','pant','phil','play','port','prox','qwap',
          'sage','sams','sany','sch-','sec-','send','seri','sgh-','shar','sie-',
          'siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-','tosh',
          'tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp','wapr',
          'webc','winw','winw','xda','xda-');
 
      if (in_array($MobileUserAgent, $MobileUserAgents))
         $Mobile++;
 
      if (strpos($AllHttp, 'operamini') > 0)
         $Mobile++;
 
      // Windows Mobile 7 contains "windows" in the useragent string, so must comment this out
      // if (strpos($UserAgent, 'windows') > 0)
      //   $Mobile = 0;
      
      $IsMobile = ($Mobile > 0);
      
      $ForceNoMobile = Gdn_CookieIdentity::GetCookiePayload('VanillaNoMobile');
      if (($Mobile > 0) && $ForceNoMobile !== FALSE && is_array($ForceNoMobile) && in_array('force', $ForceNoMobile))
         $IsMobile = NULL;
      
      return $IsMobile;
   }
}

if (!function_exists('IsSearchEngine')) {
   function IsSearchEngine() {
      $Engines = array(
         'googlebot', 
         'slurp', 
         'search.msn.com', 
         'nutch', 
         'simpy', 
         'bot', 
         'aspseek', 
         'crawler', 
         'msnbot', 
         'libwww-perl', 
         'fast', 
         'baidu', 
      );
      $HttpUserAgent = strtolower(GetValue('HTTP_USER_AGENT', $_SERVER, ''));
      if ($HttpUserAgent != '') {
         foreach ($Engines as $Engine) {
            if (strpos($HttpUserAgent, $Engine) !== FALSE)
               return TRUE;
         }
      }
      return FALSE;
   }
}

if (!function_exists('IsTimestamp')) {
   function IsTimestamp($Stamp) {
      return checkdate(
         @date("m", $Stamp),
         @date("d", $Stamp),
         @date("Y", $Stamp)
      );
   }
}

if (!function_exists('IsWritable')) {
   /**
    * PHP's native is_writable() function fails to correctly determine write
    * capabilities on some systems (Windows), and in our tests it returned TRUE
    * despite not being able to create subfolders within the folder being
    * checked. Our version truly verifies permissions by performing file-write
    * tests.
    */
   function IsWritable($Path) {
      if ($Path{strlen($Path) - 1} == DS) {
         // Recursively return a temporary file path
         return IsWritable($Path . uniqid(mt_rand()) . '.tmp');
      } elseif (is_dir($Path)) {
         return IsWritable($Path . '/' . uniqid(mt_rand()) . '.tmp');
      }
      // Check tmp file for read/write capabilities
      $KeepPath = file_exists($Path);
      $File = @fopen($Path, 'a');
      if ($File === FALSE)
         return FALSE;
      
      fclose($File);
      
      if (!$KeepPath)
         unlink($Path);
      
      return TRUE;
   }
}

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

if (!function_exists('Now')) {
   function Now() {
      return microtime(TRUE);
   }
}

if (!function_exists('OffsetLimit')) {
   /** Convert various forms of querystring limit/offset, page, limit/range to database limit/offset
    *
    * @param string $OffsetOrPage The page query in one of the following formats:
    *  - p<x>: Get page x.
    *  - <x>-<y>: This is a range viewing records x through y.
    *  - <x>lim<n>: This is a limit/offset pair.
    *  - <x>: This is a limit where offset is given in the next parameter.
    * @param int $LimitOrPageSize The page size or limit.
    */
   function OffsetLimit($OffsetOrPage = '', $LimitOrPageSize = '') {
      $LimitOrPageSize = is_numeric($LimitOrPageSize) ? $LimitOrPageSize : 50;

      if (is_numeric($OffsetOrPage)) {
         $Offset = $OffsetOrPage;
         $Limit = $LimitOrPageSize;
      } elseif (preg_match('/p(\d+)/i', $OffsetOrPage, $Matches)) {
         $Page = $Matches[1];
         $Offset = $LimitOrPageSize * ($Page - 1);
         $Limit = $LimitOrPageSize;
      } elseif (preg_match('/(\d+)-(\d+)/', $OffsetOrPage, $Matches)) {
         $Offset = $Matches[1] - 1;
         $Limit = $Matches[2] - $Matches[1] + 1;
      } elseif (preg_match('/(\d+)lim(\d*)/i', $OffsetOrPage, $Matches)) {
         $Offset = $Matches[1];
         $Limit = $Matches[2];
         if (!is_numeric($Limit))
            $Limit = $LimitOrPageSize;
      } elseif (preg_match('/(\d+)lin(\d*)/i', $OffsetOrPage, $Matches)) {
         $Offset = $Matches[1] - 1;
         $Limit = $Matches[2];
         if (!is_numeric($Limit))
            $Limit = $LimitOrPageSize;
      } else {
         $Offset = 0;
         $Limit = $LimitOrPageSize;
      }

      if ($Offset < 0)
         $Offset = 0;
      if ($Limit < 0)
         $Limit = 50;

      return array($Offset, $Limit);
   }
}

if (!function_exists('PageNumber')) {
   /** Get the page number from a database offset and limit.
    *
    * @param int $Offset The database offset, starting at zero.
    * @param int $Limit The database limit, otherwise known as the page size.
    * @param bool|string $UrlParam Whether or not the result should be formatted as a url parameter, suitable for OffsetLimit.
    *  - bool: true means yes, false means no.
    *  - string: The prefix for the page number.
    * @param bool $First Whether or not to return the page number if it is the first page.
    */
   function PageNumber($Offset, $Limit, $UrlParam = FALSE, $First = TRUE) {
      $Result = floor($Offset / $Limit) + 1;

      if ($UrlParam !== FALSE && !$First && $Result == 1)
         $Result = '';
      elseif ($UrlParam === TRUE)
         $Result = 'p'.$Result;
      elseif (is_string($UrlParam))
         $Result = $UrlParam.$Result;

      return $Result;
   }
}

if (!function_exists('parse_ini_string')) {
   /**
    * parse_ini_string not supported until PHP 5.3.0, and we currently support
    * PHP 5.2.0.
    */
   function parse_ini_string ($Ini) {
      $Lines = explode("\n", $Ini);
      $Result = array();
      foreach($Lines as $Line) {
         $Parts = explode('=', $Line, 2);
         if(count($Parts) == 1) {
            $Result[trim($Parts[0])] = '';
         } elseif(count($Parts) >= 2) {
            $Result[trim($Parts[0])] = trim($Parts[1]);
         }
      }
      return $Result;
   }
}

if (!function_exists('write_ini_string')) {
   function write_ini_string($Data) {
      $Flat = array();
      foreach($Data as $Topic => $Settings) {
         if (is_array($Settings)) {
            $Flat[] = "[{$Topic}]";
               foreach ($Settings as $SettingsKey => $SettingsVal) $Flat[] = "{$SettingsKey} = ".(is_numeric($SettingsVal) ? $SettingsVal : '"'.$SettingsVal.'"');
            $Flat[] = "";
         }
         else $Flat[] = "{$Topic} = ".(is_numeric($Settings) ? $Settings : '"'.$Settings.'"');
      }
      return implode("\n", $Flat);
   }
}

if (!function_exists('write_ini_file')) {
   function write_ini_file($File, $Data) {
      $String = write_ini_string($Data);
      Gdn_FileSystem::SaveFile($File, $String);
   }
}

if (!function_exists('SignInPopup')) {
   /**
    * Returns a boolean value indicating if sign in windows should be "popped"
    * into modal in-page popups.
    */
   function SignInPopup() {
      return C('Garden.SignIn.Popup') && !IsMobile();
   }
}

if (!function_exists('PrefixString')) {
   /**
    * Takes a string, and prefixes it with $Prefix unless it is already prefixed that way.
    *
    * @param string $Prefix The prefix to use.
    * @param string $String The string to be prefixed.
    */
   function PrefixString($Prefix, $String) {
      if (substr($String, 0, strlen($Prefix)) != $Prefix) {
         $String = $Prefix . $String;
      }
      return $String;
   }
}

if (!function_exists('PrepareArray')) {
   /**
    * Makes sure that the key in question exists and is of the specified type,
    * by default also an array.
    * 
    * @param string $Key Key to prepare
    * @param array $Array Array to repare
    * @param string $PrepareType Optional, 
    */
   function PrepareArray($Key, &$Array, $PrepareType = 'array') {
      if (!array_key_exists($Key, $Array))
         $Array[$Key] = NULL;
      
      switch ($PrepareType) {
         case 'array':
            if (!is_array($Array[$Key]))
               $Array[$Key] = array();
            break;
            
         case 'integer':
            if (!is_integer($Array[$Key]))
               $Array[$Key] = 0;
            break;
            
         case 'float':
            if (!is_float($Array[$Key]))
               $Array[$Key] = 0.0;
            break;
            
         case 'null':
            if (!is_null($Array[$Key]))
               $Array[$Key] = NULL;
            break;
            
         case 'string':
            if (!is_string($Array[$Key]))
               $Array[$Key] = '';
            break;
      }
   }
}

if (!function_exists('ProxyHead')) {
   
   function ProxyHead($Url, $Headers=NULL, $Timeout = FALSE, $FollowRedirects = FALSE) {
      if (is_null($Headers))
         $Headers = array();
      
      $OriginalHeaders = $Headers;
      $OriginalTimeout = $Timeout;
		if(!$Timeout)
			$Timeout = C('Garden.SocketTimeout', 1.0);

      $UrlParts = parse_url($Url);
      $Scheme = GetValue('scheme', $UrlParts, 'http');
      $Host = GetValue('host', $UrlParts, '');
      $Port = GetValue('port', $UrlParts, '80');
      $Path = GetValue('path', $UrlParts, '');
      $Query = GetValue('query', $UrlParts, '');
      
      // Get the cookie.
      $Cookie = '';
      $EncodeCookies = C('Garden.Cookie.Urlencode',TRUE);
      
      foreach($_COOKIE as $Key => $Value) {
         if(strncasecmp($Key, 'XDEBUG', 6) == 0)
            continue;
         
         if(strlen($Cookie) > 0)
            $Cookie .= '; ';
            
         $EValue = ($EncodeCookies) ? urlencode($Value) : $Value;
         $Cookie .= "{$Key}={$EValue}";
      }
      $Cookie = array('Cookie' => $Cookie);
      
      $Response = '';
      if (function_exists('curl_init')) {
         //$Url = $Scheme.'://'.$Host.$Path;
         $Handler = curl_init();
			curl_setopt($Handler, CURLOPT_TIMEOUT, $Timeout);
         curl_setopt($Handler, CURLOPT_URL, $Url);
         curl_setopt($Handler, CURLOPT_PORT, $Port);
         curl_setopt($Handler, CURLOPT_HEADER, 1);
         curl_setopt($Handler, CURLOPT_NOBODY, 1);
         curl_setopt($Handler, CURLOPT_USERAGENT, ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'));
         curl_setopt($Handler, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($Handler, CURLOPT_HTTPHEADER, $Headers);
         
         if (strlen($Cookie['Cookie']))
            curl_setopt($Handler, CURLOPT_COOKIE, $Cookie['Cookie']);
            
         //if ($Query != '') {
         //   curl_setopt($Handler, CURLOPT_POST, 1);
         //   curl_setopt($Handler, CURLOPT_POSTFIELDS, $Query);
         //}
         $Response = curl_exec($Handler);
         if ($Response == FALSE)
            $Response = curl_error($Handler);
            
         curl_close($Handler);
      } else if (function_exists('fsockopen')) {
         $Referer = Gdn::Request()->WebRoot();
      
         // Make the request
         $Pointer = @fsockopen($Host, $Port, $ErrorNumber, $Error, $Timeout);
         if (!$Pointer)
            throw new Exception(sprintf(T('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s'), $Url, $ErrorNumber, $Error));
         
         $Request = "HEAD $Path?$Query HTTP/1.1\r\n";
         
         $HostHeader = $Host.($Post != 80) ? ":{$Port}" : '';
         $Header = array(
            'Host'            => $HostHeader,
            'User-Agent'      => ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'),
            'Accept'          => '*/*',
            'Accept-Charset'  => 'utf-8',
            'Referer'         => $Referer,
            'Connection'      => 'close'
         );
         
         if (strlen($Cookie['Cookie']))
            $Header = array_merge($Header, $Cookie);
            
         $Header = array_merge($Header, $Headers);
         
         $HeaderString = "";
         foreach ($Header as $HeaderName => $HeaderValue) {
            $HeaderString .= "{$HeaderName}: {$HeaderValue}\r\n";
         }
         $HeaderString .= "\r\n";
                  
         // Send the headers and get the response
         fputs($Pointer, $Request);
         fputs($Pointer, $HeaderString);
         while ($Line = fread($Pointer, 4096)) {
            $Response .= $Line;
         }
         @fclose($Pointer);
         $Response = trim($Response);

      } else {
         throw new Exception(T('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.'));
      }
      
      $ResponseLines = explode("\n",trim($Response));
      $Status = array_shift($ResponseLines);
      $Response = array();
      $Response['HTTP'] = trim($Status);
      
      /* get the numeric status code. 
       * - trim off excess edge whitespace, 
       * - split on spaces, 
       * - get the 2nd element (as a single element array), 
       * - pop the first (only) element off it... 
       * - return that.
       */
      $Response['StatusCode'] = array_pop(array_slice(explode(' ',trim($Status)),1,1));
      foreach ($ResponseLines as $Line) {
         $Line = explode(':',trim($Line));
         $Key = trim(array_shift($Line));
         $Value = trim(implode(':',$Line));
         $Response[$Key] = $Value;
      }
      
      if ($FollowRedirects) { 
         $Code = GetValue('StatusCode',$Response, 200);
         if (in_array($Code, array(301,302))) {
            if (array_key_exists('Location', $Response)) {
               $Location = GetValue('Location', $Response);
               return ProxyHead($Location, $OriginalHeaders, $OriginalTimeout, $FollowRedirects);
            }
         }
      }
      
      return $Response;
   }

}

if (!function_exists('ProxyRequest')) {
   /**
    * Uses curl or fsock to make a request to a remote server. Returns the
    * response.
    *
    * @param string $Url The full url to the page being requested (including http://)
    * @param integer $Timeout How long to allow for this request. Default Garden.SocketTimeout or 1, 0 to never timeout
    * @param boolean $FollowRedirects Whether or not to follow 301 and 302 redirects. Defaults false.
    * @return string Response (no headers)
    */
   function ProxyRequest($Url, $Timeout = FALSE, $FollowRedirects = FALSE) {
      $OriginalTimeout = $Timeout;
      if ($Timeout === FALSE)
         $Timeout = C('Garden.SocketTimeout', 1.0);

      $UrlParts = parse_url($Url);
      $Scheme = GetValue('scheme', $UrlParts, 'http');
      $Host = GetValue('host', $UrlParts, '');
      $Port = GetValue('port', $UrlParts, $Scheme == 'https' ? '443' : '80');
      $Path = GetValue('path', $UrlParts, '');
      $Query = GetValue('query', $UrlParts, '');
      // Get the cookie.
      $Cookie = '';
      $EncodeCookies = C('Garden.Cookie.Urlencode',TRUE);
      
      foreach($_COOKIE as $Key => $Value) {
         if(strncasecmp($Key, 'XDEBUG', 6) == 0)
            continue;
         
         if(strlen($Cookie) > 0)
            $Cookie .= '; ';
            
         $EValue = ($EncodeCookies) ? urlencode($Value) : $Value;
         $Cookie .= "{$Key}={$EValue}";
      }
      $Response = '';
      if (function_exists('curl_init')) {
         //$Url = $Scheme.'://'.$Host.$Path;
         $Handler = curl_init();
         curl_setopt($Handler, CURLOPT_URL, $Url);
         curl_setopt($Handler, CURLOPT_PORT, $Port);
         curl_setopt($Handler, CURLOPT_SSL_VERIFYPEER, FALSE);
         curl_setopt($Handler, CURLOPT_HEADER, 1);
         curl_setopt($Handler, CURLOPT_USERAGENT, ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'));
         curl_setopt($Handler, CURLOPT_RETURNTRANSFER, 1);
         
         if ($Cookie != '')
            curl_setopt($Handler, CURLOPT_COOKIE, $Cookie);
         
         if ($Timeout > 0)
            curl_setopt($Handler, CURLOPT_TIMEOUT, $Timeout);
         
         // TIM @ 2010-06-28: Commented this out because it was forcing all requests with parameters to be POST. Same for the $Url above
         // 
         //if ($Query != '') {
         //   curl_setopt($Handler, CURLOPT_POST, 1);
         //   curl_setopt($Handler, CURLOPT_POSTFIELDS, $Query);
         //}
         $Response = curl_exec($Handler);
         $Success = TRUE;
         if ($Response == FALSE) {
            $Success = FALSE;
            $Response = '';
            throw new Exception(curl_error($Handler));
         }
         
         curl_close($Handler);
      } else if (function_exists('fsockopen')) {
         $Referer = Gdn_Url::WebRoot(TRUE);
      
         // Make the request
         $Pointer = @fsockopen($Host, $Port, $ErrorNumber, $Error, $Timeout);
         if (!$Pointer)
            throw new Exception(sprintf(T('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s'), $Url, $ErrorNumber, $Error));
   
         stream_set_timeout($Pointer, $Timeout);
         if (strlen($Cookie) > 0)
            $Cookie = "Cookie: $Cookie\r\n";
         
         $HostHeader = $Host.(($Port != 80) ? ":{$Port}" : '');
         $Header = "GET $Path?$Query HTTP/1.1\r\n"
            ."Host: {$HostHeader}\r\n"
            // If you've got basic authentication enabled for the app, you're going to need to explicitly define the user/pass for this fsock call
            // "Authorization: Basic ". base64_encode ("username:password")."\r\n" . 
            ."User-Agent: ".ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0')."\r\n"
            ."Accept: */*\r\n"
            ."Accept-Charset: utf-8;\r\n"
            ."Referer: {$Referer}\r\n"
            ."Connection: close\r\n";
            
         if ($Cookie != '')
            $Header .= $Cookie;
         
         $Header .= "\r\n";
         
         // Send the headers and get the response
         fputs($Pointer, $Header);
         while ($Line = fread($Pointer, 4096)) {
            $Response .= $Line;
         }
         @fclose($Pointer);
         $Bytes = strlen($Response);
         $Response = trim($Response);
         $Success = TRUE;
         
         $StreamInfo = stream_get_meta_data($Pointer);
         if (GetValue('timed_out', $StreamInfo, FALSE) === TRUE) {
            $Success = FALSE;
            $Response = "Operation timed out after {$Timeout} seconds with {$Bytes} bytes received.";
         }
      } else {
         throw new Exception(T('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.'));
      }
      
      if (!$Success)
         return $Response;
      
      $ResponseHeaderData = trim(substr($Response, 0, strpos($Response, "\r\n\r\n")));
      $Response = trim(substr($Response, strpos($Response, "\r\n\r\n") + 4));
      
      $ResponseHeaderLines = explode("\n",trim($ResponseHeaderData));
      $Status = array_shift($ResponseHeaderLines);
      $ResponseHeaders = array();
      $ResponseHeaders['HTTP'] = trim($Status);
      
      /* get the numeric status code. 
       * - trim off excess edge whitespace, 
       * - split on spaces, 
       * - get the 2nd element (as a single element array), 
       * - pop the first (only) element off it... 
       * - return that.
       */
      $ResponseHeaders['StatusCode'] = array_pop(array_slice(explode(' ',trim($Status)),1,1));
      foreach ($ResponseHeaderLines as $Line) {
         $Line = explode(':',trim($Line));
         $Key = trim(array_shift($Line));
         $Value = trim(implode(':',$Line));
         $ResponseHeaders[$Key] = $Value;
      }
      
      if ($FollowRedirects) { 
         $Code = GetValue('StatusCode',$ResponseHeaders, 200);
         if (in_array($Code, array(301,302))) {
            if (array_key_exists('Location', $ResponseHeaders)) {
               $Location = AbsoluteSource(GetValue('Location', $ResponseHeaders), $Url);
               return ProxyRequest($Location, $OriginalTimeout, $FollowRedirects);
            }
         }
      }
      
      return $Response;
   }
}

if (!function_exists('RandomString')) {
   function RandomString($Length, $Characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
      $CharLen = strlen($Characters) - 1;
      $String = '' ;
      for ($i = 0; $i < $Length; ++$i) {
        $Offset = mt_rand() % $CharLen;
        $String .= substr($Characters, $Offset, 1);
      }
      return $String;
   }
}

if (!function_exists('BetterRandomString')) {
   function BetterRandomString($Length, $CharacterOptions = 'A0') {
      $CharacterClasses = array(
          'A' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
          'a' => 'abcdefghijklmnopqrstuvwxyz',
          '0' => '0123456789',
          '!' => '~!@#$^&*_+-'
      );

      $Characters = '';
      for ($i=0;$i<strlen($CharacterOptions);$i++)
         $Characters .= GetValue($CharacterOptions{$i}, $CharacterClasses);

      $CharLen = strlen($Characters) - 1;
      $String = '' ;
      for ($i = 0; $i < $Length; ++$i) {
        $Offset = rand() % $CharLen;
        $String .= substr($Characters, $Offset, 1);
      }
      return $String;
   }
}

if (!function_exists('Redirect')) {
   function Redirect($Destination = FALSE, $StatusCode = NULL) {
      if (!$Destination)
         $Destination = Url('');
      
      if (Debug() && $Trace = Trace()) {
         Trace("Redirecting to $Destination");
         return;
      }
         
      // Close any db connections before exit
      $Database = Gdn::Database();
      $Database->CloseConnection();
      // Clear out any previously sent content
      @ob_end_clean();
      
      // assign status code
      $SendCode = (is_null($StatusCode)) ? 302 : $StatusCode;
      // re-assign the location header
      header("Location: ".Url($Destination), TRUE, $SendCode);
      // Exit
      exit();
   }
}

if (!function_exists('ReflectArgs')) {
   /**
    * Reflect the arguments on a callback and returns them as an associative array.
    * @param callback $Callback A callback to the function.
    * @param array $Args1 An array of arguments.
    * @param array $Args2 An optional other array of arguments.
    * @return array The arguments in an associative array, in order ready to be passed to call_user_func_array().
    */
   function ReflectArgs($Callback, $Args1, $Args2 = NULL) {
      $Result = array();
      
      if (is_string($Callback) && !function_exists($Callback))
         throw new Exception("Function $Callback does not exist");
      
      if (is_array($Callback) && !method_exists($Callback[0], $Callback[1]))
         throw new Exception("Method {$Callback[1]} does not exist.");
      
      if ($Args2 !== NULL)
         $Args1 = array_merge($Args2, $Args1);
      $Args1 = array_change_key_case($Args1);

      if (is_string($Callback)) {
         $Meth = new ReflectionFunction($Callback);
         $MethName = $Meth;
      } else {
         $Meth = new ReflectionMethod($Callback[0], $Callback[1]);
         if (is_string($Callback[0])) {
            $MethName = $Callback[0].'::'.$Meth->getName();
         } else {
            $MethName = get_class($Callback[0]).'->'.$Meth->getName();
         }
      }
      
      $MethArgs = $Meth->getParameters();
      
      $Args = array();
      $MissingArgs = array();

      // Set all of the parameters.
      foreach ($MethArgs as $Index => $MethParam) {
         $ParamName = $MethParam->getName();
         $ParamNameL = strtolower($ParamName);

         if (isset($Args1[$ParamNameL]))
            $ParamValue = $Args1[$ParamNameL];
         elseif (isset($Args1[$Index]))
            $ParamValue = $Args1[$Index];
         elseif ($MethParam->isDefaultValueAvailable())
            $ParamValue = $MethParam->getDefaultValue();
         else {
            $ParamValue = NULL;
            $MissingArgs[] = '$'.$ParamName;
         }
         
         $Args[$ParamName] = $ParamValue;
      }
      
      // Add optional parameters so that methods that use get_func_args() will still work.
      for ($Index = count($Args); array_key_exists($Index, $Args1); $Index++) {
         $Args[$Index] = $Args1[$Index];
      }
      
      if (count($MissingArgs) > 0) {
         trigger_error("$MethName() expects the following parameters: ".implode(', ', $MissingArgs).'.', E_USER_NOTICE);
      }

      return $Args;
   }
}

if (!function_exists('RemoteIP')) {
   function RemoteIP() {
      return GetValue('REMOTE_ADDR', $_SERVER, 'undefined');
   }
}

if (!function_exists('RemoveFromConfig')) {
   function RemoveFromConfig($Name, $Options = array()) {
      Gdn::Config()->RemoveFromConfig($Name, $Options);
   }
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

if (!function_exists('RemoveKeysFromNestedArray')) {
   function RemoveKeysFromNestedArray($Array, $Matches) {
      if (is_array($Array)) {
         foreach ($Array as $Key => $Value) {
            $IsMatch = FALSE;
            foreach ($Matches as $Match) {
               if (StringEndsWith($Key, $Match)) {
                  unset($Array[$Key]);
                  $IsMatch = TRUE;
               }
            }
            if (!$IsMatch && (is_array($Value) || is_object($Value)))
               $Array[$Key] = RemoveKeysFromNestedArray($Value, $Matches);
         }
      } else if (is_object($Array)) {
         $Arr = get_object_vars($Array);
         foreach ($Arr as $Key => $Value) {
            $IsMatch = FALSE;
            foreach ($Matches as $Match) {
               if (StringEndsWith($Key, $Match)) {
                  unset($Array->$Key);
                  $IsMatch = TRUE;
               }
            }
            if (!$IsMatch && (is_array($Value) || is_object($Value)))
               $Array->$Key = RemoveKeysFromNestedArray($Value, $Matches);
         }
      }
      return $Array;
   }
}

if (!function_exists('RemoveQuoteSlashes')) {
 	function RemoveQuoteSlashes($String) {
		return str_replace("\\\"", '"', $String);
	}
}

if (!function_exists('RemoveValueFromArray')) {
   function RemoveValueFromArray(&$Array, $Value) {
      foreach ($Array as $key => $val) {
         if ($val == $Value) {
            unset($Array[$key]);
            break;
         }
      }
   }
}

if (!function_exists('SafeGlob')) {
   function SafeGlob($Pattern, $Extensions = array()) {
      $Result = glob($Pattern);
      if (!is_array($Result))
         $Result = array();

      // Check against allowed extensions.
      if (count($Extensions) > 0) {
         foreach ($Result as $Index => $Path) {
            if (!$Path)
               continue;
            if (!in_array(strtolower(pathinfo($Path, PATHINFO_EXTENSION)), $Extensions))
               unset($Result[$Index]);
         }
      }
         
      return $Result;
   }
}

if (!function_exists('SafeImage')) {
   /**
    * Examines the provided url & checks to see if there is a valid image on the other side. Optionally you can specify minimum dimensions.
    * @param string $ImageUrl Full url (including http) of the image to examine.
    * @param int $MinHeight Minimum height (in pixels) of image. 0 means any height.
    * @param int $MinWidth Minimum width (in pixels) of image. 0 means any width.
    * @return mixed The url of the image if safe, FALSE otherwise.
    */
   function SafeImage($ImageUrl, $MinHeight = 0, $MinWidth = 0) {
      try {
         list($Width, $Height, $Type, $Attributes) = getimagesize($ImageUrl);
         if ($MinHeight > 0 && $MinHeight < $Height)
            return FALSE;
         
         if ($MinWidth > 0 && $MinWidth < $Width)
            return FALSE;
      } catch (Exception $ex) {
         return FALSE;
      }
      return $ImageUrl;
   }
}

if (!function_exists('SafeParseStr')) {
   function SafeParseStr($Str, &$Output, $Original = NULL) {
      $Exploded = explode('&',$Str);
      $Output = array();
      if (is_array($Original)) {
         $FirstValue = reset($Original);
         $FirstKey = key($Original);
         unset($Original[$FirstKey]);
      }
      foreach ($Exploded as $Parameter) {
         $Parts = explode('=', $Parameter);
         $Key = $Parts[0];
         $Value = count($Parts) > 1 ? $Parts[1] : '';
         
         if (!is_null($Original)) {
            $Output[$Key] = $FirstValue;
            $Output = array_merge($Output, $Original);
            break;
         }
         
         $Output[$Key] = $Value;
      }
   }
}

if (!function_exists('SaveToConfig')) {
   /**
    * Save values to the application's configuration file.
    *
    * @param string|array $Name One of the following:
    *  - string: The key to save.
    *  - array: An array of key/value pairs to save.
    * @param mixed|null $Value The value to save.
    * @param array $Options An array of additional options for the save.
    *  - Save: If this is false then only the in-memory config is set.
    *  - RemoveEmpty: If this is true then empty/false values will be removed from the config.
    * @return bool: Whether or not the save was successful. NULL if no changes were necessary.
    */
   function SaveToConfig($Name, $Value = '', $Options = array()) {
      Gdn::Config()->SaveToConfig($Name, $Value, $Options);
   }
}

if (!function_exists('SliceParagraph')) {
   /**
    * Slices a string at a paragraph. 
    * This function will attempt to slice a string at paragraph that is no longer than the specified maximum length.
    * If it can't slice the string at a paragraph it will attempt to slice on a sentence.
    * 
    * Note that you should not expect this function to return a string that is always shorter than max-length.
    * The purpose of this function is to provide a string that is reaonably easy to consume by a human.
    * 
    * @param string $String The string to slice.
    * @param int $MaxLength The maximum length of the string.
    * @param string $Suffix The suffix if the string must be sliced mid-sentence.
    * @return string
    */
   function SliceParagraph($String, $MaxLength = 500, $Suffix = '…') {
      if ($MaxLength >= strlen($String))
         return $String;
      
//      $String = preg_replace('`\s+\n`', "\n", $String);
      
      // See if there is a paragraph.
      $Pos = strrpos(SliceString($String, $MaxLength, ''), "\n\n");
      
      if ($Pos === FALSE) {
         // There was no paragraph so try and split on sentences.
         $Sentences = preg_split('`([.!?:]\s+)`', $String, NULL, PREG_SPLIT_DELIM_CAPTURE);
         
         $Result = '';
         if (count($Sentences) > 1) {
            $Result = $Sentences[0].$Sentences[1];
            
            for ($i = 2; $i < count($Sentences); $i++) {
               $Sentence = $Sentences[$i];
               
               if ((strlen($Result) + strlen($Sentence)) <= $MaxLength || preg_match('`[.!?:]\s+`', $Sentence))
                  $Result .= $Sentence;
               else
                  break;
            }
         }
         
         if ($Result) {
            return rtrim($Result);
         }
         
         // There was no sentence. Slice off the last word and call it a day.
         $Pos = strrpos(SliceString($String, $MaxLength, ''), ' ');
         if ($Pos === FALSE) {
            return $String.$Suffix;
         } else {
            return SliceString($String, $Pos + 1, $Suffix);
         }
      } else {
         return substr($String, 0, $Pos + 1);
      }
   }
}

if (!function_exists('SliceString')) {
   function SliceString($String, $Length, $Suffix = '…') {
      if (function_exists('mb_strimwidth')) {
      	static $Charset;
      	if(is_null($Charset)) $Charset = Gdn::Config('Garden.Charset', 'utf-8');
      	return mb_strimwidth($String, 0, $Length, $Suffix, $Charset);
      } else {
         $Trim = substr($String, 0, $Length);
         return $Trim . ((strlen($Trim) != strlen($String)) ? $Suffix: ''); 
      }
   }
}

if (!function_exists('SmartAsset')) {
   /**
    * Takes the path to an asset (image, js file, css file, etc) and prepends the webroot.
    */
   function SmartAsset($Destination = '', $WithDomain = FALSE, $AddVersion = FALSE) {
      $Destination = str_replace('\\', '/', $Destination);
      if (substr($Destination, 0, 7) == 'http://' || substr($Destination, 0, 8) == 'https://') {
         $Result = $Destination;
      } else {
         $Parts = array(Gdn_Url::WebRoot($WithDomain), $Destination);
         if (!$WithDomain)
            array_unshift($Parts, '/');
            
         $Result = CombinePaths($Parts, '/');
      }

      if ($AddVersion) {
         if (strpos($Result, '?') === FALSE)
            $Result .= '?';
         else
            $Result .= '&';

         // Figure out which version to put after the asset.
         $Version = APPLICATION_VERSION;
         if (preg_match('`^/([^/]+)/([^/]+)/`', $Destination, $Matches)) {
            $Type = $Matches[1];
            $Key = $Matches[2];
            static $ThemeVersion = NULL;

            switch ($Type) {
               case 'plugins':
                  $PluginInfo = Gdn::PluginManager()->GetPluginInfo($Key);
                  $Version = GetValue('Version', $PluginInfo, $Version);
                  break;
               case 'themes':
                  if ($ThemeVersion === NULL) {
                     $ThemeInfo = Gdn::ThemeManager()->GetThemeInfo(Theme());
                     if ($ThemeInfo !== FALSE) {
                        $ThemeVersion = GetValue('Version', $ThemeInfo, $Version);
                     } else {
                        $ThemeVersion = $Version;
                     }
                  }
                  $Version = $ThemeVersion;
                  break;
            }
         }

         $Result.= 'v='.urlencode($Version);
      }
      return $Result;
   }
}

if (!function_exists('StringBeginsWith')) {
   /** Checks whether or not string A begins with string B.
    *
    * @param string $Haystack The main string to check.
    * @param string $Needle The substring to check against.
    * @param bool $CaseInsensitive Whether or not the comparison should be case insensitive.
    * @param bool Whether or not to trim $B off of $A if it is found.
    * @return bool|string Returns true/false unless $Trim is true.
    */
   function StringBeginsWith($Haystack, $Needle, $CaseInsensitive = FALSE, $Trim = FALSE) {
      if (strlen($Haystack) < strlen($Needle))
         return $Trim ? $Haystack : FALSE;
      elseif (strlen($Needle) == 0) {
         if ($Trim)
            return $Haystack;
         return TRUE;
      } else {
         $Result = substr_compare($Haystack, $Needle, 0, strlen($Needle), $CaseInsensitive) == 0;
         if ($Trim)
            $Result = $Result ? substr($Haystack, strlen($Needle)) : $Haystack;
         return $Result;
      }
   }
}

if (!function_exists('StringEndsWith')) {
   /** Checks whether or not string A ends with string B.
    *
    * @param string $Haystack The main string to check.
    * @param string $Needle The substring to check against.
    * @param bool $CaseInsensitive Whether or not the comparison should be case insensitive.
    * @param bool Whether or not to trim $B off of $A if it is found.
    * @return bool|string Returns true/false unless $Trim is true.
    */
   function StringEndsWith($Haystack, $Needle, $CaseInsensitive = FALSE, $Trim = FALSE) {
      if (strlen($Haystack) < strlen($Needle)) {
         return $Trim ? $Haystack : FALSE;
      } elseif (strlen($Needle) == 0) {
         if ($Trim)
            return $Haystack;
         return TRUE;
      } else {
         $Result = substr_compare($Haystack, $Needle, -strlen($Needle), strlen($Needle), $CaseInsensitive) == 0;
         if ($Trim)
            $Result = $Result ? substr($Haystack, 0, -strlen($Needle)) : $Haystack;
         return $Result;
      }
   }
}

if (!function_exists('StringIsNullOrEmpty')) {
   /** Checks whether or not a string is null or an empty string (after trimming).
    *
    * @param string $String The string to check.
    * @return bool
    */
   function StringIsNullOrEmpty($String) {
      return is_null($String) === TRUE || (is_string($String) && trim($String) == '');
   }
}


if (!function_exists('SetValue')) {
	/**
	 * Set the value on an object/array.
	 *
	 * @param string $Needle The key or property name of the value.
	 * @param mixed $Haystack The array or object to set.
	 * @param mixed $Value The value to set.
	 */
	function SetValue($Key, &$Collection, $Value) {
		if(is_array($Collection))
			$Collection[$Key] = $Value;
		elseif(is_object($Collection))
			$Collection->$Key = $Value;
	}
}


if (!function_exists('T')) {
   /**
	 * Translates a code into the selected locale's definition.
	 *
	 * @param string $Code The code related to the language-specific definition.
    *   Codes thst begin with an '@' symbol are treated as literals and not translated.
	 * @param string $Default The default value to be displayed if the translation code is not found.
	 * @return string The translated string or $Code if there is no value in $Default.
	 * @see Gdn::Translate()
	 */
   function T($Code, $Default = FALSE) {
      return Gdn::Translate($Code, $Default);
   }
}

if (!function_exists('Theme')) {
   function Theme() {
      return C(!IsMobile() ? 'Garden.Theme' : 'Garden.MobileTheme', 'default');
   }
}

if (!function_exists('TouchValue')) {
	/**
	 * Set the value on an object/array if it doesn't already exist.
	 *
	 * @param string $Key The key or property name of the value.
	 * @param mixed $Collection The array or object to set.
	 * @param mixed $Default The value to set.
	 */
	function TouchValue($Key, &$Collection, $Default) {
		if(is_array($Collection) && !array_key_exists($Key, $Collection))
			$Collection[$Key] = $Default;
		elseif(is_object($Collection) && !property_exists($Collection, $Key))
			$Collection->$Key = $Default;

      return GetValue($Key, $Collection);
	}
}

if (!function_exists('Trace')) {
   function Trace($Value = NULL, $Type = TRACE_INFO) {
      static $Traces = array();
      
      if ($Value === NULL)
         return $Traces;
      
      $Traces[] = array($Value, $Type);
   }
}

if (!function_exists('TrueStripSlashes')) {
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
   function Url($Path = '', $WithDomain = FALSE, $RemoveSyndication = FALSE) {
      $Result = Gdn::Request()->Url($Path, $WithDomain);
      return $Result;
   }
}
