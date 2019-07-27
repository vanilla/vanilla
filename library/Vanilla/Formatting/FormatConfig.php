<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class representing various configuration options for formatting.
 */
class FormatConfig {

    /** @var bool */
    private $shouldReplaceNewLines;

    /** @var bool */
    private $useVanillaMarkdownFlavor;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config) {
        $this->shouldReplaceNewLines = $config->get('Garden.Format.ReplaceNewlines', true);
        $this->useVanillaMarkdownFlavor = $config->get('Garden.Format.UseVanillaMarkdownFlavor', true);
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
