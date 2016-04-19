<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Writes the search box to the page.
 *
 * @param array The parameters passed into the function. This currently takes no parameters.
 * @param $smarty The smarty object rendering the template.
 * @return The url.
 */
function smarty_function_searchbox($params, &$smarty) {
    $placeholder = array_key_exists('placeholder', $params) ? val('placeholder', $params, '', true) : t('SearchBoxPlaceHolder', 'Search');
    $form = Gdn::factory('Form');
    $result =
        $form->open(array('action' => url('/search'), 'method' => 'get')).
        $form->textBox('Search', array('placeholder' => $placeholder, 'accesskey' => '/')).
        $form->button('Go', array('Name' => '')).
        $form->close();

    return $result;
}
