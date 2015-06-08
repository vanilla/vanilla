<?php
/**
 * Head module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

if (!class_exists('HeadModule', false)) {
    /**
     * Manages collections of items to be placed between the <HEAD> tags of the
     * page.
     */
    class HeadModule extends Gdn_Module {

        /** The name of the key in a tag that refers to the tag's name. */
        const TAG_KEY = '_tag';

        /**  */
        const CONTENT_KEY = '_content';

        /**  */
        const SORT_KEY = '_sort';

        /** @var array A collection of tags to be placed in the head. */
        private $_Tags;

        /** @var array  A collection of strings to be placed in the head. */
        private $_Strings;

        /** @var string The main text for the "title" tag in the head. */
        protected $_Title;

        /** @var string A string to be concatenated with $this->_Title. */
        protected $_SubTitle;

        /** @var A string to be concatenated with $this->_Title if there is also a $this->_SubTitle string being concatenated. */
        protected $_TitleDivider;

        /** @var bool  */
        private $_FavIconSet = false;

        /**
         *
         *
         * @param string $Sender
         */
        public function __construct($Sender = '') {
            $this->_Tags = array();
            $this->_Strings = array();
            $this->_Title = '';
            $this->_SubTitle = '';
            $this->_TitleDivider = '';
            parent::__construct($Sender);
        }

        /**
         * Adds a "link" tag to the head containing a reference to a stylesheet.
         *
         * @param string $HRef Location of the stylesheet relative to the web root (if an absolute path with http:// is provided, it will use the HRef as provided). ie. /themes/default/css/layout.css or http://url.com/layout.css
         * @param string $Media Type media for the stylesheet. ie. "screen", "print", etc.
         * @param bool $AddVersion Whether to append version number as query string.
         * @param array $Options Additional properties to pass to AddTag, e.g. 'ie' => 'lt IE 7';
         */
        public function addCss($HRef, $Media = '', $AddVersion = true, $Options = null) {
            $Properties = array(
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => Asset($HRef, false, $AddVersion),
                'media' => $Media);

            // Use same underscore convention as AddScript
            if (is_array($Options)) {
                foreach ($Options as $Key => $Value) {
                    $Properties['_'.strtolower($Key)] = $Value;
                }
            }

            $this->addTag('link', $Properties);
        }

        /**
         *
         *
         * @param $HRef
         * @param $Title
         */
        public function addRss($HRef, $Title) {
            $this->addTag('link', array(
                'rel' => 'alternate',
                'type' => 'application/rss+xml',
                'title' => Gdn_Format::text($Title),
                'href' => Asset($HRef)
            ));
        }

        /**
         * Adds a new tag to the head.
         *
         * @param string The type of tag to add to the head. ie. "link", "script", "base", "meta".
         * @param array An associative array of property => value pairs to be placed in the tag.
         * @param string an index to give the tag for later manipulation.
         */
        public function addTag($Tag, $Properties, $Content = null, $Index = null) {
            $Tag = array_merge(array(self::TAG_KEY => strtolower($Tag)), array_change_key_case($Properties));
            if ($Content) {
                $Tag[self::CONTENT_KEY] = $Content;
            }
            if (!array_key_exists(self::SORT_KEY, $Tag)) {
                $Tag[self::SORT_KEY] = count($this->_Tags);
            }

            if ($Index !== null) {
                $this->_Tags[$Index] = $Tag;
            }

            // Make sure this item has not already been added.
            if (!in_array($Tag, $this->_Tags)) {
                $this->_Tags[] = $Tag;
            }
        }

        /**
         * Adds a "script" tag to the head.
         *
         * @param string The location of the script relative to the web root. ie. "/js/jquery.js"
         * @param string The type of script being added. ie. "text/javascript"
         * @param mixed Additional options to add to the tag. The following values are accepted:
         *  - numeric: This will be the script's sort.
         *  - string: This will hint the script (inline will inline the file in the page.
         *  - array: An array of options (ex. sort, hint, version).
         *
         */
        public function addScript($Src, $Type = 'text/javascript', $Options = array()) {
            if (is_numeric($Options)) {
                $Options = array('sort' => $Options);
            } elseif (is_string($Options)) {
                $Options = array('hint' => $Options);
            } elseif (!is_array($Options)) {
                $Options = array();
            }

            $Attributes = array();
            if ($Src) {
                $Attributes['src'] = Asset($Src, false, val('version', $Options));
            }
            $Attributes['type'] = $Type;
            if (isset($Options['defer'])) {
                $Attributes['defer'] = $Options['defer'];
            }

            foreach ($Options as $Key => $Value) {
                $Attributes['_'.strtolower($Key)] = $Value;
            }

            $this->addTag('script', $Attributes);
        }

        /**
         * Adds a string to the collection of strings to be inserted into the head.
         *
         * @param string The string to be inserted.
         */
        public function addString($String) {
            $this->_Strings[] = $String;
        }

        /**
         *
         *
         * @return string
         */
        public function assetTarget() {
            return 'Head';
        }

        /**
         * Removes any added stylesheets from the head.
         */
        public function clearCSS() {
            $this->ClearTag('link', array('rel' => 'stylesheet'));
        }

        /**
         * Removes any script include tags from the head.
         */
        public function clearScripts() {
            $this->ClearTag('script');
        }

        /**
         * Removes any tags with the specified $Tag, $Property, and $Value.
         *
         * Only $Tag is required.
         *
         * @param string The name of the tag to remove from the head.  ie. "link"
         * @param string Any property to search for in the tag.
         *    - If this is an array then it will be treated as a query of attribute/value pairs to match against.
         * @param string Any value to search for in the specified property.
         */
        public function clearTag($Tag, $Property = '', $Value = '') {
            $Tag = strtolower($Tag);
            if (is_array($Property)) {
                $Query = array_change_key_case($Property);
            } elseif ($Property)
                $Query = array(strtolower($Property) => $Value);
            else {
                $Query = false;
            }

            foreach ($this->_Tags as $Index => $Collection) {
                $TagName = $Collection[self::TAG_KEY];

                if ($TagName == $Tag) {
                    // If no property is specified and the tag is found, remove it directly.
                    // Otherwise remove it only if all specified property/value pairs match.
                    if (!$Query || count(array_intersect_assoc($Query, $Collection)) == count($Query)) {
                        unset($this->_Tags[$Index]);
                    }
                }
            }
        }

        /**
         * Return all strings.
         */
        public function getStrings() {
            return $this->_Strings;
        }

        /**
         * Return all Tags of the specified type (or all tags).
         */
        public function getTags($RequestedType = '') {
            // Make sure that css loads before js (for jquery)
            usort($this->_Tags, array('HeadModule', 'TagCmp')); // "link" comes before "script"

            if ($RequestedType == '') {
                return $this->_Tags;
            }

            // Loop through each tag.
            $Tags = array();
            foreach ($this->_Tags as $Index => $Attributes) {
                $TagType = $Attributes[self::TAG_KEY];
                if ($TagType == $RequestedType) {
                    $Tags[] = $Attributes;
                }
            }
            return $Tags;
        }

        /**
         * Sets the favicon location.
         *
         * @param string The location of the fav icon relative to the web root. ie. /themes/default/images/layout.css
         */
        public function setFavIcon($HRef) {
            if (!$this->_FavIconSet) {
                $this->_FavIconSet = true;
                $this->addTag(
                    'link',
                    array('rel' => 'shortcut icon', 'href' => $HRef, 'type' => 'image/x-icon'),
                    null,
                    'favicon'
                );
            }
        }

        /**
         * Gets or sets the tags collection.
         *
         * @param array $Value .
         */
        public function tags($Value = null) {
            if ($Value != null) {
                $this->_Tags = $Value;
            }
            return $this->_Tags;
        }

        /**
         *
         *
         * @param string $Title
         * @param bool $NoSubTitle
         * @return mixed|string
         */
        public function title($Title = '', $NoSubTitle = false) {
            if ($Title != '') {
                // Apply $Title to $this->_Title and return it;
                $this->_Title = $Title;
                $this->_Sender->title($Title);
                return $Title;
            } elseif ($this->_Title != '') {
                // Return $this->_Title if set;
                return $this->_Title;
            } elseif ($NoSubTitle) {
                return valr('Data.Title', $this->_Sender, '');
            } else {
                $Subtitle = valr('Data._Subtitle', $this->_Sender, c('Garden.Title'));

                // Default Return title from controller's Data.Title + banner title;
                return ConcatSep(' - ', valr('Data.Title', $this->_Sender, ''), $Subtitle);
            }
        }

        /**
         *
         *
         * @param $A
         * @param $B
         * @return int
         */
        public static function tagCmp($A, $B) {
            if ($A[self::TAG_KEY] == 'title') {
                return -1;
            }
            if ($B[self::TAG_KEY] == 'title') {
                return 1;
            }
            $Cmp = strcasecmp($A[self::TAG_KEY], $B[self::TAG_KEY]);
            if ($Cmp == 0) {
                $SortA = val(self::SORT_KEY, $A, 0);
                $SortB = val(self::SORT_KEY, $B, 0);
                if ($SortA < $SortB) {
                    $Cmp = -1;
                } elseif ($SortA > $SortB)
                    $Cmp = 1;
            }

            return $Cmp;
        }

        /**
         * Render the entire head module.
         */
        public function toString() {
            // Add the canonical Url if necessary.
            if (method_exists($this->_Sender, 'CanonicalUrl') && !c('Garden.Modules.NoCanonicalUrl', false)) {
                $CanonicalUrl = $this->_Sender->canonicalUrl();

                if (!isUrl($CanonicalUrl)) {
                    $CanonicalUrl = Gdn::router()->ReverseRoute($CanonicalUrl);
                }

                $this->_Sender->canonicalUrl($CanonicalUrl);
//            $CurrentUrl = url('', true);
//            if ($CurrentUrl != $CanonicalUrl) {
                $this->addTag('link', array('rel' => 'canonical', 'href' => $CanonicalUrl));
//            }
            }

            // Include facebook open-graph meta information.
            if ($FbAppID = c('Plugins.Facebook.ApplicationID')) {
                $this->addTag('meta', array('property' => 'fb:app_id', 'content' => $FbAppID));
            }

            $SiteName = c('Garden.Title', '');
            if ($SiteName != '') {
                $this->addTag('meta', array('property' => 'og:site_name', 'content' => $SiteName));
            }

            $Title = Gdn_Format::text($this->title('', true));
            if ($Title != '') {
                $this->addTag('meta', array('property' => 'og:title', 'itemprop' => 'name', 'content' => $Title));
            }

            if (isset($CanonicalUrl)) {
                $this->addTag('meta', array('property' => 'og:url', 'content' => $CanonicalUrl));
            }

            if ($Description = $this->_Sender->Description()) {
                $this->addTag('meta', array('name' => 'description', 'property' => 'og:description', 'itemprop' => 'description', 'content' => $Description));
            }

            // Default to the site logo if there were no images provided by the controller.
            if (count($this->_Sender->Image()) == 0) {
                $Logo = c('Garden.ShareImage', c('Garden.Logo', ''));
                if ($Logo != '') {
                    // Fix the logo path.
                    if (stringBeginsWith($Logo, 'uploads/')) {
                        $Logo = substr($Logo, strlen('uploads/'));
                    }

                    $Logo = Gdn_Upload::url($Logo);
                    $this->addTag('meta', array('property' => 'og:image', 'itemprop' => 'image', 'content' => $Logo));
                }
            } else {
                foreach ($this->_Sender->Image() as $Img) {
                    $this->addTag('meta', array('property' => 'og:image', 'itemprop' => 'image', 'content' => $Img));
                }
            }

            $this->fireEvent('BeforeToString');

            $Tags = $this->_Tags;

            // Make sure that css loads before js (for jquery)
            usort($this->_Tags, array('HeadModule', 'TagCmp')); // "link" comes before "script"

            $Tags2 = $this->_Tags;

            // Start with the title.
            $Head = '<title>'.Gdn_Format::text($this->title())."</title>\n";

            $TagStrings = array();
            // Loop through each tag.
            foreach ($this->_Tags as $Index => $Attributes) {
                $Tag = $Attributes[self::TAG_KEY];

                // Inline the content of the tag, if necessary.
                if (val('_hint', $Attributes) == 'inline') {
                    $Path = val('_path', $Attributes);
                    if (!stringBeginsWith($Path, 'http')) {
                        $Attributes[self::CONTENT_KEY] = file_get_contents($Path);

                        if (isset($Attributes['src'])) {
                            $Attributes['_src'] = $Attributes['src'];
                            unset($Attributes['src']);
                        }
                        if (isset($Attributes['href'])) {
                            $Attributes['_href'] = $Attributes['href'];
                            unset($Attributes['href']);
                        }
                    }
                }

                // If we set an IE conditional AND a "Not IE" condition, we will need to make a second pass.
                do {
                    // Reset tag string
                    $TagString = '';

                    // IE conditional? Validates condition.
                    $IESpecific = (isset($Attributes['_ie']) && preg_match('/((l|g)t(e)? )?IE [0-9\.]/', $Attributes['_ie']));

                    // Only allow $NotIE if we're not doing a conditional this loop.
                    $NotIE = (!$IESpecific && isset($Attributes['_notie']));

                    // Open IE conditional tag
                    if ($IESpecific) {
                        $TagString .= '<!--[if '.$Attributes['_ie'].']>';
                    }
                    if ($NotIE) {
                        $TagString .= '<!--[if !IE]> -->';
                    }

                    // Build tag
                    $TagString .= '  <'.$Tag.Attribute($Attributes, '_');
                    if (array_key_exists(self::CONTENT_KEY, $Attributes)) {
                        $TagString .= '>'.$Attributes[self::CONTENT_KEY].'</'.$Tag.'>';
                    } elseif ($Tag == 'script') {
                        $TagString .= '></script>';
                    } else {
                        $TagString .= ' />';
                    }

                    // Close IE conditional tag
                    if ($IESpecific) {
                        $TagString .= '<![endif]-->';
                    }
                    if ($NotIE) {
                        $TagString .= '<!-- <![endif]-->';
                    }

                    // Cleanup (prevent infinite loop)
                    if ($IESpecific) {
                        unset($Attributes['_ie']);
                    }

                    $TagStrings[] = $TagString;

                } while ($IESpecific && isset($Attributes['_notie'])); // We need a second pass

            } //endforeach

            $Head .= implode("\n", array_unique($TagStrings));

            foreach ($this->_Strings as $String) {
                $Head .= $String;
                $Head .= "\n";
            }

            return $Head;
        }
    }
}
