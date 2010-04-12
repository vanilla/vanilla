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

      $Prefix = substr($Destination, 0, 7);
      if (!in_array($Prefix, array('http://', 'mailto:')) && ($Destination != '' || $ForceAnchor === FALSE))
         $Destination = Url($Destination);

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
      return substr($Word, -1) == 's' ? $Word."'" : $Word."'s";
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

      if (substr($Image, 0, 7) != 'http://' && $Image != '')
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
      return T($Number == 1 ? $Singular : $Plural);
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
         $PhotoUrl = strtolower(substr($User->Photo, 0, 7)) == 'http://' ? $User->Photo : 'uploads/n'.$User->Photo;
         return '<a href="'.Url('/profile/'.$User->UserID.'/'.urlencode($User->Name)).'"'.$CssClass.'>'
            .Img($PhotoUrl, array('alt' => urlencode($User->Name)))
            .'</a>';
      } else {
         return '';
      }
   }
}