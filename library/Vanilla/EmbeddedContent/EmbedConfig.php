<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

use Gdn;
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

    /** @var string */
    private $legacyEmbedSize;

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
        $this->legacyEmbedSize = config('Garden.Format.EmbedSize', 'normal');
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

    const EMBED_SIZES = [
        'tiny' => [400, 225],
        'small' => [560, 340],
        'normal' => [640, 385],
        'big' => [853, 505],
        'huge' => [1280, 745]
    ];

    /**
     * Returns embedded video width and height, based on configuration.
     *
     * @return array [Width, Height]
     */
    public function getLegacyEmbedSize() {
        $size = $this->legacyEmbedSize;

        // We allow custom sizes <Width>x<Height>
        if (!isset(self::EMBED_SIZES[$size])) {
            if (strpos($size, 'x')) {
                list($width, $height) = explode('x', $size);
                $width = intval($width);
                $height = intval($height);

                // Dimensions are too small, or 0
                if ($width < 30 or $height < 30) {
                    $size = 'normal';
                }
            } else {
                $size = 'normal';
            }
        }
        list($width, $height) = self::EMBED_SIZES[$size];
        return [$width, $height];
    }
}
