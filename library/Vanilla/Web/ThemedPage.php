<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Vanilla\Models\SiteMeta;
use Vanilla\Models\ThemePreloadProvider;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Web\Asset\AssetPreloadModel;
use Vanilla\Web\Asset\WebpackAssetProvider;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;

/**
 * A Web\Page that makes use of custom theme data from the theming API.
 */
abstract class ThemedPage extends Page {

    /** @var ThemePreloadProvider */
    private $themeProvider;

    /**
     * @inheritdoc
     */
    public function setDependencies(
        SiteMeta $siteMeta,
        \Gdn_Request $request,
        \Gdn_Session $session,
        PageHead $pageHead,
        ThemePreloadProvider $themeProvider = null // Default required to conform to interface
    ) {
        parent::setDependencies($siteMeta, $request, $session, $pageHead);
        $this->themeProvider = $themeProvider;
        $this->initAssets();
    }

    /**
     * Initialize data that is shared among all of the controllers.
     */
    protected function initAssets() {
        // Preload for frontend
        $this->registerReduxActionProvider($this->themeProvider);

        // HTML handling
        $this->headerHtml = $this->themeProvider->getThemeHeaderHtml();
        $this->footerHtml = $this->themeProvider->getThemeFooterHtml();

        // Add the theme's script asset if it exists.
        $script = $this->themeProvider->getThemeScript();
        if ($script !== null) {
            $this->scripts[] = $script;
        }
    }
}
