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
 * Renders an asset from the controller.
 * 
 * @param array The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>name</b>: The name of the asset.
 * - <b>tag</b>: The type of tag to wrap the asset in.
 * - <b>id</b>: The id of the tag if different than the name.
 * @param Smarty The smarty object rendering the template.
 * @return The rendered asset.
 */
function smarty_function_asset($Params, &$Smarty) {
	$Name = ArrayValue('name', $Params);
	$Tag = ArrayValue('tag', $Params, '');
	$Id = ArrayValue('id', $Params, $Name);
	$Class = ArrayValue('class', $Params, '');
	if ($Class != '')
		$Class = ' class="'.$Class.'"';
	
	$Controller = $Smarty->Controller;
   $Controller->EventArguments['AssetName'] = $Name;
   
   $Result = '';

   ob_start();
   $Controller->FireEvent('BeforeRenderAsset');
   $Result .= ob_get_clean();

   $Asset = $Controller->GetAsset($Name);
   
   if (is_object($Asset)) {
      $Asset->AssetName = $Name;
      
      if (GetValue('Visible', $Asset, TRUE))
         $Asset = $Asset->ToString();
      else
         $Asset = '';
   }

   if (!empty($Tag)) {
      $Result .= '<' . $Tag . ' id="' . $Id . '"'.$Class.'>' . $Asset . '</' . $Tag . '>';
   } else {
      $Result .= $Asset;
   }
   
   ob_start();
   $Controller->FireEvent('AfterRenderAsset');
   $Result .= ob_get_clean();

   return $Result;
}
