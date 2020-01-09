<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\Navigation\Breadcrumb;

/**
 * Interface for all public page head methods.
 */
interface PageHeadInterface {

    /**
     * Set the section of the site we are serving assets for.
     *
     * @param string $section 'forum', 'admin', 'knowledge'
     *
     * @return string
     */
    public function setAssetSection(string $section);

    /**
     * Add a JSON-LD item to be represented.
     *
     * @param AbstractJsonLDItem $item
     *
     * @return $this For chaining.
     */
    public function addJsonLDItem(AbstractJsonLDItem $item);

    /**
     * Set the page title (in the browser tab).
     *
     * @param string $title The title to set.
     * @param bool $withSiteTitle Whether or not to append the global site title.
     *
     * @return $this Own instance for chaining.
     */
    public function setSeoTitle(string $title, bool $withSiteTitle = true);

    /**
     * Set an the site meta description.
     *
     * @param string $description
     *
     * @return $this Own instance for chaining.
     */
    public function setSeoDescription(string $description);

    /**
     * Set an the canonical URL for the page.
     *
     * @param string $path Either a partial path or a full URL.
     *
     * @return $this Own instance for chaining.
     */
    public function setCanonicalUrl(string $path);

    /**
     * Set an array of breadcrumbs.
     *
     * @param Breadcrumb[] $crumbs
     *
     * @return $this Own instance for chaining.
     */
    public function setSeoBreadcrumbs(array $crumbs);

    /**
     * Set page link tag attributes.
     *
     * @param array $attributes Array of attributes to set for tag.
     *
     * @return $this Own instance for chaining.
     */
    public function addLinkTag(array $attributes);

    /**
     * Set page meta tag attributes.
     *
     * @param array $attributes Array of attributes to set for tag.
     *
     * @return $this Own instance for chaining.
     */
    public function addMetaTag(array $attributes);

    /**
     * Apply an open graph tag.
     *
     * @param string $property
     * @param string $content
     * @return $this
     */
    public function addOpenGraphTag(string $property, string $content);

    /**
     * Add an inline script to the page head.
     *
     * @param string $script
     * @return $this
     */
    public function addInlineScript(string $script);

    /**
     * Add a script to the page head.
     *
     * @param AssetInterface $script
     * @return $this
     */
    public function addScript(AssetInterface $script);

    /**
     * @return string
     */
    public function getSeoTitle(): ?string;

    /**
     * @return string
     */
    public function getSeoDescription(): ?string;

    /**
     * @return Breadcrumb[]|null
     */
    public function getSeoBreadcrumbs(): ?array;

    /**
     * @return string
     */
    public function getCanonicalUrl(): ?string;
}
