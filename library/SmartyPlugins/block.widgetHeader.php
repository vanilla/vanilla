<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 *
 *
 * @param array $params The parameters passed into the function.
 * @param string $content
 * @param object $smarty The smarty object rendering the template.
 * @param bool $repeat
 * @return string The url.
 */
function smarty_block_widgetHeader($params, $content, &$smarty, &$repeat) {
    if (!$repeat){
        $class = '_widget-header '.trim(val('class', $params, ''));
        $title = val('title', $params);
        $headingLevel = val('heading', $params, "h2");

        if ($title) {
            $headerContent = "<$headingLevel class='_widget-title'>$title</$headingLevel>";
        } else {
            $headerContent = $content;
        }
        return <<<EOT
        <div class="$class">
            <div class="_widget-contents">
                $headerContent
            </div>
        </div>
EOT;
    }
}

