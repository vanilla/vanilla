<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
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