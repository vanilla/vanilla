<?php
/**
 * UI functions
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

if (!function_exists('alternate')) {
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

if (!function_exists('dashboardSymbol')) {
    /**
     * Render SVG icons in the dashboard. Icon must exist in applications/dashboard/views/symbols.php
     *
     * @param string $name The name of the icon to render. Must be set in applications/dashboard/views/symbols.php.
     * @param string $class If set, overrides any 'class' attribute in the $attr param.
     * @param array $attr The dashboard symbol attributes. The default 'alt' attribute will be set to $name.
     * @return string An HTML-formatted string to render svg icons.
     */
    function dashboardSymbol($name, $class = '', array $attr = []) {
        if (empty($attr['alt'])) {
            $attr['alt'] = $name;
        }

        if (!empty($class)) {
            $attr['class'] = $class.' ';
        } else {
            $attr['class'] = isset($attr['class']) ? $attr['class'].' ' : '';
        }

        $baseCssClass = 'icon icon-svg-'.$name;
        $attr['class'] .= $baseCssClass;

        return '<svg '.attribute($attr).' viewBox="0 0 17 17"><use xlink:href="#'.$name.'" /></svg>';
    }
}

if (!function_exists('bigPlural')) {
    /**
     * English "plural" formatting for numbers that can get really big.
     *
     * @param $number
     * @param $singular
     * @param bool $plural
     * @return string
     */
    function bigPlural($number, $singular, $plural = false) {
        if (!$plural) {
            $plural = $singular.'s';
        }
        $title = sprintf(t($number == 1 ? $singular : $plural), number_format($number));

        return '<span title="'.$title.'" class="Number">'.Gdn_Format::bigNumber($number).'</span>';
    }
}

if (!function_exists('helpAsset')) {
    /**
     * Formats a help element and adds it to the help asset.
     *
     * @param $title
     * @param $description
     */
    function helpAsset($title, $description) {
        Gdn_Theme::assetBegin('Help');
        echo '<aside role="note" class="help">';
        echo wrap($title, 'h2', ['class' => 'help-title']);
        echo wrap($description, 'div', ['class' => 'help-description']);
        echo '</aside>';
        Gdn_Theme::assetEnd();
    }
}

if (!function_exists('heading')) {
    /**
     * Formats a h1 header block for the dashboard. Only to be used once on a page as the h1 header.
     * Handles url-ifying. Adds an optional button or return link.
     *
     * @param string $title The page title.
     * @param string|array $buttonText The text appearing on the button or an array of button definitions.
     * @param string $buttonUrl The url for the button.
     * @param string|array $buttonAttributes Can be string CSS class or an array of attributes. CSS class defaults to `btn btn-primary`.
     * @param string $returnUrl The url for the return chrevron button.
     * @return string The structured heading string.
     */
    function heading($title, $buttonText = '', $buttonUrl = '', $buttonAttributes = [], $returnUrl = '') {
        if (is_array($buttonText)) {
            $buttons = $buttonText;
        } elseif (!empty($buttonText)) {
            $buttons = [[
                'text' => $buttonText,
                'url' => $buttonUrl,
                'attributes' => $buttonAttributes
            ]];
        } else {
            $buttons = [];
        }

        $buttonsString = '';
        foreach ($buttons as $button) {
            $buttonText = $button['text'] ?? '';
            $buttonUrl = $button['url'] ?? '';
            $buttonAttributes = $button['attributes'] ?? [];
            if (is_string($buttonAttributes)) {
                $buttonAttributes = ['class' => $buttonAttributes];
            }

            if ($buttonText !== '') {
                if (val('class', $buttonAttributes, false) === false) {
                    $buttonAttributes['class'] = 'btn btn-primary';
                }
            }

            if ($buttonUrl === '') {
                $buttonsString .= ' <button type="button" '.attribute($buttonAttributes).'>'.$buttonText.'</button>';
            } else {
                $buttonsString .= ' <a '.attribute($buttonAttributes).' href="'.url($buttonUrl).'">'.$buttonText.'</a>';
            }
        }
        $buttonsString = '<div class="btn-container">'.$buttonsString.'</div>';

        $title = '<h1>'.$title.'</h1>';

        if ($returnUrl !== '') {
            $title = '<div class="title-block">
                <a class="btn btn-icon btn-return" aria-label="Return" href="'.url($returnUrl).'">'.
                    dashboardSymbol('chevron-left').'
                </a>
                '.$title.'
            </div>';
        }

        return '<header class="header-block">'.$title.$buttonsString.'</header>';
    }
}


if (!function_exists('subheading')) {
    /**
     * Renders a h2 subheading for the dashboard.
     *
     * @param string $title The subheading title.
     * @param string $description The optional description for the subheading.
     * @return string The structured subheading string.
     */
    function subheading($title, $description = '') {
        if ($description === '') {
            return '<h2 class="subheading">'.$title.'</h2>';
        } else {
            return '<header class="subheading-block">
                <h2 class="subheading-title">'.$title.'</h2>
                <div class="subheading-description">'.$description.'</div>
            </header>';
        }
    }
}

if (!function_exists('badge')) {
    /**
     * Outputs standardized HTML for a badge.
     *
     * A badge generally designates a count, and displays with a contrasting background.
     *
     * @param string|int $badge Info to put into a badge, usually a number.
     * @return string Badge HTML string.
     */
    function badge($badge) {
        return ' <span class="badge">'.$badge.'</span> ';
    }
}

if (!function_exists('popin')) {
    /**
     * Outputs standardized HTML for a popin badge.
     *
     * A popin contains data that is injected after the page loads.
     * A badge generally designates a count, and displays with a contrasting background.
     *
     * @param string $rel Endpoint for a popin.
     * @return string Popin HTML string.
     */
    function popin($rel) {
        return ' <span class="badge js-popin" rel="'.$rel.'"></span> ';
    }
}

if (!function_exists('icon')) {
    /**
     * Outputs standardized HTML for an icon.
     *
     * Uses the same css class naming conventions as font-vanillicon.
     *
     * @param string $icon Name of the icon you want to use, excluding the 'icon-' prefix.
     * @return string Icon HTML string.
     */
    function icon($icon) {
        if (substr(trim($icon), 0, 1) === '<') {
            return $icon;
        } else {
        $icon = strtolower($icon);
        return ' <span class="icon icon-'.$icon.'"></span> ';
}
    }
}

if (!function_exists('bullet')) {
    /**
     * Return a bullet character in html.
     *
     * @param string $pad A string used to pad either side of the bullet.
     * @return string
     *
     * @changes
     *    2.2 Added the $pad parameter.
     */
    function bullet($pad = '') {
        //·
        return $pad.'<span class="Bullet">&middot;</span>'.$pad;
    }
}

if (!function_exists('buttonDropDown')) {
    /**
     * Write a button drop down control.
     *
     * @param array $links An array of arrays with the following keys:
     *  - Text: The text of the link.
     *  - Url: The url of the link.
     * @param string|array $cssClass The css class of the link. This can be a two-item array where the second element will be added to the buttons.
     * @param string $label The text of the button.
     * @since 2.1
     */
    function buttonDropDown($links, $cssClass = 'Button', $label = false) {
        if (!is_array($links) || count($links) < 1) {
            return;
        }

        $buttonClass = '';
        if (is_array($cssClass)) {
            list($cssClass, $buttonClass) = $cssClass;
        }

        if (count($links) < 2) {
            $link = array_pop($links);

            if (strpos(val('CssClass', $link, ''), 'Popup') !== false) {
                $cssClass .= ' Popup';
            }

            echo anchor($link['Text'], $link['Url'], val('ButtonCssClass', $link, $cssClass));
        } else {
            // NavButton or Button?
            $buttonClass = concatSep(' ', $buttonClass, strpos($cssClass, 'NavButton') !== false ? 'NavButton' : 'Button');
            if (strpos($cssClass, 'Primary') !== false) {
                $buttonClass .= ' Primary';
            }

            // Strip "Button" or "NavButton" off the group class.
            echo '<div class="ButtonGroup'.str_replace(['NavButton', 'Button'], ['', ''], $cssClass).'">';

            echo '<ul class="Dropdown MenuItems">';
            foreach ($links as $link) {
                echo wrap(anchor($link['Text'], $link['Url'], val('CssClass', $link, '')), 'li');
            }
            echo '</ul>';

            echo anchor($label.' '.sprite('SpDropdownHandle'), '#', $buttonClass.' Handle');
            echo '</div>';
        }
    }
}

if (!function_exists('buttonGroup')) {
    /**
     * Write a button group control.
     *
     * @param array $links An array of arrays with the following keys:
     *  - Text: The text of the link.
     *  - Url: The url of the link.
     * @param string|array $cssClass The css class of the link. This can be a two-item array where the second element will be added to the buttons.
     * @param string|false $default The url of the default link.
     * @since 2.1
     */
    function buttonGroup($links, $cssClass = 'Button', $default = false) {
        if (!is_array($links) || count($links) < 1) {
            return;
        }

        $text = $links[0]['Text'];
        $url = $links[0]['Url'];

        $buttonClass = '';
        if (is_array($cssClass)) {
            list($cssClass, $buttonClass) = $cssClass;
        }

        if ($default && count($links) > 1) {
            if (is_array($default)) {
                $defaultText = $default['Text'];
                $default = $default['Url'];
            }

            // Find the default button.
            $default = ltrim($default, '/');
            foreach ($links as $link) {
                if (stringBeginsWith(ltrim($link['Url'], '/'), $default)) {
                    $text = $link['Text'];
                    $url = $link['Url'];
                    break;
                }
            }

            if (isset($defaultText)) {
                $text = $defaultText;
            }
        }

        if (count($links) < 2) {
            echo anchor($text, $url, $cssClass);
        } else {
            // NavButton or Button?
            $buttonClass = concatSep(' ', $buttonClass, strpos($cssClass, 'NavButton') !== false ? 'NavButton' : 'Button');
            if (strpos($cssClass, 'Primary') !== false) {
                $buttonClass .= ' Primary';
            }
            // Strip "Button" or "NavButton" off the group class.
            echo '<div class="ButtonGroup Multi '.str_replace(['NavButton', 'Button'], ['', ''], $cssClass).'">';
            echo anchor($text, $url, $buttonClass);

            echo '<ul class="Dropdown MenuItems">';
            foreach ($links as $link) {
                echo wrap(anchor($link['Text'], $link['Url'], val('CssClass', $link, '')), 'li');
            }
            echo '</ul>';
            echo anchor(sprite('SpDropdownHandle', 'Sprite', t('Expand for more options.')), '#', $buttonClass.' Handle');

            echo '</div>';
        }
    }
}

if (!function_exists('category')) {
    /**
     * Get the current category on the page.
     *
     * @param int $depth The level you want to look at.
     * @param array $category
     * @return array
     */
    function category($depth = null, $category = null) {
        if (!$category) {
            $category = Gdn::controller()->data('Category');
        } elseif (!is_array($category)) {
            $category = CategoryModel::categories($category);
        }

        if (!$category) {
            $category = Gdn::controller()->data('CategoryID');
            if ($category) {
                $category = CategoryModel::categories($category);
            }
        }
        if (!$category) {
            return null;
        }

        $category = (array)$category;

        if ($depth !== null) {
            // Get the category at the correct level.
            while ($category['Depth'] > $depth) {
                $category = CategoryModel::categories($category['ParentCategoryID']);
                if (!$category) {
                    return null;
                }
            }
        }

        return $category;
    }
}

if (!function_exists('categoryFilters')) {
    /**
     * Returns category filtering.
     *
     * @param string $extraClasses any extra classes you add to the drop down
     * @return string
     */
    function categoryFilters($extraClasses = '') {
        if (!Gdn::session()->isValid()) {
            return;
        }

        $baseUrl = 'categories';
        $filters = [
            [
                'name' => 'Following',
                'param' => 'followed',
                'extra' => ['save' => 1]
            ]
        ];

        $defaultParams = ['save' => 1];
        if (Gdn::request()->get('followed')) {
            $defaultParams['followed'] = 0;
        }

        if (!empty($defaultParams)) {
            $defaultUrl = $baseUrl.'?'.http_build_query($defaultParams);
        } else {
            $defaultUrl = $baseUrl;
        }

        return filtersDropDown(
            $baseUrl,
            $filters,
            $extraClasses,
            'All',
            $defaultUrl,
            'View'
        );
    }
}

if (!function_exists('categoryUrl')) {
    /**
     * Return a url for a category. This function is in here and not functions.general so that plugins can override.
     *
     * @param string|array $category
     * @param string|int $page The page number.
     * @param bool $withDomain Whether to add the domain to the URL
     * @return string The url to a category.
     */
    function categoryUrl($category, $page = '', $withDomain = true) {
        if (is_string($category)) {
            $category = CategoryModel::categories($category);
        }
        $category = (array)$category;

        $result = '/categories/'.rawurlencode($category['UrlCode']);
        if ($page && $page > 1) {
            $result .= '/p'.$page;
        }
        return url($result, $withDomain);
    }
}

if (!function_exists('condense')) {
    /**
     *
     *
     * @param string $html
     * @return mixed
     */
    function condense($html) {
        $html = preg_replace('`(?:<br\s*/?>\s*)+`', "<br />", $html);
        $html = preg_replace('`/>\s*<br />\s*<img`', "/> <img", $html);
        return $html;
    }
}

if (!function_exists('countString')) {
    /**
     *
     *
     * @param $number
     * @param string $url
     * @param array $options
     * @return string
     */
    function countString($number, $url = '', $options = []) {
        if (!$number && $number !== null) {
            return '';
        }

        if (is_array($options)) {
            $options = array_change_key_case($options);
            $cssClass = val('cssclass', $options, '');
        } else {
            $cssClass = $options;
        }

        if ($number) {
            $cssClass = trim($cssClass.' Count', ' ');
            return "<span class=\"$cssClass\">$number</span>";
        } elseif ($number === null && $url) {
            $cssClass = trim($cssClass.' Popin TinyProgress', ' ');
            $url = htmlspecialchars($url);
            return "<span class=\"$cssClass\" rel=\"$url\"></span>";
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
     * @param array|object $row
     * @return string The CSS classes to be inserted into the row.
     */
    function cssClass($row, $inList = true) {
        static $alt = false;
        $row = (array)$row;
        $cssClass = 'Item';
        $session = Gdn::session();

        // Alt rows
        if ($alt) {
            $cssClass .= ' Alt';
        }
        $alt = !$alt;

        // Category list classes
        if (array_key_exists('UrlCode', $row)) {
            $cssClass .= ' Category-'.Gdn_Format::alphaNumeric($row['UrlCode']);
        }
        if (val('CssClass', $row)) {
            $cssClass .= ' Item-'.$row['CssClass'];
        }

        if (array_key_exists('Depth', $row)) {
            $cssClass .= " Depth{$row['Depth']} Depth-{$row['Depth']}";
        }

        if (array_key_exists('Archive', $row)) {
            $cssClass .= ' Archived';
        }

        // Discussion list classes.
        if ($inList) {
            $cssClass .= val('Bookmarked', $row) == '1' ? ' Bookmarked' : '';

            $announce = val('Announce', $row);
            if ($announce == 2) {
                $cssClass .= ' Announcement Announcement-Category';
            } elseif ($announce) {
                $cssClass .= ' Announcement Announcement-Everywhere';
            }

            $cssClass .= val('Closed', $row) == '1' ? ' Closed' : '';
            $cssClass .= val('InsertUserID', $row) == $session->UserID ? ' Mine' : '';
            $cssClass .= val('Participated', $row) == '1' ? ' Participated' : '';
            if (array_key_exists('CountUnreadComments', $row) && $session->isValid()) {
                $countUnreadComments = $row['CountUnreadComments'];
                if ($countUnreadComments === true) {
                    $cssClass .= ' New';
                } elseif ($countUnreadComments == 0) {
                    $cssClass .= ' Read';
                } else {
                    $cssClass .= ' Unread';
                }
            } elseif (($isRead = val('Read', $row, null)) !== null) {
                // Category list
                $cssClass .= $isRead ? ' Read' : ' Unread';
            }
        }

        // Comment list classes
        if (array_key_exists('CommentID', $row)) {
            $cssClass .= ' ItemComment';
        } elseif (array_key_exists('DiscussionID', $row)) {
            $cssClass .= ' ItemDiscussion';
        }

        if (function_exists('IsMeAction')) {
            $cssClass .= isMeAction($row) ? ' MeAction' : '';
        }

        if ($_CssClss = val('_CssClass', $row)) {
            $cssClass .= ' '.$_CssClss;
        }

        // Insert User classes.
        if ($userID = val('InsertUserID', $row)) {
            $user = Gdn::userModel()->getID($userID);
            if ($_CssClss = val('_CssClass', $user)) {
                $cssClass .= ' '.$_CssClss;
            }
        }

        return trim($cssClass);
    }
}

if (!function_exists('dateUpdated')) {
    /**
     *
     *
     * @param $row
     * @param null $wrap
     * @return string
     */
    function dateUpdated($row, $wrap = null) {
        $result = '';
        $dateUpdated = val('DateUpdated', $row);
        $updateUserID = val('UpdateUserID', $row);

        if ($dateUpdated) {
            $updateUser = Gdn::userModel()->getID($updateUserID);
            if ($updateUser) {
                $title = sprintf(t('Edited %s by %s.'), Gdn_Format::dateFull($dateUpdated), val('Name', $updateUser));
            } else {
                $title = sprintf(t('Edited %s.'), Gdn_Format::dateFull($dateUpdated));
            }

            $result = ' <span title="'.htmlspecialchars($title).'" class="DateUpdated">'.
                sprintf(t('edited %s'), Gdn_Format::date($dateUpdated)).
                '</span> ';

            if ($wrap) {
                $result = $wrap[0].$result.$wrap[1];
            }
        }

        return $result;
    }
}

if (!function_exists('anchor')) {
    /**
     * Builds and returns an anchor tag.
     *
     * @param $text
     * @param string $destination
     * @param string $cssClass
     * @param array $attributes
     * @param bool $forceAnchor
     * @return string
     */
    function anchor($text, $destination = '', $cssClass = '', $attributes = [], $forceAnchor = false) {
        if (!is_array($cssClass) && $cssClass != '') {
            $cssClass = ['class' => $cssClass];
        }

        if ($destination == '' && $forceAnchor === false) {
            return $text;
        }

        if (!is_array($attributes)) {
            $attributes = [];
        }

        $sSL = null;
        if (isset($attributes['SSL'])) {
            $sSL = $attributes['SSL'];
            unset($attributes['SSL']);
        }

        $withDomain = false;
        if (isset($attributes['WithDomain'])) {
            $withDomain = $attributes['WithDomain'];
            unset($attributes['WithDomain']);
        }

        $prefix = substr($destination, 0, 7);
        if (!in_array($prefix, ['https:/', 'http://', 'mailto:']) && ($destination != '' || $forceAnchor === false)) {
            $destination = Gdn::request()->url($destination, $withDomain, $sSL);
        }

        return '<a href="'.htmlspecialchars($destination, ENT_COMPAT, 'UTF-8').'"'.attribute($cssClass).attribute($attributes).'>'.$text.'</a>';
    }
}

if (!function_exists('commentUrl')) {
    /**
     * Return a URL for a comment. This function is in here and not functions.general so that plugins can override.
     *
     * @param object $comment
     * @param bool $withDomain
     * @return string
     */
    function commentUrl($comment, $withDomain = true) {
        $comment = (object)$comment;
        $result = "/discussion/comment/{$comment->CommentID}#Comment_{$comment->CommentID}";
        return url($result, $withDomain);
    }
}

if (!function_exists('discussionFilters')) {
    /**
     * Returns discussions filtering.
     *
     * @param string $extraClasses any extra classes you add to the drop down
     * @return string
     */
    function discussionFilters($extraClasses = '') {
        if (!Gdn::session()->isValid()) {
            return;
        }

        $baseUrl = 'discussions';
        $filters = [
            [
                'name' => 'Following',
                'param' => 'followed',
                'extra' => ['save' => 1]
            ]
        ];

        $defaultParams = ['save' => 1];
        if (Gdn::request()->get('followed')) {
            $defaultParams['followed'] = 0;
        }

        if (!empty($defaultParams)) {
            $defaultUrl = $baseUrl.'?'.http_build_query($defaultParams);
        } else {
            $defaultUrl = $baseUrl;
        }

        return filtersDropDown(
            $baseUrl,
            $filters,
            $extraClasses,
            'All',
            $defaultUrl,
            'View'
        );
    }
}

if (!function_exists('discussionUrl')) {
    /**
     * Return a URL for a discussion. This function is in here and not functions.general so that plugins can override.
     *
     * @param object $discussion
     * @param int|string $page
     * @param bool $withDomain
     * @return string
     */
    function discussionUrl($discussion, $page = '', $withDomain = true) {
        $discussion = (object)$discussion;
        $name = Gdn_Format::url($discussion->Name);

        // Disallow an empty name slug in discussion URLs.
        if (empty($name)) {
            $name = 'x';
        }

        $result = '/discussion/'.$discussion->DiscussionID.'/'.$name;

        if ($page) {
            if ($page > 1 || Gdn::session()->UserID) {
                $result .= '/p'.$page;
            }
        }

        return url($result, $withDomain);
    }
}

if (!function_exists('exportCSV')) {
    /**
     * Create a CSV given a list of column names & rows.
     *
     * @param array $columnNames
     * @param array $data
     */
    function exportCSV($columnNames, $data = []) {
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

if (!function_exists('filtersDropDown')) {
    /**
     * Returns a filtering drop-down menu.
     *
     * @param string $baseUrl Target URL with no query string applied.
     * @param array $filters A multidimensional array of rows with the following properties:
     *     ** 'name': Friendly name for the filter.
     *     ** 'param': URL parameter associated with the filter.
     *     ** 'value': A value for the URL parameter.
     * @param string $extraClasses any extra classes you add to the drop down
     * @param string $default The default label for when no filter is active.
     * @param string|null $defaultURL URL override to return to the default, unfiltered state.
     * @param string $label Text for the label to attach to the cont
     * @return string
     */
    function filtersDropDown($baseUrl, array $filters = [], $extraClasses = '', $default = 'All', $defaultUrl = null, $label = 'View') {
        $output = '';

        if (c('Vanilla.EnableCategoryFollowing')) {
            $links = [];
            $active = null;

            // Translate filters into links.
            foreach ($filters as $filter) {
                // Make sure we have the bare minimum: a label and a URL parameter.
                if (!array_key_exists('name', $filter)) {
                    throw new InvalidArgumentException('Filter does not have a name field.');
                }
                if (!array_key_exists('param', $filter)) {
                    throw new InvalidArgumentException('Filter does not have a param field.');
                }

                // Prepare for consumption by linkDropDown.
                $value = val('value', $filter, 1);
                $query = [$filter['param'] => $value];
                if (array_key_exists('extra', $filter) && is_array($filter['extra'])) {
                    $query += $filter['extra'];
                }
                $url = url($baseUrl.'?'.http_build_query($query));
                $link = [
                    'name' => $filter['name'],
                    'url' => $url
                ];

                // If we don't already have an active link, and this parameter and value match, this is the active link.
                if ($active === null && Gdn::request()->get($filter['param']) == $value) {
                    $active = $filter['name'];
                    $link['active'] = true;
                }

                // Queue up another filter link.
                $links[] = $link;
            }

            // Add the default link to the top of the list.
            array_unshift($links, [
                'active' => $active === null,
                'name' => $default,
                'url' => $defaultUrl ?: $baseUrl
            ]);

            // Generate the markup for the drop down menu.
            $output = linkDropDown($links, 'selectBox-following '.trim($extraClasses), t($label).': ');
        }

        return $output;
    }
}

if (!function_exists('fixnl2br')) {
    /**
     * Removes the break above and below tags that have a natural margin.
     *
     * @param string $text The text to fix.
     * @return string
     * @since 2.1
     */
    function fixnl2br($text) {
        $allblocks = '(?:table|dl|ul|ol|pre|blockquote|address|p|h[1-6]|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary|li|tbody|tr|td|th|thead|tbody|tfoot|col|colgroup|caption|dt|dd)';
        $text = preg_replace('!(?:<br\s*/>){1,2}\s*(<'.$allblocks.'[^>]*>)!', "\n$1", $text);
        $text = preg_replace('!(</'.$allblocks.'[^>]*>)\s*(?:<br\s*/>){1,2}!', "$1\n", $text);
        return $text;
    }
}

if (!function_exists('formatIP')) {
    /**
     * Format an IP address for display.
     *
     * @param string $iP An IP address to be formatted.
     * @param bool $html Format as HTML.
     * @return string Returns the formatted IP address.
     */
    function formatIP($iP, $html = true) {
        $result = '';

        // Is this a packed IP address?
        if (!filter_var($iP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6) && $unpackedIP = @inet_ntop($iP)) {
            $iP = $unpackedIP;
        }

        if (filter_var($iP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $result = $html ? htmlspecialchars($iP) : $iP;
        } elseif (filter_var($iP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $result = $html ? wrap(t('IPv6'), 'span', ['title' => $iP]) : $iP;
        }

        return $result;
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
    function formatPossessive($word) {
        if (function_exists('formatPossessiveCustom')) {
            return formatPossesiveCustom($word);
        }

        return substr($word, -1) == 's' ? $word."'" : $word."'s";
    }
}

if (!function_exists('formatRssCustom')) {
    /**
     * @param string $html
     * @return string Returns the filtered RSS.
     */
    function formatRssHtmlCustom($html) {
        return Htmlawed::filterRSS($html);
    }
}

if (!function_exists('formatUsername')) {
    /**
     *
     *
     * @param $user
     * @param $format
     * @param bool $viewingUserID
     * @return mixed|string
     */
    function formatUsername($user, $format, $viewingUserID = false) {
        if ($viewingUserID === false) {
            $viewingUserID = Gdn::session()->UserID;
        }
        $userID = val('UserID', $user);
        $name = val('Name', $user);
        $gender = strtolower(val('Gender', $user));

        $uCFirst = substr($format, 0, 1) == strtoupper(substr($format, 0, 1));

        switch (strtolower($format)) {
            case 'you':
                if ($viewingUserID == $userID) {
                    return t("Format $format", $format);
                }
                return $name;
            case 'his':
            case 'her':
            case 'your':
                if ($viewingUserID == $userID) {
                    return t("Format Your", 'Your');
                } else {
                    switch ($gender) {
                        case 'm':
                            $format = 'his';
                            break;
                        case 'f':
                            $format = 'her';
                            break;
                        default:
                            $format = 'their';
                            break;
                    }
                    if ($uCFirst) {
                        $format = ucfirst($format);
                    }
                    return t("Format $format", $format);
                }
                break;
            default:
                return $name;
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
        if (checkPermission(['Garden.Users.Edit', 'Moderation.Profiles.Edit'])) {
            return true;
        }
        if ($userID != Gdn::session()->UserID) {
            return false;
        }

        $result = checkPermission('Garden.Profiles.Edit') && c('Garden.UserAccount.AllowEdit');

        $result = $result && (
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
     * @param string $string
     * @param string $help
     * @return string
     */
    function hoverHelp($string, $help) {
        return wrap($string.wrap($help, 'span', ['class' => 'Help']), 'span', ['class' => 'HoverHelp']);
    }
}

if (!function_exists('img')) {
    /**
     * Returns an img tag.
     *
     * @param string $image
     * @param string $attributes
     * @param bool|false $withDomain
     * @return string
     */
    function img($image, $attributes = '', $withDomain = false) {
        if ($attributes != '') {
            $attributes = attribute($attributes);
        }

        if (!isUrl($image)) {
            $image = smartAsset($image, $withDomain);
        }

        return '<img src="'.htmlspecialchars($image, ENT_QUOTES).'"'.$attributes.' />';
    }
}

if (!function_exists('inCategory')) {
    /**
     * Return whether or not the page is in a given category.
     *
     * @param string $category The url code of the category.
     * @return boolean
     * @since 2.1
     */
    function inCategory($category) {
        $breadcrumbs = (array)Gdn::controller()->data('Breadcrumbs', []);

        foreach ($breadcrumbs as $breadcrumb) {
            if (isset($breadcrumb['CategoryID']) && strcasecmp($breadcrumb['UrlCode'], $category) == 0) {
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
     * @param string|array $section
     * @return bool
     * @since 2.1
     */
    function inSection($section) {
        return Gdn_Theme::inSection($section);
    }
}

if (!function_exists('ipAnchor')) {
    /**
     * Returns an IP address with a link to the user search.
     *
     * @param string $iP
     * @param string $cssClass
     * @return string
     */
    function ipAnchor($iP, $cssClass = '') {
        if ($iP) {
            return anchor(formatIP($iP), '/user/browse?keywords='.urlencode(ipDecode($iP)), $cssClass);
        } else {
            return $iP;
        }
    }
}

if (!function_exists('linkDropDown')) {
    /**
     * Write a link drop down control.
     *
     * @param array $links
     *   Has the following properties:
     *     ** 'url': string: The url for the link
     *     ** 'name': string: The text for the link
     *     ** 'active': boolean: is it the current page
     * @param string $extraClasses any extra classes you add to the drop down
     * @param string $label the label of the drop down
     *
     */
    function linkDropDown($links, $extraClasses = '', $label) {
        $output = '';
        $selectedKey = 0;
        foreach($links as $i => $link) {
            if (val('active', $link)) {
                $selectedKey = $i;
                break;
            }
        }
        $selectedLink = val($selectedKey, $links);
        $extraClasses = trim($extraClasses);
        $linkName = val('name', $selectedLink);

        $output .= <<<EOT
        <span class="ToggleFlyout selectBox {$extraClasses}">
          <span class="selectBox-label">{$label}</span>
          <span class="selectBox-main">
              <a href="#" role="button" rel="nofollow" class="FlyoutButton selectBox-toggle" tabindex="0">
                <span class="selectBox-selected">{$linkName}</span>
                <span class="vanillaDropDown-arrow">▾</span>
              </a>
              <ul class="Flyout MenuItems selectBox-content" role="presentation">
EOT;
        foreach($links as $i => $link) {
                if (val('separator', $link)) {
                    $output .= '<li class="menu-separator" role="presentation">';
                        $output .= '<hr/>';
                    $output .= '</li>';
                } else {
                    if (val('active', $link)) {
                        $output .= '<li class="selectBox-item isActive" role="presentation">';
                        $output .= '  <a href="'.htmlspecialchars(val('url', $link)).'" role="menuitem" class="dropdown-menu-link selectBox-link" tabindex="0" aria-current="location">';
                        $output .= '    <svg class="vanillaIcon selectBox-selectedIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18">';
                        $output .= '      <title>✓</title>';
                        $output .= '      <polygon fill="currentColor" points="1.938,8.7 0.538,10.1 5.938,15.5 17.337,3.9 15.938,2.5 5.938,12.8"></polygon>';
                        $output .= '    </svg>';
                        $output .= '    <span class="selectBox-selectedText">';
                        $output .=        val('name', $link);
                        $output .= '    </span>';
                        $output .= '  </a>';
                        $output .= '</li>';
                    } else {
                        $output .= '<li class="selectBox-item" role="presentation">';
                        $output .= '  <a href="'.htmlspecialchars(val('url', $link)).'" role="menuitem" class="dropdown-menu-link selectBox-link" tabindex="0" href="#">';
                        $output .=      val('name', $link);
                        $output .= '  </a>';
                        $output .= '</li>';
                    }
                }
            }
                $output .= <<<EOT
              </ul>
            </span>
          </span>
        </span>
EOT;

        return $output;
    }
}

if (!function_exists('panelHeading')) {
    /**
     * Define default head tag for the side panel.
     *
     * @param string $content The content of the tag.
     * @param string $attributes The attributes of the tag.
     * @return string The full tag.
     */
    function panelHeading($content, $attributes = []) {
        $attributes = array_merge(['aria-level' => '2'], $attributes);
        return wrap($content, 'h4', $attributes);
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
     *
     * @param $number
     * @param $singular
     * @param $plural
     * @param bool $formattedNumber
     * @return string
     */
    function plural($number, $singular, $plural, $formattedNumber = false) {
        // Make sure to fix comma-formatted numbers
        $workingNumber = str_replace(',', '', $number);
        if ($formattedNumber === false) {
            $formattedNumber = $number;
        }

        $format = t(abs($workingNumber) == 1 ? $singular : $plural);

        return sprintf($format, $formattedNumber);
    }
}

if (!function_exists('pluralTranslate')) {
    /**
     * Translate a plural string.
     *
     * @param int $number
     * @param string $singular
     * @param string $plural
     * @param string|bool $singularDefault
     * @param string|bool $pluralDefault
     * @return string
     * @since 2.1
     */
    function pluralTranslate($number, $singular, $plural, $singularDefault = false, $pluralDefault = false) {
        if ($number == 1) {
            return t($singular, $singularDefault);
        } else {
            return t($plural, $pluralDefault);
        }
    }
}

if (!function_exists('searchExcerpt')) {
    /**
     * Excerpt a search result.
     *
     * @param string $plainText
     * @param array|string $searchTerms
     * @param int $length
     * @param bool $mark
     * @return string
     */
    function searchExcerpt($plainText, $searchTerms, $length = 200, $mark = true) {
        if (empty($searchTerms)) {
            return substrWord($plainText, 0, $length);
        }

        if (is_string($searchTerms)) {
            $searchTerms = preg_split('`[\s|-]+`i', $searchTerms);
        }

        // Split the string into lines.
        $lines = explode("\n", $plainText);
        // Find the first line that includes a search term.
        foreach ($lines as $i => &$line) {
            $line = trim($line);
            if (!$line) {
                continue;
            }

            foreach ($searchTerms as $term) {
                if (!$term) {
                    continue;
                }

                if (($pos = mb_stripos($line, $term)) !== false) {
                    $line = substrWord($line, $term, $length);

                    if ($mark) {
                        return markString($searchTerms, $line);
                    } else {
                        return $line;
                    }
                }
            }
        }

        // No line was found so return the first non-blank line.
        foreach ($lines as $line) {
            if ($line) {
                return sliceString($line, $length);
            }
        }
        return '';
    }

    /**
     *
     *
     * @param int $str
     * @param int $start
     * @param int $length
     * @return string
     */
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
     *
     * @param array|object $user
     * @param null $cssClass
     * @param null $options
     * @return string
     */
    function userAnchor($user, $cssClass = null, $options = null) {
        static $nameUnique = null;
        if ($nameUnique === null) {
            $nameUnique = c('Garden.Registration.NameUnique');
        }

        if (is_array($cssClass)) {
            $options = $cssClass;
            $cssClass = null;
        } elseif (is_string($options)) {
            $options = ['Px' => $options];
        }

        $px = val('Px', $options, '');
        $name = val($px.'Name', $user, t('Unknown'));
        $text = val('Text', $options, htmlspecialchars($name)); // Allow anchor text to be overridden.

        $attributes = [
            'class' => $cssClass,
            'rel' => val('Rel', $options)
        ];
        if (isset($options['title'])) {
            $attributes['title'] = $options['title'];
        }

        $userUrl = userUrl($user, $px);

        return '<a href="'.htmlspecialchars(url($userUrl)).'"'.attribute($attributes).'>'.$text.'</a>';
    }
}

if (!function_exists('userBuilder')) {
    /**
     * Take an object & prefix value and convert it to a user object that can be used by userAnchor() && userPhoto().
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
     * @param object|array $user A user object or array.
     * @param array $options
     * @return string HTML.
     */
    function userPhoto($user, $options = []) {
        if (is_string($options)) {
            $options = ['LinkClass' => $options];
        }

        if ($px = val('Px', $options)) {
            $user = userBuilder($user, $px);
        } else {
            $user = (object)$user;
        }

        $linkClass = concatSep(' ', val('LinkClass', $options, ''), 'PhotoWrap');
        $imgClass = val('ImageClass', $options, 'ProfilePhoto');

        $size = val('Size', $options);
        if ($size) {
            $linkClass .= " PhotoWrap{$size}";
            $imgClass .= " {$imgClass}{$size}";
        } else {
            $imgClass .= " {$imgClass}Medium"; // backwards compat
        }

        $fullUser = Gdn::userModel()->getID(val('UserID', $user), DATASET_TYPE_ARRAY);
        $userCssClass = val('_CssClass', $fullUser);
        if ($userCssClass) {
            $linkClass .= ' '.$userCssClass;
        }

        $linkClass = $linkClass == '' ? '' : ' class="'.$linkClass.'"';

        $photo = val('Photo', $fullUser, val('PhotoUrl', $user));
        $name = val('Name', $fullUser);
        $title = htmlspecialchars(val('Title', $options, $name));

        if ($fullUser && $fullUser['Banned']) {
            $photo = c('Garden.BannedPhoto', 'https://images.v-cdn.net/banned_large.png');
            $title .= ' ('.t('Banned').')';
        }

        if ($photo) {
            if (!isUrl($photo)) {
                $photoUrl = Gdn_Upload::url(changeBasename($photo, 'n%s'));
            } else {
                $photoUrl = $photo;
            }
        } else {
            $photoUrl = UserModel::getDefaultAvatarUrl($fullUser, 'thumbnail');
        }

        $href = (val('NoLink', $options)) ? '' : ' href="'.url(userUrl($fullUser)).'"';

        return '<a title="'.$title.'"'.$href.$linkClass.'>'
                .img($photoUrl, ['alt' => $name, 'class' => $imgClass])
            .'</a>';
    }
}

if (!function_exists('userPhotoUrl')) {
    /**
     * Take a user object an return the URL to their photo.
     *
     * @param object|array $user
     * @return string
     */
    function userPhotoUrl($user) {
        $fullUser = Gdn::userModel()->getID(val('UserID', $user), DATASET_TYPE_ARRAY);
        $photo = val('Photo', $user);
        if ($fullUser && $fullUser['Banned']) {
            $photo = 'https://images.v-cdn.net/banned_100.png';
        }

        if ($photo) {
            if (!isUrl($photo)) {
                $photoUrl = Gdn_Upload::url(changeBasename($photo, 'n%s'));
            } else {
                $photoUrl = $photo;
            }
            return $photoUrl;
        }
        return UserModel::getDefaultAvatarUrl($user);
    }
}

if (!function_exists('userUrl')) {
    /**
     * Return the URL for a user.
     *
     * @param array|object $user The user to get the url for.
     * @param string $px The prefix to apply before fieldnames.
     * @param string $method Optional. ProfileController method to target.
     * @param array? $get An optional query string array to add to the URL.
     * @return string The url suitable to be passed into the url() function.
     * @since 2.1
     */
    function userUrl($user, $px = '', $method = '', $get = null) {
        static $nameUnique = null;
        if ($nameUnique === null) {
            $nameUnique = c('Garden.Registration.NameUnique');
        }

        $userName = val($px.'Name', $user);
        // Make sure that the name will not be split if the p parameter is set.
        // Prevent p=/profile/a&b to be translated to $_GET['p'=>'/profile/a?', 'b'=>'']
        $userName = str_replace(['/', '&'], ['%2f', '%26'], $userName);

        $result = '/profile/'.
            ($method ? trim($method, '/').'/' : '').
            ($nameUnique ? '' : val($px.'UserID', $user, 0).'/').
            rawurlencode($userName);

        if (!empty($get)) {
            $result .= '?'.http_build_query($get);
        }

        return $result;
    }
}

if (!function_exists('wrap')) {
    /**
     * Wrap the provided string in the specified tag.
     *
     * @example wrap('This is bold!', 'b');
     *
     * @param $string
     * @param string $tag
     * @param string $attributes
     * @return string
     */
    function wrap($string, $tag = 'span', $attributes = '') {
        if ($tag == '') {
            return $string;
        }

        if (is_array($attributes)) {
            $attributes = attribute($attributes);
        }

        // Strip the first part of the tag as the closing tag - this allows us to
        // easily throw 'span class="something"' into the $Tag field.
        $space = strpos($tag, ' ');
        $closingTag = $space ? substr($tag, 0, $space) : $tag;
        return '<'.$tag.$attributes.'>'.$string.'</'.$closingTag.'>';
    }
}

if (!function_exists('wrapIf')) {
    /**
     * Wrap the provided string if it isn't empty.
     *
     * @param string $string
     * @param string $tag
     * @param array|string $attributes
     * @return string
     * @since 2.1
     */
    function wrapIf($string, $tag = 'span', $attributes = '') {
        if (empty($string)) {
            return '';
        } else {
            return wrap($string, $tag, $attributes);
        }
    }
}

if (!function_exists('discussionLink')) {
    /**
     * Build URL for discussion.
     *
     * @deprecated discussionUrl()
     * @param $discussion
     * @param bool $extended
     * @return string
     */
    function discussionLink($discussion, $extended = true) {
        deprecated('discussionLink', 'discussionUrl');

        $discussionID = val('DiscussionID', $discussion);
        $discussionName = val('Name', $discussion);
        $parts = [
            'discussion',
            $discussionID,
            Gdn_Format::url($discussionName)
        ];
        if ($extended) {
            $parts[] = ($discussion->CountCommentWatch > 0) ? '#Item_'.$discussion->CountCommentWatch : '';
        }
        return url(implode('/', $parts), true);
    }
}

if (!function_exists('registerUrl')) {
    /**
     * Build URL for registration.
     *
     * @param string $target
     * @param bool $force
     * @return string
     */
    function registerUrl($target = '', $force = false) {
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

        return '/entry/register'.($target ? '?Target='.urlencode($target) : '');
    }
}

if (!function_exists('signInUrl')) {
    /**
     * Build URL for signin.
     *
     * @param string $target
     * @param bool $force
     * @return string
     */
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
    /**
     * Build URL for signout.
     *
     * @param string $target
     * @return string
     */
    function signOutUrl($target = '') {
        if ($target) {
            // Strip out the SSO from the target so that the user isn't signed back in again.
            $parts = explode('?', $target, 2);
            if (isset($parts[1])) {
                parse_str($parts[1], $query);
                unset($query['sso']);
                $target = $parts[0].'?'.http_build_query($query);
            }
        }

        return '/entry/signout?TransientKey='.urlencode(Gdn::session()->transientKey()).($target ? '&Target='.urlencode($target) : '');
    }
}

if (!function_exists('socialSignInButton')) {
    /**
     * Build HTML for a social signin button.
     *
     * @param $name
     * @param $url
     * @param string $type
     * @param array $attributes
     * @return string HTML.
     */
    function socialSignInButton($name, $url, $type = 'button', $attributes = []) {
        touchValue('title', $attributes, sprintf(t('Sign In with %s'), $name));
        $title = $attributes['title'];
        $class = val('class', $attributes, '');
        unset($attributes['class']);

        switch ($type) {
            case 'icon':
                $result = anchor(
                    '<span class="Icon"></span>',
                    $url,
                    'SocialIcon SocialIcon-'.$name.' '.$class,
                    $attributes
                );
                break;
            case 'button':
            default:
                $result = anchor(
                    '<span class="Icon"></span><span class="Text">'.$title.'</span>',
                    $url,
                    'SocialIcon SocialIcon-'.$name.' HasText '.$class,
                    $attributes
                );
                break;
        }

        return $result;
    }
}

if (!function_exists('sprite')) {
    /**
     * Build HTML for a sprite.
     *
     * @param string $name
     * @param string $type
     * @param bool $text
     * @return string
     */
    function sprite($name, $type = 'Sprite', $text = false) {
        $sprite = '<span aria-hidden="true" class="'.$type.' '.$name.'"></span>';
        if ($text) {
            $sprite .= '<span class="sr-only">'.$text.'</span>';
        }

        return $sprite;
    }
}

if (!function_exists('hero')) {
    /**
     * A hero component is a stand-alone message on a page. It's great for "empty"-type messages, or to really draw
     * attention. It gets used in the (hidden) Vanilla Tutorial sections and in empty messages.
     *
     * @param string $title The title for the message.
     * @param string $body The message body.
     * @param array $buttonArray An array representing a button. Appears below the hero body.
     * Has the following properties:
     * ** 'text': The text to add on the button.
     * ** 'url': OPTIONAL The url to follow if the button is an anchor.
     * ** 'attributes': OPTIONAL The attributes on the button.
     * @param string $media An image or video to include in the hero.
     * @return string A string representing a hero component
     */
    function hero($title = '', $body = '', array $buttonArray = [], $media = '') {
        if ($title === '' && $body === '' && $media = '') {
            return '';
        }

        if (!empty($title)) {
            $title = wrap($title, 'div', ['class' => 'hero-title']);
        }

        if (!empty($body)) {
            $body = wrap($body, 'div', ['class' => 'hero-body']);
        }

        if (!empty($media)) {
            $media = wrap($media, 'div', ['class' => 'hero-media-wrapper']);
        }

        if (!empty($buttonArray)) {
            if (!isset($buttonArray['attributes']['class'])) {
                $buttonArray['attributes']['class'] = 'btn btn-secondary';
            }

            if (isset($buttonArray['url'])) {
                $button = anchor(val('text', $buttonArray), val('url', $buttonArray), '', val('attributes', $buttonArray));
            } else {
                $button = wrap(val('text', $buttonArray), 'button', val('attributes', $buttonArray));
            }
        } else {
            $button = '';
        }

        $content = wrap($title.$body.$button, 'div', ['class' => 'hero-content']);
        return wrap($content.$media, 'div', ['class' => 'hero']);
    }
}

if (!function_exists('writeReactions')) {
    /**
     * Write the HTML for a reaction button.
     *
     * @param $row
     */
    function writeReactions($row) {
        $attributes = val('Attributes', $row);
        if (is_string($attributes)) {
            $attributes = dbdecode($attributes);
            setValue('Attributes', $row, $attributes);
        }

        Gdn::controller()->EventArguments['ReactionTypes'] = [];

        if ($iD = val('CommentID', $row)) {
            $recordType = 'comment';
        } elseif ($iD = val('ActivityID', $row)) {
            $recordType = 'activity';
        } else {
            $recordType = 'discussion';
            $iD = val('DiscussionID', $row);
        }
        Gdn::controller()->EventArguments['RecordType'] = $recordType;
        Gdn::controller()->EventArguments['RecordID'] = $iD;

        echo '<div class="Reactions">';
        Gdn_Theme::bulletRow();

        // Write the flags.
        static $flags = null;
        if ($flags === null) {
            Gdn::controller()->EventArguments['Flags'] = &$flags;
            Gdn::controller()->fireEvent('Flags');
        }

        // Allow addons to work with flags
        Gdn::controller()->EventArguments['Flags'] = &$flags;
        Gdn::controller()->fireEvent('BeforeFlag');

        if (!empty($flags) && is_array($flags)) {
            echo Gdn_Theme::bulletItem('Flags');

            echo ' <span class="FlagMenu ToggleFlyout">';
            // Write the handle.
            echo anchor(sprite('ReactFlag', 'ReactSprite').' '.wrap(t('Flag'), 'span', ['class' => 'ReactLabel']), '', 'Hijack ReactButton-Flag FlyoutButton', ['title' => t('Flag')], true);
            echo sprite('SpFlyoutHandle', 'Arrow');
            echo '<ul class="Flyout MenuItems Flags" style="display: none;">';
            foreach ($flags as $flag) {
                if (is_callable($flag)) {
                    echo '<li>'.call_user_func($flag, $row, $recordType, $iD).'</li>';
                } else {
                    echo '<li>'.reactionButton($row, $flag['UrlCode']).'</li>';
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
