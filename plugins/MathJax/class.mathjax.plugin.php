<?php

/**
 * @copyright 2010-2014 Vanilla Forums Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

$PluginInfo['MathJax'] = array(
    'Description' => 'This plugin enables MathJax syntax in discussions and comments.',
    'Version' => '1.1',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'MobileFriendly' => TRUE,
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

/**
 * MathJax Plugin
 *
 * This plugin allows the forum to parse MathJax syntax to support rendering of complex mathematical formulas
 * in discussions and comments.
 *
 * Currently, MathJax version 2.4 is supported.
 *
 * Changes:
 *  1.0     Initial release
 *  1.1     Support previews
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package addons
 */
class MathJaxPlugin extends Gdn_Plugin {

    /**
     * Insert MathJax javascript into discussion pages
     *
     * @param DiscussionController $sender
     */
    public function DiscussionController_Render_Before(&$sender) {

        // Add basic MathJax configuration
        $mathJaxConfig = <<<MATHJAX
<script type="text/x-mathjax-config">
    MathJax.Hub.Config({
        jax: ["input/TeX","output/HTML-CSS"],
        extensions: ["tex2jax.js","MathMenu.js","MathZoom.js"],
        TeX: {
            extensions: ["AMSmath.js","AMSsymbols.js","noErrors.js","noUndefined.js"]
        },
        tex2jax: {
            inlineMath: [ ['$\(','\)$'] ],
            displayMath: [ ['$$\(','\)$$'] ],
            processEscapes: true
        },
        "HTML-CSS": { availableFonts: ["TeX"] },
        messageStyle: "none",
        showProcessingMessages: false
    });
</script>
MATHJAX;
        $sender->Head->addString($mathJaxConfig);

        $sender->addJsFile("http://cdn.mathjax.org/mathjax/2.4-latest/MathJax.js?delayStartupUntil=onload");
        $sender->addJsFile("live.js", "plugins/MathJax");
    }

}
