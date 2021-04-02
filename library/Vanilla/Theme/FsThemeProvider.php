<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Contracts\ConfigurationInterface;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\RequestInterface;

/**
 * Handle custom themes.
 */
class FsThemeProvider implements ThemeProviderInterface {

    /** @var AddonManager $addonManager */
    private $addonManager;

    /** @var ThemeServiceHelper */
    private $themeHelper;

    /** @var RequestInterface $request */
    private $request;

    /** @var ConfigurationInterface */
    private $config;

    /** @var string|null A theme option value if set in the form of '%s_optionName' */
    private $themeOptionValue;

    /**
     * FsThemeProvider constructor.
     *
     * @param AddonManager $addonManager
     * @param RequestInterface $request
     * @param ConfigurationInterface $config
     * @param ThemeServiceHelper $themeHelper
     */
    public function __construct(
        AddonManager $addonManager,
        RequestInterface $request,
        ConfigurationInterface $config,
        ThemeServiceHelper $themeHelper
    ) {
        $this->addonManager = $addonManager;
        $this->request = $request;
        $this->config = $config;
        $this->themeOptionValue = $this->config->get('Garden.ThemeOptions.Styles.Value', '');
        $this->themeHelper = $themeHelper;
    }

    /**
     * @inheritdoc
     */
    public function handlesThemeID($themeID): bool {
        $addon = @$this->addonManager->lookupTheme($themeID);
        return $addon !== null;
    }

    /**
     * @inheritdoc
     */
    public function getTheme($themeKey, array $args = []): Theme {
        $addon = $this->getThemeAddon($themeKey);
        return Theme::fromAddon($addon);
    }

    /**
     * @inheritdoc
     */
    public function getThemeRevisions($themeKey): array {
        return [$this->getTheme($themeKey)];
    }

    /**
     * @inheritdoc
     */
    public function getMasterThemeKey($themeKey): string {
        $theme = $this->getThemeAddon($themeKey);
        return $theme->getKey();
    }

    /**
     * Get the current theme, or fallback to the default one.
     *
     * @param int|string $themeKey
     *
     * @return Addon
     */
    public function getThemeAddon($themeKey): Addon {
        $theme = $this->addonManager->lookupTheme($themeKey);
        if (!$theme) {
            // Try to load our fallback theme.
            $theme = $this->addonManager->lookupTheme(ThemeService::FALLBACK_THEME_KEY);
            if (!$theme) {
                throw new NotFoundException("Theme");
            }
        }

        return $theme;
    }

    /**
     * @inheritdoc
     */
    public function themeExists($themeKey): bool {
        $theme = $this->addonManager->lookupTheme($themeKey);
        return $theme !== null;
    }

    /**
     * @inheritDoc
     */
    public function getAllThemes(): array {
        /** @var Addon[] $allThemes */
        $allThemes = $this->addonManager->lookupAllByType(Addon::TYPE_THEME);
        $allAvailableThemes = [];

        foreach ($allThemes as $theme) {
            if ($this->themeHelper->isThemeVisible($theme)) {
                $allAvailableThemes[] = $this->getTheme($theme->getKey());
            }
        }
        return $allAvailableThemes;
    }

    /**
     * @inheritdoc
     */
    public function setCurrentTheme($themeKey): Theme {
        $this->config->set('Garden.Theme', $themeKey);
        $this->config->set('Garden.MobileTheme', $themeKey);
        $this->config->set('Garden.CurrentTheme', $themeKey);
        return $this->getTheme($themeKey);
    }

    /**
     * @inheritdoc
     */
    public function setPreviewTheme($themeID, int $revisionID = null): Theme {
        $theme = $this->getTheme($themeID);
        $this->themeHelper->setSessionPreviewTheme($theme);
        return $theme;
    }
}
