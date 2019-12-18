<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Vanilla\Contracts\Web\AssetInterface;

/**
 * Class for proxying one page head interface to another.
 */
trait PageHeadProxyTrait { // implements PageHeadInterface

    /** @var PageHeadInterface */
    private $proxy;

    /**
     * Set the proxy instance.
     *
     * @param PageHeadInterface $proxy
     */
    public function setPageHeadProxy(PageHeadInterface $proxy) {
        $this->proxy = $proxy;
    }

    /**
     * @inheritdoc
     */
    public function setAssetSection(string $section) {
        $this->proxy->setAssetSection($section);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addJsonLDItem(AbstractJsonLDItem $item) {
        $this->proxy->addJsonLDItem($item);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSeoTitle(string $title, bool $withSiteTitle = true) {
        $this->proxy->setSeoTitle($title, $withSiteTitle);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSeoDescription(string $description) {
        $this->proxy->setSeoDescription($description);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCanonicalUrl(string $path) {
        $this->proxy->setCanonicalUrl($path);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSeoBreadcrumbs(array $crumbs) {
        $this->proxy->setSeoBreadcrumbs($crumbs);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addInlineScript(string $script) {
        $this->proxy->addInlineScript($script);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addScript(AssetInterface $script) {
        $this->proxy->addScript($script);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addLinkTag(array $attributes) {
        $this->proxy->addLinkTag($attributes);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addMetaTag(array $attributes) {
        $this->proxy->addMetaTag($attributes);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addOpenGraphTag(string $property, string $content) {
        $this->proxy->addOpenGraphTag($property, $content);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSeoTitle(): string {
        return $this->proxy->getSeoTitle();
    }

    /**
     * @inheritdoc
     */
    public function getSeoDescription(): string {
        return $this->proxy->getSeoDescription();
    }

    /**
     * @inheritdoc
     */
    public function getSeoBreadcrumbs(): ?array {
        return $this->proxy->getSeoBreadcrumbs();
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalUrl(): string {
        return $this->proxy->getCanonicalUrl();
    }
}
