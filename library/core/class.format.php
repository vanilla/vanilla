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
class Format {

   /**
    * The ActivityType table has some special sprintf search/replace values in the
    * FullHeadline and ProfileHeadline fields. The ProfileHeadline field is to be
    * used on this page (the user profile page). The FullHeadline field is to be
    * used on the main activity page. The replacement definitions are as follows:
    *  %1 = ActivityName
    *  %2 = ActivityName Possessive
    *  %3 = RegardingName
    *  %4 = RegardingName Possessive
    *  %5 = Link to RegardingName's Wall
    *  %6 = his/her
    *  %7 = he/she
    *  %8 = route & routecode
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
      
      if ($ViewingUserID == $Activity->ActivityUserID) {
         $ActivityName = $ActivityNameP = T('You');
      } else {
         $ActivityName = $Activity->ActivityName;
         $ActivityNameP = FormatPossessive($ActivityName);
      }
      if ($ProfileUserID != $Activity->ActivityUserID) {
         // If we're not looking at the activity user's profile, link the name
         $ActivityNameD = urlencode($Activity->ActivityName);
         $ActivityName = Anchor($ActivityName, '/profile/' . $Activity->ActivityUserID . '/' . $ActivityNameD);
         $ActivityNameP = Anchor($ActivityNameP, '/profile/' . $Activity->ActivityUserID  . '/' . $ActivityNameD);
      }
      $Gender = T($Activity->ActivityGender == 'm' ? 'his' : 'her');
      $Gender2 = T($Activity->ActivityGender == 'm' ? 'he' : 'she');
      if ($ViewingUserID == $Activity->RegardingUserID || ($Activity->RegardingUserID == '' && $Activity->ActivityUserID == $ViewingUserID))
         $Gender = $Gender2 = T('your');

      $IsYou = FALSE;
      if ($ViewingUserID == $Activity->RegardingUserID) {
         $IsYou = TRUE;
         $RegardingName = T('you');
         $RegardingNameP = T('your');
      } else {
         $RegardingName = $Activity->RegardingName == '' ? T('somebody') : $Activity->RegardingName;
         $RegardingNameP = FormatPossessive($RegardingName);
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
         }
         $RegardingWall = Anchor(T('wall'), '/profile/activity/' . $Activity->RegardingUserID . '/' . $RegardingNameD . '#Activity_' . $Activity->ActivityID);
      }
      if ($RegardingWall == '')
         $RegardingWall = T('wall');

      if ($Activity->Route == '')
         $Route = T($Activity->RouteCode);
      else
         $Route = Anchor(T($Activity->RouteCode), $Activity->Route);

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
      */
      return sprintf($ProfileUserID == $Activity->ActivityUserID || $ProfileUserID == '' ? T($Activity->FullHeadline) : T($Activity->ProfileHeadline), $ActivityName, $ActivityNameP, $RegardingName, $RegardingNameP, $RegardingWall, $Gender, $Gender2, $Route);
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
         if (method_exists('Format', $FormatMethod)) {
            $Mixed = self::$FormatMethod($Mixed);
         } else if (function_exists($FormatMethod)) {
            $Mixed = $FormatMethod($Mixed);
         } else {
            $Mixed = Format::Text($Mixed);
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


   /**
    * Takes a mixed variable, formats it for display on the screen, and returns
    * it.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return mixed
    */
   public static function Display($Mixed) {
      if (!is_string($Mixed))
         return self::To($Mixed, 'Display');
      else
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

         return '<p>'.nl2br($Mixed).'</p>';
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
         return nl2br(htmlspecialchars($Mixed, ENT_QUOTES, Gdn::Config('Garden.Charset', '')));
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
         $Formatter = Gdn::Factory('HtmlFormatter');
         if(is_null($Formatter)) {
            // If there is no HtmlFormatter then make sure that script injections won't work.
            return self::Display($Mixed);
         }
         
         // Allow the code tag to keep all enclosed html encoded.
         $Mixed = preg_replace(
            array('/<code([^>]*)>(.+?)<\/code>/sei'), 
            array('\'<code\'.RemoveQuoteSlashes(\'\1\').\'><![CDATA[\'.RemoveQuoteSlashes(\'\2\').\']]></code>\''), 
            $Mixed
         );
         
         // Handle @mentions
         // This one grabs mentions that start at the beginning of $Mixed
         $Mixed = preg_replace(
            '/^(@([\d\w_]{1,20}))/si',
            Anchor('\\1', '/profile/\\2'),
            $Mixed
         );
         
         // This one handles all other mentions
         $Mixed = preg_replace(
            '/([\s]+)(@([\d\w_]{1,20}))/si',
            '\\1'.Anchor('\\2', '/profile/\\3'),
            $Mixed
         );
         
         // Handle #hashtag searches
         $Mixed = preg_replace(
            '/^(#([\d\w_-]+))/si',
            Anchor('\\1', '/search?Search=%23\\2'),
            $Mixed
         );
         
         $Mixed = preg_replace(
            '/([\s]+)(#([\d\w_]+))/si',
            '\\1'.Anchor('\\2', '/search?Search=%23\\3'),
            $Mixed
         );
         
         // nl2br
         $Mixed = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $Mixed);

         return $Formatter->Format($Mixed);
      }
   }

   /**
    * Takes a mixed variable.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return string
    */
   public static function BBCode($Mixed) {
      if (!is_string($Mixed)) {
         return self::To($Mixed, 'Html');
      } else {         
		$Mixed = preg_replace("#\[b\](.*?)\[/b\]#si",'<b>\\1</b>',$Mixed);
		$Mixed = preg_replace("#\[i\](.*?)\[/i\]#si",'<i>\\1</i>',$Mixed);
		$Mixed = preg_replace("#\[u\](.*?)\[/u\]#si",'<u>\\1</u>',$Mixed);
		$Mixed = preg_replace("#\[s\](.*?)\[/s\]#si",'<s>\\1</s>',$Mixed);
		$Mixed = preg_replace("#\[quote=('(.*?)',(.*?))\](.*?)\[/quote\]#si",'<p><cite>\\2</cite> napisał:</p><blockquote>\\4</blockquote>',$Mixed);
		$Mixed = preg_replace("#\[quote\](.*?)\[/quote\]#si",'<blockquote>\\1</blockquote>',$Mixed);
		$Mixed = preg_replace("#\[code\](.*?)\[/code\]#si",'<code>\\1</code>',$Mixed);
		$Mixed = preg_replace("#\[hide\](.*?)\[/hide\]#si",'\\1',$Mixed);
		$Mixed = preg_replace("#\[url\](.*?)\[/url\]#si",'\\1',$Mixed);
		$Mixed = preg_replace("#\[url=(.*?)\](.*?)\[/url\]#si",'<a href="\\1">\\2</a>',$Mixed);
		$Mixed = preg_replace("#\[php\](.*?)\[/php\]#si",'<code>\\1</code>',$Mixed);
		$Mixed = preg_replace("#\[mysql\](.*?)\[/mysql\]#si",'<code>\\1</code>',$Mixed);
		$Mixed = preg_replace("#\[css\](.*?)\[/css\]#si",'<code>\\1</code>',$Mixed);
		$Mixed = preg_replace("#\[img=(.*?)\](.*?)\[/img\]#si",'<img src="\\1" alt="\\2" />',$Mixed);
		$Mixed = preg_replace("#\[img\](.*?)\[/img\]#si",'<img src="\\1" border="0" />',$Mixed);
		$Mixed = preg_replace("#\[color=(.*?)\](.*?)\[/color\]#si",'<font color="\\1">\\2</font>',$Mixed);
		$Mixed = preg_replace("#\[size=(.*?)\](.*?)\[/size\]#si",'<font size="\\1">\\2</font>',$Mixed);
		
         // Handle @mentions
         // This one grabs mentions that start at the beginning of $Mixed
         $Mixed = preg_replace(
            '/^(@([\d\w_]{1,20}))/si',
            Anchor('\\1', '/profile/\\2'),
            $Mixed
         );
         
         // This one handles all other mentions
         $Mixed = preg_replace(
            '/([\s]+)(@([\d\w_]{3,20}))/si',
            '\\1'.Anchor('\\2', '/profile/\\3'),
            $Mixed
         );
		 
		 $Formatter = Gdn::Factory('HtmlFormatter');
         if(is_null($Formatter)) {
            return $Mixed;
         } else {
            return $Formatter->Format($Mixed);
         }
      }
   }
   
   public static function Wiki($Mixed) {
      if (!is_string($Mixed)) {
         return self::To($Mixed, 'Html');
      } else {
         // Allow the code tag to keep all enclosed html encoded.
         $Mixed = preg_replace(
            array('/<code([^>]*)>(.+?)<\/code>/sei'), 
            array('\'<code\'.RemoveQuoteSlashes(\'\1\').\'><![CDATA[\'.RemoveQuoteSlashes(\'\2\').\']]></code>\''), 
            $Mixed
         );
         $Mixed = preg_replace(
            array('/<pre([^>]*)>(.+?)<\/pre>/sei'), 
            array('\'<pre\'.RemoveQuoteSlashes(\'\1\').\'><![CDATA[\'.RemoveQuoteSlashes(\'\2\').\']]></pre>\''), 
            $Mixed
         );

         // Replace Wiki Hyperlinks with actual hyperlinks
         $Mixed = preg_replace(
            '/\[\[([A-z0-9:.]+)\]\]/si', 
            Anchor('\\1', 'page/\\1'), 
            $Mixed
         );
         
         $Mixed = preg_replace(
            '/\[\[([A-z0-9:.]+)([\|]{1})([A-z0-9\s\-&,.\*]+)\]\]/si', 
            Anchor('\\3', 'page/\\1'), 
            $Mixed
         );

         $Formatter = Gdn::Factory('HtmlFormatter');
         if(is_null($Formatter)) {
            return $Mixed;
         } else {
            return $Formatter->Format($Mixed);
         }
      }
   }

   /**
    * Takes a mixed variable, formats it for display on the screen as plain text
    * with no newlines and returns it.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return mixed
    */
   public static function Text($Mixed) {
      if (!is_string($Mixed))
         return self::To($Mixed, 'Text');
      else
         return htmlspecialchars(strip_tags($Mixed), ENT_QUOTES, Gdn::Config('Garden.Charset', 'UTF-8'));
   }

   /**
   * 
   */
   protected static $Code = array('-','_','&lt;','&gt;','&#039;','&amp;','&quot;','À','Á','Â','Ã','Ä','&Auml;','Å','Ā','Ą','Ă','Æ','Ç','Ć','Č','Ĉ','Ċ','Ď','Đ','Ð','È','É','Ê','Ë','Ē','Ę','Ě','Ĕ','Ė','Ĝ','Ğ','Ġ','Ģ','Ĥ','Ħ','Ì','Í','Î','Ï','Ī','Ĩ','Ĭ','Į','İ','Ĳ','Ĵ','Ķ','Ł','Ľ','Ĺ','Ļ','Ŀ','Ñ','Ń','Ň','Ņ','Ŋ','Ò','Ó','Ô','Õ','Ö','&Ouml;','Ø','Ō','Ő','Ŏ','Œ','Ŕ','Ř','Ŗ','Ś','Š','Ş','Ŝ','Ș','Ť','Ţ','Ŧ','Ț','Ù','Ú','Û','Ü','Ū','&Uuml;','Ů','Ű','Ŭ','Ũ','Ų','Ŵ','Ý','Ŷ','Ÿ','Ź','Ž','Ż','Þ','Þ','à','á','â','ã','ä','&auml;','å','ā','ą','ă','æ','ç','ć','č','ĉ','ċ','ď','đ','ð','è','é','ê','ë','ē','ę','ě','ĕ','ė','ƒ','ĝ','ğ','ġ','ģ','ĥ','ħ','ì','í','î','ï','ī','ĩ','ĭ','į','ı','ĳ','ĵ','ķ','ĸ','ł','ľ','ĺ','ļ','ŀ','ñ','ń','ň','ņ','ŉ','ŋ','ò','ó','ô','õ','ö','&ouml;','ø','ō','ő','ŏ','œ','ŕ','ř','ŗ','š','ù','ú','û','ü','ū','&uuml;','ů','ű','ŭ','ũ','ų','ŵ','ý','ÿ','ŷ','ž','ż','ź','þ','ß','ſ','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Э','Ю','Я','а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','э','ю','я');
   protected static $Translation = array(' ',' ','','','','','','A','A','A','A','Ae','A','A','A','A','A','Ae','C','C','C','C','C','D','D','D','E','E','E','E','E','E','E','E','E','G','G','G','G','H','H','I','I','I','I','I','I','I','I','I','IJ','J','K','K','K','K','K','K','N','N','N','N','N','O','O','O','O','Oe','Oe','O','O','O','O','OE','R','R','R','S','S','S','S','S','T','T','T','T','U','U','U','Ue','U','Ue','U','U','U','U','U','W','Y','Y','Y','Z','Z','Z','T','T','a','a','a','a','ae','ae','a','a','a','a','ae','c','c','c','c','c','d','d','d','e','e','e','e','e','e','e','e','e','f','g','g','g','g','h','h','i','i','i','i','i','i','i','i','i','ij','j','k','k','l','l','l','l','l','n','n','n','n','n','n','o','o','o','o','oe','oe','o','o','o','o','oe','r','r','r','s','u','u','u','ue','u','ue','u','u','u','u','u','w','y','y','y','z','z','z','t','ss','ss','A','B','V','G','D','E','YO','ZH','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','H','C','CH','SH','SCH','Y','Y','E','YU','YA','a','b','v','g','d','e','yo','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','y','y','e','yu','ya');

   public static function Clean($Mixed) {
      if(!is_string($Mixed)) return self::To($Mixed, 'Clean');
      $Mixed = str_replace(self::$Code, self::$Translation, $Mixed);
      $Mixed = preg_replace('/[^A-Za-z0-9 ]/', '', urldecode($Mixed));
      $Mixed = preg_replace('/ +/', '-', trim($Mixed));
      return strtolower($Mixed);
   }

   /**
    * Replaces all non-url-friendly characters with dashes.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return mixed
    */
   public static function Url($Mixed) {
      if (!is_string($Mixed)) {
         return self::To($Mixed, 'Url');
      } else {
         $Mixed = utf8_decode($Mixed);
         $Mixed = preg_replace('/-+/', '-', str_replace(' ', '-', trim(preg_replace('/([^\w\d_:.])/', ' ', $Mixed))));
         $Mixed = utf8_encode($Mixed);
         return strtolower($Mixed);
      }
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
    * Takes any variable and serializes it in Json format.
    *
    * @param mixed $Mixed An object, array, or string to be formatted.
    * @return string
    */
   public static function Serialize($Mixed) {
      if (is_object($Mixed))
         return 'obj:' . json_encode($Mixed);
      else if (is_array($Mixed))
         return 'arr:' . json_encode($Mixed);
      else
         return $Mixed;
   }


   /**
    * Takes a Json serialized variable and unserializes it back into it's
    * original state.
    *
    * @param string $SerializedString A JSON string to be unserialized.
    * @return mixed
    */
   public static function Unserialize($SerializedString) {
      if (is_string($SerializedString)) {
         if (strpos($SerializedString, 'obj:') === 0) {
            return json_decode(substr($SerializedString, 4));
         } else if (strpos($SerializedString, 'arr:') === 0) {
            return json_decode(substr($SerializedString, 4), TRUE);
         }
      }
      return $SerializedString;
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
    * Takes an object and convert's it's properties => values to an associative
    * array of $Array[Property] => Value sets.
    *
    * @param array $Array An array to be converted to object.
    * @return Gdn_ShellClass
    *
    * @todo could be just "return (object) $Array;"?
    */
   public static function ArrayAsObject($Array) {
      if (!is_array($Array))
         return $Array;

      $Return = new Gdn_ShellClass();
      foreach($Array as $Property => $Value) {
         $Return->$Property = $Value;
      }
      return $Return;
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
            $Format = T('Date.DefaultTimeFormat', '%I:%M%p');
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

      // Emulate %l (not supported on windows)
      return ltrim(strftime($Format, $Timestamp), '0');
   }
   
   
   public static function Timespan($timespan) {
      //$timespan -= 86400 * ($days = (int) floor($timespan / 86400));
      $timespan -= 3600 * ($hours = (int) floor($timespan / 3600));
      $timespan -= 60 * ($minutes = (int) floor($timespan / 60));
      $seconds = $timespan;
         
      $Result = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
      return $Result;
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
      if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $DateTime, $Matches)) {
         $Year = $Matches[1];
         $Month = $Matches[2];
         $Day = $Matches[3];
         $Hour = $Matches[4];
         $Minute = $Matches[5];
         $Second = $Matches[6];
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
   
   /** Format a timestamp or the current time to go into the database.
    *
    * @param int $Timestamp
    * @return string The formatted date.
    */
   public static function ToDate($Timestamp = '') {
      if ($Timestamp == '')
         $Timestamp = time();
      return date('Y-m-d', $Timestamp);
   }

   /**
    * @param int $Timestamp
    * @return string
    * @todo add summary
    */
   public static function ToDateTime($Timestamp = '') {
      if ($Timestamp == '')
         $Timestamp = time();
      return date('Y-m-d H:i:s', $Timestamp);
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
      return '<span class="Email">' . str_replace(array('@', '.'), array('<strong>' . $At . '</strong>', '<em>' . $Dot . '</em>'), $Email) . '</span>';
   }

}
