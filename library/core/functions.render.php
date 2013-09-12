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

/**
 * English "plural" formatting for numbers that can get really big.
 */
if (!function_exists('BigPlural')) {
   function BigPlural($Number, $Singular, $Plural = FALSE) {
      if (!$Plural) {
         $Plural = $Singular.'s';
      }
      $Title = sprintf(T($Number == 1 ? $Singular : $Plural), number_format($Number));
      
      return '<span title="'.$Title.'" class="Number">'.Gdn_Format::BigNumber($Number).'</span>';
   }
}

if (!function_exists('Bullet')):
   function Bullet() {
      return '<span class="Bullet">&bull;</span>';
   }
endif;

if (!function_exists('ButtonDropDown')):
   /**
    *
    * @param array $Links An array of arrays with the following keys:
    *  - Text: The text of the link.
    *  - Url: The url of the link.
    * @param string|array $CssClass The css class of the link. This can be a two-item array where the second element will be added to the buttons.
    * @param string $Label The text of the button.
    * @since 2.1
    */
   function ButtonDropDown($Links, $CssClass = 'Button', $Label = FALSE) {
      if (!is_array($Links) || count($Links) < 1)
         return;
      
      $ButtonClass = '';
      if (is_array($CssClass))
         list($CssClass, $ButtonClass) = $CssClass;
      
      if (count($Links) < 2) {
         $Link = array_pop($Links);
         
         
         if (strpos(GetValue('CssClass', $Link, ''), 'Popup') !== FALSE)
            $CssClass .= ' Popup';
         
         echo Anchor($Link['Text'], $Link['Url'], GetValue('ButtonCssClass', $Link, $CssClass));
      } else {
         // NavButton or Button?
         $ButtonClass = ConcatSep(' ', $ButtonClass, strpos($CssClass, 'NavButton') !== FALSE ? 'NavButton' : 'Button');
         if (strpos($CssClass, 'Primary') !== FALSE)
            $ButtonClass .= ' Primary';
         // Strip "Button" or "NavButton" off the group class.
         echo '<div class="ButtonGroup'.str_replace(array('NavButton', 'Button'), array('',''), $CssClass).'">';
//            echo Anchor($Text, $Url, $ButtonClass);
            
            echo '<ul class="Dropdown MenuItems">';
               foreach ($Links as $Link) {
                  echo Wrap(Anchor($Link['Text'], $Link['Url'], GetValue('CssClass', $Link, '')), 'li');
               }
            echo '</ul>';
            
            echo Anchor($Label.' '.Sprite('SpDropdownHandle'), '#', $ButtonClass.' Handle');
         echo '</div>';
      }
   }
endif;

if (!function_exists('ButtonGroup')):
   /**
    *
    * @param array $Links An array of arrays with the following keys:
    *  - Text: The text of the link.
    *  - Url: The url of the link.
    * @param string|array $CssClass The css class of the link. This can be a two-item array where the second element will be added to the buttons.
    * @param string|false $Default The url of the default link.
    * @since 2.1
    */
   function ButtonGroup($Links, $CssClass = 'Button', $Default = FALSE) {
      if (!is_array($Links) || count($Links) < 1)
         return;
      
      $Text = $Links[0]['Text'];
      $Url = $Links[0]['Url'];
      
      $ButtonClass = '';
      if (is_array($CssClass))
         list($CssClass, $ButtonClass) = $CssClass;
      
      if ($Default && count($Links) > 1) {
         if (is_array($Default)) {
            $DefaultText = $Default['Text'];
            $Default = $Default['Url'];
         }
         
         // Find the default button. 
         $Default = ltrim($Default, '/');
         foreach ($Links as $Link) {
            if (StringBeginsWith(ltrim($Link['Url'], '/') , $Default)) {
               $Text = $Link['Text'];
               $Url = $Link['Url'];
               break;
            }
         }
         
         if (isset($DefaultText))
            $Text = $DefaultText;
      }
      
      if (count($Links) < 2) {
         echo Anchor($Text, $Url, $CssClass);
      } else {
         // NavButton or Button?
         $ButtonClass = ConcatSep(' ', $ButtonClass, strpos($CssClass, 'NavButton') !== FALSE ? 'NavButton' : 'Button');
         if (strpos($CssClass, 'Primary') !== FALSE)
            $ButtonClass .= ' Primary';
         // Strip "Button" or "NavButton" off the group class.
         echo '<div class="ButtonGroup Multi '.str_replace(array('NavButton', 'Button'), array('',''), $CssClass).'">';
            echo Anchor($Text, $Url, $ButtonClass);
            
            echo '<ul class="Dropdown MenuItems">';
               foreach ($Links as $Link) {
                  echo Wrap(Anchor($Link['Text'], $Link['Url'], GetValue('CssClass', $Link, '')), 'li');
               }
            echo '</ul>';
            echo Anchor(Sprite('SpDropdownHandle'), '#', $ButtonClass.' Handle');
            
         echo '</div>';
      }
   }
endif;

if (!function_exists('Category')):

/**
 * Get the current category on the page.
 * @param int $Depth The level you want to look at.
 */
function Category($Depth = NULL) {
   $Category = Gdn::Controller()->Data('Category');
   if (!$Category) {
      $Category = Gdn::Controller()->Data('CategoryID');
      if ($Category)
         $Category = CategoryModel::Categories($Category);
   }
   if (!$Category)
      return NULL;
   
   $Category = (array)$Category;
   
   if ($Depth !== NULL) {
      // Get the category at the correct level.
      while ($Category['Depth'] > $Depth) {
         $Category = CategoryModel::Categories($Category['ParentCategoryID']);
         if (!$Category)
            return NULL;
      }
   }
   
   return $Category;
}
   
endif;

if (!function_exists('CategoryUrl')):

/**
 * Return a url for a category. This function is in here and not functions.general so that plugins can override.
 * @param array $Category
 * @return string
 */
function CategoryUrl($Category, $Page = '', $WithDomain = TRUE) {
   if (is_string($Category))
      $Category = CategoryModel::Categories($Category);
   $Category = (array)$Category;
   
   $Result = '/categories/'.rawurlencode($Category['UrlCode']);
   if ($Page && $Page > 1) {
         $Result .= '/p'.$Page;
   }
   return Url($Result, $WithDomain);
}
   
endif;

if (!function_exists('Condense')) {
   function Condense($Html) {
      $Html = preg_replace('`(?:<br\s*/?>\s*)+`', "<br />", $Html);
      $Html = preg_replace('`/>\s*<br />\s*<img`', "/> <img", $Html);
      return $Html;
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

if (!function_exists('CssClass')):
   
/** 
 * Add CSS class names to a row depending on other elements/values in that row. 
 * Used by category, discussion, and comment lists.
 * 
 * @staticvar boolean $Alt
 * @param type $Row
 * @return string The CSS classes to be inserted into the row.
 */
function CssClass($Row, $InList = TRUE) {
   static $Alt = FALSE;
   $Row = (array)$Row;
   $CssClass = 'Item';
   $Session = Gdn::Session();

   // Alt rows
   if ($Alt)
      $CssClass .= ' Alt';
   $Alt = !$Alt;
      
   // Category list classes
   if (array_key_exists('UrlCode', $Row))
      $CssClass .= ' Category-'.Gdn_Format::AlphaNumeric($Row['UrlCode']);
   if (GetValue('CssClass', $Row))
      $CssClass .= ' Item-'.$Row['CssClass'];

   if (array_key_exists('Depth', $Row))
      $CssClass .= " Depth{$Row['Depth']} Depth-{$Row['Depth']}";

   if (array_key_exists('Archive', $Row))
      $CssClass .= ' Archived';
      
   // Discussion list classes.
   if ($InList) {
      $CssClass .= GetValue('Bookmarked', $Row) == '1' ? ' Bookmarked' : '';

      $Announce = GetValue('Announce', $Row);
      if ($Announce == 2)
         $CssClass .= ' Announcement Announcement-Category';
      elseif ($Announce)
         $CssClass .= ' Announcement Announcement-Everywhere';

      $CssClass .= GetValue('Closed', $Row) == '1' ? ' Closed' : '';
      $CssClass .= GetValue('InsertUserID', $Row) == $Session->UserID ? ' Mine' : '';
      if (array_key_exists('CountUnreadComments', $Row) && $Session->IsValid()) {
         $CountUnreadComments = $Row['CountUnreadComments'];
         if ($CountUnreadComments === TRUE) {
            $CssClass .= ' New';
         } elseif ($CountUnreadComments == 0) {
            $CssClass .= ' Read';
         } else {
            $CssClass .= ' Unread';
         }
      } elseif (($IsRead = GetValue('Read', $Row, NULL)) !== NULL) {
         // Category list
         $CssClass .= $IsRead ? ' Read' : ' Unread';
      }
   }
         
   // Comment list classes
   if (array_key_exists('CommentID', $Row))
       $CssClass .= ' ItemComment';
   else if (array_key_exists('DiscussionID', $Row))
       $CssClass .= ' ItemDiscussion';

   if (function_exists('IsMeAction'))
      $CssClass .= IsMeAction($Row) ? ' MeAction' : '';

   if ($_CssClss = GetValue('_CssClass', $Row))
      $CssClass .= ' '.$_CssClss;

   // Insert User classes.
   if ($UserID = GetValue('InsertUserID', $Row)) {
      $User = Gdn::UserModel()->GetID($UserID);
      if ($_CssClss = GetValue('_CssClass', $User)) {
         $CssClass .= ' '.$_CssClss;
      }
   }

   return trim($CssClass);
}
endif;

if (!function_exists('DateUpdated')):

function DateUpdated($Row, $Wrap = NULL) {
   $Result = '';
   $DateUpdated = GetValue('DateUpdated', $Row);
   $UpdateUserID = GetValue('UpdateUserID', $Row);
   
   if ($DateUpdated) {
      $Result = '';
      
      $UpdateUser = Gdn::UserModel()->GetID($UpdateUserID);
      if ($UpdateUser)
         $Title = sprintf(T('Edited by %s on %s.'), GetValue('Name', $UpdateUser), Gdn_Format::DateFull($DateUpdated));
      else
         $Title = sprintf(T('Edited on %s.'), Gdn_Format::DateFull($DateUpdated));
      
      $Result = ' <span title="'.htmlspecialchars($Title).'" class="DateUpdated">'.
              sprintf(T('edited %s'), Gdn_Format::Date($DateUpdated)).
              '</span> ';
      
      if ($Wrap)
         $Result = $Wrap[0].$Result.$Wrap[1];
   }
   
   return $Result;
}
   
endif;

/**
 * Writes an anchor tag
 */
if (!function_exists('Anchor')) {
   /**
    * Builds and returns an anchor tag.
    */
   function Anchor($Text, $Destination = '', $CssClass = '', $Attributes = array(), $ForceAnchor = FALSE) {
      if (!is_array($CssClass) && $CssClass != '')
         $CssClass = array('class' => $CssClass);

      if ($Destination == '' && $ForceAnchor === FALSE)
         return $Text;
      
      if (!is_array($Attributes))
         $Attributes = array();
      
      $SSL = NULL;
      if (isset($Attributes['SSL'])) {
         $SSL = $Attributes['SSL'];
         unset($Attributes['SSL']);
      }
		
		$WithDomain = FALSE;
      if (isset($Attributes['WithDomain'])) {
         $WithDomain = $Attributes['WithDomain'];
			unset($Attributes['WithDomain']);
      }

      $Prefix = substr($Destination, 0, 7);
      if (!in_array($Prefix, array('https:/', 'http://', 'mailto:')) && ($Destination != '' || $ForceAnchor === FALSE))
         $Destination = Gdn::Request()->Url($Destination, $WithDomain, $SSL);

      return '<a href="'.htmlspecialchars($Destination, ENT_COMPAT, 'UTF-8').'"'.Attribute($CssClass).Attribute($Attributes).'>'.$Text.'</a>';
   }
}

if (!function_exists('CommentUrl')):

/**
 * Return a url for a comment. This function is in here and not functions.general so that plugins can override.
 * @param object $Comment
 * @return string
 */
function CommentUrl($Comment, $WithDomain = TRUE) {
   $Comment = (object)$Comment;
   $Result = "/discussion/comment/{$Comment->CommentID}#Comment_{$Comment->CommentID}";
   return Url($Result, $WithDomain);
}
   
endif;

if (!function_exists('DiscussionUrl')):

/**
 * Return a url for a discussion. This function is in here and not functions.general so that plugins can override.
 * @param object $Discussion
 * @return string
 */
function DiscussionUrl($Discussion, $Page = '', $WithDomain = TRUE) {
   $Discussion = (object)$Discussion;
   $Result = '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name);
   if ($Page) {
      if ($Page > 1 || Gdn::Session()->UserID)
         $Result .= '/p'.$Page;
   }
   return Url($Result, $WithDomain);
}
   
endif;

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

      if (!IsUrl($Image))
         $Image = SmartAsset($Image, $WithDomain);

      return '<img src="'.$Image.'"'.Attribute($Attributes).' />';
   }
}

if (!function_exists('InCategory')) {
   /**
    * Returns whether or not the page is in a given category.
    * 
    * @param string $Category The url code of the category.
    * @return boolean
    * @since 2.1
    */
   function InCategory($Category) {
      $Breadcrumbs = (array)Gdn::Controller()->Data('Breadcrumbs', array());
      
      foreach ($Breadcrumbs as $Breadcrumb) {
         if (isset($Breadcrumb['CategoryID']) && strcasecmp($Breadcrumb['UrlCode'], $Category) == 0) {
            return TRUE;
         }
      }
      
      return FALSE;
   }
}

if (!function_exists('InSection')) {
   /**
    * Returns whether or not the page is in one of the given section(s).
    * @since 2.1
    * @param string|array $Section
    * @return bool
    */
   function InSection($Section) {
      return Gdn_Theme::InSection($Section);
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
   function Plural($Number, $Singular, $Plural, $FormattedNumber = FALSE) {
		// Make sure to fix comma-formatted numbers
      $WorkingNumber = str_replace(',', '', $Number);
      if ($FormattedNumber === FALSE)
         $FormattedNumber = $Number;
      
      $Format = T(abs($WorkingNumber) == 1 ? $Singular : $Plural);
      
      return sprintf($Format, $FormattedNumber);
   }
}

if (!function_exists('PluralTranslate')) {
   /**
    * Translate a plural string.
    * 
    * @param int $Number
    * @param string $Singular
    * @param string $Plural
    * @param string|false $SingularDefault
    * @param string|false $PluralDefault
    * @return string
    * @since 2.1
    */
   function PluralTranslate($Number, $Singular, $Plural, $SingularDefault = FALSE, $PluralDefault = FALSE) {
      if ($Number == 1)
         return T($Singular, $SingularDefault);
      else
         return T($Plural, $PluralDefault);
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
      
      $Px = GetValue('Px', $Options, '');
      
      $Name = GetValue($Px.'Name', $User, T('Unknown'));
      $UserID = GetValue($Px.'UserID', $User, 0);
		$Text = GetValue('Text', $Options, htmlspecialchars($Name)); // Allow anchor text to be overridden.
      
      $Attributes = array(
          'class' => $CssClass,
          'rel' => GetValue('Rel', $Options)
          );
      $UserUrl = UserUrl($User,$Px);
      return '<a href="'.htmlspecialchars(Url($UserUrl)).'"'.Attribute($Attributes).'>'.$Text.'</a>';
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
      
      $LinkClass = ConcatSep(' ', GetValue('LinkClass', $Options, ''), 'PhotoWrap');
      $ImgClass = GetValue('ImageClass', $Options, 'ProfilePhoto');
      
      $Size = GetValue('Size', $Options);
      if ($Size) {
         $LinkClass .= " PhotoWrap{$Size}";
         $ImgClass .= " {$ImgClass}{$Size}";
      } else {
         $ImgClass .= " {$ImgClass}Medium"; // backwards compat
      }
      
      $FullUser = Gdn::UserModel()->GetID(GetValue('UserID', $User), DATASET_TYPE_ARRAY);
      $UserCssClass = GetValue('_CssClass', $FullUser);
      if ($UserCssClass)
         $LinkClass .= ' '.$UserCssClass;
      
      $LinkClass = $LinkClass == '' ? '' : ' class="'.$LinkClass.'"';

      $Photo = GetValue('Photo', $User);
      $Name = GetValue('Name', $User);
      $Title = htmlspecialchars(GetValue('Title', $Options, $Name));
      
      if ($FullUser && $FullUser['Banned']) {
         $Photo = C('Garden.BannedPhoto', 'http://cdn.vanillaforums.com/images/banned_large.png');;
         $Title .= ' ('.T('Banned').')';
      }
      
      if (!$Photo && function_exists('UserPhotoDefaultUrl'))
         $Photo = UserPhotoDefaultUrl($User, $ImgClass);

      if ($Photo) {
         if (!isUrl($Photo)) {
            $PhotoUrl = Gdn_Upload::Url(ChangeBasename($Photo, 'n%s'));
         } else {
            $PhotoUrl = $Photo;
         }
         $Href = Url(UserUrl($User));
         return '<a title="'.$Title.'" href="'.$Href.'"'.$LinkClass.'>'
            .Img($PhotoUrl, array('alt' => htmlspecialchars($Name), 'class' => $ImgClass))
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
    * @param string $Method Optional. ProfileController method to target.
    * @return string The url suitable to be passed into the Url() function.
    */
   function UserUrl($User, $Px = '', $Method = '', $Get = FALSE) {
      static $NameUnique = NULL;
      if ($NameUnique === NULL)
         $NameUnique = C('Garden.Registration.NameUnique');
      
      $UserName = GetValue($Px.'Name', $User);
      $UserName = preg_replace('/([\?&]+)/', '', $UserName);
      
      $Result = '/profile/'.
         ($Method ? trim($Method, '/').'/' : '').
         ($NameUnique ? '' : GetValue($Px.'UserID', $User, 0).'/').
         rawurlencode($UserName);
      
      if ($Get)
         $Result .= '?'.http_build_query($Get);
      
      return $Result;
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
      
      // Strip the first part of the tag as the closing tag - this allows us to 
      // easily throw 'span class="something"' into the $Tag field.
      $Space = strpos($Tag, ' ');
      $ClosingTag = $Space ? substr($Tag, 0, $Space) : $Tag;         
      return '<'.$Tag.$Attributes.'>'.$String.'</'.$ClosingTag.'>';
   }
}

if (!function_exists('WrapIf')) {
   /**
    * Wrap the provided string if it isn't empty.
    * 
    * @param string $String
    * @param string $Tag
    * @param array $Attributes
    * @return string
    * @since 2.1 
    */
   function WrapIf($String, $Tag = 'span', $Attributes = '') {
      if (empty($String))
         return '';
      else
         return Wrap($String, $Tag, $Attributes);
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
      if ($Target) {
         // Strip out the SSO from the target so that the user isn't signed back in again.
         $Parts = explode('?', $Target, 2);
         if (isset($Parts[1])) {
            parse_str($Parts[1], $Query);
            unset($Query['sso']);
            $Target = $Parts[0].'?'.http_build_query($Query);
         }
      }
      
      return '/entry/signout?TransientKey='.urlencode(Gdn::Session()->TransientKey()).($Target ? '&Target='.urlencode($Target) : '');
   }
}

if (!function_exists('Sprite')) {
	function Sprite($Name, $Type = 'Sprite') {
		return '<span class="'.$Type.' '.$Name.'"></span>';
	}
}

if (!function_exists('WriteReactions')):
   function WriteReactions($Row) {
      list($RecordType, $RecordID) = RecordType($Row);

      Gdn::Controller()->EventArguments['RecordType'] = strtolower($RecordType);
      Gdn::Controller()->EventArguments['RecordID'] = $RecordID;

      echo '<div class="Reactions">';
      Gdn_Theme::BulletRow();

      // Write the flags.
      static $Flags = NULL;

      // Allow addons to work with flags menu
      Gdn::Controller()->EventArguments['Flags'] = &$Flags;
      Gdn::Controller()->FireEvent('BeforeFlag');

      if (!empty($Flags)) {
         echo Gdn_Theme::BulletItem('Flags');

         echo ' <span class="FlagMenu ToggleFlyout">';
         // Write the handle.
         echo Anchor(Sprite('ReactFlag', 'ReactSprite').' '.Wrap(T('Flag'), 'span', array('class'=>'ReactLabel')), '', 'Hijack ReactButton-Flag FlyoutButton', array('title'=>'Flag'), TRUE);
         echo Sprite('SpFlyoutHandle', 'Arrow');
         echo '<ul class="Flyout MenuItems Flags" style="display: none;">';
         Gdn::Controller()->FireEvent('AfterFlagOptions');
         echo '</ul>';
         echo '</span> ';
      }

      Gdn::Controller()->FireEvent('AfterFlag');

      Gdn::Controller()->FireEvent('AfterReactions');
      echo '</div>';
      Gdn::Controller()->FireEvent('Replies');
   }
endif;
