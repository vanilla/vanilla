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
 * Writes the search box to the page.
 *
 * @param array The parameters passed into the function. This currently takes no parameters.
 * @param $smarty The smarty object rendering the template.
 * @return The url.
 */
function smarty_function_searchbox($params, &$smarty) {
   $placeholder = array_key_exists('placeholder', $params) ? val('placeholder', $params, '', TRUE) : t('SearchBoxPlaceHolder', 'Search');
   /* @var Gdn_Form $form */
   $form = Gdn::factory('Form');
   $form->InputPrefix = '';
   $result =
      $form->open(array('action' => url('/search'), 'method' => 'get')).
      $form->textBox('Search', array('placeholder' => $placeholder, 'accesskey' => '/')).
      $form->button('Go', array('Name' => '')).
      $form->close();

   return $result;
}

