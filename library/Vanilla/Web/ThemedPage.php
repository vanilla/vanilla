<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use Vanilla\Models\SiteMeta;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Theme\JsonAsset;
use Vanilla\Web\Asset\AssetPreloadModel;
use Vanilla\Web\Asset\WebpackAssetProvider;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Theme\FontsAsset;

/**
 * A Web\Page that makes use of custom theme data from the theming API.
 */
abstract class ThemedPage extends Page {

    /** @var \ThemesApiController */
    private $themesApi;

    /**
     * @inheritdoc
     */
    public function setDependencies(
        SiteMeta $siteMeta,
        \Gdn_Request $request,
        \Gdn_Session $session,
        WebpackAssetProvider $assetProvider,
        BreadcrumbModel $breadcrumbModel,
        ContentSecurityPolicyModel $cspModel,
        AssetPreloadModel $preloadModel,
        \ThemesApiController $themesApi = null // Default required to conform to interface
    ) {
        parent::setDependencies($siteMeta, $request, $session, $assetProvider, $breadcrumbModel, $cspModel, $preloadModel);
        $this->themesApi = $themesApi;
        $this->initAssets();
    }

    /**
     * Initialize data that is shared among all of the controllers.
     */
    protected function initAssets() {
        $themeKey = $this->siteMeta->getActiveTheme()->getKey();
        $themeData = $this->themesApi->get($themeKey);
        $assets = [];

        /** @var JsonAsset $variablesAsset */
        $variablesAsset = $themeData['assets']['variables'] ?? null;
        if ($variablesAsset && $variablesAsset->getType()) {
            $variables = json_decode($variablesAsset->getData(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ServerException(
                    "Failed to initialize theme data for theme $themeKey. Theme variables were not valid JSON"
                );
            }
            $assets["variables"] = $variables;
        }

        /** @var FontsAsset $fontsAsset */
        $fontsAsset = $themeData["assets"]["fonts"] ?? null;
        if ($fontsAsset) {
            $assets["fonts"] = $fontsAsset->getData();
        }

                /** @var ImageAsset $logoAsset */
        $logoAsset = $themeData["assets"]["logo"] ?? null;
        if ($logoAsset) {
            $assets["logo"] = $logoAsset;
        }

        /** @var ImageAsset $logoAsset */
        $mobileLogoAsset = $themeData["assets"]["mobileLogo"] ?? null;
        if ($mobileLogoAsset) {
            $assets["mobileLogo"] = $mobileLogoAsset;
        }

        $styleSheet = $themeData['assets']['styles'] ?? null;
        $headerFooterPrefix = '';
        if ($styleSheet) {
            $style = $this->themesApi->get_assets($themeKey, 'styles.css');
            $headerFooterPrefix = '<style>' . $style->getData() . '</style>';
        }

        // Add the themes javascript to the page.
        $script = $themeData['assets']['javascript'] ?? null;
        if ($script) {
            $this->scripts[] = new Asset\ThemeScriptAsset($this->request, $themeKey, $themeData['version']);
        }

        // Apply theme data to the master view.
        $this->headerHtml = $headerFooterPrefix . ($themeData['assets']['header'] ?? '');
        $this->footerHtml = $headerFooterPrefix . ($themeData['assets']['footer'] ?? '');

        // Preload the theme variables for the frontend.
        $this->addReduxAction(new ReduxAction(
            \ThemesApiController::GET_THEME_ACTION,
            Data::box(["assets" => $assets]),
            [ 'key' => $themeKey ]
        ));
    }
}
