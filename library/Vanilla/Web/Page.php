<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Garden\Web\Exception\HttpException;
use Gdn_Upload;
use Garden\CustomExceptionHandler;
use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\InjectableInterface;
use Vanilla\Models\SiteMeta;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Web\Asset\AssetPreloadModel;
use Vanilla\Web\Asset\WebpackAssetProvider;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\JsInterpop\PhpAsJsVariable;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionPreloadTrait;
use Vanilla\Web\JsInterpop\ReduxErrorAction;

/**
 * Class representing a single page in the application.
 */
abstract class Page implements InjectableInterface, CustomExceptionHandler {

    use TwigRenderTrait, ReduxActionPreloadTrait;

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

    /** @var string|null */
    private $seoContent;

    /** @var array */
    private $metaTags = [];

    /** @var AssetInterface[] */
    protected $scripts = [];

    /** @var AssetInterface[] */
    protected $styles = [];

    /** @var string[] */
    protected $inlineScripts = [];

    /** @var string[] */
    protected $inlineStyles = [];

    /** @var bool */
    private $requiresSeo = true;

    /** @var int The page status code. */
    private $statusCode = 200;

    /** @var AbstractJsonLDItem */
    private $jsonLDItems = [];

    /**
     * Prepare the page contents.
     *
     * @return void
     */
    abstract public function initialize();

    /** @var SiteMeta */
    protected $siteMeta;

    /** @var \Gdn_Request */
    protected $request;

    /** @var \Gdn_Session */
    protected $session;

    /** @var WebpackAssetProvider */
    protected $assetProvider;

    /** @var BreadcrumbModel */
    protected $breadcrumbModel;

    /** @var string */
    protected $headerHtml = '';

    /** @var string */
    protected $footerHtml = '';

    /** @var ContentSecurityPolicyModel */
    protected $cspModel;

    /** @var AssetPreloadModel */
    protected $preloadModel;

    /** @var EventManager */
    protected $eventManager;

    /**
     * Dependendency Injection.
     *
     * @param SiteMeta $siteMeta
     * @param \Gdn_Request $request
     * @param \Gdn_Session $session
     * @param WebpackAssetProvider $assetProvider
     * @param BreadcrumbModel $breadcrumbModel
     * @param ContentSecurityPolicyModel $cspModel
     * @param AssetPreloadModel $preloadModel
     * @param EventManager $eventManager
     */
    public function setDependencies(
        SiteMeta $siteMeta,
        \Gdn_Request $request,
        \Gdn_Session $session,
        WebpackAssetProvider $assetProvider,
        BreadcrumbModel $breadcrumbModel,
        ContentSecurityPolicyModel $cspModel,
        AssetPreloadModel $preloadModel,
        EventManager $eventManager
    ) {
        $this->siteMeta = $siteMeta;
        $this->request = $request;
        $this->session = $session;
        $this->assetProvider = $assetProvider;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->cspModel = $cspModel;
        $this->preloadModel = $preloadModel;
        $this->eventManager = $eventManager;

        if ($mobileAddressBarColor = $this->siteMeta->getMobileAddressBarColor()) {
            $this->addMetaTag(["name" => "theme-color", "content" => $mobileAddressBarColor]);
        }
    }

    /**
     * Render the page content and wrap it in a data object for the dispatcher.
     *
     * @return Data Data object for global dispatcher.
     */
    public function render(): Data {
        return $this->renderMasterView();
    }

    /**
     * Render the page content and wrap it in a data object for the dispatcher.
     *
     * This method is kept private so that it can be called internally for error pages without being overridden.
     *
     * @return Data Data object for global dispatcher.
     */
    private function renderMasterView(): Data {
        $this->validateSeo();
        $this->applyMetaTags();

        $this->inlineScripts[] = new PhpAsJsVariable('gdn', [
            'meta' => $this->siteMeta,
        ]);
        $this->inlineScripts[] = $this->getReduxActionsAsJsVariable();
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
            'seoContent' => $this->seoContent,
            'metaTags' => $this->metaTags,
            'header' => $this->headerHtml,
            'footer' => $this->footerHtml,
            'preloadModel' => $this->preloadModel,
            'cssClasses' => ['isLoading'],
            'favIcon' => $this->siteMeta->getFavIcon(),
            'jsonLD' => $this->getJsonLDScriptContent(),
        ];

        $this->eventManager->fireArray('BeforeRenderMasterView', [&$viewData]);

        $viewContent = $this->renderTwig('resources/views/default-master.twig', $viewData);

        return new Data($viewContent, $this->statusCode);
    }

    /**
     * Add a JSON-LD item to be represented.
     *
     * @param AbstractJsonLDItem $item
     *
     * @return $this For chaining.
     */
    public function addJsonLDItem(AbstractJsonLDItem $item): self {
        $this->jsonLDItems[] = $item;
        return $this;
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

    /**
     * Use existing site data to create open graph meta tags.
     */
    private function applyMetaTags() {

        // Standard meta tags
        if ($this->seoDescription) {
            $this->addMetaTag(['name' => 'description', 'content' => $this->seoDescription]);
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
     * Validate that we have sufficient SEO data.
     *
     * @throws ServerException If the page has not implemented valid SEO metrics in debug mode.
     */
    private function validateSeo() {
        $hasInvalidSeo =
            $this->siteMeta->getDebugModeEnabled() &&
            $this->requiresSeo &&
            (
                $this->seoTitle === null ||
                $this->seoBreadcrumbs === null ||
                $this->seoContent === null ||
                $this->seoDescription === null ||
                $this->canonicalUrl === null
            );
        if ($hasInvalidSeo) {
            throw new ServerException('Page SEO data is not fully implemented');
        }
    }

    /**
     * Indicate to crawlers that they should not index this page.
     *
     * @return $this Own instance for chaining.
     */
    public function blockRobots(): self {
        header('X-Robots-Tag: noindex', true);
        $this->addMetaTag(['name' => 'robots', 'content' => 'noindex']);

        return $this;
    }

    /**
     * Enable or disable validation of server side SEO content. This is only important on certain pages.
     *
     * @param bool $required
     *
     * @return $this Own instance for chaining.
     */
    protected function setSeoRequired(bool $required = true): self {
        $this->requiresSeo = $required;

        return $this;
    }

    /**
     * Set the page title (in the browser tab).
     *
     * @param string $title The title to set.
     * @param bool $withSiteTitle Whether or not to append the global site title.
     *
     * @return $this Own instance for chaining.
     */
    protected function setSeoTitle(string $title, bool $withSiteTitle = true): self {
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
     * Set an the site meta description.
     *
     * @param string $description
     *
     * @return $this Own instance for chaining.
     */
    protected function setSeoDescription(string $description): self {
        $this->seoDescription = $description;

        return $this;
    }

    /**
     * Set an the canonical URL for the page.
     *
     * @param string $path Either a partial path or a full URL.
     *
     * @return $this Own instance for chaining.
     */
    protected function setCanonicalUrl(string $path): self {
        $this->canonicalUrl = $this->request->url($path, true);

        return $this;
    }

    /**
     * Set an array of breadcrumbs.
     *
     * @param Breadcrumb[] $crumbs
     *
     * @return $this Own instance for chaining.
     */
    protected function setSeoBreadcrumbs(array $crumbs): self {
        $this->seoBreadcrumbs = $crumbs;
        $this->addJsonLDItem(new BreadcrumbJsonLD($crumbs));
        return $this;
    }

    /**
     * Render and set the SEO page content.
     *
     * @param string $viewPathOrView The path to the view to render or the rendered view.
     * @param array $viewData The data to render the view if we gave a path.
     *
     * @return $this Own instance for chaining.
     */
    protected function setSeoContent(string $viewPathOrView, array $viewData = null): self {
        // No view data so assume the view is rendered already.
        if ($viewData === null) {
            $this->seoContent = $viewPathOrView;
            return $this;
        }

        $this->seoContent = $this->renderTwig($viewPathOrView, $viewData);

        return $this;
    }

    /**
     * Set page meta tag attributes.
     *
     * @param array $attributes Array of attributes to set for tag.
     *
     * @return $this Own instance for chaining.
     */
    protected function addMetaTag(array $attributes): self {
        $this->metaTags[] = $attributes;

        return $this;
    }

    /**
     * Apply an open graph tag.
     *
     * @param string $property
     * @param string $content
     * @return $this
     */
    protected function addOpenGraphTag(string $property, string $content): self {
        return $this->addMetaTag(['property' => $property, 'content' => $content]);
    }

    /**
     * @inheritdoc
     */
    public function hasExceptionHandler(\Throwable $e): bool {
        return $e instanceof HttpException;
    }

    /**
     * @inheritdoc
     */
    public function handleException(\Throwable $e): Data {
        $this->requiresSeo = false;
        $this->statusCode = $e->getCode();
        $this->addReduxAction(new ReduxErrorAction($e))
            ->setSeoTitle($e->getMessage())
            ->addMetaTag(['name' => 'robots', 'content' => 'noindex'])
            ->setSeoContent('resources/views/error.twig', [
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode()
            ])
        ;

        return $this->renderMasterView();
    }

    /**
     * Redirect user to sign in page if they are not signed in.
     *
     * @param string $redirectTarget URI user should be redirected back when log in.
     *
     * @return $this
     */
    public function requiresSession(string $redirectTarget): self {
        if (!$this->session->isValid()) {
            header(
                'Location: /entry/signin?Target=' . urlencode($redirectTarget),
                true,
                302
            );
            exit();
        } else {
            return $this;
        }
    }
}
