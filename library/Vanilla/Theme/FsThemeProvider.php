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
class FsThemeProvider implements ThemeProviderInterface
{
    /** @var AddonManager $addonManager */
    private $addonManager;

    /** @var ThemeServiceHelper */
    private $themeHelper;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * FsThemeProvider constructor.
     *
     * @param AddonManager $addonManager
     * @param ConfigurationInterface $config
     * @param ThemeServiceHelper $themeHelper
     */
    public function __construct(
        AddonManager $addonManager,
        ConfigurationInterface $config,
        ThemeServiceHelper $themeHelper
    ) {
        $this->addonManager = $addonManager;
        $this->config = $config;
        $this->themeHelper = $themeHelper;
    }

    /**
     * @param ThemeService $themeService
     */
    public function setThemeService(ThemeService $themeService): void
    {
        // Don't actually need it.
    }

    /**
     * @inheritdoc
     */
    public function handlesThemeID($themeID): bool
    {
        $addon = @$this->addonManager->lookupTheme($themeID);
        return $addon !== null;
    }

    /**
     * @inheritdoc
     */
    public function getTheme($themeKey, array $args = []): Theme
    {
        $addon = $this->getThemeAddon($themeKey);
        return Theme::fromAddon($addon);
    }

    /**
     * @inheritdoc
     */
    public function getThemeRevisions($themeKey): array
    {
        return [$this->getTheme($themeKey)];
    }

    /**
     * @inheritdoc
     */
    public function getMasterThemeKey($themeKey): string
    {
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
    public function getThemeAddon($themeKey): Addon
    {
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
    public function themeExists($themeKey): bool
    {
        $theme = $this->addonManager->lookupTheme($themeKey);
        return $theme !== null;
    }

    /**
     * @inheritDoc
     */
    public function getAllThemes(): array
    {
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
    public function setCurrentTheme($themeID): Theme
    {
        $this->config->set("Garden.Theme", $themeID);
        $this->config->set("Garden.MobileTheme", $themeID);
        $this->config->set("Garden.CurrentTheme", $themeID);
        return $this->getTheme($themeID);
    }

    /**
     * @inheritdoc
     */
    public function setPreviewTheme($themeID, int $revisionID = null): Theme
    {
        $theme = $this->getTheme($themeID);
        $this->themeHelper->setSessionPreviewTheme($theme);
        return $theme;
    }
}
