<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

/**
 * Model for collecting information about what a theme applys to.
 *
 * The idea is that this will evolve over time as parts Vanilla start to use the modern system.
 * When first introduced KB is the only modern section, while the forum is the legacy section.
 *
 * Themes with the `DataDrivenTheme` theme feature have a compatibility layer and apply to both.
 * Foundation is the first such theme.
 *
 * Over time more parts of the App will begin to use the "modern" theming system and likely register a different section
 * based on some feature flag.
 */
class ThemeSectionModel {

    /** @var string[] */
    private $modernSections = [];

    /** @var string[] */
    private $legacySections = [];

    /**
     * Register an application as using the modern theming system.
     *
     * @param string $appName Translated, user-visible name of the app using the modern system.
     */
    public function registerModernSection(string $appName) {
        if (!in_array($appName, $this->modernSections, true)) {
            $this->modernSections[] = $appName;
        }
    }

    /**
     * Register an application as using the legacy theming system.
     *
     * @param string $appName Translated, user-visible name of the app using the modern system.
     */
    public function registerLegacySection(string $appName) {
        if (!in_array($appName, $this->legacySections, true)) {
            $this->legacySections[] = $appName;
        }
    }

    /**
     * @return string[]
     */
    public function getModernSections(): array {
        return $this->modernSections;
    }

    /**
     * @return string[]
     */
    public function getLegacySections(): array {
        return $this->legacySections;
    }
}
