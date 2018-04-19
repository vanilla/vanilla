<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use \Garden\EventManager;

/**
 * Class VanillaHtmlFormatter
 */
class VanillaHtmlFormatter {

    /** @var array Classes users may have in their content. */
    protected $allowedClasses = [
        'AlignCenter',
        'AlignLeft',
        'AlignRight',
        'bbcode_center',
        'bbcode_left',
        'bbcode_right',
        'CodeBlock',
        'CodeInline',
        'P',
        'post-clear-both',
        'post-clear-left',
        'post-clear-right',
        'post-color-aqua',
        'post-color-black',
        'post-color-blue',
        'post-color-fuchsia',
        'post-color-gray',
        'post-color-green',
        'post-color-lime',
        'post-color-maroon',
        'post-color-navy',
        'post-color-olive',
        'post-color-orange',
        'post-color-purple',
        'post-color-red',
        'post-color-silver',
        'post-color-teal',
        'post-color-white',
        'post-color-yellow',
        'post-float-left',
        'post-float-right',
        'post-font-size-h1',
        'post-font-size-h2',
        'post-font-size-large',
        'post-font-size-larger',
        'post-font-size-medium',
        'post-font-size-small',
        'post-font-size-smaller',
        'post-font-size-x-large',
        'post-font-size-x-small',
        'post-font-size-xx-large',
        'post-font-size-xx-small',
        'post-fontfamily-arial',
        'post-fontfamily-comicsansms',
        'post-fontfamily-couriernew',
        'post-fontfamily-default',
        'post-fontfamily-georgia',
        'post-fontfamily-impact',
        'post-fontfamily-timesnewroman',
        'post-fontfamily-trebuchetms',
        'post-fontfamily-verdana',
        'post-highlightcolor-aqua',
        'post-highlightcolor-black',
        'post-highlightcolor-blue',
        'post-highlightcolor-fuchsia',
        'post-highlightcolor-gray',
        'post-highlightcolor-green',
        'post-highlightcolor-lime',
        'post-highlightcolor-maroon',
        'post-highlightcolor-navy',
        'post-highlightcolor-olive',
        'post-highlightcolor-orange',
        'post-highlightcolor-purple',
        'post-highlightcolor-red',
        'post-highlightcolor-silver',
        'post-highlightcolor-teal',
        'post-highlightcolor-white',
        'post-highlightcolor-yellow',
        'post-text-align-center',
        'post-text-align-justify',
        'post-text-align-left',
        'post-text-align-right',
        'post-text-decoration-line-through',
        'Quote',
        'QuoteAuthor',
        'QuoteLink',
        'QuoteText',
        'Spoiled',
        'Spoiler',
        'SpoilerText',
        'SpoilerTitle',
        'UserQuote',
        'UserSpoiler'
    ];

    /** @var array HTML elements allowed to have classes in user generated content. */
    protected $classedElements = [
        'a',
        'b',
        'blockquote',
        'code',
        'dd',
        'div',
        'dl',
        'dt',
        'em',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'i',
        'img',
        'li',
        'ol',
        'p',
        'pre',
        'span',
        'strong',
        'ul',
        'table',
        'thead',
        'tbody',
        'tr',
        'th',
        'td',
        'tfoot',
        'caption',
        'col',
        'colgroup'
    ];

    /** @var array Extra allowed classes. */
    protected $extraAllowedClasses = [];

    /**
     * Filter provided HTML through htmlLawed and return the result.
     *
     * @param string $html String of HTML to filter.
     * @param array $options An array of options. The "spec" key is used for extra HTML specifications.
     * @return string Returns the filtered HTML.
     */
    public function format($html, $options = []) {
        $attributes = c('Garden.Html.BlockedAttributes', 'on*, target, download');

        $specOverrides = val('spec', $options, []);
        if (!is_array($specOverrides)) {
            $specOverrides = [];
        }

        $config = [
            'anti_link_spam' => ['`.`', ''],
            'balance' => 1,
            'cdata' => 0,
            'comment' => 1,
            'css_expression' => 1,
            'deny_attribute' => $attributes,
            'direct_list_nest' => 1,
            'elements' => '*-applet-button-embed-fieldset-form-iframe-input-legend-link-object-optgroup-option-script-select-style-textarea',
            'keep_bad' => 0,
            'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
            'unique_ids' => 1,
            'valid_xhtml' => 0
        ];

        // If we don't allow URL embeds, don't allow HTML media embeds, either.
        if (c('Garden.Format.DisableUrlEmbeds')) {
            if (!array_key_exists('elements', $config) || !is_string($config['elements'])) {
                $config['elements'] = '';
            }
            $config['elements'] .= '-audio-video';
        }

        // Turn embedded videos into simple links (legacy workaround)
        $html = Gdn_Format::unembedContent($html);

        // We check the flag within Gdn_Format to see
        // if htmLawed should place rel="nofollow" links
        // within output or not.
        // A plugin can set this flag (for example).
        // The default is to show rel="nofollow" on all links.
        if (Gdn_Format::$DisplayNoFollow) {
            // display rel="nofollow" on all links.
            $config['anti_link_spam'] = ['`.`', ''];
        } else {
            // never display rel="nofollow"
            $config['anti_link_spam'] = ['', ''];
        }

        // Deny all class and style attributes.
        // A lot of damage can be done by hackers with these attributes.
        $config['deny_attribute'] .= ',style,class';

        // Block some IDs so you can't break Javascript
        $GLOBALS['hl_Ids'] = [
            'Bookmarks' => 1,
            'CommentForm' => 1,
            'Content' => 1,
            'Definitions' => 1,
            'DiscussionForm' => 1,
            'Foot' => 1,
            'Form_Comment' => 1,
            'Form_User_Password' => 1,
            'Form_User_SignIn' => 1,
            'Head' => 1,
            'HighlightColor' => 1,
            'InformMessageStack' => 1,
            'Menu' => 1,
            'PagerMore' => 1,
            'Panel' => 1,
            'Status' => 1
        ];

        $spec = $this->spec();
        if (is_array($specOverrides) && !empty($specOverrides)) {
            $spec = array_merge_recursive($spec, $specOverrides);
        }

        return Htmlawed::filter($html, $config, $spec);
    }

    /**
     * Add extra allowed classes.
     *
     * @param array $extraAllowedClasses
     */
    public function addExtraAllowedClasses($extraAllowedClasses) {
        $this->extraAllowedClasses = array_unique(array_merge($this->extraAllowedClasses, $extraAllowedClasses));
    }

    /**
     * Get the currently defined extra allowed classes.
     *
     * @return array Extra allowed classes
     */
    public function getExtraAllowedClasses() {
        return $this->extraAllowedClasses;
    }

    /**
     * Grab the default htmLawed spec.
     *
     * @return array
     */
    private function spec() {
        static $spec;
        if ($spec === null) {
            $spec = [];
            $allowedClasses = implode('|', array_merge($this->allowedClasses, $this->extraAllowedClasses));
            foreach ($this->classedElements as $tag) {
                if (!array_key_exists($tag, $spec) || !is_array($spec[$tag])) {
                    $spec[$tag] = [];
                }
                if (!array_key_exists('class', $spec[$tag])) {
                    $spec[$tag]['class'] = [];
                }
                $spec[$tag]['class']['oneof'] = $allowedClasses;
            }
        }
        return $spec;
    }
}
