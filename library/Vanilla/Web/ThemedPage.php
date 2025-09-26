<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Vanilla\Models\SiteMeta;
use Vanilla\Theme\ThemePreloadProvider;
use Vanilla\Web\Asset\WebAsset;

/**
 * A Web\Page that makes use of custom theme data from the theming API.
 */
abstract class ThemedPage extends Page
{
    /** @var ThemePreloadProvider */
    protected $themeProvider;

    /** @var string|null */
    protected $forcedThemeKey = null;

    /** @var string|null */
    protected $forcedThemeRevisionID = null;

    /**
     * @inheritdoc
     */
    public function setDependencies(
        SiteMeta $siteMeta,
        \Gdn_Request $request,
        \Gdn_Session $session,
        PageHead $pageHead,
        MasterViewRenderer $masterViewRenderer,
        ThemePreloadProvider $themeProvider = null
    ) {
        // Default required to conform to interface
        parent::setDependencies($siteMeta, $request, $session, $pageHead, $masterViewRenderer);
        $this->themeProvider = $themeProvider;
        if ($this->forcedThemeKey !== null) {
            $this->themeProvider->setForcedThemeKey($this->forcedThemeKey);
            if ($this->forcedThemeRevisionID !== null) {
                $this->themeProvider->setForcedRevisionID($this->forcedThemeRevisionID);
            }
        }
        $this->initAssets();
    }

    /**
     * @param string $themeKey
     * @param int|null $revisionID
     * @return $this
     */
    public function withForcedTheme(string $themeKey, ?int $revisionID = null): ThemedPage
    {
        $this->forcedThemeKey = $themeKey;
        $this->forcedThemeRevisionID = $revisionID;
        return $this;
    }

    /**
     * Initialize data that is shared among all of the controllers.
     */
    protected function initAssets()
    {
        // Preload for frontend
        $this->registerPreloader($this->themeProvider);

        // HTML handling
        $this->headerHtml = $this->themeProvider->getThemeHeaderHtml();
        $this->footerHtml = $this->themeProvider->getThemeFooterHtml();

        // Add the theme's script asset if it exists.
        $script = $this->themeProvider->getThemeScript();
        if ($script !== null) {
            $this->addScript($script);
        }

        foreach ($this->themeProvider->getPreloadFragmentScriptUrls() as $scriptUrl) {
            $this->addLinkTag([
                "rel" => "modulepreload",
                "href" => $scriptUrl,
            ]);
        }
    }
}
