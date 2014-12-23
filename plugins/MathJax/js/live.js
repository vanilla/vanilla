
/*
 * MathJax Plugin: live js
 *
 * This javascript ensures that newly added content is immediately "jaxed" when
 * loaded via dom manipulation.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2010-2014 Vanilla Forums Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package addons
 */

jQuery(document).on('CommentAdded', function () {
    MathJax.Hub.Queue(["Typeset", MathJax.Hub]);
});

jQuery('form').on('PreviewLoaded', function() {
    MathJax.Hub.Queue(["Typeset", MathJax.Hub]);
});