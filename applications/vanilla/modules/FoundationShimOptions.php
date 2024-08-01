<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Modules;

/**
 * Options for the foundation shims.
 */
final class FoundationShimOptions
{
    /** @var string|null */
    private $title = null;

    /** @var string|null */
    private $description = null;

    /** @var string|null */
    private $viewAllUrl = null;

    /** @var bool */
    private $isMainContent = false;

    /**
     * @return FoundationShimOptions
     */
    public static function create(): FoundationShimOptions
    {
        return new FoundationShimOptions();
    }

    /**
     * Fluent setter.
     *
     * @param string|null $title
     *
     * @return $this
     */
    public function setTitle(?string $title): FoundationShimOptions
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Fluent setter.
     *
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description): FoundationShimOptions
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Fluent setter.
     *
     * @param string|null $viewAllUrl
     *
     * @return $this
     */
    public function setViewAllUrl(?string $viewAllUrl): FoundationShimOptions
    {
        $this->viewAllUrl = $viewAllUrl;
        return $this;
    }

    /**
     * Set this if we are shimming the primary content of the page.
     *
     * @param bool $isMainContent
     *
     * @return $this
     */
    public function setIsMainContent(bool $isMainContent): FoundationShimOptions
    {
        $this->isMainContent = $isMainContent;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getViewAllUrl(): ?string
    {
        return $this->viewAllUrl;
    }

    /**
     * @return bool
     */
    public function isMainContent(): bool
    {
        return $this->isMainContent;
    }
}
