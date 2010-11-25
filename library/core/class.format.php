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
 * Utility class that helps to format strings, objects, and arrays.
 *
 *
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */
class Gdn_Format {

   /**
    * The ActivityType table has some special sprintf search/replace values in the
    * FullHeadline and ProfileHeadline fields. The ProfileHeadline field is to be
    * used on this page (the user profile page). The FullHeadline field is to be
    * used on the main activity page. The replacement definitions are as follows:
    *  %1$s = ActivityName
    *  %2$s = ActivityName Possessive
    *  %3$s = RegardingName
    *  %4$s = RegardingName Possessive
    *  %5$s = Link to RegardingName's Wall
    *  %6$s = his/her
    *  %7$s = he/she
    *  %8$s = route & routecode
    *  %9$s = gender suffix (some languages require this).
    *
    * @param object $Activity An object representation of the activity being formatted.
    * @param int $ProfileUserID If looking at a user profile, this is the UserID of the profile we are
    *  looking at.
    * @return string
    */
   public static function ActivityHeadline($Activity, $ProfileUserID = '', $ViewingUserID = '') {
      if ($ViewingUserID == '') {
         $Session = Gdn::Session();
         $ViewingUserID = $Session->IsValid() ? $Session->UserID : -1;
      }

      $GenderSuffixCode = 'First';
      $GenderSuffixGender = $Activity->ActivityGender;
      
      if ($ViewingUserID == $Activity->ActivityUserID) {
         $ActivityName = $ActivityNameP = T('You');
      } else {
         $ActivityName = $Activity->ActivityName;
         $ActivityNameP = FormatPossessive($ActivityName);
         $GenderSuffixCode = 'Third';
      }
      if ($ProfileUserID != $Activity->ActivityUserID) {
         // If we're not looking at the activity user's profile, link the name
         $ActivityNameD = urlencode($Activity->ActivityName);
         $ActivityName = Anchor($ActivityName, '/profile/' . $Activity->ActivityUserID . '/' . $ActivityNameD);
         $ActivityNameP = Anchor($ActivityNameP, '/profile/' . $Activity->ActivityUserID  . '/' . $ActivityNameD);
         $GenderSuffixCode = 'Third';
      }
      $Gender = T($Activity->ActivityGender == 'm' ? 'his' : 'her');
      $Gender2 = T($Activity->ActivityGender == 'm' ? 'he' : 'she');
      if ($ViewingUserID == $Activity->RegardingUserID || ($Activity->RegardingUserID == '' && $Activity->ActivityUserID == $ViewingUserID)) {
         $Gender = $Gender2 = T('your');
      }

      $IsYou = FALSE;
      if ($ViewingUserID == $Activity->RegardingUserID) {
         $IsYou = TRUE;
         $RegardingName = T('you');
         $RegardingNameP = T('your');
         $GenderSuffixGender = $Activity->RegardingGender;
      } else {
         $RegardingName = $Activity->RegardingName == '' ? T('somebody') : $Activity->RegardingName;
         $RegardingNameP = FormatPossessive($RegardingName);

         if ($Activity->ActivityUserID != $ViewingUserID)
            $GenderSuffixCode = 'Third';
      }
      $RegardingWall = '';

      if ($Activity->ActivityUserID == $Activity->RegardingUserID) {
         // If the activityuser and regardinguser are the same, use the $Gender Ref as the RegardingName
         $RegardingName = $RegardingProfile = $Gender;
         $RegardingNameP = $RegardingProfileP = $Gender;
      } else if ($Activity->RegardingUserID > 0 && $ProfileUserID != $Activity->RegardingUserID) {
         // If there is a regarding user and we're not looking at his/her profile, link the name.
         $RegardingNameD = urlencode($Activity->RegardingName);
         if (!$IsYou) {
            $RegardingName = Anchor($RegardingName, '/profile/' . $Activity->RegardingUserID . '/' . $RegardingNameD);
            $RegardingNameP = Anchor($RegardingNameP, '/profile/' . $Activity->RegardingUserID . '/' . $RegardingNameD);
            $GenderSuffixCode = 'Third';
            $GenderSuffixGender = $Activity->RegardingGender;
         }
         $RegardingWall = Anchor(T('wall'), '/profile/activity/' . $Activity->RegardingUserID . '/' . $RegardingNameD . '#Activity_' . $Activity->ActivityID);
      }
      if ($RegardingWall == '')
         $RegardingWall = T('wall');

      if ($Activity->Route == '') {
         if ($Activity->RouteCode)
            $Route = T($Activity->RouteCode);
         else
            $Route = '';
      } else
         $Route = Anchor(T($Activity->RouteCode), $Activity->Route);

      // Translate the gender suffix.
      $GenderSuffixCode = "GenderSuffix.$GenderSuffixCode.$GenderSuffixGender";
      $GenderSuffix = T($GenderSuffixCode, '');
      if ($GenderSuffix == $GenderSuffixCode)
         $GenderSuffix = ''; // in case translate doesn't support empty strings.

      /*
        Debug:
      return $ActivityName
      .'/'.$ActivityNameP
      .'/'.$RegardingName
      .'/'.$RegardingNameP
      .'/'.$RegardingWall
      .'/'.$Gender
      .'/'.$Gender2
      .'/'.$Route
      .'/'.$GenderSuffix.($GenderSuffixCode)
      */

      $FullHeadline = T("Activity.{$Activity->ActivityType}.FullHeadline", T($Activity->FullHeadline));
      $ProfileHeadline = T("Activity.{$Activity->ActivityType}.ProfileHeadline", T($Activity->ProfileHeadline));
      $MessageFormat = ($ProfileUserID == $Activity->ActivityUserID || $ProfileUserID == '' ? $FullHeadline : $ProfileHeadline);
      
      return sprintf($MessageFormat, $ActivityName, $ActivityNameP, $RegardingName, $RegardingNameP, $RegardingWall, $Gender, $Gender2, $Route, $GenderSuffix);
   }

   /**
    * Removes all non-alpha-numeric characters (except for _ and -) from
    *
    * @param string $Mixed An object, array, or string to be formatted.
    * @return unknown
    */
   public static function AlphaNumeric($Mixed) {
      if (!is_string($Mixed))
         return self::To($Mixed, 'ForAlphaNumeric');
      else
         return preg_replace('/([^\w\d_-])/', '', $Mixed);
   }

   /**
    * @param array $Array
    * @return string
    *
    * @todo add summary
    */
   public static function ArrayAsAttributes($Array) {
      $Return = '';
      foreach($Array as $Property => $Value) {
         $Return .= ' ' . $Property . '="' . $Value . '"';
      }
      return $Return;
   }

   /**
    * Takes an object and convert's it's properties => values to an associative
    * array of $Array[Property] => Value sets.
    *
    * @param array $Array An array to be converted to object.
    * @return stdClass
    *
    * @todo could be just "return (object) $Array;"?
    */
   public static function ArrayAsObject($Array) {
      if (!is_array($Array))
         return $Array;

      $Return = new stdClass();
      foreach($Array as $Property => $Value) {
         $Return->$Property = $Value;
      }
      return $Return;
   }

   /**
    * Takes a string and formats it so that it can be saved to a PHP file in
    * double-quotes of an array value assignment. For example, from garden/library/core/class.locale.php:
    *  $FileContents[] = "\$LocaleSources['".$SafeLocaleName."'][] = '".$Format->ArrayValueForPhp($LocaleSources[$i])."';";
    *
    * @param string The string to be formatted.
    * @return string
    */
   public static function ArrayValueForPhp($String) {
      return str_replace('\\', '\\', html_entity_decode($String, ENT_QUOTES));
      // $String = str_replace('\\', '\\', html_entity_decode($String, ENT_QUOTES));
      // return str_replace(array("'", "\n", "\r"), array('\\\'', '\\\n', '\\\r'), $String);
   }

   /**
    * Takes a mixed variable.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return string
    */
   public static function BBCode($Mixed) {
      if (!is_string($Mixed)) {
         return self::To($Mixed, 'BBCode');
      } else {
         // See if there is a custom BBCode formatter.
         $BBCodeFormatter = Gdn::Factory('BBCodeFormatter');
         if (is_object($BBCodeFormatter)) {
            $Result = $BBCodeFormatter->Format($Mixed);
            $Result = Gdn_Format::Links($Result);
            $Result = Gdn_Format::Mentions($Result);

            return $Result;
         }

         $Formatter = Gdn::Factory('HtmlFormatter');
         if (is_null($Formatter)) {
            return Gdn_Format::Display($Mixed);
         } else {
				try {
					$Mixed2 = $Mixed;
					//$Mixed2 = str_replace("\n", '<br />', $Mixed2);

               $Mixed2 = preg_replace("#\[b\](.*?)\[/b\]#si",'<b>\\1</b>',$Mixed2);
               $Mixed2 = preg_replace("#\[i\](.*?)\[/i\]#si",'<i>\\1</i>',$Mixed2);
               $Mixed2 = preg_replace("#\[u\](.*?)\[/u\]#si",'<u>\\1</u>',$Mixed2);
               $Mixed2 = preg_replace("#\[s\](.*?)\[/s\]#si",'<s>\\1</s>',$Mixed2);
               $Mixed2 = preg_replace("#\[quote=[\"']?(.*?)[\"']?\](.*?)\[/quote\]#si",'<p><cite>\\1</cite>:</p><blockquote>\\2</blockquote>',$Mixed2);
               $Mixed2 = preg_replace("#\[quote\](.*?)\[/quote\]#si",'<blockquote>\\1</blockquote>',$Mixed2);
               $Mixed2 = preg_replace("#\[cite\](.*?)\[/cite\]#si",'<blockquote>\\1</blockquote>',$Mixed2);
               $Mixed2 = preg_replace("#\[code\](.*?)\[/code\]#si",'<code>\\1</code>',$Mixed2);
               $Mixed2 = preg_replace("#\[hide\](.*?)\[/hide\]#si",'\\1',$Mixed2);
               $Mixed2 = preg_replace("#\[url\]([^/]*?)\[/url\]#si",'<a href="http://\\1">\\1</a>',$Mixed2);
               $Mixed2 = preg_replace("#\[url\](.*?)\[/url\]#si",'\\1',$Mixed2);
               $Mixed2 = preg_replace("#\[url=[\"']?(.*?)[\"']?\](.*?)\[/url\]#si",'<a href="\\1">\\2</a>',$Mixed2);
               $Mixed2 = preg_replace("#\[php\](.*?)\[/php\]#si",'<code>\\1</code>',$Mixed2);
               $Mixed2 = preg_replace("#\[mysql\](.*?)\[/mysql\]#si",'<code>\\1</code>',$Mixed2);
               $Mixed2 = preg_replace("#\[css\](.*?)\[/css\]#si",'<code>\\1</code>',$Mixed2);
               $Mixed2 = preg_replace("#\[img=[\"']?(.*?)[\"']?\](.*?)\[/img\]#si",'<img src="\\1" alt="\\2" />',$Mixed2);
               $Mixed2 = preg_replace("#\[img\](.*?)\[/img\]#si",'<img src="\\1" border="0" />',$Mixed2);
               $Mixed2 = str_ireplace(array('[indent]', '[/indent]'), array('<div class="Indent">', '</div>'), $Mixed2);

               $Mixed2 = preg_replace("#\[font=[\"']?(.*?)[\"']?\]#i",'<span style="font-family:\\1;">',$Mixed2);
               $Mixed2 = preg_replace("#\[color=[\"']?(.*?)[\"']?\]#i",'<span style="color:\\1">',$Mixed2);
               $Mixed2 = str_ireplace(array("[/size]", "[/font]", "[/color]"), "</span>", $Mixed2);
               
               $Mixed2 = preg_replace("#\[size=[\"']?(.*?)[\"']?\]#si",'<font size="\\1">',$Mixed2);
               $Mixed2 = str_ireplace('[/font]', '</font>', $Mixed2);

               $Mixed2 = preg_replace('#\[/?left\]#si', '', $Mixed2);
               $Mixed2 = Gdn_Format::Links($Mixed2);
               $Mixed2 = Gdn_Format::Mentions($Mixed2);
					$Result = Gdn_Format::Html($Mixed2);
					return $Result;
				} catch(Exception $Ex) {
					return self::Display($Mixed);
				}
         }         
      }
   }

   /** Format a number by putting K/M/B suffix after it when appropriate.
    *
    * @param mixed $Number The number to format. If a number isn't passed then it is returned as is.
    * @return string The formatted number.
    * @todo Make this locale aware.
    */
   public static function BigNumber($Number) {
      if (!is_numeric($Number))
         return $Number;

      if ($Number >= 1000000000) {
         $Number = $Number / 1000000000;
         $Suffix = "B";
      } elseif ($Number >= 1000000) {
         $Number = $Number / 1000000;
         $Suffix = "M";
      } elseif ($Number >= 1000) {
         $Number = $Number / 1000;
         $Suffix = "K";
      }

      if (isset($Suffix)) {
         return number_format($Number, 1).$Suffix;
      } else {
         return $Number;
      }
   }

   /** Format a number as if it's a number of bytes by adding the appropriate B/K/M/G/T suffix.
    *
    * @param int $Bytes The bytes to format.
    * @param int $Precision The number of decimal places to return.
    * @return string The formatted bytes.
    */
   public static function Bytes($Bytes, $Precision = 2) {
      $Units = array('B', 'K', 'M', 'G', 'T');
      $Bytes = max($Bytes, 0);
      $Pow = floor(($Bytes ? log($Bytes) : 0) / log(1024));
      $Pow = min($Pow, count($Units) - 1);
      $Bytes /= pow(1024, $Pow);
      return round($Bytes, $Precision) . $Units[$Pow]; 
   }

   /**
   * 
   */
   protected static $Code = array('-','_','&lt;','&gt;','&#039;','&amp;','&quot;','À','Á','Â','Ã','Ä','&Auml;','Å','Ā','Ą','Ă','Æ','Ç','Ć','Č','Ĉ','Ċ','Ď','Đ','Ð','È','É','Ê','Ë','Ē','Ę','Ě','Ĕ','Ė','Ĝ','Ğ','Ġ','Ģ','Ĥ','Ħ','Ì','Í','Î','Ï','Ī','Ĩ','Ĭ','Į','İ','Ĳ','Ĵ','Ķ','Ł','Ľ','Ĺ','Ļ','Ŀ','Ñ','Ń','Ň','Ņ','Ŋ','Ò','Ó','Ô','Õ','Ö','&Ouml;','Ø','Ō','Ő','Ŏ','Œ','Ŕ','Ř','Ŗ','Ś','Š','Ş','Ŝ','Ș','Ť','Ţ','Ŧ','Ț','Ù','Ú','Û','Ü','Ū','&Uuml;','Ů','Ű','Ŭ','Ũ','Ų','Ŵ','Ý','Ŷ','Ÿ','Ź','Ž','Ż','Þ','Þ','à','á','â','ã','ä','&auml;','å','ā','ą','ă','æ','ç','ć','č','ĉ','ċ','ď','đ','ð','è','é','ê','ë','ē','ę','ě','ĕ','ė','ƒ','ĝ','ğ','ġ','ģ','ĥ','ħ','ì','í','î','ï','ī','ĩ','ĭ','į','ı','ĳ','ĵ','ķ','ĸ','ł','ľ','ĺ','ļ','ŀ','ñ','ń','ň','ņ','ŉ','ŋ','ò','ó','ô','õ','ö','&ouml;','ø','ō','ő','ŏ','œ','ŕ','ř','ŗ','š','ù','ú','û','ü','ū','&uuml;','ů','ű','ŭ','ũ','ų','ŵ','ý','ÿ','ŷ','ž','ż','ź','þ','ß','ſ','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я','а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я');
   protected static $Translation = array(' ',' ','','','','','','A','A','A','A','Ae','A','A','A','A','A','Ae','C','C','C','C','C','D','D','D','E','E','E','E','E','E','E','E','E','G','G','G','G','H','H','I','I','I','I','I','I','I','I','I','IJ','J','K','K','K','K','K','K','N','N','N','N','N','O','O','O','O','Oe','Oe','O','O','O','O','OE','R','R','R','S','S','S','S','S','T','T','T','T','U','U','U','Ue','U','Ue','U','U','U','U','U','W','Y','Y','Y','Z','Z','Z','T','T','a','a','a','a','ae','ae','a','a','a','a','ae','c','c','c','c','c','d','d','d','e','e','e','e','e','e','e','e','e','f','g','g','g','g','h','h','i','i','i','i','i','i','i','i','i','ij','j','k','k','l','l','l','l','l','n','n','n','n','n','n','o','o','o','o','oe','oe','o','o','o','o','oe','r','r','r','s','u','u','u','ue','u','ue','u','u','u','u','u','w','y','y','y','z','z','z','t','ss','ss','A','B','V','G','D','E','YO','ZH','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','H','C','CH','SH','SCH','','Y','','E','YU','YA','a','b','v','g','d','e','yo','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya');

   public static function Clean($Mixed) {
      if(!is_string($Mixed)) return self::To($Mixed, 'Clean');
      $Mixed = str_replace(self::$Code, self::$Translation, $Mixed);
      $Mixed = preg_replace('/[^A-Za-z0-9 ]/', '', urldecode($Mixed));
      $Mixed = preg_replace('/ +/', '-', trim($Mixed));
      return strtolower($Mixed);
   }

   /**
    * Formats a Mysql DateTime string in the specified format.
    *
    * @param string $Timestamp A timestamp or string in Mysql DateTime format. ie. YYYY-MM-DD HH:MM:SS
    * @param string $Format The format string to use. Defaults to the application's default format.
    * For instructions on how the format string works:
    *  http://ca.php.net/manual/en/function.date.php
    * @return string
    */
   public static function Date($Timestamp = '', $Format = '') {
      // Was a mysqldatetime passed?
      if (!is_numeric($Timestamp))
         $Timestamp = self::ToTimestamp($Timestamp);
         
      if (!$Timestamp)
         $Timestamp = time(); // return '&nbsp;'; Apr 22, 2009 - found a bug where "Draft Saved At X" returned a nbsp here instead of the formatted current time.

      // Alter the timestamp based on the user's hour offset
      $Session = Gdn::Session();
      if ($Session->UserID > 0)
         $Timestamp += ($Session->User->HourOffset * 3600);

      if ($Format == '') {
         // If the timestamp was during the current day
         if (date('Y m d', $Timestamp) == date('Y m d', time())) {
            // Use the time format
            $Format = T('Date.DefaultTimeFormat', '%l:%M%p');
         } else if (date('Y', $Timestamp) == date('Y', time())) {
            // If the timestamp is the same year, show the month and date
            $Format = T('Date.DefaultDayFormat', '%B %e');
         } else if (date('Y', $Timestamp) != date('Y', time())) {
            // If the timestamp is not the same year, just show the year
            $Format = T('Date.DefaultYearFormat', '%B %Y');
         } else {
            // Otherwise, use the date format
            $Format = T('Date.DefaultFormat', '%B %e, %Y');
         }
      }

      // Emulate %l and %e for Windows
      if (strpos($Format, '%l') !== false)
          $Format = str_replace('%l', ltrim(strftime('%I', $Timestamp), '0'), $Format);
      if (strpos($Format, '%e') !== false)
          $Format = str_replace('%e', ltrim(strftime('%d', $Timestamp), '0'), $Format);

      return strftime($Format, $Timestamp);
   }
   
   /**
    * Format a string from of "Deleted" content (comment, message, etc).
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return string
    */
   public static function Deleted($Mixed) {
      if (!is_string($Mixed)) {
         return self::To($Mixed, 'Deleted');
      } else {         
         $Formatter = Gdn::Factory('HtmlFormatter');
         if (is_null($Formatter)) {
            return Gdn_Format::Display($Mixed);
         } else {
            return $Formatter->Format(Wrap($Mixed, 'div', ' class="Deleted"'));
         }
      }
   }
   
   /**
    * Takes a mixed variable, formats it for display on the screen, and returns
    * it.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return string
    */
   public static function Display($Mixed) {
      if (!is_string($Mixed))
         return self::To($Mixed, 'Display');
      else {
         $Mixed = htmlspecialchars($Mixed, ENT_QUOTES, Gdn::Config('Garden.Charset', ''));
         $Mixed = str_replace(array("&quot;","&amp;"), array('"','&'), $Mixed);
         $Mixed = preg_replace(
            "/
            (?<!<a href=\")
            (?<!\")(?<!\">)
            ((https?|ftp):\/\/)
            ([\@a-z0-9\x21\x23-\x27\x2a-\x2e\x3a\x3b\/;\x3f-\x7a\x7e\x3d]+)
            /msxi",
            "<a href=\"$0\" target=\"_blank\" rel=\"nofollow\">$0</a>",
            $Mixed
         );

         return nl2br($Mixed);
      }
   }

   /**
    * Formats an email address in a non-scrapable format that Garden can then
    * make linkable using jquery.
    * 
    * @param string $Email
    * @return string
    */
   public static function Email($Email) {
      $At = T('at');
      $Dot = T('dot');
      return '<span class="Email EmailUnformatted">' . str_replace(array('@', '.'), array('<strong>' . $At . '</strong>', '<em>' . $Dot . '</em>'), $Email) . '</span>';
   }

   /**
    * Takes a mixed variable, formats it for display in a form, and returns it.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return string
    */
   public static function Form($Mixed) {
      if (!is_string($Mixed))
         return self::To($Mixed, 'Form');
      else
         return nl2br(htmlspecialchars($Mixed, ENT_QUOTES, C('Garden.Charset', '')));
   }

   /**
    * Takes a mixed variable, filters unsafe html and returns it.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return string
    */
   public static function Html($Mixed) {
      if (!is_string($Mixed)) {
         return self::To($Mixed, 'Html');
      } else {
         $IsHtml = strpos($Mixed, '<') !== FALSE
            || (bool)preg_match('/&#?[a-z0-9]{1,10};/i', $Mixed);

         if ($IsHtml) {
            // The text contains html and must be purified.

            $Formatter = Gdn::Factory('HtmlFormatter');
            if(is_null($Formatter)) {
               // If there is no HtmlFormatter then make sure that script injections won't work.
               return self::Display($Mixed);
            }

            // Allow the code tag to keep all enclosed html encoded.
            $Mixed = preg_replace(
               array('/<code([^>]*)>(.+?)<\/code>/sei'),
               array('\'<code\'.RemoveQuoteSlashes(\'\1\').\'>\'.htmlspecialchars(RemoveQuoteSlashes(\'\2\')).\'</code>\''),
               $Mixed
            );

            // Links
            $Mixed = Gdn_Format::Links($Mixed);
            // Mentions & Hashes
            $Mixed = Gdn_Format::Mentions($Mixed);

            // nl2br
            $Mixed = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $Mixed);

            $Result = $Formatter->Format($Mixed);

//            $Result = $Result.
//               "<h3>Html</h3><pre>".nl2br(htmlspecialchars(str_replace("<br />", "\n", $Mixed)))."</pre>".
//               "<h3>Formatted</h3><pre>".nl2br(htmlspecialchars(str_replace("<br />", "\n", $Result)))."</pre>";
         } else {
            // The text does not contain text and does not have to be purified.
            // This is an optimization because purifying is very slow and memory intense.
            $Result = htmlspecialchars($Mixed);
            $Result = Gdn_Format::Mentions($Result);
            $Result = Gdn_Format::Links($Result);
            $Result = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $Result);
         }
         
         return $Result;
      }
   }

   /** Formats the anchor tags around the links in text.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return string
    */
   public static function Links($Mixed) {
      if (!is_string($Mixed))
         return self::To($Mixed, 'Links');
      else {
         $Mixed = preg_replace_callback(
            "/
            (?<!<a href=\")
            (?<!\")(?<!\">)
            ((?:https?|ftp):\/\/)
            ([\@a-z0-9\x21\x23-\x27\x2a-\x2e\x3a\x3b\/;\x3f-\x7a\x7e\x3d]+)
            /msxi",
         array('Gdn_Format', 'LinksCallback'),
         $Mixed);

         return $Mixed;
      }
   }
   protected static function LinksCallback($Matches) {
      $Pr = $Matches[1];
      $Url = $Matches[2];
      if (preg_match('/www.youtube.com\/watch\?v=([^&]+)/', $Url, $Matches) && C('Garden.Format.YouTube')) {
         $ID = $Matches[1];
         $Width = 400;
         $Height = 225;
         $Result = <<<EOT
<div class="Video"><object width="$Width" height="$Height"><param name="movie" value="http://www.youtube.com/v/$ID&hl=en_US&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/$ID&hl=en_US&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="$Width" height="$Height"></embed></object></div>
EOT;
      } elseif (preg_match('/vimeo.com\/(\d+)/', $Url, $Matches) && C('Garden.Format.Vimeo')) {
         $ID = $Matches[1];
         $Width = 400;
         $Height = 225;

         $Result = <<<EOT
<div class="Video"><object width="$Width" height="$Height"><param name="allowfullscreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=$ID&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" /><embed src="http://vimeo.com/moogaloop.swf?clip_id=$ID&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="$Width" height="$Height"></embed></object></div>
EOT;
      } else {
         $Result = <<<EOT
<a href="$Pr$Url" target="_blank" rel="nofollow">$Pr$Url</a>
EOT;
      }
      return $Result;
   }

   /**
    * Format a string using Markdown syntax. Also purifies the output html.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return string
    */
   public static function Markdown($Mixed) {
      if (!is_string($Mixed)) {
         return self::To($Mixed, 'Markdown');
      } else {         
         $Formatter = Gdn::Factory('HtmlFormatter');
         if (is_null($Formatter)) {
            return Gdn_Format::Display($Mixed);
         } else {
            require_once(PATH_LIBRARY.DS.'vendors'.DS.'markdown'.DS.'markdown.php');
            $Mixed = Markdown($Mixed);
            $Mixed = Gdn_Format::Mentions($Mixed);
            return $Formatter->Format($Mixed);
         }
      }
   }
   
   public static function Mentions($Mixed) {
      if (!is_string($Mixed)) {
         return self::To($Mixed, 'Mentions');
      } else {         
         // Handle @mentions.
         if(C('Garden.Format.Mentions')) {
            $Mixed = preg_replace(
               '/(^|[\s,\.])@(\w{1,20})\b/i', //{3,20}
               '\1'.Anchor('@\2', '/profile/\\2'),
               $Mixed
            );
         }
         
         // This one handles all other mentions
//         $Mixed = preg_replace(
//            '/([\s]+)(@([\d\w_]{1,20}))/si',
//            '\\1'.Anchor('\\2', '/profile/\\3'),
//            $Mixed
//         );
         
         // Handle #hashtag searches
			if(C('Garden.Format.Hashtags')) {
				$Mixed = preg_replace(
					'/(^|[\s,\.])\#([\w\-]+)(?=[\s,\.!?]|$)/i',
					'\1'.Anchor('#\2', '/search?Search=%23\2&amp;Mode=like').'\3',
					$Mixed
				);
			}
         
//         $Mixed = preg_replace(
//            '/([\s]+)(#([\d\w_]+))/si',
//            '\\1'.Anchor('\\2', '/search?Search=%23\\3'),
//            $Mixed
//         );
         return $Mixed;
      }
   }

   /** Return the input without any operations performed at all.
    *  This format should only be used when administrators have access.
    *
    * @param string|object|array $Mixed The data to format.
    * @return string
    */
   public static function Raw($Mixed) {
      if (!is_string($Mixed)) {
         return self::To($Mixed, 'Raw');
      } else {
         return $Mixed;
      }
   }

   /**
    * Takes an object and convert's it's properties => values to an associative
    * array of $Array[Property] => Value sets.
    *
    * @param object $Object The object to be converted to an array.
    * @return unknown
    * @todo could be just "return (array) $Object;"?
    */
   public static function ObjectAsArray($Object) {
      if (!is_object($Object))
         return $Object;

      $Return = array();
      foreach(get_object_vars($Object) as $Property => $Value) {
         $Return[$Property] = $Value;
      }
      return $Return;
   }

   /**
    * Takes any variable and serializes it.
    *
    * @param mixed $Mixed An object, array, or string to be serialized.
    * @return string The serialized version of the string.
    */
   public static function Serialize($Mixed) {
		if(is_array($Mixed) || is_object($Mixed)
			|| (is_string($Mixed) && (substr_compare('a:', $Mixed, 0, 2) === 0 || substr_compare('O:', $Mixed, 0, 2) === 0
				|| substr_compare('arr:', $Mixed, 0, 4) === 0 || substr_compare('obj:', $Mixed, 0, 4) === 0))) {
			$Result = serialize($Mixed);
		} else {
			$Result = $Mixed;
		}
		return $Result;
   }

   /**
    * Takes a mixed variable, formats it for display on the screen as plain text.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return mixed
    */
   public static function Text($Mixed, $AddBreaks = TRUE) {
      if (!is_string($Mixed))
         return self::To($Mixed, 'Text');
      else {
         $Charset = C('Garden.Charset', 'UTF-8');
         $Result = htmlspecialchars(strip_tags(html_entity_decode($Mixed, ENT_COMPAT, $Charset)), ENT_QUOTES, $Charset);
         if ($AddBreaks)
            $Result = nl2br($Result);
         return $Result;
      }
   }

   /**
    * Takes a mixed variable, formats it in the specified format type, and
    * returns it.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @param string $FormatMethod The method with which the variable should be formatted.
    * @return mixed
    */
   public static function To($Mixed, $FormatMethod) {
      if ($FormatMethod == '')
         return $Mixed;
      
      if (is_string($Mixed)) {
         if (method_exists('Gdn_Format', $FormatMethod)) {
            $Mixed = self::$FormatMethod($Mixed);
         } elseif (function_exists($FormatMethod)) {
            $Mixed = $FormatMethod($Mixed);
         } elseif ($Formatter = Gdn::Factory($FormatMethod.'Formatter')) {;
            $Mixed = $Formatter->Format($Mixed);
         } else {
            $Mixed = Gdn_Format::Text($Mixed);
         }
      } else if (is_array($Mixed)) {
         foreach($Mixed as $Key => $Val) {
            $Mixed[$Key] = self::To($Val, $FormatMethod);
         }
      } else if (is_object($Mixed)) {
         foreach(get_object_vars($Mixed) as $Prop => $Val) {
            $Mixed->$Prop = self::To($Val, $FormatMethod);
         }
      }
      return $Mixed;
   }

   /** Format a timestamp or the current time to go into the database.
    *
    * @param int $Timestamp
    * @return string The formatted date.
    */
   public static function ToDate($Timestamp = '') {
      if ($Timestamp == '')
         $Timestamp = time();
      elseif (!is_numeric($Timestamp))
         $Timestamp = self::ToTimestamp($Timestamp);

      return date('Y-m-d', $Timestamp);
   }

   /** Format a timestamp or the current time to go into the database.
    * 
    * @param int $Timestamp
    * @return string The formatted date and time.
    */
   public static function ToDateTime($Timestamp = '') {
      if ($Timestamp == '')
         $Timestamp = time();
      return date('Y-m-d H:i:s', $Timestamp);
   }

   /**
    * Convert a datetime to a timestamp
    *
    * @param string $DateTime The Mysql-formatted datetime to convert to a timestamp. Should be in one
    * of the following formats: YYYY-MM-DD or YYYY-MM-DD HH:MM:SS. Returns
    * FALSE upon failure.
    * @return unknown
    */
   public static function ToTimestamp($DateTime = '') {
      if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\s{1}(\d{2}):(\d{2})(?::(\d{2}))?)?$/', $DateTime, $Matches)) {
         $Year = $Matches[1];
         $Month = $Matches[2];
         $Day = $Matches[3];
         $Hour = ArrayValue(4, $Matches, 0);
         $Minute = ArrayValue(5, $Matches, 0);
         $Second = ArrayValue(6, $Matches, 0);
         return mktime($Hour, $Minute, $Second, $Month, $Day, $Year);
      } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $DateTime, $Matches)) {
         $Year = $Matches[1];
         $Month = $Matches[2];
         $Day = $Matches[3];
         return mktime(0, 0, 0, $Month, $Day, $Year);
      // } elseif ($DateTime == '') {
      //    return time();
      } else {
         return FALSE;
      }
   }

   public static function Timespan($timespan) {
      //$timespan -= 86400 * ($days = (int) floor($timespan / 86400));
      $timespan -= 3600 * ($hours = (int) floor($timespan / 3600));
      $timespan -= 60 * ($minutes = (int) floor($timespan / 60));
      $seconds = $timespan;
         
      $Result = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
      return $Result;
   }

   protected static $_UrlTranslations = array('–' => '-', '—' => '-', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Ä' => 'A', 'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae', 'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D', 'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E', 'Ę' => 'E', 'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G', 'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I', 'İ' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'K', 'Ľ' => 'K', 'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ö' => 'Oe', 'Ø' => 'O', 'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O', 'Œ' => 'OE', 'Ŕ' => 'R', 'Ř' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S', 'Ş' => 'S', 'Ŝ' => 'S', 'Ș' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T', 'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ū' => 'U', 'Ü' => 'Ue', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U', 'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'Ž' => 'Z', 'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'ä' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a', 'æ' => 'ae', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ƒ' => 'f', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'ĩ' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij', 'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l', 'ŀ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n', 'ŋ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ö' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o', 'œ' => 'oe', 'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ū' => 'u', 'ü' => 'ue', 'ů' => 'u', 'ű' => 'u', 'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'ß' => 'ss', 'ſ' => 'ss', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'ș' => 's', 'Ș' => 'S', 'ț' => 't', 'Ț' => 'T',  'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya');

   /**
    * Replaces all non-url-friendly characters with dashes.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return mixed
    */
   public static function Url($Mixed) {
      if (!is_string($Mixed)) {
         return self::To($Mixed, 'Url');
      } elseif (preg_replace('`([^\PP])`u', '', 'Test') == '') {
         // No Unicode PCRE support
         $Mixed = strip_tags(html_entity_decode($Mixed, ENT_COMPAT, 'UTF-8'));
         $Mixed = strtr($Mixed, self::$_UrlTranslations);
         $Mixed = preg_replace('/([^\w\d_:.])/', ' ', $Mixed); // get rid of punctuation and symbols
         $Mixed = str_replace(' ', '-', trim($Mixed)); // get rid of spaces
         $Mixed = preg_replace('/-+/', '-', $Mixed); // limit to 1 hyphen at a time
         $Mixed = urlencode(strtolower($Mixed));
         return $Mixed;
      } else {
         // Better Unicode support
         $Mixed = strip_tags(html_entity_decode($Mixed, ENT_COMPAT, 'UTF-8'));
         $Mixed = strtr($Mixed, self::$_UrlTranslations);
         $Mixed = preg_replace('`([^\PP.\-_])`u', '', $Mixed); // get rid of punctuation
         $Mixed = preg_replace('`([^\PS+])`u', '', $Mixed); // get rid of symbols
         $Mixed = preg_replace('`[\s\-/+]+`u', '-', $Mixed); // replace certain characters with dashes
         $Mixed = urlencode(strtolower($Mixed));
			return $Mixed;
      }
   }

   /**
    * Takes a serialized variable and unserializes it back into it's
    * original state.
    *
    * @param string $SerializedString A json or php serialized string to be unserialized.
    * @return mixed
    */
   public static function Unserialize($SerializedString) {
		$Result = $SerializedString;
		
      if(is_string($SerializedString)) {
			if(substr_compare('a:', $SerializedString, 0, 2) === 0 || substr_compare('O:', $SerializedString, 0, 2) === 0)
				$Result = unserialize($SerializedString);
			elseif(substr_compare('obj:', $SerializedString, 0, 4) === 0)
            $Result = json_decode(substr($SerializedString, 4), FALSE);
         elseif(substr_compare('arr:', $SerializedString, 0, 4) === 0)
            $Result = json_decode(substr($SerializedString, 4), TRUE);
      }
      return $Result;
   }
   
   public static function VanillaSprintf($PlaceholderString, $ReplaceWith) {
      // Set replacement array inside callback
      Gdn_Format::VanillaSprintfCallback(NULL, $ReplaceWith);  
      
      $FinalString = preg_replace_callback('/({([a-z0-9_:]+)})/i', array('Gdn_Format', 'VanillaSprintfCallback'), $PlaceholderString);
      
      // Cleanup replacement list
      Gdn_Format::VanillaSprintfCallback(NULL, array());
      
      return $FinalString;
   }
   
   protected static function VanillaSprintfCallback($Match, $InternalReplacementList = FALSE) {
      static $InternalReplacement = array();
      
      if (is_array($InternalReplacementList)) {
         $InternalReplacement = $InternalReplacementList;
      } else {
         $MatchStr = $Match[2];
         $Format = (count($SplitMatch = explode(':',$MatchStr)) > 1) ? $SplitMatch[1] : FALSE;
         
         if (array_key_exists($MatchStr, $InternalReplacement)) {
            if ($Format) {
               // TODO: Apply format
            }
            return $InternalReplacement[$MatchStr];
         }

         return $Match[1];
      }
   }

   /**
    * Formats seconds in a human-readable way (ie. 45 seconds, 15 minutes, 2 hours, 4 days, 2 months, etc).
    */
   public static function Seconds($Seconds) {
      $Minutes = floor($Seconds/60);
      $Hours = floor($Seconds/60/24);
      $Days = floor($Seconds/60/60/24);
      $Weeks = floor($Seconds/60/60/24/7);
      $Months = floor($Seconds/60/60/24/30);
      $Years = floor($Seconds/60/60/24/365);

      if ($Seconds < 60)
         return sprintf(Plural($Seconds, '%s second', '%s seconds'), $Seconds);
      elseif ($Minutes < 60)
         return sprintf(Plural($Minutes, '%s minute', '%s minutes'), $Minutes);
      elseif ($Hours < 24)
         return sprintf(Plural($Hours, '%s hour', '%s hours'), $Hours);
      elseif ($Days < 7)
         return sprintf(Plural($Days, '%s day', '%s days'), $Days);
      elseif ($Weeks < 4)
         return sprintf(Plural($Weeks, '%s week', '%s weeks'), $Weeks);
      elseif ($Months < 12)
         return sprintf(Plural($Months, '%s month', '%s months'), $Months);
      else
         return sprintf(Plural($Years, '%s year', '%s years'), $Years);
   }
   
}
