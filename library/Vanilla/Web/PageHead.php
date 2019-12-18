<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Garden\Web\RequestInterface;
use Twig\Markup;
use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\Models\SiteMeta;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Web\Asset\AssetPreloadModel;
use Vanilla\Web\Asset\WebpackAssetProvider;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\JsInterpop\PhpAsJsVariable;

/**
 * Class for holding information for rendering and HMTL page head.
 */
final class PageHead implements PageHeadInterface {

    use TwigRenderTrait;

    /** @var ContentSecurityPolicyModel */
    private $cspModel;

    /** @var AssetPreloadModel */
    private $preloadModel;

    /** @var EventManager */
    private $eventManager;

    /** @var SiteMeta */
    private $siteMeta;

    /** @var WebpackAssetProvider */
    protected $assetProvider;

    /** @var RequestInterface */
    protected $request;

    /**
     * Dependency Injection.
     *
     * @param ContentSecurityPolicyModel $cspModel
     * @param AssetPreloadModel $preloadModel
     * @param EventManager $eventManager
     * @param SiteMeta $siteMeta
     * @param WebpackAssetProvider $assetProvider
     * @param RequestInterface $request
     */
    public function __construct(
        ContentSecurityPolicyModel $cspModel,
        AssetPreloadModel $preloadModel,
        EventManager $eventManager,
        SiteMeta $siteMeta,
        WebpackAssetProvider $assetProvider,
        RequestInterface $request
    ) {
        $this->cspModel = $cspModel;
        $this->preloadModel = $preloadModel;
        $this->eventManager = $eventManager;
        $this->siteMeta = $siteMeta;
        $this->assetProvider = $assetProvider;
        $this->request = $request;
    }

    /** @var string */
    private $assetSection;

    /** @var string */
    private $canonicalUrl;

    /** @var string */
    private $favIcon;

    /** @var string */
    private $seoTitle;

    /** @var string */
    private $seoDescription;

    /** @var Breadcrumb[]|null */
    private $seoBreadcrumbs;

    /** @var array */
    private $metaTags = [];

    /** @var array */
    private $linkTags = [];

    /** @var AssetInterface[] */
    protected $scripts = [];

    /** @var AssetInterface[] */
    protected $styles = [];

    /** @var string[] */
    protected $inlineScripts = [];

    /** @var string[] */
    protected $inlineStyles = [];

    /** @var AbstractJsonLDItem */
    private $jsonLDItems = [];

    /**
     * @return Markup
     */
    public function renderHtml(): Markup {
        $this->applyMetaTags();

        $this->inlineScripts[] = $this->assetProvider->getInlinePolyfillContents();
        $this->scripts = array_merge($this->scripts, $this->assetProvider->getScripts($this->assetSection));
        $this->styles = array_merge($this->styles, $this->assetProvider->getStylesheets($this->assetSection));

        $this->inlineScripts[] = new PhpAsJsVariable('gdn', [
            'meta' => $this->siteMeta,
        ]);
        $viewData = [
            'nonce' => $this->cspModel->getNonce(),
            'title' => $this->seoTitle,
            'description' => $this->seoDescription,
            'canonicalUrl' => $this->canonicalUrl,
            'locale' => $this->siteMeta->getLocaleKey(),
            'debug' => $this->siteMeta->getDebugModeEnabled(),
            'scripts' => $this->scripts,
            'inlineScripts' => $this->inlineScripts,
            'styles' => $this->styles,
            'inlineStyles' => $this->inlineStyles,
            'metaTags' => $this->metaTags,
            'linkTags' => $this->linkTags,
            'preloadModel' => $this->preloadModel,
            'cssClasses' => ['isLoading'],
            'favIcon' => $this->siteMeta->getFavIcon(),
            'jsonLD' => $this->getJsonLDScriptContent(),
        ];
        $this->eventManager->fireArray('BeforeRenderMasterView', [&$viewData]);
        return new Markup($this->renderTwig(__DIR__.'/PageHead.twig', $viewData), 'utf-8');
    }

    /**
     * @inheritdoc
     */
    public function setAssetSection(string $section) {
        $this->assetSection = $section;
    }

    /**
     * @inheritdoc
     */
    public function getSeoTitle(): string {
        return $this->seoTitle;
    }

    /**
     * @inheritdoc
     */
    public function getSeoDescription(): string {
        return $this->seoDescription;
    }

    /**
     * @inheritdoc
     */
    public function getSeoBreadcrumbs(): ?array {
        return $this->seoBreadcrumbs;
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalUrl(): string {
        return $this->canonicalUrl;
    }

    /**
     * @inheritdoc
     */
    public function addJsonLDItem(AbstractJsonLDItem $item) {
        $this->jsonLDItems[] = $item;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSeoTitle(string $title, bool $withSiteTitle = true) {
        if ($withSiteTitle) {
            if ($title === "") {
                $title = $this->siteMeta->getSiteTitle();
            } else {
                $title .= " - " . $this->siteMeta->getSiteTitle();
            }
        }
        $this->seoTitle = $title;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSeoDescription(string $description) {
        $this->seoDescription = $description;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCanonicalUrl(string $path) {
        $this->canonicalUrl = $this->request->url($path, true);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSeoBreadcrumbs(array $crumbs) {
        $this->seoBreadcrumbs = $crumbs;
        $this->addJsonLDItem(new BreadcrumbJsonLD($crumbs));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addInlineScript(string $script) {
        $this->inlineScripts[] = $script;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addScript(AssetInterface $script) {
        $this->scripts[] = $script;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addLinkTag(array $attributes) {
        $this->linkTags[] = $attributes;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addMetaTag(array $attributes) {
        $this->metaTags[] = $attributes;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addOpenGraphTag(string $property, string $content) {
        return $this->addMetaTag(['property' => $property, 'content' => $content]);
    }

    /**
     * Use existing site data to create open graph meta tags.
     */
    private function applyMetaTags() {

        // Standard meta tags
        if ($this->seoDescription) {
            $this->addMetaTag(['name' => 'description', 'content' => $this->seoDescription]);
        }

        if ($mobileAddressBarColor = $this->siteMeta->getMobileAddressBarColor()) {
            $this->addMetaTag(["name" => "theme-color", "content" => $mobileAddressBarColor]);
        }

        // Site name
        $this->addOpenGraphTag('og:site_name', $this->siteMeta->getSiteTitle());

        if ($this->seoTitle) {
            $this->addOpenGraphTag('og:title', $this->seoTitle);
        }

        if ($this->seoDescription) {
            $this->addOpenGraphTag('og:description', $this->seoDescription);
        }

        if ($this->canonicalUrl) {
            $this->addOpenGraphTag('og:url', $this->canonicalUrl);
            $this->addLinkTag(['rel' => 'canonical', 'href' => $this->canonicalUrl]);
        }

        // Twitter specific tags
        $this->addMetaTag(['name' => 'twitter:card', 'content' => 'summary']);
        // There is no need to duplicate twitter & OG tags.
        //
        // When the Twitter card processor looks for tags on a page, it first checks for the Twitter-specific property,
        // and if not present, falls back to the supported Open Graph property.
        // From https://developer.twitter.com/en/docs/tweets/optimize-with-cards/guides/getting-started#twitter-cards-and-open-graph
    }

    /**
     * Get the content of the page's JSON-LD script.
     * @return string
     */
    private function getJsonLDScriptContent(): string {
        $data = [
            '@context' => "https://schema.org",
            "@graph" => $this->jsonLDItems,
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
