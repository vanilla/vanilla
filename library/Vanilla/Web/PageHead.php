<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Garden\Web\RequestInterface;
use Twig\Markup;
use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\Models\SiteMeta;
use Vanilla\Models\SiteMetaExtra;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Utility\StringUtils;
use Vanilla\Web\Asset\AssetPreloadModel;
use Vanilla\Web\Asset\NoScriptStylesAsset;
use Vanilla\Web\Asset\ViteAssetProvider;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\JsInterpop\PhpAsJsVariable;
use Webmozart\PathUtil\Path;

/**
 * Class for holding information for rendering and HMTL page head.
 */
final class PageHead implements PageHeadInterface
{
    use TwigRenderTrait;

    /** @var ContentSecurityPolicyModel */
    private $cspModel;

    /** @var AssetPreloadModel */
    private $preloadModel;

    /** @var EventManager */
    private $eventManager;

    /** @var SiteMeta */
    private $siteMeta;

    /** @var ViteAssetProvider */
    protected $assetProvider;

    /** @var RequestInterface */
    protected $request;

    protected SeoMetaModel $seoMetaModel;

    /**
     * Dependency Injection.
     *
     * @param ContentSecurityPolicyModel $cspModel
     * @param AssetPreloadModel $preloadModel
     * @param EventManager $eventManager
     * @param SiteMeta $siteMeta
     * @param ViteAssetProvider $assetProvider
     * @param RequestInterface $request
     * @param SeoMetaModel $seoMetaModel
     * @param NoScriptStylesAsset $noScriptLayoutStylesAsset
     */
    public function __construct(
        ContentSecurityPolicyModel $cspModel,
        AssetPreloadModel $preloadModel,
        EventManager $eventManager,
        SiteMeta $siteMeta,
        ViteAssetProvider $assetProvider,
        RequestInterface $request,
        SeoMetaModel $seoMetaModel,
        NoScriptStylesAsset $noScriptLayoutStylesAsset
    ) {
        $this->cspModel = $cspModel;
        $this->preloadModel = $preloadModel;
        $this->eventManager = $eventManager;
        $this->siteMeta = $siteMeta;
        $this->assetProvider = $assetProvider;
        $this->request = $request;
        $this->seoMetaModel = $seoMetaModel;
        $this->styles[] = $noScriptLayoutStylesAsset;
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

    /** @var array */
    private $jsonLDArray = [];

    /** @var SiteMetaExtra */
    private $siteMetaExtras = [];

    /**
     * @return Markup
     */
    public function renderHtml(): Markup
    {
        $this->applyMetaTags();

        $this->inlineScripts[] = $this->assetProvider->getBootstrapInlineScript();
        if ($this->assetProvider->isHotBuild()) {
            $this->inlineScripts[] = $this->assetProvider->getHotBuildInlineScript();
            $this->scripts = array_merge(
                $this->scripts,
                $this->assetProvider->getHotBuildScriptAssets($this->assetSection)
            );
        } else {
            $viteAssets = $this->assetProvider->getEnabledEntryAssets($this->assetSection);
            foreach ($viteAssets as $viteAsset) {
                if ($viteAsset->isScript()) {
                    $this->addScript($viteAsset);
                } elseif ($viteAsset->isStyleSheet()) {
                    $this->styles[$viteAsset->getWebPath()] = $viteAsset;
                }
            }
        }
        $this->inlineScripts[] = new PhpAsJsVariable([
            "gdn" => [
                "meta" => $this->siteMeta->value($this->siteMetaExtras),
            ],
        ]);
        $viewData = [
            "nonce" => $this->cspModel->getNonce(),
            "title" => $this->seoTitle,
            "description" => $this->seoDescription,
            "canonicalUrl" => $this->canonicalUrl,
            "locale" => $this->siteMeta->getLocaleKey(),
            "debug" => $this->siteMeta->getDebugModeEnabled(),
            "scripts" => $this->scripts,
            "inlineScripts" => $this->inlineScripts,
            "styles" => $this->styles,
            "inlineStyles" => $this->inlineStyles,
            "metaTags" => $this->metaTags,
            "linkTags" => $this->linkTags,
            "preloadModel" => $this->preloadModel,
            "cssClasses" => ["isLoading"],
            "favIcon" => $this->siteMeta->getFavIcon(),
            "jsonLD" => $this->getJsonLDScriptContent(),
        ];
        $this->eventManager->fireArray("BeforeRenderMasterView", [&$viewData]);
        return new Markup($this->renderTwig(__DIR__ . "/PageHead.twig", $viewData), "utf-8");
    }

    /**
     * @inheritdoc
     */
    public function setAssetSection(string $section)
    {
        $this->assetSection = $section;
    }

    /**
     * @inheritdoc
     */
    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    /**
     * @inheritdoc
     */
    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    /**
     * @inheritdoc
     */
    public function getSeoBreadcrumbs(): ?array
    {
        return $this->seoBreadcrumbs;
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    /**
     * @inheritdoc
     */
    public function addJsonLDItem(AbstractJsonLDItem $item)
    {
        $this->jsonLDItems[] = $item;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setJsonLdItems(array $setJsonLDItems)
    {
        $this->jsonLDArray = $setJsonLDItems;
    }

    public function getJsonLdItems(): array
    {
        return $this->jsonLDArray;
    }

    /**
     * @inheritdoc
     */
    public function setSeoTitle(string $title, bool $withSiteTitle = true)
    {
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
    public function setSeoDescription(string $description)
    {
        $this->seoDescription = $description;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCanonicalUrl(string $path)
    {
        $this->canonicalUrl = $this->request->url($path, true);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSeoBreadcrumbs(array $crumbs)
    {
        $this->seoBreadcrumbs = $crumbs;
        $this->addJsonLDItem(new BreadcrumbJsonLD($crumbs));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addInlineScript(string $script)
    {
        $this->inlineScripts[] = $script;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addInlineStyles(string $styles)
    {
        $this->inlineStyles[] = $styles;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getInlineStyleFromUrl(string $url): ?string
    {
        $baseUrl = asset("", true);
        if (str_starts_with($url, $baseUrl)) {
            // This is our own file.
            $localFilePath = rawurldecode(str_replace($baseUrl, "", $url));
            if (file_exists(PATH_ROOT . "/" . $localFilePath)) {
                $fileContents = file_get_contents(PATH_ROOT . "/" . $localFilePath);
                $fileContents = $this->unrelativeStylesheet($fileContents, parse_url($url, PHP_URL_PATH));
                return $fileContents;
            } else {
                return null;
            }
            return $localFileContents;
        } else {
            return null;
        }
    }

    /**
     * Given a stylesheet resolve all relative paths in it.
     *
     * @param string $stylesheet
     * @param string $relativeTo
     *
     * @return string
     */
    private function unrelativeStylesheet(string $stylesheet, string $relativeTo): string
    {
        $cssUrls = StringUtils::parseCssUrls($stylesheet);

        $replacements = [];
        foreach ($cssUrls as $cssUrl) {
            $replacements[$cssUrl] = Path::makeAbsolute($cssUrl, Path::getDirectory($relativeTo));
        }

        $finalCss = str_replace(array_keys($replacements), array_values($replacements), $stylesheet);
        return $finalCss;
    }

    /**
     * @inheritdoc
     */
    public function addScript(AssetInterface $script)
    {
        $this->scripts[$script->getWebPath()] = $script;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addLinkTag(array $attributes)
    {
        $this->linkTags[] = $attributes;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addMetaTag(array $attributes)
    {
        $this->metaTags[] = $attributes;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addOpenGraphTag(string $property, string $content)
    {
        // Clear out existing tags.
        $this->metaTags = array_filter($this->metaTags, function (array $tag) use ($property) {
            return ($tag["property"] ?? null) !== $property;
        });

        return $this->addMetaTag(["property" => $property, "content" => $content]);
    }

    /**
     * Get an existing opengraph tag.
     *
     * @param string $property
     *
     * @return array|null
     */
    public function getOpenGraphTag(string $property): ?array
    {
        $results = array_filter($this->metaTags, function (array $tag) use ($property) {
            return isset($tag["property"]) && $tag["property"] === $property;
        });
        return array_values($results)[0] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function addSiteMetaExtra(SiteMetaExtra $extra)
    {
        $this->siteMetaExtras[] = $extra;
        return $this;
    }

    /**
     * Use existing site data to create open graph meta tags.
     */
    public function applyMetaTags()
    {
        // Standard meta tags
        if ($this->seoDescription) {
            $this->addMetaTag(["name" => "description", "content" => $this->seoDescription]);
        }

        if ($mobileAddressBarColor = $this->siteMeta->getMobileAddressBarColor()) {
            $this->addMetaTag(["name" => "theme-color", "content" => $mobileAddressBarColor]);
        }

        // Site name
        $this->addOpenGraphTag("og:site_name", $this->siteMeta->getSiteTitle());

        if ($this->seoTitle) {
            $this->addOpenGraphTag("og:title", $this->seoTitle);
        }

        if ($this->seoDescription) {
            $this->addOpenGraphTag("og:description", $this->seoDescription);
        }

        if ($this->canonicalUrl) {
            $this->addOpenGraphTag("og:url", $this->canonicalUrl);
            $this->addLinkTag(["rel" => "canonical", "href" => $this->canonicalUrl]);
        }

        // Apply the share image.
        if ($this->getOpenGraphTag("og:image") === null) {
            $defaultShareImage = $this->siteMeta->getShareImage() ?? $this->siteMeta->getLogo();
            $this->addOpenGraphTag("og:image", \Gdn_Upload::url($defaultShareImage));
        }

        // Twitter specific tags
        $this->addMetaTag(["name" => "twitter:card", "content" => "summary"]);
        // There is no need to duplicate twitter & OG tags.
        //
        // When the Twitter card processor looks for tags on a page, it first checks for the Twitter-specific property,
        // and if not present, falls back to the supported Open Graph property.
        // From https://developer.twitter.com/en/docs/tweets/optimize-with-cards/guides/getting-started#twitter-cards-and-open-graph

        // Apply config based seo meta tags.
        $seoMetas = $this->seoMetaModel->getMetas();
        foreach ($seoMetas as $seoMeta) {
            $this->addMetaTag($seoMeta);
        }
    }

    /**
     * Get the content of the page's JSON-LD script.
     * @return string
     */
    public function getJsonLDScriptContent(): string
    {
        $data = [
            "@context" => "https://schema.org",
            "@graph" => $this->jsonLDItems,
        ];
        if (array_key_exists("@graph", $this->jsonLDArray)) {
            $data["@graph"] = array_merge($data["@graph"], $this->jsonLDArray["@graph"]);
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Return MetaTags.
     *
     * @return array
     */
    public function getMetaTags(): array
    {
        return $this->metaTags;
    }

    /**
     * Return MetaTags.
     *
     * @return array
     */
    public function getLinkTags(): array
    {
        return $this->linkTags;
    }
}
