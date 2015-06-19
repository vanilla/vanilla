<?php
/**
 * Gdn_Format.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Output formatter
 *
 * Utility class that helps to format strings, objects, and arrays.
 */
class Gdn_Format {

    /**
     * Flag which allows plugins to decide if the output should include rel="nofollow" on any <a> links.
     * Example: a plugin can run on "BeforeCommentBody" to check the current users role and decide if his/her post
     * should contain rel="nofollow" links. The default setting is true, meaning all links will contain
     * the rel="nofollow" attribute.
     */
    public static $DisplayNoFollow = true;

    /** @var bool Whether or not to replace plain text links with anchors. */
    public static $FormatLinks = true;

    /** @var string  */
    public static $MentionsUrlFormat = '/profile/{name}';

    /** @var array  */
    protected static $SanitizedFormats = array(
        'html', 'bbcode', 'wysiwyg', 'text', 'textex', 'markdown'
    );

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
    public static function activityHeadline($Activity, $ProfileUserID = '', $ViewingUserID = '') {
        $Activity = (object)$Activity;
        if ($ViewingUserID == '') {
            $Session = Gdn::session();
            $ViewingUserID = $Session->isValid() ? $Session->UserID : -1;
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
            $ActivityName = Anchor($ActivityName, UserUrl($Activity, 'Activity'));
            $ActivityNameP = Anchor($ActivityNameP, UserUrl($Activity, 'Activity'));
            $GenderSuffixCode = 'Third';
        }

        $Gender = t('their'); //TODO: this isn't preferable but I don't know a better option
        $Gender2 = t('they'); //TODO: this isn't preferable either
        if ($Activity->ActivityGender == 'm') {
            $Gender = t('his');
            $Gender2 = t('he');
        } elseif ($Activity->ActivityGender == 'f') {
            $Gender = t('her');
            $Gender2 = t('she');
        }

        if ($ViewingUserID == $Activity->RegardingUserID || ($Activity->RegardingUserID == '' && $Activity->ActivityUserID == $ViewingUserID)) {
            $Gender = $Gender2 = t('your');
        }

        $IsYou = false;
        if ($ViewingUserID == $Activity->RegardingUserID) {
            $IsYou = true;
            $RegardingName = t('you');
            $RegardingNameP = t('your');
            $GenderSuffixGender = $Activity->RegardingGender;
        } else {
            $RegardingName = $Activity->RegardingName == '' ? T('somebody') : $Activity->RegardingName;
            $RegardingNameP = formatPossessive($RegardingName);

            if ($Activity->ActivityUserID != $ViewingUserID) {
                $GenderSuffixCode = 'Third';
            }
        }
        $RegardingWall = '';
        $RegardingWallLink = '';

        if ($Activity->ActivityUserID == $Activity->RegardingUserID) {
            // If the activityuser and regardinguser are the same, use the $Gender Ref as the RegardingName
            $RegardingName = $RegardingProfile = $Gender;
            $RegardingNameP = $RegardingProfileP = $Gender;
        } elseif ($Activity->RegardingUserID > 0 && $ProfileUserID != $Activity->RegardingUserID) {
            // If there is a regarding user and we're not looking at his/her profile, link the name.
            $RegardingNameD = urlencode($Activity->RegardingName);
            if (!$IsYou) {
                $RegardingName = anchor($RegardingName, userUrl($Activity, 'Regarding'));
                $RegardingNameP = anchor($RegardingNameP, userUrl($Activity, 'Regarding'));
                $GenderSuffixCode = 'Third';
                $GenderSuffixGender = $Activity->RegardingGender;
            }
            $RegardingWallActivityPath = userUrl($Activity, 'Regarding');
            $RegardingWallLink = url($RegardingWallActivityPath);
            $RegardingWall = anchor(T('wall'), $RegardingWallActivityPath);
        }
        if ($RegardingWall == '') {
            $RegardingWall = t('wall');
        }

        if ($Activity->Route == '') {
            $ActivityRouteLink = '';
            if ($Activity->RouteCode) {
                $Route = t($Activity->RouteCode);
            } else {
                $Route = '';
            }
        } else {
            $ActivityRouteLink = url($Activity->Route);
            $Route = anchor(T($Activity->RouteCode), $Activity->Route);
        }

        // Translate the gender suffix.
        $GenderSuffixCode = "GenderSuffix.$GenderSuffixCode.$GenderSuffixGender";
        $GenderSuffix = t($GenderSuffixCode, '');
        if ($GenderSuffix == $GenderSuffixCode) {
            $GenderSuffix = ''; // in case translate doesn't support empty strings.
        }
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

        $FullHeadline = t("Activity.{$Activity->ActivityType}.FullHeadline", t($Activity->FullHeadline));
        $ProfileHeadline = t("Activity.{$Activity->ActivityType}.ProfileHeadline", t($Activity->ProfileHeadline));
        $MessageFormat = ($ProfileUserID == $Activity->ActivityUserID || $ProfileUserID == '' || !$ProfileHeadline ? $FullHeadline : $ProfileHeadline);

        return sprintf($MessageFormat, $ActivityName, $ActivityNameP, $RegardingName, $RegardingNameP, $RegardingWall, $Gender, $Gender2, $Route, $GenderSuffix, $RegardingWallLink, $ActivityRouteLink);
    }

    /**
     * Removes all non-alpha-numeric characters (except for _ and -) from
     *
     * @param string $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function alphaNumeric($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'ForAlphaNumeric');
        } else {
            return preg_replace('/([^\w-])/', '', $Mixed);
        }
    }

    /**
     *
     *
     * @param array $Array
     * @return string
     */
    public static function arrayAsAttributes($Array) {
        $Return = '';
        foreach ($Array as $Property => $Value) {
            $Return .= ' '.$Property.'="'.$Value.'"';
        }
        return $Return;
    }

    /**
     * Takes an object and convert's it's properties => values to an associative
     * array of $Array[Property] => Value sets.
     *
     * @param array $Array An array to be converted to object.
     * @return stdClass
     */
    public static function arrayAsObject($Array) {
        if (!is_array($Array)) {
            return $Array;
        }

        $Return = new stdClass();
        foreach ($Array as $Property => $Value) {
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
    public static function arrayValueForPhp($String) {
        return str_replace('\\', '\\', html_entity_decode($String, ENT_QUOTES));
        // $String = str_replace('\\', '\\', html_entity_decode($String, ENT_QUOTES));
        // return str_replace(array("'", "\n", "\r"), array('\\\'', '\\\n', '\\\r'), $String);
    }

    /**
     * Takes a mixed variable, filters unsafe things, renders BBCode and returns it.
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function auto($Mixed) {
        $Formatter = C('Garden.InputFormatter');
        if (!method_exists('Gdn_Format', $Formatter)) {
            return $Mixed;
        }

        return Gdn_Format::$Formatter($Mixed);
    }

    /**
     * Takes a mixed variable.
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function bbCode($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'BBCode');
        } else {
            // See if there is a custom BBCode formatter.
            $BBCodeFormatter = Gdn::factory('BBCodeFormatter');
            if (is_object($BBCodeFormatter)) {
                $Result = $BBCodeFormatter->format($Mixed);
                $Result = Gdn_Format::links($Result);
                $Result = Gdn_Format::mentions($Result);
                $Result = Emoji::instance()->translateToHtml($Result);

                return $Result;
            }

            $Formatter = Gdn::factory('HtmlFormatter');
            if (is_null($Formatter)) {
                return Gdn_Format::display($Mixed);
            } else {
                try {
                    $Mixed2 = $Mixed;
                    //$Mixed2 = str_replace("\n", '<br />', $Mixed2);

                    $Mixed2 = preg_replace("#\[noparse\](.*?)\[/noparse\]#sie", "str_replace(array('[',']',':'), array('&#91;','&#93;','&#58;'), htmlspecialchars('\\1'))", $Mixed2);
                    $Mixed2 = str_ireplace(array("[php]", "[mysql]", "[css]"), "[code]", $Mixed2);
                    $Mixed2 = str_ireplace(array("[/php]", "[/mysql]", "[/css]"), "[/code]", $Mixed2);
                    $Mixed2 = preg_replace("#\[code\](.*?)\[/code\]#sie", "'<div class=\"PreContainer\"><pre>'.str_replace(array('[',']',':'), array('&#91;','&#93;','&#58;'), htmlspecialchars('\\1')).'</pre></div>'", $Mixed2);
                    $Mixed2 = preg_replace("#\[b\](.*?)\[/b\]#si", '<b>\\1</b>', $Mixed2);
                    $Mixed2 = preg_replace("#\[i\](.*?)\[/i\]#si", '<i>\\1</i>', $Mixed2);
                    $Mixed2 = preg_replace("#\[u\](.*?)\[/u\]#si", '<u>\\1</u>', $Mixed2);
                    $Mixed2 = preg_replace("#\[s\](.*?)\[/s\]#si", '<s>\\1</s>', $Mixed2);
                    $Mixed2 = preg_replace("#\[strike\](.*?)\[/strike\]#si", '<s>\\1</s>', $Mixed2);
                    $Mixed2 = preg_replace("#\[quote=[\"']?([^\]]+)(;[\d]+)?[\"']?\](.*?)\[/quote\]#si", '<blockquote class="Quote" rel="\\1"><div class="QuoteAuthor">'.sprintf(T('%s said:'), '\\1').'</div><div class="QuoteText">\\3</div></blockquote>', $Mixed2);
                    $Mixed2 = preg_replace("#\[quote\](.*?)\[/quote\]#si", '<blockquote class="Quote"><div class="QuoteText">\\1</div></blockquote>', $Mixed2);
                    $Mixed2 = preg_replace("#\[cite\](.*?)\[/cite\]#si", '<blockquote class="Quote">\\1</blockquote>', $Mixed2);
                    $Mixed2 = preg_replace("#\[hide\](.*?)\[/hide\]#si", '\\1', $Mixed2);
                    $Mixed2 = preg_replace("#\[url\]((https?|ftp):\/\/.*?)\[/url\]#si", '<a rel="nofollow" target="_blank" href="\\1">\\1</a>', $Mixed2);
                    $Mixed2 = preg_replace("#\[url\](.*?)\[/url\]#si", '\\1', $Mixed2);
                    $Mixed2 = preg_replace("#\[url=[\"']?((https?|ftp):\/\/.*?)[\"']?\](.*?)\[/url\]#si", '<a rel="nofollow" target="_blank" href="\\1">\\3</a>', $Mixed2);
                    $Mixed2 = preg_replace("#\[url=[\"']?(.*?)[\"']?\](.*?)\[/url\]#si", '\\2', $Mixed2);
                    $Mixed2 = preg_replace("#\[img\]((https?|ftp):\/\/.*?)\[/img\]#si", '<img src="\\1" border="0" />', $Mixed2);
                    $Mixed2 = preg_replace("#\[img\](.*?)\[/img\]#si", '\\1', $Mixed2);
                    $Mixed2 = preg_replace("#\[img=[\"']?((https?|ftp):\/\/.*?)[\"']?\](.*?)\[/img\]#si", '<img src=\\1" border="0" alt="\\3" />', $Mixed2);
                    $Mixed2 = preg_replace("#\[img=[\"']?(.*?)[\"']?\](.*?)\[/img\]#si", '\\2', $Mixed2);
                    $Mixed2 = preg_replace("#\[thread\]([\d]+)\[/thread\]#si", '<a href="/discussion/\\1">/discussion/\\1</a>', $Mixed2);
                    $Mixed2 = preg_replace("#\[thread=[\"']?([\d]+)[\"']?\](.*?)\[/thread\]#si", '<a href="/discussion/\\1">\\2</a>', $Mixed2);
                    $Mixed2 = preg_replace("#\[post\]([\d]+)\[/post\]#si", '<a href="/discussion/comment/\\1#Comment_\\1">/discussion/comment/\\1</a>', $Mixed2);
                    $Mixed2 = preg_replace("#\[post=[\"']?([\d]+)[\"']?\](.*?)\[/post\]#si", '<a href="/discussion/comment/\\1#Comment_\\1">\\2</a>', $Mixed2);
                    $Mixed2 = preg_replace("#\[size=[\"']?(.*?)[\"']?\]#si", '<font size="\\1">', $Mixed2);
                    $Mixed2 = preg_replace("#\[font=[\"']?(.*?)[\"']?\]#si", '<font face="\\1">', $Mixed2);
                    $Mixed2 = preg_replace("#\[color=[\"']?(.*?)[\"']?\]#si", '<font color="\\1">', $Mixed2);
                    $Mixed2 = str_ireplace(array("[/size]", "[/font]", "[/color]"), "</font>", $Mixed2);
                    $Mixed2 = str_ireplace(array('[indent]', '[/indent]'), array('<div class="Indent">', '</div>'), $Mixed2);
                    $Mixed2 = str_ireplace(array("[left]", "[/left]"), '', $Mixed2);
                    $Mixed2 = preg_replace_callback("#\[list\](.*?)\[/list\]#si", array('Gdn_Format', 'ListCallback'), $Mixed2);

                    $Result = Gdn_Format::html($Mixed2);
                    return $Result;
                } catch (Exception $Ex) {
                    return self::display($Mixed);
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
    public static function bigNumber($Number, $Format = '') {
        if (!is_numeric($Number)) {
            return $Number;
        }

        $Negative = false;
        $WorkingNumber = $Number;
        if ($Number < 0) {
            $Negative = true;
            $WorkingNumber = $Number - ($Number * 2);
        }

        if ($WorkingNumber >= 1000000000) {
            $Number2 = $WorkingNumber / 1000000000;
            $Suffix = "B";
        } elseif ($WorkingNumber >= 1000000) {
            $Number2 = $WorkingNumber / 1000000;
            $Suffix = "M";
        } elseif ($WorkingNumber >= 1000) {
            $Number2 = $WorkingNumber / 1000;
            $Suffix = "K";
        } else {
            $Number2 = $Number;
        }

        if ($Negative) {
            $Number2 = $Number2 - ($Number2 * 2);
        }

        if (isset($Suffix)) {
            $Result = number_format($Number2, 1);
            if (substr($Result, -2) == '.0') {
                $Result = substr($Result, 0, -2);
            }

            $Result .= $Suffix;
        } else {
            $Result = $Number;
        }

        if ($Format == 'html') {
            $Result = wrap($Result, 'span', array('title' => number_format($Number)));
        }

        return $Result;
    }

    /** Format a number as if it's a number of bytes by adding the appropriate B/K/M/G/T suffix.
     *
     * @param int $Bytes The bytes to format.
     * @param int $Precision The number of decimal places to return.
     * @return string The formatted bytes.
     */
    public static function bytes($Bytes, $Precision = 2) {
        $Units = array('B', 'K', 'M', 'G', 'T');
        $Bytes = max($Bytes, 0);
        $Pow = floor(($Bytes ? log($Bytes) : 0) / log(1024));
        $Pow = min($Pow, count($Units) - 1);
        $Bytes /= pow(1024, $Pow);
        return round($Bytes, $Precision).$Units[$Pow];
    }

    /**
     * @var array Unicode to ascii conversion table.
     */

    protected static $_CleanChars = array(
        '-' => ' ', '_' => ' ', '&lt;' => '', '&gt;' => '', '&#039;' => '', '&amp;' => '',
        '&quot;' => '', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae',
        '&Auml;' => 'A', 'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae',
        'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D',
        'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E',
        'Ę' => 'E', 'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G',
        'Ġ' => 'G', 'Ģ' => 'G', 'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I',
        'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I',
        'İ' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'K', 'Ľ' => 'K',
        'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N',
        'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
        'Ö' => 'Oe', '&Ouml;' => 'Oe', 'Ø' => 'O', 'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O',
        'Œ' => 'OE', 'Ŕ' => 'R', 'Ř' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S',
        'Ş' => 'S', 'Ŝ' => 'S', 'Ș' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T',
        'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ū' => 'U',
        '&Uuml;' => 'Ue', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U',
        'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'Ž' => 'Z',
        'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
        'ä' => 'ae', '&auml;' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a',
        'æ' => 'ae', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c',
        'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e',
        'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e',
        'ƒ' => 'f', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h',
        'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i',
        'ĩ' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij', 'ĵ' => 'j',
        'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l',
        'ŀ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n',
        'ŋ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe',
        '&ouml;' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o', 'œ' => 'oe',
        'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ù' => 'u', 'ú' => 'u',
        'û' => 'u', 'ü' => 'ue', 'ū' => 'u', '&uuml;' => 'ue', 'ů' => 'u', 'ű' => 'u',
        'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y',
        'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'ß' => 'ss',
        'ſ' => 'ss', 'ый' => 'iy', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G',
        'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I',
        'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F',
        'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '',
        'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a',
        'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l',
        'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
        'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e',
        'ю' => 'yu', 'я' => 'ya'
    );

    /**
     * Convert certain unicode characters into their ascii equivalents.
     *
     * @param mixed $Mixed The text to clean.
     * @return string
     */
    public static function clean($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Clean');
        }
        $Mixed = strtr($Mixed, self::$_CleanChars);
        $Mixed = preg_replace('/[^A-Za-z0-9 ]/', '', urldecode($Mixed));
        $Mixed = preg_replace('/ +/', '-', trim($Mixed));
        return strtolower($Mixed);
    }


    /**
     * Formats a Mysql DateTime string in the specified format.
     *
     * For instructions on how the format string works:
     * @link http://us.php.net/manual/en/function.strftime.php
     *
     * @param string $Timestamp A timestamp or string in Mysql DateTime format. ie. YYYY-MM-DD HH:MM:SS
     * @param string $Format The format string to use. Defaults to the application's default format.
     * @return string
     */
    public static function date($Timestamp = '', $Format = '') {
        static $GuestHourOffset;

        // Was a mysqldatetime passed?
        if ($Timestamp !== null && !is_numeric($Timestamp)) {
            $Timestamp = self::toTimestamp($Timestamp);
        }

        if (function_exists('FormatDateCustom') && (!$Format || strcasecmp($Format, 'html') == 0)) {
            if (!$Timestamp) {
                $Timestamp = time();
            }

            return formatDateCustom($Timestamp, $Format);
        }

        if ($Timestamp === null) {
            return T('Null Date', '-');
        }

        if (!$Timestamp) {
            $Timestamp = time(); // return '&#160;'; Apr 22, 2009 - found a bug where "Draft Saved At X" returned a nbsp here instead of the formatted current time.
        }        $GmTimestamp = $Timestamp;

        $Now = time();

        // Alter the timestamp based on the user's hour offset
        $Session = Gdn::session();
        $HourOffset = 0;

        if ($Session->UserID > 0) {
            $HourOffset = $Session->User->HourOffset;
        } elseif (class_exists('DateTimeZone')) {
            if (!isset($GuestHourOffset)) {
                $GuestTimeZone = c('Garden.GuestTimeZone');
                if ($GuestTimeZone) {
                    try {
                        $TimeZone = new DateTimeZone($GuestTimeZone);
                        $Offset = $TimeZone->getOffset(new DateTime('now', new DateTimeZone('UTC')));
                        $GuestHourOffset = floor($Offset / 3600);
                    } catch (Exception $Ex) {
                        $GuestHourOffset = 0;
                        // Do nothing, but don't set the timezone.
                        logException($Ex);
                    }
                }
            }
            $HourOffset = $GuestHourOffset;
        }

        if ($HourOffset <> 0) {
            $SecondsOffset = $HourOffset * 3600;
            $Timestamp += $SecondsOffset;
            $Now += $SecondsOffset;
        }

        $Html = false;
        if (strcasecmp($Format, 'html') == 0) {
            $Format = '';
            $Html = true;
        }

        if ($Format == '') {
            // If the timestamp was during the current day
            if (date('Y m d', $Timestamp) == date('Y m d', $Now)) {
                // Use the time format
                $Format = t('Date.DefaultTimeFormat', '%l:%M%p');
            } elseif (date('Y', $Timestamp) == date('Y', $Now)) {
                // If the timestamp is the same year, show the month and date
                $Format = t('Date.DefaultDayFormat', '%B %e');
            } elseif (date('Y', $Timestamp) != date('Y', $Now)) {
                // If the timestamp is not the same year, just show the year
                $Format = t('Date.DefaultYearFormat', '%B %Y');
            } else {
                // Otherwise, use the date format
                $Format = t('Date.DefaultFormat', '%B %e, %Y');
            }
        }

        $FullFormat = t('Date.DefaultDateTimeFormat', '%c');

        // Emulate %l and %e for Windows.
        if (strpos($Format, '%l') !== false) {
            $Format = str_replace('%l', ltrim(strftime('%I', $Timestamp), '0'), $Format);
        }
        if (strpos($Format, '%e') !== false) {
            $Format = str_replace('%e', ltrim(strftime('%d', $Timestamp), '0'), $Format);
        }

        $Result = strftime($Format, $Timestamp);

        if ($Html) {
            $Result = wrap($Result, 'time', array('title' => strftime($FullFormat, $Timestamp), 'datetime' => gmdate('c', $GmTimestamp)));
        }
        return $Result;
    }

    /**
     * Formats a MySql datetime or a unix timestamp for display in the system.
     *
     * @param int $Timestamp
     * @param string $Format
     * @since 2.1
     */
    public static function dateFull($Timestamp, $Format = '') {
        if ($Timestamp === null) {
            return T('Null Date', '-');
        }

        // Was a mysqldatetime passed?
        if (!is_numeric($Timestamp)) {
            $Timestamp = self::toTimestamp($Timestamp);
        }

        if (!$Timestamp) {
            $Timestamp = time(); // return '&#160;'; Apr 22, 2009 - found a bug where "Draft Saved At X" returned a nbsp here instead of the formatted current time.
        }        $GmTimestamp = $Timestamp;

        $Now = time();

        // Alter the timestamp based on the user's hour offset
        $Session = Gdn::session();
        if ($Session->UserID > 0) {
            $SecondsOffset = ($Session->User->HourOffset * 3600);
            $Timestamp += $SecondsOffset;
            $Now += $SecondsOffset;
        }

        $Html = false;
        if (strcasecmp($Format, 'html') == 0) {
            $Format = '';
            $Html = true;
        }

        $FullFormat = t('Date.DefaultDateTimeFormat', '%c');

        // Emulate %l and %e for Windows.
        if (strpos($FullFormat, '%l') !== false) {
            $FullFormat = str_replace('%l', ltrim(strftime('%I', $Timestamp), '0'), $FullFormat);
        }
        if (strpos($FullFormat, '%e') !== false) {
            $FullFormat = str_replace('%e', ltrim(strftime('%d', $Timestamp), '0'), $FullFormat);
        }

        $Result = strftime($FullFormat, $Timestamp);

        if ($Html) {
            $Result = wrap($Result, 'time', array('title' => strftime($FullFormat, $Timestamp), 'datetime' => gmdate('c', $GmTimestamp)));
        }
        return $Result;
    }

    /**
     * Format a string from of "Deleted" content (comment, message, etc).
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function deleted($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Deleted');
        } else {
            $Formatter = Gdn::factory('HtmlFormatter');
            if (is_null($Formatter)) {
                return Gdn_Format::display($Mixed);
            } else {
                return $Formatter->format(Wrap($Mixed, 'div', ' class="Deleted"'));
            }
        }
    }

    /**
     * Return the default input formatter.
     *
     * @param bool|null $is_mobile Whether or not you want the format for mobile browsers.
     * @return string
     */
    public static function defaultFormat($is_mobile = null) {
        if ($is_mobile === true || ($is_mobile === null && isMobile())) {
            return c('Garden.MobileInputFormatter', c('Garden.InputFormatter', 'Html'));
        } else {
            return c('Garden.InputFormatter', 'Html');
        }
    }

    /**
     * Takes a mixed variable, formats it for display on the screen, and returns
     * it.
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function display($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Display');
        } else {
            $Mixed = htmlspecialchars($Mixed, ENT_QUOTES, c('Garden.Charset', 'UTF-8'));
            $Mixed = str_replace(array("&quot;", "&amp;"), array('"', '&'), $Mixed);
            $Mixed = self::mentions($Mixed);
            $Mixed = self::links($Mixed);
            $Mixed = Emoji::instance()->translateToHtml($Mixed);

            return $Mixed;
        }
    }

    /**
     * Formats an email address in a non-scrapable format.
     *
     * @param string $Email
     * @return string
     */
    public static function email($Email) {
        $Max = max(3, floor(strlen($Email) / 2));
        $Chunks = str_split($Email, mt_rand(3, $Max));
        $Chunks = array_map('htmlentities', $Chunks);

        $St = mt_rand(0, 1);
        $End = count($Chunks) - mt_rand(1, 4);

        $Result = '';
        foreach ($Chunks as $i => $Chunk) {
            if ($i >= $St && $i <= $End) {
                $Result .= '<span style="display:inline;display:none">'.str_rot13($Chunk).'</span>';
            }

            $Result .= '<span style="display:none;display:inline">'.$Chunk.'</span>';
        }

        return '<span class="Email">'.$Result.'</span>';
    }

    /**
     * Takes a mixed variable, formats it for display in a form, and returns it.
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function form($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Form');
        } else {
            if (c('Garden.Format.ReplaceNewlines', true)) {
                return nl2br(htmlspecialchars($Mixed, ENT_QUOTES, c('Garden.Charset', 'UTF-8')));
            } else {
                return htmlspecialchars($Mixed, ENT_QUOTES, c('Garden.Charset', 'UTF-8'));
            }
        }
    }

    /**
     * Show times relative to now
     *
     * e.g. "4 hours ago"
     *
     * Credit goes to: http://byteinn.com/res/426/Fuzzy_Time_function/
     *
     * @param int optional $Timestamp, otherwise time() is used
     * @return string
     */
    public static function fuzzyTime($Timestamp = null, $MorePrecise = false) {
        if (is_null($Timestamp)) {
            $Timestamp = time();
        } elseif (!is_numeric($Timestamp))
            $Timestamp = self::toTimestamp($Timestamp);

        $time = $Timestamp;

        $NOW = time();
        if (!defined('ONE_MINUTE')) {
            define('ONE_MINUTE', 60);
        }
        if (!defined('ONE_HOUR')) {
            define('ONE_HOUR', 3600);
        }
        if (!defined('ONE_DAY')) {
            define('ONE_DAY', 86400);
        }
        if (!defined('ONE_WEEK')) {
            define('ONE_WEEK', ONE_DAY * 7);
        }
        if (!defined('ONE_MONTH')) {
            define('ONE_MONTH', ONE_WEEK * 4);
        }
        if (!defined('ONE_YEAR')) {
            define('ONE_YEAR', ONE_MONTH * 12);
        }

        $SecondsAgo = $NOW - $time;

        // sod = start of day :)
        $sod = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));
        $sod_now = mktime(0, 0, 0, date('m', $NOW), date('d', $NOW), date('Y', $NOW));

        // used to convert numbers to strings
        $convert = array(0 => T('a'), 1 => T('a'), 2 => T('two'), 3 => T('three'), 4 => T('four'), 5 => T('five'), 6 => T('six'), 7 => T('seven'), 8 => T('eight'), 9 => T('nine'), 10 => T('ten'), 11 => T('eleven'));

        // today
        if ($sod_now == $sod) {
            if ($time > $NOW - (ONE_MINUTE * 3)) {
                return t('just now');
            } elseif ($time > $NOW - (ONE_MINUTE * 7)) {
                return t('a few minutes ago');
            } elseif ($time > $NOW - (ONE_HOUR)) {
                if ($MorePrecise) {
                    $MinutesAgo = ceil($SecondsAgo / 60);
                    return sprintf(t('%s minutes ago'), $MinutesAgo);
                }
                return t('less than an hour ago');
            }
            return sprintf(t('today at %s'), date('g:ia', $time));
        }

        // yesterday
        if (($sod_now - $sod) <= ONE_DAY) {
            if (date('i', $time) > (ONE_MINUTE + 30)) {
                $time += ONE_HOUR / 2;
            }
            return sprintf(t('yesterday around %s'), date('ga', $time));
        }

        // within the last 5 days
        if (($sod_now - $sod) <= (ONE_DAY * 5)) {
            $str = date('l', $time);
            $hour = date('G', $time);
            if ($hour < 12) {
                $str .= t(' morning');
            } elseif ($hour < 17) {
                $str .= t(' afternoon');
            } elseif ($hour < 20) {
                $str .= t(' evening');
            } else {
                $str .= t(' night');
            }
            return $str;
        }

        // number of weeks (between 1 and 3)...
        if (($sod_now - $sod) < (ONE_WEEK * 3.5)) {
            if (($sod_now - $sod) < (ONE_WEEK * 1.5)) {
                return t('about a week ago');
            } elseif (($sod_now - $sod) < (ONE_DAY * 2.5)) {
                return t('about two weeks ago');
            } else {
                return t('about three weeks ago');
            }
        }

        // number of months (between 1 and 11)...
        if (($sod_now - $sod) < (ONE_MONTH * 11.5)) {
            for ($i = (ONE_WEEK * 3.5), $m = 0; $i < ONE_YEAR; $i += ONE_MONTH, $m++) {
                if (($sod_now - $sod) <= $i) {
                    return sprintf(t('about %s month%s ago'), $convert[$m], (($m > 1) ? 's' : ''));
                }
            }
        }

        // number of years...
        for ($i = (ONE_MONTH * 11.5), $y = 0; $i < (ONE_YEAR * 10); $i += ONE_YEAR, $y++) {
            if (($sod_now - $sod) <= $i) {
                return sprintf(t('about %s year%s ago'), $convert[$y], (($y > 1) ? 's' : ''));
            }
        }

        // more than ten years...
        return t('more than ten years ago');
    }

    /**
     * Takes a mixed variable, filters unsafe HTML and returns it.
     * Does "magic" formatting of links, mentions, link embeds, emoji, & linebreaks.
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function html($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Html');
        } else {
            if (self::isHtml($Mixed)) {
                // Purify HTML
                $Mixed = Gdn_Format::htmlFilter($Mixed);
                // Links
                $Mixed = Gdn_Format::links($Mixed);
                // Mentions & Hashes
                $Mixed = Gdn_Format::mentions($Mixed);
                // Emoji
                $Mixed = Emoji::instance()->translateToHtml($Mixed);

                // nl2br
                if (C('Garden.Format.ReplaceNewlines', true)) {
                    $Mixed = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $Mixed);
                    $Mixed = fixNl2Br($Mixed);
                }

                $Result = $Mixed;

//            $Result = $Result.
//               "<h3>Html</h3><pre>".nl2br(htmlspecialchars(str_replace("<br />", "\n", $Mixed)))."</pre>".
//               "<h3>Formatted</h3><pre>".nl2br(htmlspecialchars(str_replace("<br />", "\n", $Result)))."</pre>";
            } else {
                // The text does not contain html and does not have to be purified.
                // This is an optimization because purifying is very slow and memory intense.
                $Result = htmlspecialchars($Mixed, ENT_NOQUOTES, C('Garden.Charset', 'UTF-8'));
                $Result = Gdn_Format::mentions($Result);
                $Result = Gdn_Format::links($Result);
                $Result = Emoji::instance()->translateToHtml($Result);
                if (C('Garden.Format.ReplaceNewlines', true)) {
                    $Result = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $Result);
                    $Result = fixNl2Br($Result);
                }
            }

            return $Result;
        }
    }

    /**
     * Takes a mixed variable, filters unsafe HTML and returns it.
     * Use this instead of Gdn_Format::Html() when you do not want magic formatting.
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function htmlFilter($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'HtmlFilter');
        } else {
            if (self::isHtml($Mixed)) {
                // Purify HTML with our formatter.
                $Formatter = Gdn::factory('HtmlFormatter');
                if (is_null($Formatter)) {
                    // If there is no HtmlFormatter then make sure that script injections won't work.
                    return self::display($Mixed);
                }

                // Allow the code tag to keep all enclosed HTML encoded.
                $Mixed = preg_replace_callback('`<code([^>]*)>(.+?)<\/code>`si', function ($Matches) {
                    $Result = "<code{$Matches[1]}>".
                        htmlspecialchars($Matches[2]).
                        '</code>';
                    return $Result;
                }, $Mixed);

                // Do HTML filtering before our special changes.
                $Result = $Formatter->format($Mixed);
            } else {
                $Result = htmlspecialchars($Mixed, ENT_NOQUOTES, 'UTF-8');
            }

            return $Result;
        }
    }

    /**
     * Format a serialized string of image properties as html.
     * @param string $Body a serialized array of image properties (Image, Thumbnail, Caption)
     */
    public static function image($Body) {
        if (is_string($Body)) {
            $Image = @unserialize($Body);

            if (!$Image) {
                return Gdn_Format::html($Body);
            }
        }

        $Url = GetValue('Image', $Image);
        $Caption = Gdn_Format::plainText(val('Caption', $Image));
        return '<div class="ImageWrap">'
        .'<div class="Image">'
        .Img($Url, array('alt' => $Caption, 'title' => $Caption))
        .'</div>'
        .'<div class="Caption">'.$Caption.'</div>'
        .'</div>';
    }

    /**
     * Detect HTML for the purposes of doing advanced filtering.
     *
     * @param $Text
     * @return bool
     */
    protected static function isHtml($Text) {
        return strpos($Text, '<') !== false || (bool)preg_match('/&#?[a-z0-9]{1,10};/i', $Text);
    }

    /**
     * Check to see if a string has spoilers and replace them with an innocuous string.
     *
     * @param string $html An HTML-formatted string.
     * @param string $replaceWith The translation code to replace spoilers with.
     * @return string Returns the html with spoilers removed.
     */
    protected static function replaceSpoilers($html, $replaceWith = '(Spoiler)') {
        if (preg_match('/class="(User)?Spoiler"/i', $html)) {
            // Transform $html into a dom object and replace the spoiler block.
            if (!function_exists('str_get_html')) {
                require_once(PATH_LIBRARY.'/vendors/simplehtmldom/simple_html_dom.php');
            }
            $html = str_get_html($html);
            $html->find('.Spoiler,.UserSpoiler', 0)->outertext = t($replaceWith);
        }
        return $html;
    }

    /**
     * Format a string as plain text.
     * @param string $Body The text to format.
     * @param string $Format The current format of the text.
     * @return string
     * @since 2.1
     */
    public static function plainText($Body, $Format = 'Html') {
        $Result = Gdn_Format::to($Body, $Format);
        $Result = Gdn_Format::replaceSpoilers($Result);

        if ($Format != 'Text') {
            // Remove returns and then replace html return tags with returns.
            $Result = str_replace(array("\n", "\r"), ' ', $Result);
            $Result = preg_replace('`<br\s*/?>`', "\n", $Result);

            // Fix lists.
            $Result = str_replace('<li>', '* ', $Result);
            $Result = preg_replace('`</(?:li|ol|ul)>`', "\n", $Result);

            $Allblocks = '(?:div|table|dl|pre|blockquote|address|p|h[1-6]|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
            $Result = preg_replace('`</'.$Allblocks.'>`', "\n\n", $Result);

            // TODO: Fix hard returns within pre blocks.

            $Result = strip_tags($Result);
        }
        $Result = trim(html_entity_decode($Result, ENT_QUOTES, 'UTF-8'));
        return $Result;
    }

    /**
     * Format some text in a way suitable for passing into an rss/atom feed.
     * @since 2.1
     * @param string $Text The text to format.
     * @param string $Format The current format of the text.
     * @return string
     */
    public static function rssHtml($Text, $Format = 'Html') {
        if (!in_array($Text, array('Html', 'Raw'))) {
            $Text = Gdn_Format::to($Text, $Format);
        }

        if (function_exists('FormatRssHtmlCustom')) {
            return FormatRssHtmlCustom($Text);
        } else {
            return Gdn_Format::html($Text);
        }
    }

    public static function tagContent($Html, $Callback, $SkipAnchors = true) {
        $Regex = "`([<>])`i";
        $Parts = preg_split($Regex, $Html, null, PREG_SPLIT_DELIM_CAPTURE);

//      echo htmlspecialchars($Html);
//      echo '<pre>';
//      echo htmlspecialchars(print_r($Parts, TRUE));
//      echo '</pre>';

        $InTag = false;
        $InAnchor = false;
        $TagName = false;

        foreach ($Parts as $i => $Str) {
            switch ($Str) {
                case '<':
                    $InTag = true;
                    break;
                case '>':
                    $InTag = false;
                    break;
                case '':
                    break;
                default;
                    if ($InTag) {
                        if ($Str[0] == '/') {
                            $TagName = preg_split('`\s`', substr($Str, 1), 2);
                            $TagName = $TagName[0];

                            if ($TagName == 'a') {
                                $InAnchor = false;
                            }
                        } else {
                            $TagName = preg_split('`\s`', trim($Str), 2);
                            $TagName = $TagName[0];

                            if ($TagName == 'a') {
                                $InAnchor = true;
                            }
                        }
                    } else {
                        if (!$InAnchor || !$SkipAnchors) {
                            $Parts[$i] = call_user_func($Callback, $Str);
                        }
                    }
                    break;
            }
        }

//      return htmlspecialchars(implode('', $Parts));
        return implode($Parts);
    }

    /** Formats the anchor tags around the links in text.
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function links($Mixed) {
        if (!C('Garden.Format.Links', true)) {
            return $Mixed;
        }

        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Links');
        } else {
            if (unicodeRegexSupport()) {
                $Regex = "`(?:(</?)([!a-z]+))|(/?\s*>)|((?:https?|ftp)://[@\p{L}\p{N}\x21\x23-\x27\x2a-\x2e\x3a\x3b\/;\x3f-\x7a\x7e\x3d]+)`iu";
            } else {
                $Regex = "`(?:(</?)([!a-z]+))|(/?\s*>)|((?:https?|ftp)://[@a-z0-9\x21\x23-\x27\x2a-\x2e\x3a\x3b\/;\x3f-\x7a\x7e\x3d]+)`i";
            }

//         $Parts = preg_split($Regex, $Mixed, null, PREG_SPLIT_DELIM_CAPTURE);
//         echo '<pre>', print_r($Parts, TRUE), '</pre>';

            self::linksCallback(null);

            $Mixed = Gdn_Format::replaceButProtectCodeBlocks(
                $Regex,
                array('Gdn_Format', 'LinksCallback'),
                $Mixed,
                true
            );

            Gdn::pluginManager()->fireAs('Format')->fireEvent('Links', array(
                'Mixed' => &$Mixed
            ));

            return $Mixed;
        }
    }

    /**
     * Strips out embed/iframes we support and replaces with placeholder.
     *
     * This allows later parsing to insert a sanitized video video embed normally.
     * Necessary for backwards compatibility from when we allowed embed & object tags.
     *
     * This is not an HTML filter; it enables old YouTube videos to theoretically work,
     * it doesn't effectively block YouTube iframes or objects.
     *
     * @param mixed $Mixed
     * @return HTML string
     */
    public static function unembedContent($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'UnembedContent');
        } else {
            if (C('Garden.Format.YouTube')) {
                $Mixed = preg_replace('`<iframe.*src="((https?)://.*youtube\.com/embed/([a-z0-9_-]*))".*</iframe>`i', "\n$2://www.youtube.com/watch?v=$3\n", $Mixed);
                $Mixed = preg_replace('`<object.*value="((https?)://.*youtube\.com/v/([a-z0-9_-]*)[^"]*)".*</object>`i', "\n$2://www.youtube.com/watch?v=$3\n", $Mixed);
            }
            if (C('Garden.Format.Vimeo')) {
                $Mixed = preg_replace('`<iframe.*src="((https?)://.*vimeo\.com/video/([0-9]*))".*</iframe>`i', "\n$2://vimeo.com/$3\n", $Mixed);
                $Mixed = preg_replace('`<object.*value="((https?)://.*vimeo\.com.*clip_id=([0-9]*)[^"]*)".*</object>`i', "\n$2://vimeo.com/$3\n", $Mixed);
            }
            if (C('Garden.Format.Getty', true)) {
                $Mixed = preg_replace('`<iframe.*src="(https?:)?//embed\.gettyimages\.com/embed/([\w=?&+-]*)" width="([\d]*)" height="([\d]*)".*</iframe>`i', "\nhttp://embed.gettyimages.com/$2/$3/$4\n", $Mixed);
            }
        }

        return $Mixed;
    }

    /**
     * Transform match to clickable links or to embedded equivalent.
     *
     * URLs are typically matched against, which are then translated into a
     * clickable link or transformed into their equivalent embed, if supported.
     * There is a universal config to disable automatic embedding.
     *
     * @param array $Matches Captured and grouped matches against string.
     * @return string
     */
    protected static function linksCallback($Matches) {
        static $Width, $Height, $InTag = 0, $InAnchor = false;
        if (!isset($Width)) {
            list($Width, $Height) = Gdn_Format::getEmbedSize();
        }
        if ($Matches === null) {
            $InTag = 0;
            $InAnchor = false;
            return;
        }

        $InOut = $Matches[1];
        $Tag = strtolower($Matches[2]);
//      $End = $Matches[3];
//      $Url = $Matches[4];

        if ($InOut == '<') {
            $InTag++;
            if ($Tag == 'a') {
                $InAnchor = true;
            }
        } elseif ($InOut == '</') {
            $InTag++;
            if ($Tag == 'a') {
                $InAnchor = false;
            }
        } elseif ($Matches[3])
            $InTag--;

        if (!isset($Matches[4]) || $InTag || $InAnchor) {
            return $Matches[0];
        }
        $Url = $Matches[4];

        // Supported youtube embed urls:
        //
        // http://www.youtube.com/playlist?list=PL4CFF79651DB8159B
        // https://www.youtube.com/playlist?list=PL4CFF79651DB8159B
        // https://www.youtube.com/watch?v=sjm_gBpJ63k&list=PL4CFF79651DB8159B&index=1
        // http://youtu.be/sjm_gBpJ63k
        // https://www.youtube.com/watch?v=sjm_gBpJ63k
        // http://YOUTU.BE/sjm_gBpJ63k?list=PL4CFF79651DB8159B
        // http://youtu.be/GUbyhoU81sQ?t=1m8s
        // https://m.youtube.com/watch?v=iAEKPcz9www
        // https://youtube.com/watch?v=iAEKPcz9www
        // https://www.youtube.com/watch?v=p5kcBxL7-qI
        // https://www.youtube.com/watch?v=bG6b3V2MNxQ#t=33

        $YoutubeUrlMatch = '/https?:\/\/(?:(?:www.)|(?:m.))?(?:(?:youtube.com)|(?:youtu.be))\/(?:(?:playlist?)|(?:(?:watch\?v=)?(?P<videoId>[\w-]*)))(?:\?|\&)?(?:list=(?P<listId>[\w-]*))?(?:t=(?:(?P<minutes>\d)*m)?(?P<seconds>\d)*s)?(?:#t=(?P<start>\d*))?/i';
        $VimeoUrlMatch = 'https?://(www\.)?vimeo\.com/(?:channels/[a-z0-9]+/)?(\d+)';
        $TwitterUrlMatch = 'https?://(?:www\.)?twitter\.com/(?:#!/)?(?:[^/]+)/status(?:es)?/([\d]+)';
        $GithubCommitUrlMatch = 'https?://(?:www\.)?github\.com/([^/]+)/([^/]+)/commit/([\w]{40})';
        $VineUrlMatch = 'https?://(?:www\.)?vine.co/v/([\w]+)';
        $InstagramUrlMatch = 'https?://(?:www\.)?instagr(?:\.am|am\.com)/p/([\w-]+)';
        $PintrestUrlMatch = 'https?://(?:www\.)?pinterest.com/pin/([\d]+)';
        $GettyUrlMatch = 'http://embed.gettyimages.com/([\w=?&;+-_]*)/([\d]*)/([\d]*)';
        $TwitchUrlMatch = 'http://www.twitch.tv/([\w]+)';
        $HitboxUrlMatch = 'http://www.hitbox.tv/([\w]+)';
        $SoundcloudUrlMatch = 'https://soundcloud.com/([\w=?&;+-_]*)/([\w=?&;+-_]*)';

        // YouTube
        if ((preg_match($YoutubeUrlMatch, $Url, $Matches))
            && c('Garden.Format.YouTube', true)
            && !c('Garden.Format.DisableUrlEmbeds')
        ) {
            $videoId = val('videoId', $Matches);
            $listId = val('listId', $Matches);

            if (!empty($listId)) {
                // Playlist.
                if (empty($videoId)) {
                    // Playlist, no video.
                    $Result = <<<EOT
   <iframe width="{$Width}" height="{$Height}" src="//www.youtube.com/embed/videoseries?list={$listId}" frameborder="0" allowfullscreen></iframe>
EOT;
                } else {
                    // Video in a playlist.
                    $Result = <<<EOT
   <iframe width="{$Width}" height="{$Height}" src="https://www.youtube.com/embed/{$videoId}?list={$listId}" frameborder="0" allowfullscreen></iframe>
EOT;
                }
            } else {
                // Regular ol' youtube video embed.
                $minutes = val('minutes', $Matches);
                $seconds = val('seconds', $Matches);
                $fullUrl = $videoId.'?autoplay=1';
                if (!empty($minutes) || !empty($seconds)) {
                    $time = $minutes * 60 + $seconds;
                    $fullUrl .= '&start='.$time;
                }

                // Jump to start time.
                if ($start = val('start', $Matches)) {
                    $fullUrl .= '&start='.$start;
                    $start = '#t='.$start;
                }

                $Result = '<span class="VideoWrap">';
                $Result .= '<span class="Video YouTube" data-youtube="youtube-'.$fullUrl.'">';

                $Result .= '<span class="VideoPreview"><a href="//youtube.com/watch?v='.$videoId.$start.'">';
                $Result .= '<img src="//img.youtube.com/vi/'.$videoId.'/0.jpg" width="'.$Width.'" height="'.$Height.'" border="0" /></a></span>';
                $Result .= '<span class="VideoPlayer"></span>';
                $Result .= '</span>';

            }
            $Result .= '</span>';


            // Vimeo
        } elseif (preg_match("`{$VimeoUrlMatch}`", $Url, $Matches) && C('Garden.Format.Vimeo', true)
            && !c('Garden.Format.DisableUrlEmbeds')
        ) {
            $ID = $Matches[2];
            $Result = <<<EOT
      <iframe src="//player.vimeo.com/video/{$ID}" width="{$Width}" height="{$Height}" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
EOT;

            // Twitter
        } elseif (preg_match("`{$TwitterUrlMatch}`", $Url, $Matches) && C('Garden.Format.Twitter', true)
            && !c('Garden.Format.DisableUrlEmbeds')
        ) {
            $Result = <<<EOT
<div class="twitter-card" data-tweeturl="{$Matches[0]}" data-tweetid="{$Matches[1]}"><a href="{$Matches[0]}" class="tweet-url" rel="nofollow" target="_blank">{$Matches[0]}</a></div>
EOT;

            // Github
// @tim : 2013-08-22
// Experiment on hold
//
//      } elseif (preg_match("`{$GithubCommitUrlMatch}`", $Url, $Matches) && C('Garden.Format.Github', true)) {
//         $Result = <<<EOT
//<div class="github-commit" data-commiturl="{$Matches[0]}" data-commituser="{$Matches[1]}" data-commitrepo="{$Matches[2]}" data-commithash="{$Matches[3]}"><a href="{$Matches[0]}" class="commit-url" rel="nofollow" target="_blank">{$Matches[0]}</a></div>
//EOT;

            // Vine
        } elseif (preg_match("`{$VineUrlMatch}`i", $Url, $Matches) && C('Garden.Format.Vine', true)
            && !c('Garden.Format.DisableUrlEmbeds')
        ) {
            $Result = <<<EOT
<div class="VideoWrap">
   <iframe class="vine-embed" src="//vine.co/v/{$Matches[1]}/embed/simple" width="320" height="320" frameborder="0"></iframe><script async src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script>
</div>
EOT;

            // Instagram
        } elseif (preg_match("`{$InstagramUrlMatch}`i", $Url, $Matches) && C('Garden.Format.Instagram', true)
            && !c('Garden.Format.DisableUrlEmbeds')
        ) {
            $Result = <<<EOT
<div class="VideoWrap">
   <iframe src="//instagram.com/p/{$Matches[1]}/embed/" width="412" height="510" frameborder="0" scrolling="no" allowtransparency="true"></iframe>
</div>
EOT;

            // Pintrest
        } elseif (preg_match("`({$PintrestUrlMatch})`", $Url, $Matches) && C('Garden.Format.Pintrest', true)
            && !c('Garden.Format.DisableUrlEmbeds')
        ) {
            $Result = <<<EOT
<a data-pin-do="embedPin" href="//pinterest.com/pin/{$Matches[2]}/" class="pintrest-pin" rel="nofollow" target="_blank"></a>
EOT;

            // Getty
        } elseif (preg_match("`({$GettyUrlMatch})`i", $Url, $Matches) && C('Garden.Format.Getty', true)
            && !c('Garden.Format.DisableUrlEmbeds')
        ) {
            $Result = <<<EOT
<iframe src="//embed.gettyimages.com/embed/{$Matches[2]}" width="{$Matches[3]}" height="{$Matches[4]}" frameborder="0" scrolling="no"></iframe>
EOT;

            // Twitch
        } elseif (preg_match("`({$TwitchUrlMatch})`i", $Url, $Matches) && C('Garden.Format.Twitch', true)
            && !c('Garden.Format.DisableUrlEmbeds')
        ) {
            $Result = <<<EOT
<object type="application/x-shockwave-flash" height="378" width="620" id="live_embed_player_flash" data="http://www.twitch.tv/widgets/live_embed_player.swf?channel={$Matches[2]}" bgcolor="#000000"><param name="allowFullScreen" value="true" /><param name="allowScriptAccess" value="always" /><param name="allowNetworking" value="all" /><param name="movie" value="http://www.twitch.tv/widgets/live_embed_player.swf" /><param name="flashvars" value="hostname=www.twitch.tv&channel={$Matches[2]}&auto_play=false&start_volume=25" /></object><a href="http://www.twitch.tv/{$Matches[2]}" style="padding:2px 0px 4px; display:block; width:345px; font-weight:normal; font-size:10px;text-decoration:underline; text-align:center;">Watch live video from {$Matches[2]} on www.twitch.tv</a>
EOT;

            //Hitbox
        } elseif (preg_match("`({$HitboxUrlMatch})`i", $Url, $Matches) && C('Garden.Format.Hitbox', true)
            && !c('Garden.Format.DisableUrlEmbeds')
        ) {
            $Result = <<<EOT
	 <iframe width="640" height="360" src="http://hitbox.tv/#!/embed/{$Matches[2]}" frameborder="0" allowfullscreen></iframe>
<a href="http://www.hitbox.tv/{$Matches[2]}" style="padding:2px 0px 4px; display:block; width:345px; font-weight:normal; font-size:10px;text-decoration:underline; text-align:center;">Watch live video from {$Matches[2]} on www.hitbox.tv</a>
EOT;

            // Soundcloud
        } elseif (preg_match("`({$SoundcloudUrlMatch})`i", $Url, $Matches) && C('Garden.Format.Soundcloud', true)
            && !c('Garden.Format.DisableUrlEmbeds')
        ) {
            $Result = <<<EOT
<iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//soundcloud.com/{$Matches[2]}/{$Matches[3]}&amp;color=ff5500&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false"></iframe>
EOT;

            // Unformatted links
        } elseif (!self::$FormatLinks) {
            $Result = $Url;

            // Formatted links
        } else {
            // Strip punctuation off of the end of the url.
            $Punc = '';
            if (preg_match('`^(.+)([.?,;:])$`', $Url, $Matches)) {
                $Url = $Matches[1];
                $Punc = $Matches[2];
            }

            // Get human-readable text from url.
            $Text = $Url;
            if (strpos($Text, '%') !== false) {
                $Text = rawurldecode($Text);
                $Text = htmlspecialchars($Text, ENT_QUOTES, c('Garden.Charset', 'UTF-8'));
            }

            $nofollow = (self::$DisplayNoFollow) ? ' rel="nofollow"' : '';

            $Result = <<<EOT
<a href="$Url" target="_blank"$nofollow>$Text</a>$Punc
EOT;
        }
        return $Result;
    }

    /** Formats BBCode list items.
     *
     * @param array $Matches
     * @return string
     */
    protected static function listCallback($Matches) {
        $Content = explode("[*]", $Matches[1]);
        $Result = '';
        foreach ($Content as $Item) {
            if (trim($Item) != '') {
                $Result .= '<li>'.$Item.'</li>';
            }
        }
        $Result = '<ul>'.$Result.'</ul>';
        return $Result;
    }

    /**
     * Returns embedded video width and height, based on configuration.
     *
     * @return array array(Width, Height)
     */
    public static function getEmbedSize() {
        $Sizes = array(
            'tiny' => array(400, 225),
            'small' => array(560, 340),
            'normal' => array(640, 385),
            'big' => array(853, 505),
            'huge' => array(1280, 745));
        $Size = Gdn::config('Garden.Format.EmbedSize', 'normal');

        // We allow custom sizes <Width>x<Height>
        if (!isset($Sizes[$Size])) {
            if (strpos($Size, 'x')) {
                list($Width, $Height) = explode('x', $Size);
                $Width = intval($Width);
                $Height = intval($Height);

                // Dimensions are too small, or 0
                if ($Width < 30 or $Height < 30) {
                    $Size = 'normal';
                }
            } else {
                $Size = 'normal';
            }
        }
        if (isset($Sizes[$Size])) {
            list($Width, $Height) = $Sizes[$Size];
        }
        return array($Width, $Height);
    }

    /**
     * Format a string using Markdown syntax. Also purifies the output html.
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function markdown($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Markdown');
        } else {
            $Formatter = Gdn::factory('HtmlFormatter');
            if (is_null($Formatter)) {
                return Gdn_Format::display($Mixed);
            } else {
                require_once(PATH_LIBRARY.'/vendors/markdown/Michelf/MarkdownExtra.inc.php');
                $Mixed = \Michelf\MarkdownExtra::defaultTransform($Mixed);
                $Mixed = $Formatter->format($Mixed);
                $Mixed = Gdn_Format::links($Mixed);
                $Mixed = Gdn_Format::mentions($Mixed);
                $Mixed = Emoji::instance()->translateToHtml($Mixed);
                return $Mixed;
            }
        }
    }

    public static function mentions($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Mentions');
        } else {
            // Check for a custom formatter.
            $Formatter = Gdn::factory('MentionsFormatter');
            if (is_object($Formatter)) {
                return $Formatter->formatMentions($Mixed);
            }

            // Handle @mentions.
            if (C('Garden.Format.Mentions')) {
                $urlFormat = str_replace('{name}', '$2', self::$MentionsUrlFormat);

                // Unicode includes Numbers, Letters, Marks, & Connector punctuation.
                $Pattern = (unicodeRegexSupport()) ? '[\pN\pL\pM\pPc]' : '\w';
                $Mixed = Gdn_Format::replaceButProtectCodeBlocks(
                    '/(^|[\s,\.>\(])@('.$Pattern.'{1,64})\b/i', //{3,20}
                    '\1'.anchor('@$2', $urlFormat),
                    $Mixed
                );
            }

            // Handle #hashtag searches
            if (C('Garden.Format.Hashtags')) {
                $Mixed = Gdn_Format::replaceButProtectCodeBlocks(
                    '/(^|[\s,\.>])\#([\w\-]+)(?=[\s,\.!?<]|$)/i',
                    '\1'.anchor('#\2', '/search?Search=%23\2&Mode=like').'\3',
                    $Mixed
                );
            }

            // Handle "/me does x" action statements
            if (C('Garden.Format.MeActions')) {
                $Mixed = Gdn_Format::replaceButProtectCodeBlocks(
                    '/(^|[\n])(\/me)(\s[^(\n)]+)/i',
                    '\1'.wrap(wrap('\2', 'span', array('class' => 'MeActionName')).'\3', 'span', array('class' => 'AuthorAction')),
                    $Mixed
                );
            }

            return $Mixed;
        }
    }

    /**
     * Do a preg_replace, but don't affect things inside <code> tags.
     * The three parameters are identical to the ones you'd pass
     * preg_replace.
     *
     * @param mixed $Search The value being searched for, just like in
     *              preg_replace or preg_replace_callback.
     * @param mixed $Replace The replacement value, just like in
     *              preg_replace or preg_replace_callback.
     * @param mixed $Subject The string being searched.
     * @param bool $IsCallback If true, do preg_replace_callback. Do
     *             preg_replace otherwise.
     * @return string
     */
    public static function replaceButProtectCodeBlocks($Search, $Replace, $Subject, $IsCallback = false) {
        // Take the code blocks out, replace with a hash of the string, and
        // keep track of what substring got replaced with what hash.
        $CodeBlockContents = array();
        $CodeBlockHashes = array();
        $Subject = preg_replace_callback(
            '/<code>.*?<\/code>/i',
            function ($Matches) use (&$CodeBlockContents, &$CodeBlockHashes) {
                // Surrounded by whitespace to try to prevent the characters
                // from being picked up by $Pattern.
                $ReplacementString = ' '.sha1($Matches[0]).' ';
                $CodeBlockContents[] = $Matches[0];
                $CodeBlockHashes[] = $ReplacementString;
                return $ReplacementString;
            },
            $Subject
        );

        // Do the requested replacement.
        if ($IsCallback) {
            $Subject = preg_replace_callback($Search, $Replace, $Subject);
        } else {
            $Subject = preg_replace($Search, $Replace, $Subject);
        }

        // Put back the code blocks.
        $Subject = str_replace($CodeBlockHashes, $CodeBlockContents, $Subject);

        return $Subject;
    }

    /** Return the input without any operations performed at all.
     *  This format should only be used when administrators have access.
     *
     * @param string|object|array $Mixed The data to format.
     * @return string
     */
    public static function raw($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Raw');
        } else {
            // Deprecate raw formatting. It's too dangeous.
            return self::wysiwyg($Mixed);
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
    public static function objectAsArray($Object) {
        if (!is_object($Object)) {
            return $Object;
        }

        $Return = array();
        foreach (get_object_vars($Object) as $Property => $Value) {
            $Return[$Property] = $Value;
        }
        return $Return;
    }

    /**
     * Formats seconds in a human-readable way (ie. 45 seconds, 15 minutes, 2 hours, 4 days, 2 months, etc).
     */
    public static function seconds($Seconds) {
        if (!is_numeric($Seconds)) {
            $Seconds = abs(time() - self::toTimestamp($Seconds));
        }

        $Minutes = round($Seconds / 60);
        $Hours = round($Seconds / 3600);
        $Days = round($Seconds / 86400);
        $Weeks = round($Seconds / 604800);
        $Months = round($Seconds / 2629743.83);
        $Years = round($Seconds / 31556926);

        if ($Seconds < 60) {
            return sprintf(Plural($Seconds, '%s second', '%s seconds'), $Seconds);
        } elseif ($Minutes < 60)
            return sprintf(Plural($Minutes, '%s minute', '%s minutes'), $Minutes);
        elseif ($Hours < 24)
            return sprintf(Plural($Hours, '%s hour', '%s hours'), $Hours);
        elseif ($Days < 7)
            return sprintf(Plural($Days, '%s day', '%s days'), $Days);
        elseif ($Weeks < 4)
            return sprintf(Plural($Weeks, '%s week', '%s weeks'), $Weeks);
        elseif ($Months < 12)
            return sprintf(Plural($Months, '%s month', '%s months'), $Months);
        else {
            return sprintf(Plural($Years, '%s year', '%s years'), $Years);
        }
    }

    /**
     * Takes any variable and serializes it.
     *
     * @param mixed $Mixed An object, array, or string to be serialized.
     * @return string The serialized version of the string.
     */
    public static function serialize($Mixed) {
        if (is_array($Mixed) || is_object($Mixed)
            || (is_string($Mixed) && (substr_compare('a:', $Mixed, 0, 2) === 0 || substr_compare('O:', $Mixed, 0, 2) === 0
                    || substr_compare('arr:', $Mixed, 0, 4) === 0 || substr_compare('obj:', $Mixed, 0, 4) === 0))
        ) {
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
    public static function text($Mixed, $AddBreaks = true) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Text');
        } else {
            $Charset = c('Garden.Charset', 'UTF-8');
            $Result = htmlspecialchars(strip_tags(preg_replace('`<br\s?/?>`', "\n", html_entity_decode($Mixed, ENT_QUOTES, $Charset))), ENT_NOQUOTES, $Charset);
            if ($AddBreaks && c('Garden.Format.ReplaceNewlines', true)) {
                $Result = nl2br(trim($Result));
            }
            return $Result;
        }
    }

    /**
     *
     *
     * @param string $Str
     * @return string
     * @since 2.1
     */
    public static function textEx($Str) {
        $Str = self::text($Str);
        $Str = self::links($Str);
        $Str = self::mentions($Str);
        $Str = Emoji::instance()->translateToHtml($Str);
        return $Str;
    }

    /**
     * Takes a mixed variable, formats it in the specified format type, and
     * returns it.
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @param string $FormatMethod The method with which the variable should be formatted.
     * @return mixed
     */
    public static function to($Mixed, $FormatMethod) {
        // Process $Mixed based on its type.
        if (is_string($Mixed)) {
            if (in_array(strtolower($FormatMethod), self::$SanitizedFormats) && method_exists('Gdn_Format', $FormatMethod)) {
                $Mixed = self::$FormatMethod($Mixed);
            } elseif (function_exists('format'.$FormatMethod)) {
                $FormatMethod = 'format'.$FormatMethod;
                $Mixed = $FormatMethod($Mixed);
            } elseif ($Formatter = Gdn::factory($FormatMethod.'Formatter')) {
                $Mixed = $Formatter->format($Mixed);
            } else {
                $Mixed = Gdn_Format::text($Mixed);
            }
        } elseif (is_array($Mixed)) {
            foreach ($Mixed as $Key => $Val) {
                $Mixed[$Key] = self::to($Val, $FormatMethod);
            }
        } elseif (is_object($Mixed)) {
            foreach (get_object_vars($Mixed) as $Prop => $Val) {
                $Mixed->$Prop = self::to($Val, $FormatMethod);
            }
        }
        return $Mixed;
    }

    /** Format a timestamp or the current time to go into the database.
     *
     * @param int $Timestamp
     * @return string The formatted date.
     */
    public static function toDate($Timestamp = '') {
        if ($Timestamp == '') {
            $Timestamp = time();
        } elseif (!is_numeric($Timestamp))
            $Timestamp = self::toTimestamp($Timestamp);

        return date('Y-m-d', $Timestamp);
    }

    /**
     * Format a timestamp or the current time to go into the database.
     *
     * @param int $Timestamp
     * @return string The formatted date and time.
     */
    public static function toDateTime($Timestamp = '') {
        if ($Timestamp == '') {
            $Timestamp = time();
        }
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
    public static function toTimestamp($DateTime = '') {
        if ($DateTime === '0000-00-00 00:00:00') {
            return false;
        } elseif (($TestTime = strtotime($DateTime)) !== false) {
            return $TestTime;
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s{1}(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $DateTime, $Matches)) {
            $Year = $Matches[1];
            $Month = $Matches[2];
            $Day = $Matches[3];
            $Hour = arrayValue(4, $Matches, 0);
            $Minute = arrayValue(5, $Matches, 0);
            $Second = arrayValue(6, $Matches, 0);
            return mktime($Hour, $Minute, $Second, $Month, $Day, $Year);
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $DateTime, $Matches)) {
            $Year = $Matches[1];
            $Month = $Matches[2];
            $Day = $Matches[3];
            return mktime(0, 0, 0, $Month, $Day, $Year);
            // } elseif ($DateTime == '') {
            //    return time();
        } else {
            return false;
        }
    }

    /**
     * Formats a timestamp to the current user's timezone.
     *
     * @param int $Timestamp The timestamp in gmt.
     * @return int The timestamp according to the user's timezone.
     */
    public static function toTimezone($Timestamp) {
        static $GuestHourOffset;
        $Now = time();

        // Alter the timestamp based on the user's hour offset
        $Session = Gdn::session();
        $HourOffset = 0;

        if ($Session->UserID > 0) {
            $HourOffset = $Session->User->HourOffset;
        } elseif (class_exists('DateTimeZone')) {
            if (!isset($GuestHourOffset)) {
                $GuestTimeZone = c('Garden.GuestTimeZone');
                if ($GuestTimeZone) {
                    try {
                        $TimeZone = new DateTimeZone($GuestTimeZone);
                        $Offset = $TimeZone->getOffset(new DateTime('now', new DateTimeZone('UTC')));
                        $GuestHourOffset = floor($Offset / 3600);
                    } catch (Exception $Ex) {
                        $GuestHourOffset = 0;
                        logException($Ex);
                    }
                }
            }
            $HourOffset = $GuestHourOffset;
        }

        if ($HourOffset <> 0) {
            $SecondsOffset = $HourOffset * 3600;
            $Timestamp += $SecondsOffset;
            $Now += $SecondsOffset;
        }

        return $Timestamp;
    }

    public static function timespan($timespan) {
        //$timespan -= 86400 * ($days = (int) floor($timespan / 86400));
        $timespan -= 3600 * ($hours = (int)floor($timespan / 3600));
        $timespan -= 60 * ($minutes = (int)floor($timespan / 60));
        $seconds = $timespan;

        $Result = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        return $Result;
    }

    protected static $_UrlTranslations = array('–' => '-', '—' => '-', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Ä' => 'A', 'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae', 'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D', 'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E', 'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G', 'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I', 'İ' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'K', 'Ľ' => 'K', 'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ö' => 'Oe', 'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O', 'Œ' => 'OE', 'Ŕ' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S', 'Ş' => 'S', 'Ŝ' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T', 'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ū' => 'U', 'Ü' => 'Ue', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U', 'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'Ž' => 'Z', 'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'ä' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a', 'æ' => 'ae', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ƒ' => 'f', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'ĩ' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij', 'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l', 'ŀ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n', 'ŋ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ö' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o', 'œ' => 'oe', 'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ū' => 'u', 'ü' => 'ue', 'ů' => 'u', 'ű' => 'u', 'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'ß' => 'ss', 'ſ' => 'ss', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'ș' => 's', 'ț' => 't', 'Ț' => 'T', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya');

    /**
     * Creates URL codes containing only lowercase Roman letters, digits, and hyphens.
     *
     * @param mixed $Mixed An object, array, or string to be formatted.
     * @return string
     */
    public static function url($Mixed) {
        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Url');
        }

        // Preliminary decoding
        $Mixed = strip_tags(html_entity_decode($Mixed, ENT_COMPAT, 'UTF-8'));
        $Mixed = strtr($Mixed, self::$_UrlTranslations);
        $Mixed = preg_replace('`[\']`', '', $Mixed);

        // Convert punctuation, symbols, and spaces to hyphens
        if (unicodeRegexSupport()) {
            $Mixed = preg_replace('`[\pP\pS\s]`u', '-', $Mixed);
        } else {
            $Mixed = preg_replace('`[\W_]`', '-', $Mixed);
        }

        // Lowercase, no trailing or repeat hyphens
        $Mixed = preg_replace('`-+`', '-', strtolower($Mixed));
        $Mixed = trim($Mixed, '-');

        return rawurlencode($Mixed);
    }

    /**
     * Takes a serialized variable and unserializes it back into it's
     * original state.
     *
     * @param string $SerializedString A json or php serialized string to be unserialized.
     * @return mixed
     */
    public static function unserialize($SerializedString) {
        $Result = $SerializedString;

        if (is_string($SerializedString)) {
            if (substr_compare('a:', $SerializedString, 0, 2) === 0 || substr_compare('O:', $SerializedString, 0, 2) === 0) {
                $Result = unserialize($SerializedString);
            } elseif (substr_compare('obj:', $SerializedString, 0, 4) === 0)
                $Result = json_decode(substr($SerializedString, 4), false);
            elseif (substr_compare('arr:', $SerializedString, 0, 4) === 0)
                $Result = json_decode(substr($SerializedString, 4), true);
        }
        return $Result;
    }

    /**
     *
     *
     * @param $PlaceholderString
     * @param $ReplaceWith
     * @return mixed
     */
    public static function vanillaSprintf($PlaceholderString, $ReplaceWith) {
        // Set replacement array inside callback
        Gdn_Format::vanillaSprintfCallback(null, $ReplaceWith);

        $FinalString = preg_replace_callback('/({([a-z0-9_:]+)})/i', array('Gdn_Format', 'VanillaSprintfCallback'), $PlaceholderString);

        // Cleanup replacement list
        Gdn_Format::vanillaSprintfCallback(null, array());

        return $FinalString;
    }

    /**
     *
     *
     * @param $Match
     * @param bool $InternalReplacementList
     * @return mixed
     */
    protected static function vanillaSprintfCallback($Match, $InternalReplacementList = false) {
        static $InternalReplacement = array();

        if (is_array($InternalReplacementList)) {
            $InternalReplacement = $InternalReplacementList;
        } else {
            $MatchStr = $Match[2];
            $Format = (count($SplitMatch = explode(':', $MatchStr)) > 1) ? $SplitMatch[1] : false;

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
     *
     *
     * @param $Mixed
     * @return mixed|string
     */
    public static function wysiwyg($Mixed) {
        static $CustomFormatter;
        if (!isset($CustomFormatter)) {
            $CustomFormatter = c('Garden.Format.WysiwygFunction', false);
        }

        if (!is_string($Mixed)) {
            return self::to($Mixed, 'Wysiwyg');
        } elseif (is_callable($CustomFormatter)) {
            return $CustomFormatter($Mixed);
        } else {
            // The text contains html and must be purified.
            $Formatter = Gdn::factory('HtmlFormatter');
            if (is_null($Formatter)) {
                // If there is no HtmlFormatter then make sure that script injections won't work.
                return self::display($Mixed);
            }

            // HTML filter first
            $Mixed = $Formatter->format($Mixed);
            // Links
            $Mixed = Gdn_Format::links($Mixed);
            // Mentions & Hashes
            $Mixed = Gdn_Format::mentions($Mixed);
            $Mixed = Emoji::instance()->translateToHtml($Mixed);


            return $Mixed;
        }
    }
}
