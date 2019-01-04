<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
function smarty_function_asset(array $params, &$smarty) {
    $name = $params['name'] ?? false;
    $tag = $params['tag'] ?? '';
    $id = $params['id'] ?? $name;

    $class = ($params['class'] ?? '');
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

        if (($asset->Visible ?? true)) {
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
