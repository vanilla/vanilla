<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Gdn_Session as SessionInterface;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Theme helper functions.
 */
class ThemeServiceHelper {

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

    /** @var AddonManager $addonManager */
    private $addonManager;

    /** @var ConfigurationInterface $config */
    private $config;

    /**
     * ThemeServiceHelper constructor.
     *
     * @param AddonManager $addonManager
     * @param SessionInterface $session
     * @param ConfigurationInterface $config
     */
    public function __construct(
        AddonManager $addonManager,
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
     * @param Addon $theme A themes data from it's addon.json.
     * @param string $siteName The vanilla domain of the site.
     *
     * @return bool
     */
    public function isThemeVisible(Addon $theme, ?string $siteName = null): bool {
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
     * @param Theme $theme
     * @param int|null $revisionID
     */
    public function setSessionPreviewTheme(Theme $theme, ?int $revisionID = null) {
        $this->session->setPreference('PreviewThemeKey', $theme->getThemeID());
        $this->session->setPreference('PreviewThemeRevisionID', $revisionID);

        $addonKey = $theme->getAddon()->getKey();
        $displayName = $theme->getName();

        $themeInfo = $this->addonManager->lookupTheme($addonKey)->getInfo();

        $isMobile = $themeInfo['IsMobile'] ?? false;

        if ($isMobile) {
            $this->session->setPreference(
                ['PreviewMobileThemeFolder' => $addonKey,
                    'PreviewMobileThemeName' => $displayName]
            );
        } else {
            $this->session->setPreference(
                ['PreviewThemeFolder' => $addonKey,
                    'PreviewThemeName' => $displayName]
            );
        }
    }

    /**
     * Reset preview theme and switch back to current
     */
    public function cancelSessionPreviewTheme() {
        $this->session->setPreference(
            [
                'PreviewThemeKey' => '',
                'PreviewThemeRevisionID' => '',
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

        $resultConfig = implode(",", $themes);
        $this->config->saveToConfig(self::CONFIG_THEMES_VISIBLE, $resultConfig);
    }
}
