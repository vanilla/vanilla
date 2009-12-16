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
 * Takes a route and prepends the web root (expects "/controller/action/params" as $Destination).
 * 
 * @param array The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>dest</b>: The destination of the url.
 * - <b>domain</b>: Whether or not to add the domain to the url.
 * - <b>removeSyndication</b>: Whether or not to remove any syndication from the url.
 * @param Smarty The smarty object rendering the template.
 * @return The url.
 */
function smarty_function_url($Params, &$Smarty) {
	$Destination = ArrayValue('dest', $Params, '');
	$WithDomain = ArrayValue('domain', $Params, FALSE);
	$RemoveSyndication = ArrayValue('removeSyndication', $Params, FALSE);
	$Result = Url($Destination, $WithDomain, $RemoveSyndication);
	return $Result;
}