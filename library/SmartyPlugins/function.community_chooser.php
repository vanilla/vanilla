<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Create a subcommunity chooser placeholder.
 *
 * If subcommunities is enabled, this will be overwritten by the chooser.
 *
 * @param array $params The options for the component if it exists.
 * - buttonType: 'primary', 'standard', 'titleBarLink', 'transparent', 'transluscent'
 * - fullWidth: Whether or not the chooser button shoudl take the full width of it's container.
 *
 * @return string Some HTML.
 */
function smarty_function_community_chooser(array $params): string {
    $props = json_encode($params);
    return "<span data-react='subcommunity-chooser' data-props='$props'></span>";
}
