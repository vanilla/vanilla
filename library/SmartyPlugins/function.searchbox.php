<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Writes the search box to the page.
 *
 * @param array $params The parameters passed into the function. This currently takes no parameters.
 * @param Smarty $smarty The smarty object rendering the template.
 * @return string The url.
 */
function smarty_function_searchbox($params, &$smarty) {
    $placeholder = array_key_exists('placeholder', $params) ? val('placeholder', $params, '', true) : t('SearchBoxPlaceHolder', 'Search');
    $form = Gdn::factory('Form');
    /* @var Gdn_Form $form */
    $result =
        $form->open(['action' => url('/search'), 'method' => 'get']).
        $form->textBox('Search', [
            'placeholder' => $placeholder,
            'accesskey' => '/',
            'aria-label' => t('Enter your search term.'),
            'title' => t('Enter your search term.'),
            'role' => 'searchbox',
            'class' => 'InputBox js-search'
        ]).
        $form->button('Go', ['Name' => '', 'aria-label' => t('Search')]).
        $form->close();

    return $result;
}
