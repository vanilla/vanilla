<?php
/**
 * Head module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
        protected $_Subtitle;

        /** @var A string to be concatenated with $this->_Title if there is also a $this->_Subtitle string being concatenated. */
        protected $_TitleDivider;

        /** @var bool  */
        private $_FavIconSet = false;

        /** @var bool  */
        private $_TouchIconSet = false;

        /** @var bool  */
        private $_MobileAddressBarColorSet = false;

        /**
         *
         *
         * @param string $sender
         */
        public function __construct($sender = '') {
            $this->_Tags = [];
            $this->_Strings = [];
            $this->_Title = '';
            $this->_Subtitle = '';
            $this->_TitleDivider = ' — ';
            parent::__construct($sender);
        }

        /**
         * Adds a "link" tag to the head containing a reference to a stylesheet.
         *
         * @param string $hRef Location of the stylesheet relative to the web root (if an absolute path with http:// is provided, it will use the HRef as provided). ie. /themes/default/css/layout.css or http://url.com/layout.css
         * @param string $media Type media for the stylesheet. ie. "screen", "print", etc.
         * @param bool $addVersion Whether to append version number as query string.
         * @param array $options Additional properties to pass to AddTag, e.g. 'ie' => 'lt IE 7';
         */
        public function addCss($hRef, $media = '', $addVersion = true, $options = null) {
            $properties = [
                'rel' => 'stylesheet',
                'href' => asset($hRef, false, $addVersion),
                'media' => $media];

            // Use same underscore convention as AddScript
            if (is_array($options)) {
                foreach ($options as $key => $value) {
                    $properties['_'.strtolower($key)] = $value;
                }
            }

            $this->addTag('link', $properties);
        }

        /**
         *
         *
         * @param $hRef
         * @param $title
         */
        public function addRss($hRef, $title) {
            $this->addTag('link', [
                'rel' => 'alternate',
                'type' => 'application/rss+xml',
                'title' => Gdn_Format::text($title),
                'href' => asset($hRef)
            ]);
        }

        /**
         * Adds a new tag to the head.
         *
         * @param string The type of tag to add to the head. ie. "link", "script", "base", "meta".
         * @param array An associative array of property => value pairs to be placed in the tag.
         * @param string an index to give the tag for later manipulation.
         */
        public function addTag($tag, $properties, $content = null, $index = null) {
            $tag = array_merge([self::TAG_KEY => strtolower($tag)], array_change_key_case($properties));
            if ($content) {
                $tag[self::CONTENT_KEY] = $content;
            }
            if (!array_key_exists(self::SORT_KEY, $tag)) {
                $tag[self::SORT_KEY] = count($this->_Tags);
            }

            if ($index !== null) {
                $this->_Tags[$index] = $tag;
            }

            // Make sure this item has not already been added.
            if (!in_array($tag, $this->_Tags)) {
                $this->_Tags[] = $tag;
            }
        }

        /**
         * Adds a "script" tag to the head.
         *
         * @param string $src The location of the script relative to the web root. ie. "/js/jquery.js"
         * @param string $type The type of script being added. ie. "text/javascript"
         * @param bool $addVersion Whether to append version number as query string.
         * @param mixed $options Additional options to add to the tag. The following values are accepted:
         *  - numeric: This will be the script's sort.
         *  - string: This will hint the script (inline will inline the file in the page.
         *  - array: An array of options (ex. sort, hint, version).
         *
         */
        public function addScript($src, $type = 'text/javascript', $addVersion = true, $options = []) {
            if (is_numeric($options)) {
                $options = ['sort' => $options];
            } elseif (is_string($options)) {
                $options = ['hint' => $options];
            } elseif (!is_array($options)) {
                $options = [];
            }

            if (is_array($addVersion)) {
                $options = $addVersion;
                $addVersion = true;
            }

            $attributes = [];
            if ($src) {
                $attributes['src'] = asset($src, false, $addVersion);
            }
            if ($type !== 'text/javascript') {
                // Not needed in HTML5
                $attributes['type'] = $type;
            }
            if (isset($options['defer'])) {
                $attributes['defer'] = $options['defer'];
            }

            foreach ($options as $key => $value) {
                $attributes['_'.strtolower($key)] = $value;
            }

            $this->addTag('script', $attributes);
        }

        /**
         * Adds a string to the collection of strings to be inserted into the head.
         *
         * @param string The string to be inserted.
         */
        public function addString($string) {
            $this->_Strings[] = $string;
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
            $this->clearTag('link', ['rel' => 'stylesheet']);
        }

        /**
         * Removes any script include tags from the head.
         */
        public function clearScripts() {
            $this->clearTag('script');
        }

        /**
         * Removes any tags with the specified $tag, $property, and $value.
         *
         * Only $tag is required.
         *
         * @param string The name of the tag to remove from the head.  ie. "link"
         * @param string Any property to search for in the tag.
         *    - If this is an array then it will be treated as a query of attribute/value pairs to match against.
         * @param string Any value to search for in the specified property.
         */
        public function clearTag($tag, $property = '', $value = '') {
            $tag = strtolower($tag);
            if (is_array($property)) {
                $query = array_change_key_case($property);
            } elseif ($property)
                $query = [strtolower($property) => $value];
            else {
                $query = false;
            }

            foreach ($this->_Tags as $index => $collection) {
                $tagName = $collection[self::TAG_KEY];

                if ($tagName == $tag) {
                    // If no property is specified and the tag is found, remove it directly.
                    // Otherwise remove it only if all specified property/value pairs match.
                    if (!$query || count(array_intersect_assoc($query, $collection)) == count($query)) {
                        unset($this->_Tags[$index]);
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
        public function getTags($requestedType = '') {
            // Make sure that css loads before js (for jquery)
            usort($this->_Tags, ['HeadModule', 'TagCmp']); // "link" comes before "script"

            if ($requestedType == '') {
                return $this->_Tags;
            }

            // Loop through each tag.
            $tags = [];
            foreach ($this->_Tags as $index => $attributes) {
                $tagType = $attributes[self::TAG_KEY];
                if ($tagType == $requestedType) {
                    $tags[] = $attributes;
                }
            }
            return $tags;
        }

        /**
         * Sets the favicon location.
         *
         * @param string The location of the fav icon relative to the web root. ie. /themes/default/images/layout.css
         */
        public function setFavIcon($hRef) {
            if (!$this->_FavIconSet) {
                $this->_FavIconSet = true;
                $this->addTag(
                    'link',
                    [
                        'rel' => 'shortcut icon',
                        'href' => $hRef,
                        'type' => 'image/x-icon'
                    ],
                    null,
                    'favicon'
                );
            }
        }

        /**
         * Sets the touch icon
         *
         * @param string $href The location of the fav icon relative to the web root. ie. /themes/default/images/layout.css
         */
        public function setTouchIcon($href) {
            if (!$this->_TouchIconSet) {
                $this->_TouchIconSet = true;
                $this->addTag(
                    'link',
                    [
                        'rel' => 'apple-touch-icon-precomposed',
                        'href' => $href
                    ]
                );
            }
        }

        /**
         * Sets browser address bar colour.
         *
         * @param string meta tags for various browsers
         */
        public function setMobileAddressBarColor($mobileAddressBarColor) {
            if (!$this->_MobileAddressBarColorSet && $mobileAddressBarColor) {
                $this->_MobileAddressBarColorSet = true;
                $this->addTag(
                    'meta',
                    [
                        'name' => 'theme-color',
                        'content' => $mobileAddressBarColor,
                    ]
                );
            }
        }

        /**
         * Gets or sets the tags collection.
         *
         * @param array $value .
         */
        public function tags($value = null) {
            if ($value != null) {
                $this->_Tags = $value;
            }
            return $this->_Tags;
        }

        /**
         * Gets/sets the modules title.
         *
         * @param string $title
         * @param bool $noSubtitle
         * @return mixed|string
         */
        public function title($title = '', $noSubtitle = false) {
            if ($title != '') {
                // Apply $Title to $this->_Title and to $this->_Sender.
                $this->_Title = $title;
                $this->_Sender->title($title);
            } elseif ($this->_Title == '') {
                // Get Title from $this->_Sender if not supplied.
                $this->_Title = valr('Data.Title', $this->_Sender, '');
            }
            if ($noSubtitle) {
                return $this->_Title;
            } else {
                if ($this->_Subtitle == '') {
                    // Get Subtitle from controller.
                    $this->_Subtitle = valr('Data._Subtitle', $this->_Sender, c('Garden.Title'));
                }

                // Default Return title from controller's Data.Title + banner title;
                return concatSep(
                    $this->_TitleDivider,
                    $this->_Title,
                    $this->_Subtitle
                );
            }
        }

        /**
         * Sets the subtitle.
         *
         * @param string $subtitle The subtitle which should be displayed in the title.
         */
        public function setSubtitle($subtitle = '') {
            $this->_Subtitle = $subtitle;
        }

        /**
         * Sets the title divider.
         *
         * This is the string that is used to concatenate title and subtitle.
         * Defaults to ' — '.
         *
         * @param string $titleDivider The string that concats title and subtitle.
         */
        public function setTitleDivider($titleDivider = ' — ') {
            $this->_TitleDivider = $titleDivider;
        }

        /**
         *
         *
         * @param $a
         * @param $b
         * @return int
         */
        public static function tagCmp($a, $b) {
            if ($a[self::TAG_KEY] == 'title') {
                return -1;
            }
            if ($b[self::TAG_KEY] == 'title') {
                return 1;
            }
            $cmp = strcasecmp($a[self::TAG_KEY], $b[self::TAG_KEY]);
            if ($cmp == 0) {
                $sortA = val(self::SORT_KEY, $a, 0);
                $sortB = val(self::SORT_KEY, $b, 0);
                if ($sortA < $sortB) {
                    $cmp = -1;
                } elseif ($sortA > $sortB)
                    $cmp = 1;
            }

            return $cmp;
        }

        /**
         * Render the entire head module.
         */
        public function toString() {
            // Add the canonical Url if necessary.
            if (method_exists($this->_Sender, 'CanonicalUrl') && !c('Garden.Modules.NoCanonicalUrl', false)) {
                $canonicalUrl = $this->_Sender->canonicalUrl();

                if (!empty($canonicalUrl) && !isUrl($canonicalUrl)) {
                    $canonicalUrl = Gdn::router()->reverseRoute($canonicalUrl);
                    $this->_Sender->canonicalUrl($canonicalUrl);
                }
                if ($canonicalUrl) {
                    $this->addTag('link', ['rel' => 'canonical', 'href' => $canonicalUrl]);
                }
            }

            // Include facebook open-graph meta information.
            if ($fbAppID = c('Plugins.Facebook.ApplicationID')) {
                $this->addTag('meta', ['property' => 'fb:app_id', 'content' => $fbAppID]);
            }

            $siteName = c('Garden.Title', '');
            if ($siteName != '') {
                $this->addTag('meta', ['property' => 'og:site_name', 'content' => $siteName]);
            }

            $title = htmlEntityDecode(Gdn_Format::text($this->title('', true)));
            if ($title != '') {
                $this->addTag('meta', ['name' => 'twitter:title', 'property' => 'og:title', 'content' => $title]);
            }

            if (isset($canonicalUrl)) {
                $this->addTag('meta', ['property' => 'og:url', 'content' => $canonicalUrl]);
            }

            if ($description = trim(Gdn_Format::reduceWhiteSpaces($this->_Sender->description()))) {
                $this->addTag('meta', ['name' => 'description', 'property' => 'og:description', 'content' => $description]);
            }

            $hasRelevantImage = false;

            // Default to the site logo if there were no images provided by the controller.
            if (count($this->_Sender->image()) == 0) {
                $logo = c('Garden.ShareImage', c('Garden.Logo', ''));
                if ($logo != '') {
                    // Fix the logo path.
                    if (stringBeginsWith($logo, 'uploads/')) {
                        $logo = substr($logo, strlen('uploads/'));
                    }

                    $logo = Gdn_Upload::url($logo);
                    $this->addTag('meta', ['property' => 'og:image', 'content' => $logo]);
                }
            } else {
                foreach ($this->_Sender->image() as $img) {
                    $this->addTag('meta', ['name' => 'twitter:image', 'property' => 'og:image', 'content' => $img]);
                    $hasRelevantImage = true;
                }
            }

            // For the moment at least, only discussions are supported.
            if ($title && val('DiscussionID', $this->_Sender)) {
                if ($hasRelevantImage) {
                    $twitterCardType = 'summary_large_image';
                } else {
                    $twitterCardType = 'summary';
                }

                // Let's force a description for the image card since it makes sense to see a card with only an image and a title.
                if (!$description && $twitterCardType === 'summary_large_image') {
                    $description = '...';
                }

                // Card && Title && Description are required
                if ($twitterCardType && $description) {
                    $this->addTag('meta', ['name' => 'twitter:description', 'content' => $description]);
                    $this->addTag('meta', ['name' => 'twitter:card', 'content' => $twitterCardType]);
                }
            }

            $this->fireEvent('BeforeToString');

            $tags = $this->_Tags;

            // Make sure that css loads before js (for jquery)
            usort($this->_Tags, ['HeadModule', 'TagCmp']); // "link" comes before "script"

            $tags2 = $this->_Tags;

            // Start with the title.
            $head = '<title>'.Gdn_Format::text($this->title())."</title>\n";

            $tagStrings = [];
            // Loop through each tag.
            foreach ($this->_Tags as $index => $attributes) {
                $tag = $attributes[self::TAG_KEY];

                // Inline the content of the tag, if necessary.
                if (val('_hint', $attributes) == 'inline') {
                    $path = val('_path', $attributes);
                    if ($path && !stringBeginsWith($path, 'http')) {
                        $attributes[self::CONTENT_KEY] = file_get_contents($path);

                        if (isset($attributes['src'])) {
                            $attributes['_src'] = $attributes['src'];
                            unset($attributes['src']);
                        }
                        if (isset($attributes['href'])) {
                            $attributes['_href'] = $attributes['href'];
                            unset($attributes['href']);
                        }
                    }
                }

                // If we set an IE conditional AND a "Not IE" condition, we will need to make a second pass.
                do {
                    // Reset tag string
                    $tagString = '';

                    // IE conditional? Validates condition.
                    $iESpecific = (isset($attributes['_ie']) && preg_match('/((l|g)t(e)? )?IE [0-9\.]/', $attributes['_ie']));

                    // Only allow $NotIE if we're not doing a conditional this loop.
                    $notIE = (!$iESpecific && isset($attributes['_notie']));

                    // Open IE conditional tag
                    if ($iESpecific) {
                        $tagString .= '<!--[if '.$attributes['_ie'].']>';
                    }
                    if ($notIE) {
                        $tagString .= '<!--[if !IE]> -->';
                    }

                    // Build tag
                    $tagString .= '  <'.$tag.attribute($attributes, '_');
                    if (array_key_exists(self::CONTENT_KEY, $attributes)) {
                        $tagString .= '>'.$attributes[self::CONTENT_KEY].'</'.$tag.'>';
                    } elseif ($tag == 'script') {
                        $tagString .= '></script>';
                    } else {
                        $tagString .= ' />';
                    }

                    // Close IE conditional tag
                    if ($iESpecific) {
                        $tagString .= '<![endif]-->';
                    }
                    if ($notIE) {
                        $tagString .= '<!-- <![endif]-->';
                    }

                    // Cleanup (prevent infinite loop)
                    if ($iESpecific) {
                        unset($attributes['_ie']);
                    }

                    $tagStrings[] = $tagString;

                } while ($iESpecific && isset($attributes['_notie'])); // We need a second pass

            } //endforeach

            $head .= implode("\n", array_unique($tagStrings));

            foreach ($this->_Strings as $string) {
                $head .= $string;
                $head .= "\n";
            }

            return $head;
        }
    }
}
