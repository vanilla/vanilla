<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

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
        return $this->proxy->setAssetSection($section);
    }

    /**
     * @inheritdoc
     */
    public function addJsonLDItem(AbstractJsonLDItem $item) {
        return $this->proxy->addJsonLDItem($item);
    }

    /**
     * @inheritdoc
     */
    public function setSeoTitle(string $title, bool $withSiteTitle = true) {
        return $this->proxy->setSeoTitle($title, $withSiteTitle);
    }

    /**
     * @inheritdoc
     */
    public function setSeoDescription(string $description) {
        return $this->proxy->setSeoDescription($description);
    }

    /**
     * @inheritdoc
     */
    public function setCanonicalUrl(string $path) {
        return $this->proxy->setCanonicalUrl($path);
    }

    /**
     * @inheritdoc
     */
    public function setSeoBreadcrumbs(array $crumbs) {
        return $this->proxy->setSeoBreadcrumbs($crumbs);
    }

    /**
     * @inheritdoc
     */
    public function addInlineScript(string $script) {
        $this->proxy->addInlineScript($script);
    }

    /**
     * @inheritdoc
     */
    public function addLinkTag(array $attributes) {
        return $this->proxy->addLinkTag($attributes);
    }

    /**
     * @inheritdoc
     */
    public function addMetaTag(array $attributes) {
        return $this->proxy->addMetaTag($attributes);
    }

    /**
     * @inheritdoc
     */
    public function addOpenGraphTag(string $property, string $content) {
        return $this->proxy->addOpenGraphTag($property, $content);
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
