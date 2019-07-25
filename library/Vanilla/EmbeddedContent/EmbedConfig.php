<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class representing various configuration options for embeds.
 */
class EmbedConfig {

    /** @var bool */
    private $areEmbedsEnabled;

    /** @var bool */
    private $isYoutubeEnabled;

    /** @var bool */
    private $isVimeoEnabled;

    /** @var bool */
    private $isGettyEnabled;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config) {
        $this->areEmbedsEnabled = !$config->get('Garden.Format.DisableUrlEmbeds', false);
        $this->isYoutubeEnabled = $config->get('Garden.Format.YouTube', false);
        $this->isVimeoEnabled = $config->get('Garden.Format.Vimeo', false);
        $this->isGettyEnabled = $config->get('Garden.Format.Getty', true);
    }

    /**
     * @return bool
     */
    public function areEmbedsEnabled(): bool {
        return $this->areEmbedsEnabled;
    }

    /**
     * @return bool
     */
    public function isYoutubeEnabled(): bool {
        return $this->isYoutubeEnabled;
    }

    /**
     * @return bool
     */
    public function isVimeoEnabled(): bool {
        return $this->isVimeoEnabled;
    }

    /**
     * @return bool
     */
    public function isGettyEnabled(): bool {
        return $this->isGettyEnabled;
    }
}
