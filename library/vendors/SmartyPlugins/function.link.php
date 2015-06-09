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
 * Takes a route and prepends the web root (expects "/controller/action/params" as $Path).
 *
 * @param array The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>path</b>: The relative path for the url. There are some special paths that can be used to return "intelligent" links:
 *    - <b>signinout</b>: This will return a signin/signout url that will toggle depending on whether or not the user is already signed in. When this path is given the text is automaticall set.
 * - <b>withdomain</b>: Whether or not to add the domain to the url.
 * - <b>text</b>: Html text to be put inside an anchor. If this value is set then an html <a></a> is returned rather than just a url.
 * - <b>id, class, etc.</b>: When an anchor is generated then any other attributes are passed through and will be written in the resulting tag.
 * @param Smarty The smarty object rendering the template.
 * @return The url.
 */
function smarty_function_link($Params, &$Smarty) {
   $Path = GetValue('path', $Params, '', TRUE);
   $Text = GetValue('text', $Params, '', TRUE);
   $NoTag = GetValue('notag', $Params, FALSE, TRUE);
   $CustomFormat = GetValue('format', $Params, FALSE, TRUE);

   if(!$Text && $Path != 'signinout' && $Path != 'signin')
      $NoTag = TRUE;

   if ($CustomFormat)
      $Format = $CustomFormat;
   else if ($NoTag)
      $Format = '%url';
   else
      $Format = '<a href="%url" class="%class">%text</a>';

   $Options = array();
   if (isset($Params['withdomain'])) $Options['WithDomain'] = $Params['withdomain'];
   if (isset($Params['class'])) $Options['class'] = $Params['class'];
   if (isset($Params['tk'])) $Options['TK'] = $Params['tk'];
   if (isset($Params['target'])) $Options['Target'] = $Params['target'];

   $Result = Gdn_Theme::Link($Path, $Text, $Format, $Options);

   return $Result;
}