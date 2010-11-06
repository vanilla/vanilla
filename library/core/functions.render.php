<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
* 1. <li<?php echo Alternate()?>>
* Result: <li class="Alt"> and <li>
* 2. <li class="<?php echo Alternate('AltA', 'AltB')"?>>
* Result: <li class="AltA"> and <li class="AltB">
*/
if (!function_exists('Alternate')) {
   function Alternate($Odd = 'Alt', $Even = '', $AttributeName = 'class'){
      static $i = 0;
      $Value = $i++ % 2 ? $Odd : $Even;
      if($Value != '' && $Even == '')
         $Value = ' '.$AttributeName.'="'.$Value.'"';
      return $Value;
   }
}

/**
 * Writes an anchor tag
 */
if (!function_exists('Anchor')) {
   /**
    * Builds and returns an anchor tag.
    */
   function Anchor($Text, $Destination = '', $CssClass = '', $Attributes = '', $ForceAnchor = FALSE) {
      if (!is_array($CssClass) && $CssClass != '')
         $CssClass = array('class' => $CssClass);

      if ($Destination == '' && $ForceAnchor === FALSE)
         return $Text;
      
      if ($Attributes == '')
         $Attributes = array();
			
		$SSL = GetValue('SSL', $Attributes, NULL);
		if ($SSL)
			unset($Attributes['SSL']);
		
		$WithDomain = GetValue('WithDomain', $Attributes, FALSE);
		if ($WithDomain)
			unset($Attributes['WithDomain']);

      $Prefix = substr($Destination, 0, 7);
      if (!in_array($Prefix, array('https:/', 'http://', 'mailto:')) && ($Destination != '' || $ForceAnchor === FALSE))
         $Destination = Gdn::Request()->Url($Destination, $WithDomain, $SSL);

      return '<a href="'.$Destination.'"'.Attribute($CssClass).Attribute($Attributes).'>'.$Text.'</a>';
   }
}

/**
 * English "possessive" formatting.
 * This can be overridden in language definition files like:
 * /applications/garden/locale/en-US/definitions.php.
 */
if (!function_exists('FormatPossessive')) {
   function FormatPossessive($Word) {
		if(function_exists('FormatPossessiveCustom'))
			return FormatPossesiveCustom($Word);
			
      return substr($Word, -1) == 's' ? $Word."'" : $Word."'s";
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
 * @param array $Args The array of arguments. If you want to nest arrays then the keys to the nested values can be seperated by dots.
 * @return string The formatted string.
 * <code>
 * echo FormatString("Hello {Name}, It's {Now,time}.", array('Name' => 'Frank', 'Now' => '1999-12-31 23:59'));
 * // This would output the following string:
 * // Hello Frank, It's 12:59PM.
 * </code>
 */
function FormatString($String, $Args) {
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
   $Format = strtolower(trim(GetValue(1, $Parts, '')));
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
   if ($Value == '' && $Format != 'url') {
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
         case 'time':
            $Result = Gdn_Format::Date($Value, '%l:%M%p');
            break;
         case 'url':
            if (strpos($Field, '/') !== FALSE)
               $Value = $Field;
            $Result = Url($Value, $SubFormat == 'domain');
            break;
         default:
            $Result = $Value;
            break;
      }
   }
   return $Result;
}

if (!function_exists('HoverHelp')) {
   function HoverHelp($String, $Help) {
      return Wrap($String.Wrap($Help, 'span', array('class' => 'Help')), 'span', array('class' => 'HoverHelp'));
   }
}

/**
 * Writes an Img tag.
 */
if (!function_exists('Img')) {
   /**
    * Returns an img tag.
    */
   function Img($Image, $Attributes = '', $WithDomain = FALSE) {
      if ($Attributes == '')
         $Attributes = array();

      if ($Image != '' && substr($Image, 0, 7) != 'http://' && substr($Image, 0, 8) != 'https://')
         $Image = Asset($Image, $WithDomain);

      return '<img src="'.$Image.'"'.Attribute($Attributes).' />';
   }
}

/**
 * English "plural" formatting.
 * This can be overridden in language definition files like:
 * /applications/garden/locale/en-US/definitions.php.
 */
if (!function_exists('Plural')) {
   function Plural($Number, $Singular, $Plural) {
      return sprintf(T($Number == 1 ? $Singular : $Plural), $Number);
   }
}

/**
 * Takes a user object, and writes out an achor of the user's name to the user's profile.
 */
if (!function_exists('UserAnchor')) {
   function UserAnchor($User, $CssClass = '') {
      if ($CssClass != '')
         $CssClass = ' class="'.$CssClass.'"';

      return '<a href="'.Url('/profile/'.$User->UserID.'/'.urlencode($User->Name)).'"'.$CssClass.'>'.$User->Name.'</a>';
   }
}

/**
 * Takes an object & prefix value, and converts it to a user object that can be
 * used by UserAnchor() && UserPhoto() to write out anchors to the user's
 * profile. The object must have the following fields: UserID, Name, Photo.
 */
if (!function_exists('UserBuilder')) {
   function UserBuilder($Object, $UserPrefix = '') {
      $User = new stdClass();
      $UserID = $UserPrefix.'UserID';
      $Name = $UserPrefix.'Name';
      $Photo = $UserPrefix.'Photo';
      $User->UserID = $Object->$UserID;
      $User->Name = $Object->$Name;
      $User->Photo = property_exists($Object, $Photo) ? $Object->$Photo : '';
		return $User;
   }
}

/**
 * Takes a user object, and writes out an anchor of the user's icon to the user's profile.
 */
if (!function_exists('UserPhoto')) {
   function UserPhoto($User, $CssClass = '') {
      $CssClass = $CssClass == '' ? '' : ' class="'.$CssClass.'"';
      if ($User->Photo != '') {
         $IsFullPath = strtolower(substr($User->Photo, 0, 7)) == 'http://' || strtolower(substr($User->Photo, 0, 8)) == 'https://'; 
         $PhotoUrl = ($IsFullPath) ? $User->Photo : 'uploads/'.ChangeBasename($User->Photo, 'n%s');
         return '<a href="'.Url('/profile/'.$User->UserID.'/'.urlencode($User->Name)).'"'.$CssClass.'>'
            .Img($PhotoUrl, array('alt' => urlencode($User->Name)))
            .'</a>';
      } else {
         return '';
      }
   }
}
/**
 * Wrap the provided string in the specified tag. ie. Wrap('This is bold!', 'b');
 */
if (!function_exists('Wrap')) {
   function Wrap($String, $Tag = 'span', $Attributes = '') {
		if ($Tag == '')
			return $String;
		
      if (is_array($Attributes))
         $Attributes = Attribute($Attributes);
         
      return '<'.$Tag.$Attributes.'>'.$String.'</'.$Tag.'>';
   }
}
/**
 * Wrap the provided string in the specified tag. ie. Wrap('This is bold!', 'b');
 */
if (!function_exists('DiscussionLink')) {
   function DiscussionLink($Discussion, $Extended = TRUE) {
      $DiscussionID = GetValue('DiscussionID', $Discussion);
      $DiscussionName = GetValue('Name', $Discussion);
      $Parts = array(
         'discussion',
         $DiscussionID,
         Gdn_Format::Url($DiscussionName)
      );
      if ($Extended) {
         $Parts[] = ($Discussion->CountCommentWatch > 0) ? '#Item_'.$Discussion->CountCommentWatch : '';
      }
		return Url(implode('/',$Parts), TRUE);
   }
}