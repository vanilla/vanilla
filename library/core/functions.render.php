<?php if (!defined('APPLICATION')) exit();

/**
 * UI functions
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

if (!function_exists('Alternate')) {
   function Alternate($Odd = 'Alt', $Even = '', $AttributeName = 'class'){
      static $i = 0;
      $Value = $i++ % 2 ? $Odd : $Even;
      if($Value != '' && $Even == '' && $AttributeName)
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

if (!function_exists('FixNl2Br')) {
   /**
    * Removes the break above and below tags that have a natural margin.
    * @param string $Text The text to fix.
    * @return string
    * @since 2.1
    */
   function FixNl2Br($Text) {
      $allblocks = '(?:table|dl|ul|ol|pre|blockquote|address|p|h[1-6]|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
      $Text = preg_replace('!(?:<br\s*/>){1,2}\s*(<' . $allblocks . '[^>]*>)!', "\n$1", $Text);
      $Text = preg_replace('!(</' . $allblocks . '[^>]*>)\s*(?:<br\s*/>){1,2}!', "$1\n", $Text);
      return $Text;
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

if (!function_exists('FormatUsername')) {
   function FormatUsername($User, $Format, $ViewingUserID = FALSE) {
      if ($ViewingUserID === FALSE)
         $ViewingUserID = Gdn::Session()->UserID;
      $UserID = GetValue('UserID', $User);
      $Name = GetValue('Name', $User);
      $Gender = strtolower(GetValue('Gender', $User));
      
      $UCFirst = substr($Format, 0, 1) == strtoupper(substr($Format, 0, 1));
      
      
      switch (strtolower($Format)) {
         case 'you':
            if ($ViewingUserID == $UserID)
               return T("Format $Format", $Format);
            return $Name;
         case 'his':
         case 'her':
         case 'your':
            if ($ViewingUserID == $UserID)
               return T("Format Your", 'Your');
            else {
               switch ($Gender) {
                  case 'm':
                     $Format = 'his';
                     break;
                  case 'f':
                     $Format = 'her';
                     break;
                  default:
                     $Format = 'their';
                     break;
               }
               if ($UCFirst)
                  $Format = ucfirst($Format);
               return T("Format $Format", $Format);
            }
         default:
            return $Name;
      }
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

if (!function_exists('IPAnchor')) {
   /**
    * Returns an IP address with a link to the user search.
    */
   function IPAnchor($IP, $CssClass = '') {
      if ($IP)
         return Anchor(htmlspecialchars($IP), '/user/browse?keywords='.urlencode($IP), $CssClass);
      else
         return $IP;
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
   function UserAnchor($User, $CssClass = NULL, $Options = NULL) {
      static $NameUnique = NULL;
      if ($NameUnique === NULL)
         $NameUnique = C('Garden.Registration.NameUnique');
      
      if (is_array($CssClass)) {
         $Options = $CssClass;
         $CssClass = NULL;
      } elseif (is_string($Options))
         $Options = array('Px' => $Options);
      
      $Px = GetValue('Px', $Options);
      
      $Name = GetValue($Px.'Name', $User, T('Unknown'));
      $UserID = GetValue($Px.'UserID', $User, 0);
      
      $Attributes = array(
          'class' => $CssClass,
          'rel' => GetValue('Rel', $Options)
          );

      return '<a href="'.htmlspecialchars(Url('/profile/'.($NameUnique ? '' : "$UserID/").rawurlencode($Name))).'"'.Attribute($Attributes).'>'.htmlspecialchars($Name).'</a>';
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
      $Gender = $UserPrefix.'Gender';
      $User->UserID = $Object->$UserID;
      $User->Name = $Object->$Name;
      $User->Photo = property_exists($Object, $Photo) ? $Object->$Photo : '';
      $User->Email = GetValue($UserPrefix.'Email', $Object, NULL);
      $User->Gender = property_exists($Object, $Gender) ? $Object->$Gender : NULL;
		return $User;
   }
}

/**
 * Takes a user object, and writes out an anchor of the user's icon to the user's profile.
 */
if (!function_exists('UserPhoto')) {
   function UserPhoto($User, $Options = array()) {
      if (is_string($Options))
         $Options = array('LinkClass' => $Options);
      
      if ($Px = GetValue('Px', $Options))
         $User = UserBuilder($User, $Px);
      else
         $User = (object)$User;
      
      $LinkClass = GetValue('LinkClass', $Options, 'ProfileLink');
      $ImgClass = GetValue('ImageClass', $Options, 'ProfilePhotoMedium');
      
      $LinkClass = $LinkClass == '' ? '' : ' class="'.$LinkClass.'"';

      $Photo = $User->Photo;
      if (!$Photo && function_exists('UserPhotoDefaultUrl'))
         $Photo = UserPhotoDefaultUrl($User);

      if ($Photo) {
         if (!preg_match('`^https?://`i', $Photo)) {
            $PhotoUrl = Gdn_Upload::Url(ChangeBasename($Photo, 'n%s'));
         } else {
            $PhotoUrl = $Photo;
         }
         $Href = Url(UserUrl($User));
         return '<a title="'.htmlspecialchars($User->Name).'" href="'.$Href.'"'.$LinkClass.'>'
            .Img($PhotoUrl, array('alt' => htmlspecialchars($User->Name), 'class' => $ImgClass))
            .'</a>';
      } else {
         return '';
      }
   }
}

if (!function_exists('UserUrl')) {
   /**
    * Return the url for a user.
    * @param array|object $User The user to get the url for.
    * @param string $Px The prefix to apply before fieldnames. @since 2.1
    * @return string The url suitable to be passed into the Url() function.
    */
   function UserUrl($User, $Px = '') {
      static $NameUnique = NULL;
      if ($NameUnique === NULL)
         $NameUnique = C('Garden.Registration.NameUnique');
      
      return '/profile/'.($NameUnique ? '' : GetValue($Px.'UserID', $User, 0).'/').rawurlencode(GetValue($Px.'Name', $User));
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