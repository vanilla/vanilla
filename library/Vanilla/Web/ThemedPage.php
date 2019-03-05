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
use Vanilla\Web\Asset\WebpackAssetProvider;
use Vanilla\Web\JsInterpop\ReduxAction;

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
        \ThemesApiController $themesApi = null // Default required to conform to interface
    ) {
        parent::setDependencies($siteMeta, $request, $session, $assetProvider, $breadcrumbModel);
        $this->themesApi = $themesApi;
        $this->initThemeData();
    }

    /**
     * Initialize data that is shared among all of the controllers.
     */
    private function initThemeData() {
        $themeKey = $this->siteMeta->getActiveTheme()->getKey();
        $themeData = $this->themesApi->get($themeKey);
        $variables = [];

        /** @var JsonAsset $variablesAsset */
        $variablesAsset = $themeData['assets']['variables'] ?? null;
        if ($variablesAsset && $variablesAsset->getType()) {
            $variables = json_decode($variablesAsset->getData(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ServerException(
                    "Failed to initialize theme data for theme $themeKey. Theme variables were not valid JSON"
                );
            }
        }

        // Apply theme data to the master view.
        $this->headerHtml = $themeData['assets']['header'] ?? '';
        $this->footerHtml = $themeData['assets']['footer'] ?? '';

        // Preload the theme variables for the frontend.
        $this->addReduxAction(new ReduxAction(
            \ThemesApiController::GET_THEME_VARIABLES_ACTION,
            Data::box($variables),
            [ 'key' => $themeKey ]
        ));
    }
}
