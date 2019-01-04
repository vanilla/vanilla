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
function smarty_block_container($params, $content, &$smarty, &$repeat) {
    if (!$repeat){
        $class = trim('_container '.trim(val('class', $params, '')));
        $selfPadded = val('selfPadded', $params, false);
        $id = val('id', $params, false);
        $idString = $id ? "id=\"$id\"" : "";

        if ($selfPadded) {
            $class .= " _hasPaddedContent";
        }

        return <<<EOT
        <div $idString class="$class">
            $content
        </div>
EOT;
    }
}
