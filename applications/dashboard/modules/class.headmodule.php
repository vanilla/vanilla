<?php
/**
 * Head module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Vanilla\Web\AbstractJsonLDItem;

/**
 * Manages collections of items to be placed between the <HEAD> tags of the
 * page.
 */
class HeadModule extends Gdn_Module
{
    /** The name of the key in a tag that refers to the tag's name. */
    const TAG_KEY = "_tag";

    /**  */
    const CONTENT_KEY = "_content";

    /**  */
    const SORT_KEY = "_sort";

    /** @var array A collection of tags to be placed in the head. */
    private $tags;

    /** @var array  A collection of strings to be placed in the head. */
    private $strings;

    /** @var string The main text for the "title" tag in the head. */
    protected $title;

    /** @var string A string to be concatenated with $this->_Title. */
    protected $subtitle;

    /** @var string A string to be concatenated with $this->_Title if there is also a $this->_Subtitle string being concatenated. */
    protected $titleDivider;

    /** @var bool  */
    private $faviconSet = false;

    /** @var bool  */
    private $touchIconSet = false;

    /** @var bool  */
    private $mobileAddressBarColorSet = false;

    /** @var array JSON Linking Data */
    private $jsonLD = [];

    /** @var \Vanilla\Web\Asset\AssetPreloadModel */
    private $assetPreloadModel;

    /** @var Garden\EventManager */
    protected $eventManager;

    /** @var AbstractJsonLDItem */
    private $jsonLDItems = [];

    /**
     * HeadModule constructor.
     *
     * @param string $sender
     */
    public function __construct($sender = "")
    {
        $this->tags = [];
        $this->strings = [];
        $this->title = "";
        $this->subtitle = "";
        $this->titleDivider = " — ";
        parent::__construct($sender);
        // Workaround beacuse we can't do parameter injection.
        $this->assetPreloadModel = \Gdn::getContainer()->get(\Vanilla\Web\Asset\AssetPreloadModel::class);
        $this->eventManager = \Gdn::getContainer()->get(Garden\EventManager::class);
    }

    /**
     * Adds a "link" tag to the head containing a reference to a stylesheet.
     * By default a stylesheet is considered as a static-asset
     *
     * @param string $href Location of the stylesheet relative to the web root (if an absolute path with http:// is
     * provided, it will use the HRef as provided). ie. /themes/default/css/layout.css or http://url.com/layout.css
     * @param string $media Type media for the stylesheet. ie. "screen", "print", etc.
     * @param bool $addVersion Whether to append version number as query string.
     * @param array $options Additional properties to pass to AddTag, e.g. 'ie' => 'lt IE 7';
     */
    public function addCss($href, $media = "", $addVersion = true, $options = null)
    {
        $properties = [
            "rel" => "stylesheet",
            "href" => asset($href, false, $addVersion),
            "media" => $media,
            "static" => $options["static"] ?? true,
        ];

        // Use same underscore convention as AddScript
        if (is_array($options)) {
            foreach ($options as $key => $value) {
                $properties["_" . strtolower($key)] = $value;
            }
        }

        $this->addTag("link", $properties);
    }

    /**
     * Add an RSS feed link.
     *
     * @param string $href
     * @param string $title
     */
    public function addRss($href, $title)
    {
        $this->addTag("link", [
            "rel" => "alternate",
            "type" => "application/rss+xml",
            "title" => Gdn_Format::text($title),
            "href" => asset($href),
        ]);
    }

    /**
     * Adds a new tag to the head.
     *
     * @param string $tag The type of tag to add to the head. ie. "link", "script", "base", "meta".
     * @param array $properties An associative array of property => value pairs to be placed in the tag.
     * @param string|null $content an index to give the tag for later manipulation.
     * @param string|null $index
     */
    public function addTag($tag, $properties, $content = null, $index = null)
    {
        $tag = array_merge([self::TAG_KEY => strtolower($tag)], array_change_key_case($properties));
        if ($content) {
            $tag[self::CONTENT_KEY] = $content;
        }
        if (!array_key_exists(self::SORT_KEY, $tag)) {
            $tag[self::SORT_KEY] = count($this->tags);
        }

        if ($index !== null) {
            $this->tags[$index] = $tag;
        }

        // Make sure this item has not already been added.
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
    }

    /**
     * Adds a "script" tag to the head.
     * By default a script is considered as a static-asset
     *
     * @param string $src The location of the script relative to the web root. ie. "/js/jquery.js"
     * @param string $type The type of script being added. ie. "text/javascript"
     * @param bool $addVersion Whether to append version number as query string.
     * @param mixed $options Additional options to add to the tag. The following values are accepted:
     *  - numeric: This will be the script's sort.
     *  - string: This will hint the script (inline will inline the file in the page.
     *  - array: An array of options (ex. sort, hint, version).
     */
    public function addScript($src, $type = "text/javascript", $addVersion = true, $options = [])
    {
        if (is_numeric($options)) {
            $options = ["sort" => $options];
        } elseif (is_string($options)) {
            $options = ["hint" => $options];
        } elseif (!is_array($options)) {
            $options = [];
        }

        if (is_array($addVersion)) {
            $options = $addVersion;
            $addVersion = true;
        }

        $attributes = [];
        if ($src) {
            $attributes["src"] = asset($src, false, $addVersion);
            $attributes["static"] = $options["static"] ?? true;
        }
        if ($type !== "text/javascript") {
            // Not needed in HTML5
            $attributes["type"] = $type;
        }
        if (isset($options["defer"])) {
            $attributes["defer"] = $options["defer"];
        }
        if (isset($options["async"])) {
            $attributes["async"] = $options["async"];
        }
        if ($options["nomodule"] ?? false) {
            $attributes["nomodule"] = "nomodule";
        }

        foreach ($options as $key => $value) {
            $attributes["_" . strtolower($key)] = $value;
        }

        $this->addTag("script", $attributes);
    }

    /**
     * Adds a string to the collection of strings to be inserted into the head.
     *
     * @param string $string The string to be inserted.
     */
    public function addString($string)
    {
        $this->strings[] = $string;
    }

    /**
     * This module gets output in the head asset.
     *
     * @return string
     */
    public function assetTarget()
    {
        return "Head";
    }

    /**
     * Removes any added stylesheets from the head.
     */
    public function clearCSS()
    {
        $this->clearTag("link", ["rel" => "stylesheet"]);
    }

    /**
     * Removes any script include tags from the head.
     */
    public function clearScripts()
    {
        $this->clearTag("script");
    }

    /**
     * Removes any tags with the specified $tag, $property, and $value.
     *
     * Only $tag is required.
     *
     * @param string $tag The name of the tag to remove from the head.  ie. "link"
     * @param string $property Any property to search for in the tag.
     *    - If this is an array then it will be treated as a query of attribute/value pairs to match against.
     * @param string $value Any value to search for in the specified property.
     */
    public function clearTag($tag, $property = "", $value = "")
    {
        $tag = strtolower($tag);
        if (is_array($property)) {
            $query = array_change_key_case($property);
        } elseif ($property) {
            $query = [strtolower($property) => $value];
        } else {
            $query = false;
        }

        foreach ($this->tags as $index => $collection) {
            $tagName = $collection[self::TAG_KEY];

            if ($tagName == $tag) {
                // If no property is specified and the tag is found, remove it directly.
                // Otherwise remove it only if all specified property/value pairs match.
                if (!$query || count(array_intersect_assoc($query, $collection)) == count($query)) {
                    unset($this->tags[$index]);
                }
            }
        }
    }

    /**
     * Return all strings.
     */
    public function getStrings()
    {
        return $this->strings;
    }

    /**
     * Return all Tags of the specified type (or all tags).
     *
     * @param string $requestedType
     * @return array
     */
    public function getTags($requestedType = "")
    {
        // Make sure that css loads before js (for jquery)
        usort($this->tags, ["HeadModule", "TagCmp"]); // "link" comes before "script"

        if ($requestedType == "") {
            return $this->tags;
        }

        // Loop through each tag.
        $tags = [];
        foreach ($this->tags as $index => $attributes) {
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
     * @param string $href The location of the fav icon relative to the web root. ie. /themes/default/images/layout.css
     */
    public function setFavIcon($href)
    {
        if (!$this->faviconSet) {
            $this->faviconSet = true;
            $this->addTag(
                "link",
                [
                    "rel" => "shortcut icon",
                    "href" => $href,
                    "type" => "image/x-icon",
                ],
                null,
                "favicon"
            );
        }
    }

    /**
     * Sets the touch icon
     *
     * @param string $href The location of the fav icon relative to the web root. ie. /themes/default/images/layout.css
     */
    public function setTouchIcon($href)
    {
        if (!$this->touchIconSet) {
            $this->touchIconSet = true;
            $this->addTag("link", [
                "rel" => "apple-touch-icon-precomposed",
                "href" => $href,
            ]);
        }
    }

    /**
     * Sets browser address bar colour.
     *
     * @param string $mobileAddressBarColor Meta tags for various browsers.
     */
    public function setMobileAddressBarColor($mobileAddressBarColor)
    {
        if (!$this->mobileAddressBarColorSet && $mobileAddressBarColor) {
            $this->mobileAddressBarColorSet = true;
            $this->addTag("meta", [
                "name" => "theme-color",
                "content" => $mobileAddressBarColor,
            ]);
        }
    }

    /**
     * Gets or sets the tags collection.
     *
     * @param array|null $value
     * @return array
     */
    public function tags($value = null)
    {
        if ($value != null) {
            $this->tags = $value;
        }
        return $this->tags;
    }

    /**
     * Gets/sets the modules title.
     *
     * @param string $title
     * @param bool $noSubtitle
     * @return mixed|string
     */
    public function title($title = "", $noSubtitle = false)
    {
        if ($title != "") {
            // Apply $Title to $this->_Title and to $this->_Sender.
            $this->title = $title;
            $this->_Sender->title($title);
        } elseif ($this->title == "") {
            // Get Title from $this->_Sender if not supplied.
            $this->title = valr("Data.Title", $this->_Sender, "");
        }
        if ($noSubtitle) {
            return $this->title;
        } else {
            if ($this->subtitle == "") {
                // Get Subtitle from controller.
                $this->subtitle = valr("Data._Subtitle", $this->_Sender, c("Garden.Title"));
            }

            // Default Return title from controller's Data.Title + banner title;
            return concatSep($this->titleDivider, $this->title, $this->subtitle);
        }
    }

    /**
     * Sets the subtitle.
     *
     * @param string $subtitle The subtitle which should be displayed in the title.
     */
    public function setSubtitle($subtitle = "")
    {
        $this->subtitle = $subtitle;
    }

    /**
     * Sets the title divider.
     *
     * This is the string that is used to concatenate title and subtitle.
     * Defaults to ' — '.
     *
     * @param string $titleDivider The string that concats title and subtitle.
     */
    public function setTitleDivider($titleDivider = " — ")
    {
        $this->titleDivider = $titleDivider;
    }

    /**
     * Compare two tags in the tags array for sorting.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function tagCmp($a, $b)
    {
        if ($a[self::TAG_KEY] == "title") {
            return -1;
        }
        if ($b[self::TAG_KEY] == "title") {
            return 1;
        }
        $cmp = strcasecmp($a[self::TAG_KEY], $b[self::TAG_KEY]);
        if ($cmp == 0) {
            $sortA = val(self::SORT_KEY, $a, 0);
            $sortB = val(self::SORT_KEY, $b, 0);
            if ($sortA < $sortB) {
                $cmp = -1;
            } elseif ($sortA > $sortB) {
                $cmp = 1;
            }
        }

        return $cmp;
    }

    /**
     * Render the entire head module.
     */
    public function toString()
    {
        // Add the canonical Url if necessary.
        if (method_exists($this->_Sender, "CanonicalUrl") && !c("Garden.Modules.NoCanonicalUrl", false)) {
            $canonicalUrl = $this->_Sender->canonicalUrl();

            if (!empty($canonicalUrl) && !isUrl($canonicalUrl)) {
                $canonicalUrl = Gdn::router()->reverseRoute($canonicalUrl);
                $this->_Sender->canonicalUrl($canonicalUrl);
            }
            if ($canonicalUrl) {
                $this->addTag("link", ["rel" => "canonical", "href" => $canonicalUrl]);
            }
        }

        // Include facebook open-graph meta information.
        if ($fbAppID = c("Plugins.Facebook.ApplicationID")) {
            $this->addTag("meta", ["property" => "fb:app_id", "content" => $fbAppID]);
        }

        $siteName = c("Garden.Title", "");
        if ($siteName != "") {
            $this->addTag("meta", ["property" => "og:site_name", "content" => $siteName]);
        }

        $title = htmlEntityDecode(Gdn_Format::text($this->title("", true)));
        if ($title != "") {
            $this->addTag("meta", ["name" => "twitter:title", "property" => "og:title", "content" => $title]);
        }

        if (isset($canonicalUrl)) {
            $this->addTag("meta", ["property" => "og:url", "content" => $canonicalUrl]);
        }

        if ($description = trim(Gdn_Format::reduceWhiteSpaces($this->_Sender->description()))) {
            $senderClass = get_class($this->_Sender);
            if ($senderClass === "CategoriesController") {
                $description = t("Categories") . " - " . $description;
            }

            $this->addTag("meta", [
                "name" => "description",
                "property" => "og:description",
                "content" => $description,
            ]);
        }

        if ($robots = $this->_Sender->data("_robots")) {
            $this->addTag("meta", ["name" => "robots", "content" => $robots]);
        }

        $hasRelevantImage = false;

        // Default to the site logo if there were no images provided by the controller.
        if (count($this->_Sender->image()) == 0) {
            $logo = c("Garden.ShareImage", c("Garden.Logo", ""));
            if ($logo != "") {
                // Fix the logo path.
                if (stringBeginsWith($logo, "uploads/")) {
                    $logo = substr($logo, strlen("uploads/"));
                }

                $logo = Gdn_Upload::url($logo);
                $this->addTag("meta", ["property" => "og:image", "content" => $logo]);
            }
        } else {
            $img = $this->_Sender->image()[0];
            $this->addTag("meta", ["name" => "twitter:image", "property" => "og:image", "content" => $img]);
            $hasRelevantImage = true;
        }

        // For the moment at least, only discussions are supported.
        if ($title && val("DiscussionID", $this->_Sender)) {
            if ($hasRelevantImage) {
                $twitterCardType = "summary_large_image";
            } else {
                $twitterCardType = "summary";
            }

            // Let's force a description for the image card since it makes sense to see a card with only an image and a title.
            if (!$description && $twitterCardType === "summary_large_image") {
                $description = "...";
            }

            // Card && Title && Description are required
            if ($twitterCardType && $description) {
                $this->addTag("meta", ["name" => "twitter:description", "content" => $description]);
                $this->addTag("meta", ["name" => "twitter:card", "content" => $twitterCardType]);
            }
        }

        if ($this->jsonLD) {
            $this->addTag("script", ["type" => "application/ld+json"], json_encode($this->jsonLD));
        }

        if ($this->jsonLDItems) {
            $this->addTag("script", ["type" => "application/ld+json"], $this->getJsonLDScriptContent());
        }

        $this->fireEvent("BeforeToString");

        // Make sure that css loads before js (for jquery)
        usort($this->tags, ["HeadModule", "TagCmp"]); // "link" comes before "script"

        $this->eventManager->fireArray("HeadTagsBeforeRender", [&$this->tags]);

        // Start with the title.
        $head = "<title>" . Gdn_Format::text($this->title()) . "</title>\n";

        $tagStrings = [];
        // Loop through each tag.
        foreach ($this->tags as $index => $attributes) {
            $tag = $attributes[self::TAG_KEY];

            // Inline the content of the tag, if necessary.
            if (($attributes["_hint"] ?? false) == "inline") {
                $path = $attributes["_path"] ?? false;
                if ($path && !stringBeginsWith($path, "http")) {
                    $attributes[self::CONTENT_KEY] = file_get_contents($path);

                    if (isset($attributes["src"])) {
                        $attributes["_src"] = $attributes["src"];
                        unset($attributes["src"]);
                    }
                    if (isset($attributes["href"])) {
                        $attributes["_href"] = $attributes["href"];
                        unset($attributes["href"]);
                    }
                }
            }

            // If we set an IE conditional AND a "Not IE" condition, we will need to make a second pass.
            do {
                // Reset tag string
                $tagString = "";

                // IE conditional? Validates condition.
                $iESpecific = isset($attributes["_ie"]) && preg_match("/((l|g)t(e)? )?IE [0-9\.]/", $attributes["_ie"]);

                // Only allow $NotIE if we're not doing a conditional this loop.
                $notIE = !$iESpecific && isset($attributes["_notie"]);

                // Open IE conditional tag
                if ($iESpecific) {
                    $tagString .= "<!--[if " . $attributes["_ie"] . "]>";
                }
                if ($notIE) {
                    $tagString .= "<!--[if !IE]> -->";
                }

                // Build tag
                $tagString .= "  <" . $tag . attribute($attributes, "_");
                if (array_key_exists(self::CONTENT_KEY, $attributes)) {
                    $tagString .= ">" . $attributes[self::CONTENT_KEY] . "</" . $tag . ">";
                } elseif ($tag == "script") {
                    $tagString .= "></script>";
                } else {
                    $tagString .= " />";
                }

                // Close IE conditional tag
                if ($iESpecific) {
                    $tagString .= "<![endif]-->";
                }
                if ($notIE) {
                    $tagString .= "<!-- <![endif]-->";
                }

                // Cleanup (prevent infinite loop)
                if ($iESpecific) {
                    unset($attributes["_ie"]);
                }

                $tagStrings[] = $tagString;
            } while ($iESpecific && isset($attributes["_notie"])); // We need a second pass
        } //endforeach

        $head .= implode("\n", array_unique($tagStrings));

        foreach ($this->strings as $string) {
            $head .= $string;
            $head .= "\n";
        }

        // Add the HTML from the AssetPreloader
        $head .= "\n";
        $head .= "  <noscript><style>body {visibility: visible !important;}</style></noscript>";
        $head .= "\n";
        $head .= $this->assetPreloadModel->renderHtml();

        return $head;
    }

    /**
     * Get current JSON LD data.
     *
     * @return array
     */
    public function getJsonLD(): array
    {
        return $this->jsonLD;
    }

    /**
     * Set JSON LD data.
     *
     * @param string $type Document type.
     * @param array $data Metadata attributes for the document.
     * @param string $context Metadata schema context.
     * @return array
     * @link https://json-ld.org
     * @deprecated Use addJsonLDItem instead.
     */
    public function setJsonLD(string $type, array $data, string $context = "https://schema.org"): array
    {
        $data["@context"] = $context;
        $data["@type"] = $type;
        return $this->jsonLD = $data;
    }

    /**
     * Add JSON LD item.
     *
     * @param AbstractJsonLDItem $item
     * @link https://json-ld.org
     */
    public function addJsonLDItem(AbstractJsonLDItem $item): void
    {
        $this->jsonLDItems[] = $item;
    }

    /**
     * @return AbstractJsonLDItem
     */
    public function getJsonLDItems()
    {
        return $this->jsonLDItems;
    }

    /**
     * Get the content of the page's JSON-LD script.
     *
     * @see PageHead::getJsonLDScriptContent()
     * @return string
     */
    private function getJsonLDScriptContent(): string
    {
        $data = [
            "@context" => "https://schema.org",
            "@graph" => $this->jsonLDItems,
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
