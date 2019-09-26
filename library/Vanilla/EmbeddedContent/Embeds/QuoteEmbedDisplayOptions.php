<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

/**
 * Preferences on how to display a quote.
 */
class QuoteEmbedDisplayOptions implements \JsonSerializable {

    /** @var bool */
    private $showUserLabel;

    /** @var bool */
    private $showCompactUserInfo;

    /** @var bool */
    private $showDiscussionLink;

    /** @var bool */
    private $showPostLink;

    /** @var bool */
    private $showCategoryLink;

    /** @var bool */
    private $renderFullContent;

    /** @var bool */
    private $expandByDefault;

    /**
     * Consturctor.
     *
     * @param bool $showUserLabel
     * @param bool $showCompactUserInfo
     * @param bool $showDiscussionLink
     * @param bool $showPostLink
     * @param bool $showCategoryLink
     * @param bool $renderFullContent
     * @param bool $expandByDefault
     */
    public function __construct(
        bool $showUserLabel,
        bool $showCompactUserInfo,
        bool $showDiscussionLink,
        bool $showPostLink,
        bool $showCategoryLink,
        bool $renderFullContent,
        bool $expandByDefault
    ) {
        $this->showUserLabel = $showUserLabel;
        $this->showCompactUserInfo = $showCompactUserInfo;
        $this->showDiscussionLink = $showDiscussionLink;
        $this->showPostLink = $showPostLink;
        $this->showCategoryLink = $showCategoryLink;
        $this->renderFullContent = $renderFullContent;
        $this->expandByDefault = $expandByDefault;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return get_object_vars($this);
    }

    /**
     * Convert an array of data into display options.
     *
     * @param array $data
     * @return QuoteEmbedDisplayOptions
     */
    public static function from(array $data): QuoteEmbedDisplayOptions {
        return new QuoteEmbedDisplayOptions(
            $data['showUserLabel'] ?? false,
            $data['showCompactUserInfo'] ?? false,
            $data['showDiscussionLink'] ?? false,
            $data['showPostLink'] ?? false,
            $data['showCategoryLink'] ?? false,
            $data['renderFullContent'] ?? false,
            $data['expandByDefault'] ?? false
        );
    }

    /**
     * Minimum display options for something like a standard quote.
     *
     * @param bool $withDiscussionLink
     * @return QuoteEmbedDisplayOptions
     */
    public static function minimal(bool $withDiscussionLink): QuoteEmbedDisplayOptions {
        return new QuoteEmbedDisplayOptions(
            false,
            true,
            $withDiscussionLink,
            $withDiscussionLink,
            false,
            false,
            false
        );
    }

    /**
     * Minimum display options for something like a report.
     *
     * @return QuoteEmbedDisplayOptions
     */
    public static function full(): QuoteEmbedDisplayOptions {
        return new QuoteEmbedDisplayOptions(
            true,
            false,
            true,
            true,
            true,
            true,
            true
        );
    }

    /**
     * @return bool
     */
    public function isShowUserLabel(): bool {
        return $this->showUserLabel;
    }

    /**
     * @return bool
     */
    public function isShowCompactUserInfo(): bool {
        return $this->showCompactUserInfo;
    }

    /**
     * @return bool
     */
    public function isShowDiscussionLink(): bool {
        return $this->showDiscussionLink;
    }

    /**
     * @return bool
     */
    public function isShowPostLink(): bool {
        return $this->showPostLink;
    }

    /**
     * @return bool
     */
    public function isShowCategoryLink(): bool {
        return $this->showCategoryLink;
    }

    /**
     * @return bool
     */
    public function isRenderFullContent(): bool {
        return $this->renderFullContent;
    }

    /**
     * @return bool
     */
    public function isExpandByDefault(): bool {
        return $this->expandByDefault;
    }
}
