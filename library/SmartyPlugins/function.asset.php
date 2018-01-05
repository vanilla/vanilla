<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Renders an asset from the controller.
 *
 * @param array $params The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>name</b>: The name of the asset.
 * - <b>tag</b>: The type of tag to wrap the asset in.
 * - <b>id</b>: The id of the tag if different than the name.
 * @param object $smarty Smarty The smarty object rendering the template.
 * @return string The rendered asset.
 */
function smarty_function_asset($params, &$smarty) {
    $name = val('name', $params);
	$tag = val('tag', $params, '');
	$id = val('id', $params, $name);

	$class = val('class', $params, '');
	if ($class != '') {
		$class = ' class="'.$class.'"';
    }
	
	$controller = Gdn::controller();
    $controller->EventArguments['AssetName'] = $name;
   
    $result = '';

    ob_start();
    $controller->fireEvent('BeforeRenderAsset');
    $result .= ob_get_clean();

    $asset = $controller->getAsset($name);
   
    if (is_object($asset)) {
        $asset->AssetName = $name;
      
        if (val('Visible', $asset, true)) {
            $asset = $asset->toString();
        } else {
            $asset = '';
        }
    }

    if (!empty($tag)) {
        $result .= '<' . $tag . ' id="' . $id . '"'.$class.'>' . $asset . '</' . $tag . '>';
    } else {
        $result .= $asset;
    }
   
    ob_start();
    $controller->fireEvent('AfterRenderAsset');
    $result .= ob_get_clean();

    return $result;
}
