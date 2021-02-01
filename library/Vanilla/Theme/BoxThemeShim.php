<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Vanilla\Utility\HtmlUtils;

/**
 * Utility module for applying box shims to views.
 */
final class BoxThemeShim {

    /**
     * Determine if the theme shim should be applied.
     *
     * @return bool
     */
    public static function isActive(): bool {
        return \Gdn::themeFeatures()->useDataDrivenTheme();
    }

    /**
     * Apply some HTML if the shim is disabled.
     *
     * @param string $html
     */
    public static function inactiveHtml(string $html) {
        if (!self::isActive()) {
            echo $html;
        }
    }

    /**
     * Apply some HTML if the shim is enabled.
     *
     * @param string $html
     */
    public static function activeHtml(string $html) {
        if (self::isActive()) {
            echo $html;
        }
    }

    /**
     * Render the opening tag of a box.
     *
     * @param string|null $cssClass
     */
    public static function startBox(?string $cssClass = null) {
        if (self::isActive()) {
            $cssClasses = htmlspecialchars(HtmlUtils::classNames('pageBox', $cssClass));
            echo "<section class='$cssClasses'>";
        }
    }

    /**
     * Render the closing tag of a box.
     */
    public static function endBox() {
        if (self::isActive()) {
            echo "</section>";
        }
    }

    /**
     * Open a heading tag.
     *
     * @param string|null $cssClass
     */
    public static function startHeading(?string $cssClass = null) {
        if (self::isActive()) {
            $cssClasses = htmlspecialchars(HtmlUtils::classNames('pageHeadingBox', $cssClass));
            echo "<div class=\"$cssClasses\">";
        }
    }

    /**
     * End headinb.
     */
    public static function endHeading() {
        if (self::isActive()) {
            echo "</div>";
        }
    }
}
