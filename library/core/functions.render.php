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

if (!function_exists('CountString')) {
   function CountString($Number, $Url = '', $Options = array()) {
      if (is_string($Options))
         $Options = array('cssclass' => $Options);
      $Options = array_change_key_case($Options);
      $CssClass = GetValue('cssclass', $Options, '');

      if ($Number === NULL && $Url) {
         $CssClass = ConcatSep(' ', $CssClass, 'Popin TinyProgress');
         $Url = htmlspecialchars($Url);
         $Result = "<span class=\"$CssClass\" rel=\"$Url\"></span>";
      } elseif ($Number) {
         $Result = " <span class=\"Count\">$Number</span>";
      } else {
         $Result = '';
      }
      return $Result;
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

      return '<a href="'.htmlspecialchars($Destination, ENT_COMPAT, 'UTF-8').'"'.Attribute($CssClass).Attribute($Attributes).'>'.$Text.'</a>';
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
         $Image = SmartAsset($Image, $WithDomain);

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
		// Make sure to fix comma-formatted numbers
      $WorkingNumber = str_replace(',', '', $Number);
      return sprintf(T($WorkingNumber == 1 ? $Singular : $Plural), $Number);
   }
}

/**
 * Takes a user object, and writes out an achor of the user's name to the user's profile.
 */
if (!function_exists('UserAnchor')) {
   function UserAnchor($User, $CssClass = '', $Options = array()) {
      $User = (object)$User;
      
      if ($CssClass != '')
         $CssClass = ' class="'.$CssClass.'"';

      return '<a href="'.Url('/profile/'.$User->UserID.'/'.rawurlencode($User->Name)).'"'.$CssClass.'>'.$User->Name.'</a>';
   }
}

/**
 * Takes an object & prefix value, and converts it to a user object that can be
 * used by UserAnchor() && UserPhoto() to write out anchors to the user's
 * profile. The object must have the following fields: UserID, Name, Photo.
 */
if (!function_exists('UserBuilder')) {
   function UserBuilder($Object, $UserPrefix = '') {
		$Object = (object)$Object;
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
   function UserPhoto($User, $Options = array()) {
		$User = (object)$User;
      if (is_string($Options))
         $Options = array('LinkClass' => $Options);
      
      $LinkClass = GetValue('LinkClass', $Options, 'ProfileLink');
      $ImgClass = GetValue('ImageClass', $Options, 'ProfilePhotoBig');
      
      $LinkClass = $LinkClass == '' ? '' : ' class="'.$LinkClass.'"';
      if ($User->Photo) {
         if (!preg_match('`^https?://`i', $User->Photo)) {
            $PhotoUrl = Gdn_Upload::Url(ChangeBasename($User->Photo, 'n%s'));
         } else {
            $PhotoUrl = $User->Photo;
         }
         
         return '<a title="'.htmlspecialchars($User->Name).'" href="'.Url('/profile/'.$User->UserID.'/'.rawurlencode($User->Name)).'"'.$LinkClass.'>'
            .Img($PhotoUrl, array('alt' => htmlspecialchars($User->Name), 'class' => $ImgClass))
            .'</a>';
      } elseif (function_exists('UserPhotoDefault')) {
         return UserPhotoDefault($User, $Options);
      } else {
         return '';
      }
   }
}

if (!function_exists('UserUrl')) {
   /**
    * Return the url for a user.
    * @param array|object $User The user to get the url for.
    * @return string The url suitable to be passed into the Url() function.
    */
   function UserUrl($User) {
      return '/profile/'.rawurlencode(GetValue('Name', $User));
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

if (!function_exists('RegisterUrl')) {
   function RegisterUrl($Target = '') {
      return '/entry/register'.($Target ? '?Target='.urlencode($Target) : '');
   }
}

if (!function_exists('SignInUrl')) {
   function SignInUrl($Target = '') {
      return '/entry/signin'.($Target ? '?Target='.urlencode($Target) : '');
   }
}

if (!function_exists('SignOutUrl')) {
   function SignOutUrl($Target = '') {
      return '/entry/signout?TransientKey='.urlencode(Gdn::Session()->TransientKey()).($Target ? '&Target='.urlencode($Target) : '');
   }
}