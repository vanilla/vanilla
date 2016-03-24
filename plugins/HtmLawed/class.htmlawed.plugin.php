<?php
/**
 * HtmLawed Plugin.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package HtmLawed
 */

$PluginInfo['HtmLawed'] = [
    'Description' => 'Adapts HtmLawed to work with Vanilla.',
    'Version' => '1.2',
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com/profile/todd',
    'Hidden' => true
];

Gdn::factoryInstall('HtmlFormatter', 'HtmLawedPlugin', __FILE__, Gdn::FactorySingleton);

/**
 * Class HTMLawedPlugin
 */
class HtmLawedPlugin extends Gdn_Plugin {

    /** @var array Classes users may have in their content. */
    protected $allowedClasses = [
        'AlignCenter',
        'AlignLeft',
        'AlignRight',
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
        'ul'
    ];

    /** @var bool Whether SafeStyles is enabled. Turning this off is bad mojo. */
    protected $safeStyles = true;

    /**
     * The public constructor of the class.
     */
    public function __construct() {
        $this->safeStyles = c('Garden.Html.SafeStyles');
        parent::__construct();
    }

    /**
     * Filter provided HTML through htmlLawed and return the result.
     *
     * @param string $html String of HTML to filter.
     * @return string Returns the filtered HTML.
     */
    public function format($html) {
        $attributes = c('Garden.Html.BlockedAttributes', 'on*');

        $config = [
            'anti_link_spam' => ['`.`', ''],
            'balance' => 1,
            'cdata' => 3,
            'comment' => 1,
            'css_expression' => 1,
            'deny_attribute' => $attributes,
            'direct_list_nest' => 1,
            'elements' => '*-applet-form-input-textarea-iframe-script-style-embed-object-select-option-button-fieldset-optgroup-legend',
            'keep_bad' => 0,
            'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
            'unique_ids' => 1,
            'valid_xhtml' => 0
        ];

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

        if ($this->safeStyles) {
            // Deny all class and style attributes.
            // A lot of damage can be done by hackers with these attributes.
            $config['deny_attribute'] .= ',style,class';
        }

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

        $spec = 'object=-classid-type, -codebase; embed=type(oneof=application/x-shockwave-flash); ';

        // Define elements allowed to have a `class`.
        $spec .= implode(',', $this->classedElements);

        // Whitelist classes we allow.
        $spec .= '=class(oneof='.implode('|', $this->allowedClasses).'); ';

        return Htmlawed::filter($html, $config, $spec);
    }

    /**
     * No setup.
     */
    public function setup() {
    }
}

if (!function_exists('FormatRssCustom')) :
    /**
     * @param string $html
     * @return string Returns the filtered RSS.
     */
    function formatRssHtmlCustom($html) {
        return Htmlawed::filterRSS($html);
    }
endif;
