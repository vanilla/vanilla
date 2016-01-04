<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Renders an asset from the controller.
 *
 * @param array $Params The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>name</b>: The name of the asset.
 * - <b>tag</b>: The type of tag to wrap the asset in.
 * - <b>id</b>: The id of the tag if different than the name.
 * @param object $Smarty Smarty The smarty object rendering the template.
 * @return string The rendered asset.
 */
function smarty_function_asset($Params, &$Smarty) {
    $Name = val('name', $Params);
	$Tag = val('tag', $Params, '');
	$Id = val('id', $Params, $Name);

	$Class = val('class', $Params, '');
	if ($Class != '') {
		$Class = ' class="'.$Class.'"';
    }
	
	$Controller = $Smarty->Controller;
    $Controller->EventArguments['AssetName'] = $Name;
   
    $Result = '';

    ob_start();
    $Controller->fireEvent('BeforeRenderAsset');
    $Result .= ob_get_clean();

    $Asset = $Controller->getAsset($Name);
   
    if (is_object($Asset)) {
        $Asset->AssetName = $Name;
      
        if (val('Visible', $Asset, true)) {
            $Asset = $Asset->toString();
        } else {
            $Asset = '';
        }
    }

    if (!empty($Tag)) {
        $Result .= '<' . $Tag . ' id="' . $Id . '"'.$Class.'>' . $Asset . '</' . $Tag . '>';
    } else {
        $Result .= $Asset;
    }
   
    ob_start();
    $Controller->fireEvent('AfterRenderAsset');
    $Result .= ob_get_clean();

    return $Result;
}
