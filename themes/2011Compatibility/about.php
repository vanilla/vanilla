<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package 2011Compatibility Theme
 * @since 2.1
 */

/**
 * An associative array of information about this application.
 */
$ThemeInfo['2011Compatibility'] = array(
    'Name' => '2011 Compatibility',
    'Description' => "In 2012 Vanilla went to great lengths to simplify their core theme. This theme is a compatibility layer for themes that were extended from the old 2011 version.",
    'Version' => '1',
    'Author' => "Mark O'Sullivan",
    'AuthorEmail' => 'mark@vanillaforums.com',
    'AuthorUrl' => 'http://markosullivan.ca',
    'Options' => array(
        'Description' => 'This theme has <font color="red">6 color</font> options.',
        'Styles' => array(
            'Vanilla Terminal' => '%s_terminal',
            'Vanilla Grey' => '%s_grey',
            'Vanilla Big City' => '%s_bigcity',
            'Vanilla Poppy' => '%s_poppy',
            'Vanilla Lemon Sea' => '%s_lemonsea',
            'Vanilla Blue' => '%s'
        ),
    ),
    'Archived' => TRUE
);
