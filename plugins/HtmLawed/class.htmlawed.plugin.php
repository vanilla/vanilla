<?php
/**
 * HtmLawed Plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package HtmLawed
 */

$PluginInfo['HtmLawed'] = array(
    'Description' => 'Adapts HtmLawed to work with Vanilla.',
    'Version' => '1.1.1',
    'Author' => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com/profile/todd',
    'Hidden' => true
);

Gdn::factoryInstall('HtmlFormatter', 'HTMLawedPlugin', __FILE__, Gdn::FactorySingleton);

/**
 * Class HTMLawedPlugin
 */
class HTMLawedPlugin extends Gdn_Plugin {

    /**
     *
     */
    public function __construct() {
        require_once(dirname(__FILE__).'/htmLawed/htmLawed.php');

        /** @var bool Whether SafeStyles is enabled. Turning this off is bad mojo. */
        $this->SafeStyles = c('Garden.Html.SafeStyles');

        /** @var array HTML elements allowed to have classes in user generated content. */
        $this->ClassedElements = array('a', 'span', 'div', 'p', 'li', 'ul', 'ol', 'dl', 'dd', 'dt', 'i', 'b', 'strong', 'em', 'code', 'blockquote', 'img', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6');

        /** @var array Classes users may have in their content. */
        $this->AllowedClasses = array(
            "post-clear-both",
            "post-clear-left",
            "post-clear-right",
            "post-color-aqua",
            "post-highlightcolor-aqua",
            "post-color-black",
            "post-highlightcolor-black",
            "post-color-blue",
            "post-highlightcolor-blue",
            "post-color-fuchsia",
            "post-highlightcolor-fuchsia",
            "post-color-gray",
            "post-highlightcolor-gray",
            "post-color-green",
            "post-highlightcolor-green",
            "post-color-lime",
            "post-highlightcolor-lime",
            "post-color-maroon",
            "post-highlightcolor-maroon",
            "post-color-navy",
            "post-highlightcolor-navy",
            "post-color-olive",
            "post-highlightcolor-olive",
            "post-color-purple",
            "post-highlightcolor-purple",
            "post-color-red",
            "post-highlightcolor-red",
            "post-color-silver",
            "post-highlightcolor-silver",
            "post-color-teal",
            "post-highlightcolor-teal",
            "post-color-white",
            "post-highlightcolor-white",
            "post-color-yellow",
            "post-highlightcolor-yellow",
            "post-color-orange",
            "post-highlightcolor-orange",
            "post-float-left",
            "post-float-right",
            "post-font-size-large",
            "post-font-size-larger",
            "post-font-size-medium",
            "post-font-size-small",
            "post-font-size-smaller",
            "post-font-size-x-large",
            "post-font-size-x-small",
            "post-font-size-xx-large",
            "post-font-size-xx-small",
            "post-text-align-center",
            "post-text-align-justify",
            "post-text-align-left",
            "post-text-align-right",
            "post-fontfamily-default",
            "post-fontfamily-arial",
            "post-fontfamily-comicsansms",
            "post-fontfamily-couriernew",
            "post-fontfamily-georgia",
            "post-fontfamily-impact",
            "post-fontfamily-timesnewroman",
            "post-fontfamily-trebuchetms",
            "post-fontfamily-verdana",
            "post-text-decoration-line-through",
            "post-font-size-h1",
            "post-font-size-h2",
            "P",
            "Spoiler",
            "Spoiled",
            "UserSpoiler",
            "SpoilerTitle",
            "SpoilerText",
            "Quote",
            "QuoteAuthor",
            "QuoteText",
            "QuoteLink",
            "UserQuote",
            "CodeBlock",
            "CodeInline",
            "AlignRight",
            "AlignLeft",
            "AlignCenter",
        );
    }

    /** @var bool  */
    public $SafeStyles = true;

    /**
     *
     *
     * @param $Html
     * @return mixed|string
     */
    public function format($Html) {
        $Attributes = c('Garden.Html.BlockedAttributes', 'on*');
        $Config = array(
            'anti_link_spam' => array('`.`', ''),
            'comment' => 1,
            'cdata' => 3,
            'css_expression' => 1,
            'deny_attribute' => $Attributes,
            'unique_ids' => 1,
            'elements' => '*-applet-form-input-textarea-iframe-script-style-embed-object-select-option-button-fieldset-optgroup-legend',
            'keep_bad' => 0,
            'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
            'valid_xhtml' => 0,
            'direct_list_nest' => 1,
            'balance' => 1
        );

        // Turn embedded videos into simple links (legacy workaround)
        $Html = Gdn_Format::unembedContent($Html);

        // We check the flag within Gdn_Format to see
        // if htmLawed should place rel="nofollow" links
        // within output or not.
        // A plugin can set this flag (for example).
        // The default is to show rel="nofollow" on all links.
        if (Gdn_Format::$DisplayNoFollow) {
            // display rel="nofollow" on all links.
            $Config['anti_link_spam'] = array('`.`', '');
        } else {
            // never display rel="nofollow"
            $Config['anti_link_spam'] = array('', '');
        }


        if ($this->SafeStyles) {
            // Deny all class and style attributes.
            // A lot of damage can be done by hackers with these attributes.
            $Config['deny_attribute'] .= ',style,class';
//      } else {
//         $Config['hook_tag'] = 'HTMLawedHookTag';
        }

        // Block some IDs so you can't break Javascript
        $GLOBALS['hl_Ids'] = array(
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
            'Status' => 1,
        );

        $Spec = 'object=-classid-type, -codebase; embed=type(oneof=application/x-shockwave-flash); ';
        //$Spec .= 'a=class(noneof=Hijack|Dismiss|MorePager/nomatch=%pop[in|up|down]|flyout|ajax%i); ';

        // Define elements allowed to have a `class`.
        $Spec .= implode(',', $this->ClassedElements);
        // Whitelist classes we allow.
        $Spec .= '=class(oneof='.implode('|', $this->AllowedClasses).'); ';

        $Result = htmLawed($Html, $Config, $Spec);

        return $Result;
    }

    /**
     * No setup.
     */
    public function setup() {
    }
}

if (!function_exists('FormatRssCustom')) :
    /**
     *
     *
     * @param $Html
     * @return mixed|string
     */
    function formatRssHtmlCustom($Html) {
        require_once(dirname(__FILE__).'/htmLawed/htmLawed.php');

        $Config = array(
            'anti_link_spam' => array('`.`', ''),
            'comment' => 1,
            'cdata' => 3,
            'css_expression' => 1,
            'deny_attribute' => 'on*,style,class',
            'elements' => '*-applet-form-input-textarea-iframe-script-style-object-embed-comment-link-listing-meta-noscript-plaintext-xmp',
            'keep_bad' => 0,
            'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
            'valid_xml' => 2,
            'anti_link_spam' => array('`.`', '')
        );

        $Spec = 'object=-classid-type, -codebase; embed=type(oneof=application/x-shockwave-flash)';

        $Result = htmLawed($Html, $Config, $Spec);

        return $Result;
    }
endif;

/**
 *
 *
 * @param $Element
 * @param int $Attributes
 * @return string
 */
function htmlawedHookTag($Element, $Attributes = 0) {
    // If second argument is not received, it means a closing tag is being handled
    if ($Attributes === 0) {
        return "</$Element>";
    }

    $Attribs = '';
    foreach ($Attributes as $Key => $Value) {
        if (strcasecmp($Key, 'style') == 0) {
            if (strpos($Value, 'position') !== false || strpos($Value, 'z-index') !== false || strpos($Value, 'opacity') !== false) {
                continue;
            }
        }

        $Attribs .= " {$Key}=\"{$Value}\"";
    }

    static $empty_elements = array('area' => 1, 'br' => 1, 'col' => 1, 'embed' => 1, 'hr' => 1, 'img' => 1, 'input' => 1, 'isindex' => 1, 'param' => 1);

    return "<{$Element}{$Attribs}".(isset($empty_elements[$Element]) ? ' /' : '').'>';
}
