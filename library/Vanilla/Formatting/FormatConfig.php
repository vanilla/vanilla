<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Formatting\Formats\RichFormat;

/**
 * Class representing various configuration options for formatting.
 */
class FormatConfig {

    /** @var bool */
    private $shouldReplaceNewLines;

    /** @var bool */
    private $useVanillaMarkdownFlavor;

    /** @var string */
    private $defaultDesktopFormat;

    /** @var string */
    private $defaultMobileFormat;

    private $defaultFormat;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config) {
        $this->shouldReplaceNewLines = $config->get('Garden.Format.ReplaceNewlines', true);
        $this->useVanillaMarkdownFlavor = $config->get('Garden.Format.UseVanillaMarkdownFlavor', true);
        $this->defaultDesktopFormat = $config->get('Garden.InputFormatter', RichFormat::FORMAT_KEY);
        $this->defaultMobileFormat = $config->get('Garden.MobileInputFormatter', $this->defaultDesktopFormat);
        $this->defaultFormat = isMobile() ? $this->defaultMobileFormat : $this->defaultDesktopFormat;
    }

    /**
     * @return string
     */
    public function getDefaultDesktopFormat(): string {
        return $this->defaultDesktopFormat;
    }

    /**
     * @return string
     */
    public function getDefaultMobileFormat(): string {
        return $this->defaultMobileFormat;
    }

    /**
     * @return mixed|string
     */
    public function getDefaultFormat() {
        return $this->defaultFormat;
    }

    /**
     * @return bool
     */
    public function shouldReplaceNewLines(): bool {
        return $this->shouldReplaceNewLines;
    }

    /**
     * @return bool
     */
    public function useVanillaMarkdownFlavor(): bool {
        return $this->useVanillaMarkdownFlavor;
    }
}
