<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\EmbeddedContent;

use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;

/**
 * Configurable mock implementation of an embed factory for usage in tests.
 */
class MockEmbedFactory extends AbstractEmbedFactory {

    /** @var AbstractEmbed */
    private $embedToCreate;

    /** @var string[] */
    private $supportedDomains;

    /** @var string */
    private $supportedPathRegex;

    /**
     * @param AbstractEmbed $embedToCreate
     */
    public function __construct(AbstractEmbed $embedToCreate) {
        $this->embedToCreate = $embedToCreate;
    }

    /**
     * @param bool $canHandleEmptyPaths
     */
    public function setCanHandleEmptyPaths(bool $canHandleEmptyPaths): void {
        $this->canHandleEmptyPaths = $canHandleEmptyPaths;
    }

    /**
     * Configure the embed that will be returned by createEmbedForUrl and createEmbedFromData.
     * @param AbstractEmbed $embedToCreate
     */
    public function setEmbedToCreate(AbstractEmbed $embedToCreate): void {
        $this->embedToCreate = $embedToCreate;
    }

    /**
     * Configure the embed that will be returned by getSupportedDomains.
     *
     * @param array $supportedDomains
     *
     * @return $this
     */
    public function setSupportedDomains(array $supportedDomains): MockEmbedFactory {
        $this->supportedDomains = $supportedDomains;
        return $this;
    }

    /**
     * Configure the embed that will be returned by getSupportedPathRegex.
     *
     * @param string $supportedPathRegex
     *
     * @return $this
     */
    public function setSupportedPathRegex(string $supportedPathRegex): MockEmbedFactory {
        $this->supportedPathRegex = $supportedPathRegex;
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedDomains(): array {
        return $this->supportedDomains;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string {
        return $this->supportedPathRegex;
    }

    /**
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        return $this->embedToCreate;
    }
}
