<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Contracts\AddonInterface;
use Vanilla\Contracts\AddonProviderInterface;
use Gdn_Session as SessionInterface;
use Vanilla\Addon;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Theme\ThemeProviderInterface;

/**
 * Theme helper functions.
 */
class ThemeModelHelper {

    // Config holding all forced visiblity themes.
    const CONFIG_THEMES_VISIBLE = 'Garden.Themes.Visible';
    const ALL_VISIBLE = 'all';

    // Old desktop config key.
    const CONFIG_DESKTOP_THEME = 'Garden.Theme';

    // Old Mobile config key.
    const CONFIG_MOBILE_THEME = 'Garden.MobileTheme';

    // New theme API config.
    const CONFIG_CURRENT_THEME = 'Garden.CurrentTheme';

    /** @var SessionInterface $session */
    private $session;

    /** @var AddonProviderInterface $addonManager */
    private $addonManager;

    /** @var ConfigurationInterface $config */
    private $config;

    /**
     * ThemeModelHelper constructor.
     *
     * @param AddonProviderInterface $addonManager
     * @param SessionInterface $session
     * @param ConfigurationInterface $config
     */
    public function __construct(
        AddonProviderInterface $addonManager,
        SessionInterface $session,
        ConfigurationInterface $config
    ) {
        $this->session = $session;
        $this->addonManager = $addonManager;
        $this->config  = $config;
    }

    /**
     * Filter themes based on their addon.json.
     *
     * @param AddonInterface $theme A themes data from it's addon.json.
     * @param string $siteName The vanilla domain of the site.
     *
     * @return bool
     */
    public function isThemeVisible(AddonInterface $theme, ?string $siteName = null): bool {
        if ($siteName === null && defined('CLIENT_NAME')) {
            $siteName = CLIENT_NAME;
        }
        $confVisible = $this->config->get(self::CONFIG_THEMES_VISIBLE);

        if ($confVisible === self::ALL_VISIBLE) {
            // Config setup to show all themes.
            return true;
        }

        $isAdmin = ($this->session->User->Admin ?? 0) === 2;
        if ($isAdmin) {
            // All theme visible for system admins.
            return true;
        }

        $themeKey = $theme->getKey();
        $alwaysVisibleThemes = array_map('trim', explode(",", $confVisible));

        if (in_array($themeKey, $alwaysVisibleThemes, true)) {
            return true;
        }

        $currentTheme = $this->config->get('Garden.CurrentTheme', $this->config->get('Garden.Theme'));
        if ($currentTheme === $themeKey) {
            // Always visible.
            return true;
        }

        // Check if theme visibility is set through the JSON.
        $hidden = $theme->getInfoValue('hidden', null);
        $sites = $theme->getInfoValue('sites', []);
        $site = $theme->getInfoValue('site', null);
        if ($site !== null) {
            $sites[] = $site;
        }

        if ($hidden === false) {
            return true;
        } elseif ($hidden === true) {
            return false;
        } else {
            $hidden = false;
            foreach ($sites as $addonSite) {
                if ($addonSite === $siteName || fnmatch($addonSite, $siteName)) {
                    $hidden = true;
                    break;
                }
            }

            return $hidden;
        }
    }

    /**
     * Set session preview theme
     *
     * @param string $themeKey
     * @param ThemeProviderInterface $themeProvider
     * @return array Theme info array
     */
    public function setSessionPreviewTheme(string $themeKey, ThemeProviderInterface $themeProvider): array {
        $masterTheme = $themeProvider->getMasterThemeKey($themeKey);
        $displayName = $themeProvider->getName($themeKey);
        $this->session->setPreference('PreviewThemeKey', $themeKey);

        $themeInfo = $this->addonManager->lookupTheme($masterTheme)->getInfo();

        $isMobile = $themeInfo['IsMobile'] ?? false;

        if ($isMobile) {
            $this->session->setPreference(
                ['PreviewMobileThemeFolder' => $masterTheme,
                    'PreviewMobileThemeName' => $displayName]
            );
        } else {
            $this->session->setPreference(
                ['PreviewThemeFolder' => $masterTheme,
                    'PreviewThemeName' => $displayName]
            );
        }
        return $themeInfo;
    }

    /**
     * Reset preview theme and switch back to current
     */
    public function cancelSessionPreviewTheme() {
        $this->session->setPreference(
            [
                'PreviewThemeKey' => '',
                'PreviewMobileThemeFolder' => '',
                'PreviewMobileThemeName' => '',
                'PreviewThemeFolder' => '',
                'PreviewThemeName' => ''
            ]
        );
    }

    /**
     * Get the current theme key from the config.
     *
     * @return string
     */
    public function getConfigThemeKey(): string {
        return $this->config->get('Garden.CurrentTheme', $this->config->get('Garden.Theme'));
    }

    /**
     * Take the current themes in the config and save theme as visible.
     *
     * This way if themes are hidden in the future, a customer won't lose access to the theme.
     */
    public function saveCurrentThemeToVisible() {
        $currentVisible = $this->config->get(self::CONFIG_THEMES_VISIBLE, '');
        if ($currentVisible === self::ALL_VISIBLE) {
            // Don't modify because all are visible.
            return;
        }

        $themes = array_filter(array_map('trim', explode(",", $currentVisible)));
        $desktopTheme = $this->config->get(self::CONFIG_DESKTOP_THEME);
        $mobileTheme = $this->config->get(self::CONFIG_MOBILE_THEME);
        $currentTheme = $this->config->get(self::CONFIG_CURRENT_THEME);

        if ($desktopTheme && !in_array($desktopTheme, $themes, true)) {
            $themes[] = $desktopTheme;
        }

        if ($mobileTheme && !in_array($mobileTheme, $themes, true)) {
            $themes[] = $mobileTheme;
        }

        if ($currentTheme && !in_array($currentTheme, $themes, true)) {
            $themes[] = $currentTheme;
        }

        $resultConfig = implode($themes, ",");
        $this->config->saveToConfig(self::CONFIG_THEMES_VISIBLE, $resultConfig);
    }
}
