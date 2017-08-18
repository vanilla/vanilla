<?php if (!defined('APPLICATION')) exit();

/**
 * A module for rendering a media item in the Vanilla Dashboard.
 *
 * A media item usually renders a item with an image, a title, and a description. This modules supports a media item
 * that may have one image, one dropdown menu, any number of buttons, and any number of meta items.
 *
 * There are three views that you can choose to render: media-addon, media-callout, or media-sm. See these variants in
 * the dashboard styleguide.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2016 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */
class MediaItemModule extends Gdn_Module {

    /** @var string The media item title. */
    private $title = '';

    /** @var string The url for the media item title if it's an anchor. */
    private $titleUrl = '';

    /** @var string The url for documentation on the plugin. */
    private $documentationUrl = '';

    /** @var string The description for the media item. */
    private $description = '';

    /** @var array An array of HTML-formatted meta items for the media item. */
    private $meta = [];

    /**
     * Have some extra info to pass to the view and don't know where to put it? Add it here!
     *
     * @var array An array of options to add to a view.
     */
    private $options = [];

    /**
     * A button array has the following keys: text, url, icon, badge, class
     *
     * @var array An array of arrays representing buttons.
     */
    private $buttons = [];

    private $toggle = [];

    /** @var DropdownModule An optional dropdown menu. */
    private $dropdown = null;

    /** @var string The top-level HTML element for the Media Item. */
    private $tag = 'div';

    /** @var array The top-level attributes for the Media Item. */
    private $attributes = [];

    /**
     * This holds an arbitrary amount CSS classes with undefined keys. It's up to the view to implement them.
     *
     * @var array An array of CSS classes to be added to the view.
     */
    private $cssClasses = [];

    /** @var string The url to the image source. */
    private $imageSource = '';

    /** @var string The link url for the image if it's an anchor. */
    private $imageUrl = '';

    /** @var string The CSS class for the image. */
    private $imageCssClass = '';

    /** @var string The image alt. */
    private $imageAlt = '';

    /** @var string The view to render. Supported views are media-addon, media-callout, or media-sm. */
    public $view = 'media-addon';

    /**
     * MediaItemModule constructor.
     *
     * @param string $title The media item heading.
     * @param string $titleUrl If the heading is an anchor, the anchor url (the view handles url()-ing).
     * @param string $description The media item description text.
     * @param string $tag The root-level tag of the media item, usually a div or li.
     * @param array $attributes The root-level attributes for the Media Item.
     */
    public function __construct($title = '', $titleUrl = '', $description = '', $tag = 'div', $attributes = []) {
        parent::__construct();

        $this->description = $description;
        $this->title = $title;
        if ($titleUrl) {
            $this->titleUrl = $titleUrl;
        }
        $this->tag = $tag;
        $this->attributes = $attributes;
    }

    /**
     * @param string $view
     * @return MediaItemModule $this
     */
    public function setView($view) {
        $class = val('class', $this->attributes, '');
        $class .= ' media '.$view;
        $this->attributes['class'] = $class;

        return parent::setView($view);
    }

    /**
     * @return string
     */
    public function getImageSource() {
        return $this->imageSource;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $description
     * @return MediaItemModule $this
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     * @return MediaItemModule $this
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitleUrl() {
        return $this->titleUrl;
    }

    /**
     * @param string $titleUrl Url for title, the view handles url()-ing.
     * @return MediaItemModule $this
     */
    public function setTitleUrl($titleUrl) {
        $this->titleUrl = $titleUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentationLink() {
        $docLink = "";
        if (!empty($this->documentationUrl)) {
            $docLink = anchor("<svg alt=\"" . t('page') . "\" class=\"icon icon-12 icon-page\" viewBox=\"0 0 5 6\"><use xlink:href=\"#page\"></use></svg>", $this->documentationUrl, 'documentationLink', ['target' => '_blank', 'title' => $this->title . ' ' . t('Documentation')]);
        }
        return $docLink;
    }

    /**
     * @param string $documentationUrl Url for documentation
     * @return MediaItemModule $this
     */
    public function setDocumentationUrl($documentationUrl) {
        $this->documentationUrl = $documentationUrl;
        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     * @return MediaItemModule $this
     */
    public function setAttributes($attributes) {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * THIS REPLACES THE META ARRAY
     * If you want to add a meta item, use the addMeta method.
     *
     * @param array $meta
     * @return MediaItemModule $this
     */
    public function setMeta($meta) {
        $this->meta = $meta;
        return $this;
    }

    /**
     * @return array
     */
    public function getMeta() {
        return $this->meta;
    }

    /**
     * Adds a HTML-formatted string to the meta array. It's up to the view to concatinate this array,
     * using something like implode(' | ', $meta)
     *
     * THIS ADDS AN ITEM TO THE META ARRAY
     *
     * @param string $meta An HTML-formatted string.
     * @return MediaItemModule $this
     */
    public function addMeta($meta) {
        $this->meta[] = $meta;
        return $this;
    }

    /**
     * Adds a HTML-formatted string to the meta array if it satisfies the $isAllowed condition.
     * It's up to the view to concatinate the contents of this array, using something like implode(' | ', $meta)
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $meta An HTML-formatted string.
     * @return MediaItemModule $this
     */
    public function addMetaIf($isAllowed, $meta) {
        if (!$this->allowed($isAllowed)) {
            return $this;
        }
        return $this->addMeta($meta);
    }

    /**
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * Adds a HTML-formatted string to the options array. It's up to the view to add the info in the options.
     *
     * @param string $key A key to access the data.
     * @param string $value An HTML-formatted string.
     * @return MediaItemModule $this
     */
    public function addOption($key, $value) {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Adds a HTML-formatted string to the meta array if it satisfies the $isAllowed condition.
     * It's up to the view to concatinate the contents of this array, using something like implode(' | ', $meta)
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $key A key to access the data.
     * @param string $value An HTML-formatted string.
     * @return MediaItemModule $this
     */
    public function addOptionIf($isAllowed, $key, $value) {
        if (!$this->allowed($isAllowed)) {
            return $this;
        }
        return $this->addOption($key, $value);
    }

    /**
     * @return array
     */
    public function getButtons() {
        return $this->buttons;
    }

    /**
     * @return DropdownModule
     */
    public function getDropdown() {
        return $this->dropdown;
    }

    /**
     * @param DropdownModule $dropdown
     * @return MediaItemModule $this
     */
    public function setDropdown($dropdown) {
        if (is_a($dropdown, 'DropdownModule')) {
            $this->dropdown = $dropdown;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getToggle() {
        return $this->toggle;
    }

    /**
     * Adds a css class to the cssClass array. This is useful for adding an arbitrary css class to a media item view.
     *
     * @param $key
     * @param $class
     * @return MediaItemModule $this
     */
    public function addCssClass($key, $class) {
        $this->cssClasses[$key] = $class;
        return $this;
    }

    /**
     * @return array The css classes.
     */
    public function getCssClasses() {
        return $this->cssClasses;
    }



    /**
     * Renders the html for an image with the image* properties.
     *
     * @return string The html for an image.
     */
    public function getImageHtml() {
        if (empty($this->imageSource)) {
            return '';
        }

        $attr = [];

        if (!empty($this->imageAlt)) {
            $attr['alt'] = $this->imageAlt;
        }

        if (!empty($this->imageCssClass)) {
            $attr['class'] = $this->imageCssClass;
        }

        $imgHtml = img($this->imageSource, $attr);

        if (!empty($this->imageUrl)) {
            $imgHtml = anchor($imgHtml, $this->imageUrl);
        }

        return $imgHtml;
    }

    /**
     * Sets the image. The function DOES NOT handle the url()-ing.
     *
     * @param string $source The image source url. The function DOES NOT handle the url()-ing.
     * @param string $url The image anchor link, if the image is an anchor. The function DOES NOT handle the url()-ing.
     * @param string $cssClass The CSS class for the image.
     * @param string $alt The image alt.
     * @return MediaItemModule $this
     */
    public function setImage($source = '', $url = '', $cssClass = '', $alt = '') {
        $this->imageSource = $source;
        $this->imageUrl = $url;
        $this->imageCssClass = $cssClass;
        $this->imageAlt = $alt;

        return $this;
    }

    /**
     * Sets the image if it satisfies the $isAllowed condition. The function DOES NOT handle the url()-ing.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $source The image source url. The function DOES NOT handle the url()-ing.
     * @param string $url The image anchor link, if the image is an anchor. The function DOES NOT handle the url()-ing.
     * @param string $cssClass The CSS class for the image.
     * @param string $alt The image alt.
     * @return MediaItemModule $this
     */
    public function setImageIf($isAllowed, $source = '', $url = '', $cssClass = '', $alt = '') {
        if (!$this->allowed($isAllowed)) {
            return $this;
        }
        return $this->setImage($source, $url, $cssClass, $alt);
    }

    /**
     * Adds a button to the buttons array.
     *
     * @param string $text The text to display on the button.
     * @param string $url The url of the button.
     * @param $attributes The button attributes.
     * @return MediaItemModule $this
     */
    public function addButton($text, $url, $attributes) {
        if (is_string($attributes)) {
            $attr = ['class' => $attributes];
        } elseif (is_array($attributes)) {
            $attr = $attributes;
        } else {
            $attr = [];
        }

        if (!isset($attr['class'])) {
            $attr['class'] = 'btn btn-secondary';
        }

        $button = [
            'text' => $text,
            'url' => $url,
            'attributes' => $attr
        ];
        $this->buttons[] = $button;

        return $this;
    }

    /**
     * Adds a button to the buttons array if it satisfies the $isAllowed condition.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $text The text to display on the button.
     * @param string $url The url of the button.
     * @param $attributes The button attributes.
     * @return MediaItemModule $this
     */
    public function addButtonIf($isAllowed, $text, $url, $attributes) {
        if (!$this->allowed($isAllowed)) {
            return $this;
        }
        return $this->addButton($text, $url, $attributes);
    }

    /**
     * Sets up a anchor-style toggle.
     *
     * @param string $key The toggle key/slug
     * @param bool $enabled Whether the toggle is enabled.
     * @param string $url The endpoint the toggle hits.
     * @param string $label The aria-label for the toggle.
     * @param string $cssClass The toggle css class.
     * @param string $anchorCssClass The css class for the anchor. Should probably have 'Hijack' in there.
     */
    public function setToggle($key, $enabled, $url, $label, $cssClass = '', $anchorCssClass = 'Hijack') {
        $state = $enabled ? 'on' : 'off';
        $this->toggle = [
            'key' => $key,
            'state' => $state,
            'url' => $url,
            'label' => $label,
            'cssClass' => $cssClass,
            'anchorCssClass' => $anchorCssClass
        ];
    }

    /**
     * Rendering helper for the toggle.
     *
     * @return string An HTML-formatted string for a toggle.
     */
    public function getToggleHtml() {
        $slider = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>',
            val('url', $this->toggle), val('anchorCssClass', $this->toggle),
            ['aria-label' => val('label', $this->toggle)]),
            'span', ['class' => 'toggle-wrap toggle-wrap-'.val('state', $this->toggle)]);
        $toggle = wrap($slider, 'div',
            ['class' => val('cssClass', $this->toggle), 'id' => strtolower(val('key', $this->toggle)).'-toggle']);
        return $toggle;
    }

    /**
     * Prepares the media item for rendering.
     *
     * @return bool Whether to render the module.
     */
    public function prepare(){
        if ($this->dropdown !== null) {
            if (!$this->dropdown->prepare()) {
                unset($this->dropdown);
            }
        }

        return true;
    }
}
