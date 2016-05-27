<?php
/**
 * UI functions
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Write alternating strings on each call.
 *
 * Useful for adding different classes to alternating lines in a list
 * or table to enhance their readability.
 *
 * @param string $odd The text for the first and every further "odd" call.
 * @param string $even The text for the second and every further "even" call.
 * @param string $attributeName The html attribute name that should embrace $even/$odd output.
 * @return string
 */
if (!function_exists('alternate')) {
    function alternate($odd = '', $even = 'Alt', $attributeName = 'class') {
        static $b = false;
        if ($b = !$b) {
            $value = $odd;
        } else {
            $value = $even;
        }

        if ($value != '' && $attributeName != '') {
            return ' '.$attributeName.'="'.$value.'"';
        } else {
            return $value;
        }
    }
}

/**
 * English "plural" formatting for numbers that can get really big.
 */
if (!function_exists('bigPlural')) {
    function bigPlural($Number, $Singular, $Plural = false) {
        if (!$Plural) {
            $Plural = $Singular.'s';
        }
        $Title = sprintf(T($Number == 1 ? $Singular : $Plural), number_format($Number));

        return '<span title="'.$Title.'" class="Number">'.Gdn_Format::bigNumber($Number).'</span>';
    }
}

/**
 * Outputs standardized HTML for a badge.
 * A badge generally designates a count, and displays with a contrasting background.
 *
 * @param string|int $badge Info to put into a badge, usually a number.
 * @return string Badge HTML string.
 */
if (!function_exists('badge')) {
    function badge($badge) {
        return ' <span class="badge">'.$badge.'</span> ';
    }
}

/**
 * Outputs standardized HTML for a popin badge.
 * A popin contains data that is injected after the page loads.
 * A badge generally designates a count, and displays with a contrasting background.
 *
 * @param string $rel Endpoint for a popin.
 * @return string Popin HTML string.
 */
if (!function_exists('popin')) {
    function popin($rel) {
        return ' <span class="badge Popin js-popin" rel="'.$rel.'"></span> ';
    }
}

/**
 * Outputs standardized HTML for an icon.
 * Uses the same css class naming conventions as font-vanillicon.
 *
 * @param string $icon Name of the icon you want to use, excluding the 'icon-' prefix.
 * @return string Icon HTML string.
 */
if (!function_exists('icon')) {
    function icon($icon) {
        $icon = strtolower($icon);
        return ' <span class="icon icon-'.$icon.'"></span> ';
    }
}

if (!function_exists('bullet')) {
    /**
     * Return a bullet character in html.
     *
     * @param string $Pad A string used to pad either side of the bullet.
     * @return string
     *
     * @changes
     *    2.2 Added the $Pad parameter.
     */
    function bullet($Pad = '') {
        //·
        return $Pad.'<span class="Bullet">&middot;</span>'.$Pad;
    }
}

if (!function_exists('buttonDropDown')) {
    /**
     * Write a button drop down control.
     *
     * @param array $Links An array of arrays with the following keys:
     *  - Text: The text of the link.
     *  - Url: The url of the link.
     * @param string|array $CssClass The css class of the link. This can be a two-item array where the second element will be added to the buttons.
     * @param string $Label The text of the button.
     * @since 2.1
     */
    function buttonDropDown($Links, $CssClass = 'Button', $Label = false) {
        if (!is_array($Links) || count($Links) < 1) {
            return;
        }

        $ButtonClass = '';
        if (is_array($CssClass)) {
            list($CssClass, $ButtonClass) = $CssClass;
        }

        if (count($Links) < 2) {
            $Link = array_pop($Links);


            if (strpos(GetValue('CssClass', $Link, ''), 'Popup') !== false) {
                $CssClass .= ' Popup';
            }

            echo Anchor($Link['Text'], $Link['Url'], GetValue('ButtonCssClass', $Link, $CssClass));
        } else {
            // NavButton or Button?
            $ButtonClass = ConcatSep(' ', $ButtonClass, strpos($CssClass, 'NavButton') !== false ? 'NavButton' : 'Button');
            if (strpos($CssClass, 'Primary') !== false) {
                $ButtonClass .= ' Primary';
            }
            // Strip "Button" or "NavButton" off the group class.
            echo '<div class="ButtonGroup'.str_replace(array('NavButton', 'Button'), array('', ''), $CssClass).'">';
//            echo Anchor($Text, $Url, $ButtonClass);

            echo '<ul class="Dropdown MenuItems">';
            foreach ($Links as $Link) {
                echo wrap(Anchor($Link['Text'], $Link['Url'], val('CssClass', $Link, '')), 'li');
            }
            echo '</ul>';

            echo anchor($Label.' '.sprite('SpDropdownHandle'), '#', $ButtonClass.' Handle');
            echo '</div>';
        }
    }
}

if (!function_exists('buttonGroup')) {
    /**
     * Write a button group control.
     *
     * @param array $Links An array of arrays with the following keys:
     *  - Text: The text of the link.
     *  - Url: The url of the link.
     * @param string|array $CssClass The css class of the link. This can be a two-item array where the second element will be added to the buttons.
     * @param string|false $Default The url of the default link.
     * @since 2.1
     */
    function buttonGroup($Links, $CssClass = 'Button', $Default = false) {
        if (!is_array($Links) || count($Links) < 1) {
            return;
        }

        $Text = $Links[0]['Text'];
        $Url = $Links[0]['Url'];

        $ButtonClass = '';
        if (is_array($CssClass)) {
            list($CssClass, $ButtonClass) = $CssClass;
        }

        if ($Default && count($Links) > 1) {
            if (is_array($Default)) {
                $DefaultText = $Default['Text'];
                $Default = $Default['Url'];
            }

            // Find the default button.
            $Default = ltrim($Default, '/');
            foreach ($Links as $Link) {
                if (StringBeginsWith(ltrim($Link['Url'], '/'), $Default)) {
                    $Text = $Link['Text'];
                    $Url = $Link['Url'];
                    break;
                }
            }

            if (isset($DefaultText)) {
                $Text = $DefaultText;
            }
        }

        if (count($Links) < 2) {
            echo anchor($Text, $Url, $CssClass);
        } else {
            // NavButton or Button?
            $ButtonClass = concatSep(' ', $ButtonClass, strpos($CssClass, 'NavButton') !== false ? 'NavButton' : 'Button');
            if (strpos($CssClass, 'Primary') !== false) {
                $ButtonClass .= ' Primary';
            }
            // Strip "Button" or "NavButton" off the group class.
            echo '<div class="ButtonGroup Multi '.str_replace(array('NavButton', 'Button'), array('', ''), $CssClass).'">';
            echo anchor($Text, $Url, $ButtonClass);

            echo '<ul class="Dropdown MenuItems">';
            foreach ($Links as $Link) {
                echo wrap(anchor($Link['Text'], $Link['Url'], val('CssClass', $Link, '')), 'li');
            }
            echo '</ul>';
            echo anchor(sprite('SpDropdownHandle', 'Sprite', t('Expand for more options.')), '#', $ButtonClass.' Handle');

            echo '</div>';
        }
    }
}

if (!function_exists('category')) {
    /**
     * Get the current category on the page.
     *
     * @param int $Depth The level you want to look at.
     * @param array $Category
     */
    function category($Depth = null, $Category = null) {
        if (!$Category) {
            $Category = Gdn::controller()->data('Category');
        } elseif (!is_array($Category)) {
            $Category = CategoryModel::categories($Category);
        }

        if (!$Category) {
            $Category = Gdn::controller()->data('CategoryID');
            if ($Category) {
                $Category = CategoryModel::categories($Category);
            }
        }
        if (!$Category) {
            return null;
        }

        $Category = (array)$Category;

        if ($Depth !== null) {
            // Get the category at the correct level.
            while ($Category['Depth'] > $Depth) {
                $Category = CategoryModel::categories($Category['ParentCategoryID']);
                if (!$Category) {
                    return null;
                }
            }
        }

        return $Category;
    }
}

if (!function_exists('categoryUrl')) {
    /**
     * Return a url for a category. This function is in here and not functions.general so that plugins can override.
     *
     * @param string|array $Category
     * @param string|int $Page The page number.
     * @param bool $WithDomain Whether to add the domain to the URL
     * @return string The url to a category.
     */
    function categoryUrl($Category, $Page = '', $WithDomain = true) {
        if (is_string($Category)) {
            $Category = CategoryModel::categories($Category);
        }
        $Category = (array)$Category;

        $Result = '/categories/'.rawurlencode($Category['UrlCode']);
        if ($Page && $Page > 1) {
            $Result .= '/p'.$Page;
        }
        return Url($Result, $WithDomain);
    }
}

if (!function_exists('condense')) {
    function condense($Html) {
        $Html = preg_replace('`(?:<br\s*/?>\s*)+`', "<br />", $Html);
        $Html = preg_replace('`/>\s*<br />\s*<img`', "/> <img", $Html);
        return $Html;
    }
}

if (!function_exists('countString')) {
    function countString($Number, $Url = '', $Options = array()) {
        if (!$Number && $Number !== null) {
            return '';
        }

        if (is_array($Options)) {
            $Options = array_change_key_case($Options);
            $CssClass = val('cssclass', $Options, '');
        } else {
            $CssClass = $Options;
        }

        if ($Number) {
            $CssClass = trim($CssClass.' Count', ' ');
            return "<span class=\"$CssClass\">$Number</span>";
        } elseif ($Number === null && $Url) {
            $CssClass = trim($CssClass.' Popin TinyProgress', ' ');
            $Url = htmlspecialchars($Url);
            return "<span class=\"$CssClass\" rel=\"$Url\"></span>";
        } else {
            return '';
        }
    }
}

if (!function_exists('cssClass')) {
    /**
     * Add CSS class names to a row depending on other elements/values in that row.
     *
     * Used by category, discussion, and comment lists.
     *
     * @param array|object $Row
     * @return string The CSS classes to be inserted into the row.
     */
    function cssClass($Row, $InList = true) {
        static $Alt = false;
        $Row = (array)$Row;
        $CssClass = 'Item';
        $Session = Gdn::session();

        // Alt rows
        if ($Alt) {
            $CssClass .= ' Alt';
        }
        $Alt = !$Alt;

        // Category list classes
        if (array_key_exists('UrlCode', $Row)) {
            $CssClass .= ' Category-'.Gdn_Format::alphaNumeric($Row['UrlCode']);
        }
        if (GetValue('CssClass', $Row)) {
            $CssClass .= ' Item-'.$Row['CssClass'];
        }

        if (array_key_exists('Depth', $Row)) {
            $CssClass .= " Depth{$Row['Depth']} Depth-{$Row['Depth']}";
        }

        if (array_key_exists('Archive', $Row)) {
            $CssClass .= ' Archived';
        }

        // Discussion list classes.
        if ($InList) {
            $CssClass .= val('Bookmarked', $Row) == '1' ? ' Bookmarked' : '';

            $Announce = val('Announce', $Row);
            if ($Announce == 2) {
                $CssClass .= ' Announcement Announcement-Category';
            } elseif ($Announce) {
                $CssClass .= ' Announcement Announcement-Everywhere';
            }

            $CssClass .= val('Closed', $Row) == '1' ? ' Closed' : '';
            $CssClass .= val('InsertUserID', $Row) == $Session->UserID ? ' Mine' : '';
            $CssClass .= val('Participated', $Row) == '1' ? ' Participated' : '';
            if (array_key_exists('CountUnreadComments', $Row) && $Session->isValid()) {
                $CountUnreadComments = $Row['CountUnreadComments'];
                if ($CountUnreadComments === true) {
                    $CssClass .= ' New';
                } elseif ($CountUnreadComments == 0) {
                    $CssClass .= ' Read';
                } else {
                    $CssClass .= ' Unread';
                }
            } elseif (($IsRead = val('Read', $Row, null)) !== null) {
                // Category list
                $CssClass .= $IsRead ? ' Read' : ' Unread';
            }
        }

        // Comment list classes
        if (array_key_exists('CommentID', $Row)) {
            $CssClass .= ' ItemComment';
        } elseif (array_key_exists('DiscussionID', $Row)) {
            $CssClass .= ' ItemDiscussion';
        }

        if (function_exists('IsMeAction')) {
            $CssClass .= isMeAction($Row) ? ' MeAction' : '';
        }

        if ($_CssClss = val('_CssClass', $Row)) {
            $CssClass .= ' '.$_CssClss;
        }

        // Insert User classes.
        if ($UserID = val('InsertUserID', $Row)) {
            $User = Gdn::userModel()->getID($UserID);
            if ($_CssClss = val('_CssClass', $User)) {
                $CssClass .= ' '.$_CssClss;
            }
        }

        return trim($CssClass);
    }
}

if (!function_exists('dateUpdated')) {
    function dateUpdated($Row, $Wrap = null) {
        $Result = '';
        $DateUpdated = val('DateUpdated', $Row);
        $UpdateUserID = val('UpdateUserID', $Row);

        if ($DateUpdated) {
            $UpdateUser = Gdn::userModel()->getID($UpdateUserID);
            if ($UpdateUser) {
                $Title = sprintf(T('Edited %s by %s.'), Gdn_Format::dateFull($DateUpdated), val('Name', $UpdateUser));
            } else {
                $Title = sprintf(T('Edited %s.'), Gdn_Format::dateFull($DateUpdated));
            }

            $Result = ' <span title="'.htmlspecialchars($Title).'" class="DateUpdated">'.
                sprintf(T('edited %s'), Gdn_Format::date($DateUpdated)).
                '</span> ';

            if ($Wrap) {
                $Result = $Wrap[0].$Result.$Wrap[1];
            }
        }

        return $Result;
    }
}

/**
 * Writes an anchor tag
 */
if (!function_exists('anchor')) {
    /**
     * Builds and returns an anchor tag.
     */
    function anchor($Text, $Destination = '', $CssClass = '', $Attributes = array(), $ForceAnchor = false) {
        if (!is_array($CssClass) && $CssClass != '') {
            $CssClass = array('class' => $CssClass);
        }

        if ($Destination == '' && $ForceAnchor === false) {
            return $Text;
        }

        if (!is_array($Attributes)) {
            $Attributes = array();
        }

        $SSL = null;
        if (isset($Attributes['SSL'])) {
            $SSL = $Attributes['SSL'];
            unset($Attributes['SSL']);
        }

        $WithDomain = false;
        if (isset($Attributes['WithDomain'])) {
            $WithDomain = $Attributes['WithDomain'];
            unset($Attributes['WithDomain']);
        }

        $Prefix = substr($Destination, 0, 7);
        if (!in_array($Prefix, array('https:/', 'http://', 'mailto:')) && ($Destination != '' || $ForceAnchor === false)) {
            $Destination = Gdn::request()->url($Destination, $WithDomain, $SSL);
        }

        return '<a href="'.htmlspecialchars($Destination, ENT_COMPAT, 'UTF-8').'"'.attribute($CssClass).attribute($Attributes).'>'.$Text.'</a>';
    }
}

if (!function_exists('commentUrl')) {
    /**
     * Return a URL for a comment. This function is in here and not functions.general so that plugins can override.
     *
     * @param object $Comment
     * @return string
     */
    function commentUrl($Comment, $WithDomain = true) {
        $Comment = (object)$Comment;
        $Result = "/discussion/comment/{$Comment->CommentID}#Comment_{$Comment->CommentID}";
        return url($Result, $WithDomain);
    }
}

if (!function_exists('discussionUrl')) {
    /**
     * Return a URL for a discussion. This function is in here and not functions.general so that plugins can override.
     *
     * @param object $Discussion
     * @return string
     */
    function discussionUrl($Discussion, $Page = '', $WithDomain = true) {
        $Discussion = (object)$Discussion;
        $Name = Gdn_Format::url($Discussion->Name);
        if (empty($Name)) {
            $Name = 'x';
        }
        $Result = '/discussion/'.$Discussion->DiscussionID.'/'.$Name;
        if ($Page) {
            if ($Page > 1 || Gdn::session()->UserID) {
                $Result .= '/p'.$Page;
            }
        }
        return Url($Result, $WithDomain);
    }
}

if (!function_exists('exportCSV')) {
    /**
     * Create a CSV given a list of column names & rows.
     *
     * @param array $columnNames
     * @param array $data
     */
    function exportCSV($columnNames, $data = array()) {
        $output = fopen("php://output",'w');
        header("Content-Type:application/csv");
        header("Content-Disposition:attachment;filename=profiles_export.csv");
        fputcsv($output, $columnNames);
        foreach($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }
}

if (!function_exists('fixnl2br')) {
    /**
     * Removes the break above and below tags that have a natural margin.
     *
     * @param string $Text The text to fix.
     * @return string
     * @since 2.1
     */
    function fixnl2br($Text) {
        $allblocks = '(?:table|dl|ul|ol|pre|blockquote|address|p|h[1-6]|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary|li|tbody|tr|td|th|thead|tbody|tfoot|col|colgroup|caption|dt|dd)';
        $Text = preg_replace('!(?:<br\s*/>){1,2}\s*(<'.$allblocks.'[^>]*>)!', "\n$1", $Text);
        $Text = preg_replace('!(</'.$allblocks.'[^>]*>)\s*(?:<br\s*/>){1,2}!', "$1\n", $Text);
        return $Text;
    }
}

if (!function_exists('formatIP')) {
    /**
     * Format an IP address for display.
     *
     * @param string $IP An IP address to be formatted.
     * @param bool $html Format as HTML.
     * @return string Returns the formatted IP address.
     */
    function formatIP($IP, $html = true) {
        return $html ? htmlspecialchars($IP) : $IP;
    }
}

if (!function_exists('formatPossessive')) {
    /**
     * Format a word using English "possessive" formatting.
     *
     * This can be overridden in language definition files like:
     *
     * ```
     * /applications/garden/locale/en-US.php.
     * ```
     */
    function formatPossessive($Word) {
        if (function_exists('formatPossessiveCustom')) {
            return formatPossesiveCustom($Word);
        }

        return substr($Word, -1) == 's' ? $Word."'" : $Word."'s";
    }
}

if (!function_exists('formatUsername')) {
    function formatUsername($User, $Format, $ViewingUserID = false) {
        if ($ViewingUserID === false) {
            $ViewingUserID = Gdn::session()->UserID;
        }
        $UserID = val('UserID', $User);
        $Name = val('Name', $User);
        $Gender = strtolower(val('Gender', $User));

        $UCFirst = substr($Format, 0, 1) == strtoupper(substr($Format, 0, 1));


        switch (strtolower($Format)) {
            case 'you':
                if ($ViewingUserID == $UserID) {
                    return t("Format $Format", $Format);
                }
                return $Name;
            case 'his':
            case 'her':
            case 'your':
                if ($ViewingUserID == $UserID) {
                    return t("Format Your", 'Your');
                } else {
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
                    if ($UCFirst) {
                        $Format = ucfirst($Format);
                    }
                    return t("Format $Format", $Format);
                }
                break;
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
        if ($userID != Gdn::session()->UserID) {
            return false;
        }

        $result = checkPermission('Garden.Profiles.Edit') && c('Garden.UserAccount.AllowEdit');

        $result &= (
            c('Garden.Profile.Titles') ||
            c('Garden.Profile.Locations', false) ||
            c('Garden.Registration.Method') != 'Connect'
        );

        return $result;
    }
}

if (!function_exists('hoverHelp')) {
    /**
     * Add span with hover text to a string.
     *
     * @param $String
     * @param $Help
     * @return string
     */
    function hoverHelp($String, $Help) {
        return wrap($String.wrap($Help, 'span', array('class' => 'Help')), 'span', array('class' => 'HoverHelp'));
    }
}

if (!function_exists('img')) {
    /**
     * Returns an img tag.
     *
     * @param $Image
     * @param string $Attributes
     * @param bool|false $WithDomain
     * @return string
     */
    function img($Image, $Attributes = '', $WithDomain = false) {
        if ($Attributes != '') {
            $Attributes = attribute($Attributes);
        }

        if (!IsUrl($Image)) {
            $Image = smartAsset($Image, $WithDomain);
        }

        return '<img src="'.htmlspecialchars($Image, ENT_QUOTES).'"'.$Attributes.' />';
    }
}

if (!function_exists('inCategory')) {
    /**
     * Return whether or not the page is in a given category.
     *
     * @param string $Category The url code of the category.
     * @return boolean
     * @since 2.1
     */
    function inCategory($Category) {
        $Breadcrumbs = (array)Gdn::controller()->data('Breadcrumbs', array());

        foreach ($Breadcrumbs as $Breadcrumb) {
            if (isset($Breadcrumb['CategoryID']) && strcasecmp($Breadcrumb['UrlCode'], $Category) == 0) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('inSection')) {
    /**
     * Returns whether or not the page is in one of the given section(s).
     *
     * @param string|array $Section
     * @return bool
     * @since 2.1
     */
    function inSection($Section) {
        return Gdn_Theme::inSection($Section);
    }
}

if (!function_exists('ipAnchor')) {
    /**
     * Returns an IP address with a link to the user search.
     */
    function ipAnchor($IP, $CssClass = '') {
        if ($IP) {
            return anchor(formatIP($IP), '/user/browse?keywords='.urlencode($IP), $CssClass);
        } else {
            return $IP;
        }
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

if (!function_exists('plural')) {
    /**
     * Return the plural version of a word depending on a number.
     *
     * This can be overridden in language definition files like:
     *
     * ```
     * /applications/garden/locale/en-US/definitions.php.
     * ```
     */
    function plural($Number, $Singular, $Plural, $FormattedNumber = false) {
        // Make sure to fix comma-formatted numbers
        $WorkingNumber = str_replace(',', '', $Number);
        if ($FormattedNumber === false) {
            $FormattedNumber = $Number;
        }

        $Format = t(abs($WorkingNumber) == 1 ? $Singular : $Plural);

        return sprintf($Format, $FormattedNumber);
    }
}

if (!function_exists('pluralTranslate')) {
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
    function pluralTranslate($Number, $Singular, $Plural, $SingularDefault = false, $PluralDefault = false) {
        if ($Number == 1) {
            return t($Singular, $SingularDefault);
        } else {
            return t($Plural, $PluralDefault);
        }
    }
}

if (!function_exists('searchExcerpt')) {
    function searchExcerpt($PlainText, $SearchTerms, $Length = 200, $Mark = true) {
        if (empty($SearchTerms)) {
            return substrWord($PlainText, 0, $Length);
        }

        if (is_string($SearchTerms)) {
            $SearchTerms = preg_split('`[\s|-]+`i', $SearchTerms);
        }

        // Split the string into lines.
        $Lines = explode("\n", $PlainText);
        // Find the first line that includes a search term.
        foreach ($Lines as $i => &$Line) {
            $Line = trim($Line);
            if (!$Line) {
                continue;
            }

            foreach ($SearchTerms as $Term) {
                if (!$Term) {
                    continue;
                }

                if (($Pos = mb_stripos($Line, $Term)) !== false) {
                    $Line = substrWord($Line, $Term, $Length);

                    if ($Mark) {
                        return markString($SearchTerms, $Line);
                    } else {
                        return $Line;
                    }
                }
            }
        }

        // No line was found so return the first non-blank line.
        foreach ($Lines as $Line) {
            if ($Line) {
                return sliceString($Line, $Length);
            }
        }
        return '';
    }

    function substrWord($str, $start, $length) {
        // If we are offsetting on a word then find it.
        if (is_string($start)) {
            $pos = mb_stripos($str, $start);
            if ($pos !== false && (($pos + strlen($start)) <= $length)) {
                $start = 0;
            } else {
                $start = $pos - $length / 4;
            }
        }

        // Find the word break from the offset.
        if ($start > 0) {
            $pos = mb_strpos($str, ' ', $start);
            if ($pos !== false) {
                $start = $pos;
            }
        } elseif ($start < 0) {
            $pos = mb_strrpos($str, ' ', $start);
            if ($pos !== false) {
                $start = $pos;
            } else {
                $start = 0;
            }
        }

        $len = strlen($str);

        if ($start + $length > $len) {
            if ($length - $start <= 0) {
                $start = 0;
            } else {
                // Zoom the offset back a bit.
                $pos = mb_strpos($str, ' ', max(0, $len - $length));
                if ($pos === false) {
                    $pos = $len - $length;
                }
            }
        }

        $result = mb_substr($str, $start, $length);
        return $result;
    }
}

if (!function_exists('userAnchor')) {
    /**
     * Take a user object, and writes out an anchor of the user's name to the user's profile.
     */
    function userAnchor($User, $CssClass = null, $Options = null) {
        static $NameUnique = null;
        if ($NameUnique === null) {
            $NameUnique = c('Garden.Registration.NameUnique');
        }

        if (is_array($CssClass)) {
            $Options = $CssClass;
            $CssClass = null;
        } elseif (is_string($Options)) {
            $Options = array('Px' => $Options);
        }

        $Px = val('Px', $Options, '');

        $Name = val($Px.'Name', $User, t('Unknown'));
//        $UserID = GetValue($Px.'UserID', $User, 0);
        $Text = val('Text', $Options, htmlspecialchars($Name)); // Allow anchor text to be overridden.

        $Attributes = array(
            'class' => $CssClass,
            'rel' => val('Rel', $Options)
        );
        if (isset($Options['title'])) {
            $Attributes['title'] = $Options['title'];
        }
        $UserUrl = userUrl($User, $Px);
        return '<a href="'.htmlspecialchars(url($UserUrl)).'"'.attribute($Attributes).'>'.$Text.'</a>';
    }
}

if (!function_exists('userBuilder')) {
    /**
     * Take an object & prefix value and convert it to a user object that can be used by UserAnchor() && UserPhoto().
     *
     * The object must have the following fields: UserID, Name, Photo.
     *
     * @param stdClass|array $row The row with the user extract.
     * @param string|array $userPrefix Either a single string user prefix or an array of prefix searches.
     * @return stdClass Returns an object containing the user.
     */
    function userBuilder($row, $userPrefix = '') {
        $row = (object)$row;
        $user = new stdClass();

        if (is_array($userPrefix)) {
            // Look for the first user that has the desired prefix.
            foreach ($userPrefix as $px) {
                if (property_exists($row, $px.'Name')) {
                    $userPrefix = $px;
                    break;
                }
            }

            if (is_array($userPrefix)) {
                $userPrefix = '';
            }
        }

        $userID = $userPrefix.'UserID';
        $name = $userPrefix.'Name';
        $photo = $userPrefix.'Photo';
        $gender = $userPrefix.'Gender';


        $user->UserID = $row->$userID;
        $user->Name = $row->$name;
        $user->Photo = property_exists($row, $photo) ? $row->$photo : '';
        $user->Email = val($userPrefix.'Email', $row, null);
        $user->Gender = property_exists($row, $gender) ? $row->$gender : null;

        return $user;
    }
}

if (!function_exists('userPhoto')) {
    /**
     * Takes a user object, and writes out an anchor of the user's icon to the user's profile.
     *
     * @param object|array $User A user object or array.
     * @param array $Options
     */
    function userPhoto($User, $Options = array()) {
        if (is_string($Options)) {
            $Options = array('LinkClass' => $Options);
        }

        if ($Px = val('Px', $Options)) {
            $User = userBuilder($User, $Px);
        } else {
            $User = (object)$User;
        }

        $LinkClass = concatSep(' ', val('LinkClass', $Options, ''), 'PhotoWrap');
        $ImgClass = val('ImageClass', $Options, 'ProfilePhoto');

        $Size = val('Size', $Options);
        if ($Size) {
            $LinkClass .= " PhotoWrap{$Size}";
            $ImgClass .= " {$ImgClass}{$Size}";
        } else {
            $ImgClass .= " {$ImgClass}Medium"; // backwards compat
        }

        $FullUser = Gdn::userModel()->getID(val('UserID', $User), DATASET_TYPE_ARRAY);
        $UserCssClass = val('_CssClass', $FullUser);
        if ($UserCssClass) {
            $LinkClass .= ' '.$UserCssClass;
        }

        $LinkClass = $LinkClass == '' ? '' : ' class="'.$LinkClass.'"';

        $Photo = val('Photo', $User, val('PhotoUrl', $User));
        $Name = val('Name', $User);
        $Title = htmlspecialchars(val('Title', $Options, $Name));
        $Href = url(userUrl($User));

        if ($FullUser && $FullUser['Banned']) {
            $Photo = c('Garden.BannedPhoto', 'https://c3409409.ssl.cf0.rackcdn.com/images/banned_large.png');
            $Title .= ' ('.t('Banned').')';
        }

        if ($Photo) {
            if (!isUrl($Photo)) {
                $PhotoUrl = Gdn_Upload::url(changeBasename($Photo, 'n%s'));
            } else {
                $PhotoUrl = $Photo;
            }
        } else {
            $PhotoUrl = UserModel::getDefaultAvatarUrl($User, 'thumbnail');
        }

        return '<a title="'.$Title.'" href="'.$Href.'"'.$LinkClass.'>'
        .img($PhotoUrl, array('alt' => $Name, 'class' => $ImgClass))
        .'</a>';
    }
}

if (!function_exists('userPhotoUrl')) {
    /**
     * Take a user object an return the URL to their photo.
     *
     * @param object|array $User
     */
    function userPhotoUrl($User) {
        $FullUser = Gdn::userModel()->getID(val('UserID', $User), DATASET_TYPE_ARRAY);
        $Photo = val('Photo', $User);
        if ($FullUser && $FullUser['Banned']) {
            $Photo = 'https://c3409409.ssl.cf0.rackcdn.com/images/banned_100.png';
        }

        if ($Photo) {
            if (!isUrl($Photo)) {
                $PhotoUrl = Gdn_Upload::url(changeBasename($Photo, 'n%s'));
            } else {
                $PhotoUrl = $Photo;
            }
            return $PhotoUrl;
        }
        return UserModel::getDefaultAvatarUrl($User);
    }
}

if (!function_exists('userUrl')) {
    /**
     * Return the URL for a user.
     *
     * @param array|object $User The user to get the url for.
     * @param string $Px The prefix to apply before fieldnames.
     * @param string $Method Optional. ProfileController method to target.
     * @param array? $Get An optional query string array to add to the URL.
     * @return string The url suitable to be passed into the Url() function.
     * @since 2.1
     */
    function userUrl($User, $Px = '', $Method = '', $Get = null) {
        static $NameUnique = null;
        if ($NameUnique === null) {
            $NameUnique = c('Garden.Registration.NameUnique');
        }

        $UserName = val($Px.'Name', $User);
        $UserName = preg_replace('/([\?&]+)/', '', $UserName);

        $Result = '/profile/'.
            ($Method ? trim($Method, '/').'/' : '').
            ($NameUnique ? '' : val($Px.'UserID', $User, 0).'/').
            rawurlencode($UserName);

        if (!empty($Get)) {
            $Result .= '?'.http_build_query($Get);
        }

        return $Result;
    }
}


/**
 * Wrap the provided string in the specified tag. ie. Wrap('This is bold!', 'b');
 */
if (!function_exists('wrap')) {
    function wrap($String, $Tag = 'span', $Attributes = '') {
        if ($Tag == '') {
            return $String;
        }

        if (is_array($Attributes)) {
            $Attributes = Attribute($Attributes);
        }

        // Strip the first part of the tag as the closing tag - this allows us to
        // easily throw 'span class="something"' into the $Tag field.
        $Space = strpos($Tag, ' ');
        $ClosingTag = $Space ? substr($Tag, 0, $Space) : $Tag;
        return '<'.$Tag.$Attributes.'>'.$String.'</'.$ClosingTag.'>';
    }
}

if (!function_exists('wrapIf')) {
    /**
     * Wrap the provided string if it isn't empty.
     *
     * @param string $String
     * @param string $Tag
     * @param array $Attributes
     * @return string
     * @since 2.1
     */
    function wrapIf($String, $Tag = 'span', $Attributes = '') {
        if (empty($String)) {
            return '';
        } else {
            return wrap($String, $Tag, $Attributes);
        }
    }
}

/**
 * Wrap the provided string in the specified tag. ie. Wrap('This is bold!', 'b');
 */
if (!function_exists('discussionLink')) {
    function discussionLink($Discussion, $Extended = true) {
        $DiscussionID = val('DiscussionID', $Discussion);
        $DiscussionName = val('Name', $Discussion);
        $Parts = array(
            'discussion',
            $DiscussionID,
            Gdn_Format::url($DiscussionName)
        );
        if ($Extended) {
            $Parts[] = ($Discussion->CountCommentWatch > 0) ? '#Item_'.$Discussion->CountCommentWatch : '';
        }
        return url(implode('/', $Parts), true);
    }
}

if (!function_exists('registerUrl')) {
    function registerUrl($Target = '', $force = false) {
        $registrationMethod = strtolower(c('Garden.Registration.Method'));

        if ($registrationMethod === 'closed') {
            return '';
        }

        // Check to see if there is even a sign in button.
        if (!$force && $registrationMethod === 'connect') {
            $defaultProvider = Gdn_AuthenticationProviderModel::getDefault();
            if ($defaultProvider && !val('RegisterUrl', $defaultProvider)) {
                return '';
            }
        }

        return '/entry/register'.($Target ? '?Target='.urlencode($Target) : '');
    }
}

if (!function_exists('signInUrl')) {
    function signInUrl($target = '', $force = false) {
        // Check to see if there is even a sign in button.
        if (!$force && strcasecmp(c('Garden.Registration.Method'), 'Connect') !== 0) {
            $defaultProvider = Gdn_AuthenticationProviderModel::getDefault();
            if ($defaultProvider && !val('SignInUrl', $defaultProvider)) {
                return '';
            }
        }

        return '/entry/signin'.($target ? '?Target='.urlencode($target) : '');
    }
}

if (!function_exists('signOutUrl')) {
    function signOutUrl($Target = '') {
        if ($Target) {
            // Strip out the SSO from the target so that the user isn't signed back in again.
            $Parts = explode('?', $Target, 2);
            if (isset($Parts[1])) {
                parse_str($Parts[1], $Query);
                unset($Query['sso']);
                $Target = $Parts[0].'?'.http_build_query($Query);
            }
        }

        return '/entry/signout?TransientKey='.urlencode(Gdn::session()->transientKey()).($Target ? '&Target='.urlencode($Target) : '');
    }
}

if (!function_exists('socialSignInButton')) {
    function socialSignInButton($Name, $Url, $Type = 'button', $Attributes = array()) {
        TouchValue('title', $Attributes, sprintf(T('Sign In with %s'), $Name));
        $Title = $Attributes['title'];
        $Class = val('class', $Attributes, '');
        unset($Attributes['class']);

        switch ($Type) {
            case 'icon':
                $Result = Anchor(
                    '<span class="Icon"></span>',
                    $Url,
                    'SocialIcon SocialIcon-'.$Name.' '.$Class,
                    $Attributes
                );
                break;
            case 'button':
            default:
                $Result = Anchor(
                    '<span class="Icon"></span><span class="Text">'.$Title.'</span>',
                    $Url,
                    'SocialIcon SocialIcon-'.$Name.' HasText '.$Class,
                    $Attributes
                );
                break;
        }

        return $Result;
    }
}

if (!function_exists('sprite')) {
    function sprite($Name, $Type = 'Sprite', $Text = false) {
        $Sprite = '<span class="'.$Type.' '.$Name.'"></span>';
        if ($Text) {
            $Sprite .= '<span class="sr-only">'.$Text.'</span>';
        }

        return $Sprite;
    }
}

if (!function_exists('writeReactions')) {
    function writeReactions($Row) {
        $Attributes = GetValue('Attributes', $Row);
        if (is_string($Attributes)) {
            $Attributes = dbdecode($Attributes);
            SetValue('Attributes', $Row, $Attributes);
        }

        Gdn::controller()->EventArguments['ReactionTypes'] = array();

        if ($ID = GetValue('CommentID', $Row)) {
            $RecordType = 'comment';
        } elseif ($ID = GetValue('ActivityID', $Row)) {
            $RecordType = 'activity';
        } else {
            $RecordType = 'discussion';
            $ID = GetValue('DiscussionID', $Row);
        }
        Gdn::controller()->EventArguments['RecordType'] = $RecordType;
        Gdn::controller()->EventArguments['RecordID'] = $ID;

        echo '<div class="Reactions">';
        Gdn_Theme::bulletRow();

        // Write the flags.
        static $Flags = null;
        if ($Flags === null) {
            Gdn::controller()->EventArguments['Flags'] = &$Flags;
            Gdn::controller()->fireEvent('Flags');
        }

        // Allow addons to work with flags
        Gdn::controller()->EventArguments['Flags'] = &$Flags;
        Gdn::controller()->fireEvent('BeforeFlag');

        if (!empty($Flags) && is_array($Flags)) {
            echo Gdn_Theme::bulletItem('Flags');

            echo ' <span class="FlagMenu ToggleFlyout">';
            // Write the handle.
            echo anchor(sprite('ReactFlag', 'ReactSprite').' '.wrap(t('Flag'), 'span', array('class' => 'ReactLabel')), '', 'Hijack ReactButton-Flag FlyoutButton', array('title' => t('Flag')), true);
            echo sprite('SpFlyoutHandle', 'Arrow');
            echo '<ul class="Flyout MenuItems Flags" style="display: none;">';
            foreach ($Flags as $Flag) {
                if (is_callable($Flag)) {
                    echo '<li>'.call_user_func($Flag, $Row, $RecordType, $ID).'</li>';
                } else {
                    echo '<li>'.reactionButton($Row, $Flag['UrlCode']).'</li>';
                }
            }
            Gdn::controller()->fireEvent('AfterFlagOptions');
            echo '</ul>';
            echo '</span> ';
        }

        Gdn::controller()->fireEvent('AfterFlag');

        Gdn::controller()->fireEvent('AfterReactions');
        echo '</div>';
        Gdn::controller()->fireEvent('Replies');
    }
}
