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
   /**
    * Return a bullet character in html.
    * @param string $Pad A string used to pad either side of the bullet.
    * @return string
    *
    * @changes
    *    2.2 Added the $Pad parameter.
    */
   function Bullet($Pad = '') {
      //·
      return $Pad.'<span class="Bullet">&middot;</span>'.$Pad;
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
            echo Anchor(Sprite('SpDropdownHandle', 'Sprite', T('Expand for more options.')), '#', $ButtonClass.' Handle');

         echo '</div>';
      }
   }
endif;

if (!function_exists('Category')):

/**
 * Get the current category on the page.
 * @param int $Depth The level you want to look at.
 * @param array $Category
 */
function Category($Depth = NULL, $Category = NULL) {
   if (!$Category) {
      $Category = Gdn::Controller()->Data('Category');
   } elseif (!is_array($Category)) {
      $Category = CategoryModel::Categories($Category);
   }

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
      $CssClass .= GetValue('Participated', $Row) == '1' ? ' Participated' : '';
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
         $Title = sprintf(T('Edited %s by %s.'), Gdn_Format::DateFull($DateUpdated), GetValue('Name', $UpdateUser));
      else
         $Title = sprintf(T('Edited %s.'), Gdn_Format::DateFull($DateUpdated));

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
   $Name = Gdn_Format::Url($Discussion->Name);
   if (empty($Name)) {
      $Name = 'x';
   }
   $Result = '/discussion/'.$Discussion->DiscussionID.'/'.$Name;
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

if (!function_exists('hasEditProfile')) {
   /**
    * Determine whether or not a given user has the edit profile link.
    *
    * @param int $userID The user ID to check.
    * @return bool Return true if the user should have the edit profile link or false otherwise.
    */
   function hasEditProfile($userID) {
      if (checkPermission(array('Garden.Users.Edit', 'Moderation.Profiles.Edit'))) {
         return true;
      }
      if ($userID != Gdn::Session()->UserID) {
         return false;
      }

      $result = checkPermission('Garden.Profiles.Edit') && C('Garden.UserAccount.AllowEdit');

      $result &= (
            C('Garden.Profile.Titles') ||
            C('Garden.Profile.Locations', FALSE) ||
            C('Garden.Registration.Method') != 'Connect'
         );

      return $result;
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

if (!function_exists('panelHeading')) {
   /**
    * Define default head tag for the side panel.
    *
    * @param string $content The content of the tag.
    * @param string $attributes The attributes of the tag.
    *
    * @return string The full tag.
    */
   function panelHeading($content, $attributes = '') {
      return Wrap($content, 'h4', $attributes);
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

if (!function_exists('SearchExcerpt')):

function SearchExcerpt($PlainText, $SearchTerms, $Length = 200, $Mark = true) {
   if (empty($SearchTerms))
      return substrWord($PlainText, 0, $Length);

   if (is_string($SearchTerms))
      $SearchTerms = preg_split('`[\s|-]+`i', $SearchTerms);

   // Split the string into lines.
   $Lines = explode("\n", $PlainText);
   // Find the first line that includes a search term.
   foreach ($Lines as $i => &$Line) {
      $Line = trim($Line);
      if (!$Line)
         continue;

      foreach ($SearchTerms as $Term) {
         if (!$Term)
            continue;

         if (($Pos = mb_stripos($Line, $Term)) !== FALSE) {
            $Line = substrWord($Line, $Term, $Length);

//            if ($Pos + mb_strlen($Term) > $Length) {
//               $St = -(strlen($Line) - ($Pos - $Length / 4));
//               $Pos2 = strrpos($Line, ' ', $St);
//               if ($Pos2 !== FALSE)
//                  $Line = '…'.substrWord($Line, $Pos2, $Length, "!!!");
//               else
//                  $Line = '…!'.mb_substr($Line, $St, $Length);
//            } else {
//               $Line = substrWord($Line, 0, $Length, '---');
//            }

            return MarkString($SearchTerms, $Line);
         }
      }
   }

   // No line was found so return the first non-blank line.
   foreach ($Lines as $Line) {
      if ($Line)
         return SliceString($Line, $Length);
   }
}

function substrWord($str, $start, $length, $fix = '…') {
   // If we are offsetting on a word then find it.
   if (is_string($start)) {
      $pos = mb_stripos($str, $start);

      $p = $pos + strlen($start);

      if ($pos !== false && (($pos + strlen($start)) <= $length))
         $start = 0;
      else
         $start = $pos - $length / 4;
   }

   // Find the word break from the offset.
   if ($start > 0) {
      $pos = mb_strpos($str, ' ', $start);
      if ($pos !== false)
         $start = $pos;
   } elseif ($start < 0) {
      $pos = mb_strrpos($str, ' ', $start);
      if ($pos !== false)
         $start = $pos;
      else
         $start = 0;
   }

   $len = strlen($str);

   if ($start + $length > $len) {
      if ($length - $start <= 0)
         $start = 0;
      else {
         // Zoom the offset back a bit.
         $pos = mb_strpos($str, ' ', max(0, $len - $length));
         if ($pos === false)
            $pos = $len - $length;
      }
   }

   $result = mb_substr($str, $start, $length);
   return $result;
}

endif;

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
      if (isset($Options['title']))
         $Attributes['title'] = $Options['title'];
      $UserUrl = UserUrl($User,$Px);
      return '<a href="'.htmlspecialchars(Url($UserUrl)).'"'.Attribute($Attributes).'>'.$Text.'</a>';
   }
}

if (!function_exists('UserBuilder')) {
   /**
    * Takes an object & prefix value, and converts it to a user object that can be
    * used by UserAnchor() && UserPhoto() to write out anchors to the user's
    * profile. The object must have the following fields: UserID, Name, Photo.
    */
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

if (!function_exists('UserPhoto')) {
   /**
    * Takes a user object, and writes out an anchor of the user's icon to the user's profile.
    *
    * @param object|array $User User object or array
    * @param array $Options
    */
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
         $Photo = C('Garden.BannedPhoto', 'http://cdn.vanillaforums.com/images/banned_large.png');
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

if (!function_exists('UserPhotoUrl')) {
   /**
    * Takes a user object an returns the URL to their photo
    *
    * @param object|array $User
    */
   function UserPhotoUrl($User) {
      $FullUser = Gdn::UserModel()->GetID(GetValue('UserID', $User), DATASET_TYPE_ARRAY);
      $Photo = GetValue('Photo', $User);
      if ($FullUser && $FullUser['Banned']) {
         $Photo = 'http://cdn.vanillaforums.com/images/banned_100.png';
      }

      if (!$Photo && function_exists('UserPhotoDefaultUrl'))
         $Photo = UserPhotoDefaultUrl($User);

      if ($Photo) {
         if (!isUrl($Photo)) {
            $PhotoUrl = Gdn_Upload::Url(ChangeBasename($Photo, 'n%s'));
         } else {
            $PhotoUrl = $Photo;
         }
         return $PhotoUrl;
      }
      return '';
   }
}

if (!function_exists('UserUrl')) {
   /**
    * Return the url for a user.
    *
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
   function RegisterUrl($Target = '', $force = false) {
      $registrationMethod = strtolower(C('Garden.Registration.Method'));

      if ($registrationMethod === 'closed') {
         return '';
      }

      // Check to see if there is even a sign in button.
      if (!$force && $registrationMethod === 'connect') {
         $defaultProvider = Gdn_AuthenticationProviderModel::GetDefault();
         if ($defaultProvider && !val('RegisterUrl', $defaultProvider)) {
            return '';
         }
      }

      return '/entry/register'.($Target ? '?Target='.urlencode($Target) : '');
   }
}

if (!function_exists('SignInUrl')) {
   function SignInUrl($target = '', $force = false) {
      // Check to see if there is even a sign in button.
      if (!$force && strcasecmp(C('Garden.Registration.Method'), 'Connect') !== 0) {
         $defaultProvider = Gdn_AuthenticationProviderModel::GetDefault();
         if ($defaultProvider && !val('SignInUrl', $defaultProvider)) {
            return '';
         }
      }

      return '/entry/signin'.($target ? '?Target='.urlencode($target) : '');
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

if (!function_exists('SocialSignInButton')) {
   function SocialSignInButton($Name, $Url, $Type = 'button', $Attributes = array()) {
      TouchValue('title', $Attributes, sprintf(T('Sign In with %s'), $Name));
      $Title = $Attributes['title'];
      $Class = val('class', $Attributes, '');

      switch ($Type) {
         case 'icon':
            $Result = Anchor('<span class="Icon"></span>',
               $Url, 'SocialIcon SocialIcon-'.$Name . ' ' . $Class, $Attributes);
            break;
         case 'button':
         default:
            $Result = Anchor('<span class="Icon"></span><span class="Text">'.$Title.'</span>',
               $Url, 'SocialIcon SocialIcon-'.$Name.' HasText ' . $Class, $Attributes);
            break;
      }

      return $Result;
   }
}

if (!function_exists('Sprite')) {
	function Sprite($Name, $Type = 'Sprite', $Text = FALSE) {
      $Sprite = '<span class="'.$Type.' '.$Name.'"></span>';
      if ($Text) {
         $Sprite .= '<span class="sr-only">' . $Text . '</span>';
      }

		return $Sprite;
	}
}

if (!function_exists('WriteReactions')):
   function WriteReactions($Row) {
      $Attributes = GetValue('Attributes', $Row);
      if (is_string($Attributes)) {
         $Attributes = @unserialize($Attributes);
         SetValue('Attributes', $Row, $Attributes);
      }

      Gdn::Controller()->EventArguments['ReactionTypes'] = array();

      if ($ID = GetValue('CommentID', $Row)) {
         $RecordType = 'comment';
      } elseif ($ID = GetValue('ActivityID', $Row)) {
         $RecordType = 'activity';
      } else {
         $RecordType = 'discussion';
         $ID = GetValue('DiscussionID', $Row);
      }
      Gdn::Controller()->EventArguments['RecordType'] = $RecordType;
      Gdn::Controller()->EventArguments['RecordID'] = $ID;

      echo '<div class="Reactions">';
      Gdn_Theme::BulletRow();

      // Write the flags.
      static $Flags = NULL, $FlagCodes = NULL;
      if ($Flags === NULL) {
         Gdn::Controller()->EventArguments['Flags'] = &$Flags;
         Gdn::Controller()->FireEvent('Flags');
      }

      // Allow addons to work with flags
      Gdn::Controller()->EventArguments['Flags'] = &$Flags;
      Gdn::Controller()->FireEvent('BeforeFlag');

      if (!empty($Flags) && is_array($Flags)) {
         echo Gdn_Theme::BulletItem('Flags');

         echo ' <span class="FlagMenu ToggleFlyout">';
         // Write the handle.
         echo Anchor(Sprite('ReactFlag', 'ReactSprite').' '.Wrap(T('Flag'), 'span', array('class'=>'ReactLabel')), '', 'Hijack ReactButton-Flag FlyoutButton', array('title'=>'Flag'), TRUE);
         echo Sprite('SpFlyoutHandle', 'Arrow');
         echo '<ul class="Flyout MenuItems Flags" style="display: none;">';
         foreach ($Flags as $Flag) {
            if (is_callable($Flag))
               echo '<li>'.call_user_func($Flag, $Row, $RecordType, $ID).'</li>';
            else
               echo '<li>'.ReactionButton($Row, $Flag['UrlCode']).'</li>';
         }
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
