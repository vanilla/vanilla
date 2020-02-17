<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\AddonManager;
use Gdn_Session as SessionInterface;
use Vanilla\Contracts\AddonProviderInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Theme\ThemeProviderInterface;

/**
 * Theme helper functions.
 */
class ThemeModelHelper {
    /** @var SessionInterface $session */
    private $session;

    /** @var AddonManager $addonManager */
    private $addonManager;

    /** @var ConfigurationInterface $config */
    private $config;

    /**
     * ThemeModelHelper constructor.
     *
     * @param AddonManager $addonManager
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
}
