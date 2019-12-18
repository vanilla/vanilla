<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Exception\HttpException;
use Garden\CustomExceptionHandler;
use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use Vanilla\InjectableInterface;
use Vanilla\Models\SiteMeta;
use Vanilla\Web\JsInterpop\ReduxActionPreloadTrait;
use Vanilla\Web\JsInterpop\ReduxErrorAction;

/**
 * Class representing a single page in the application.
 */
abstract class Page implements InjectableInterface, CustomExceptionHandler, PageHeadInterface {

    use TwigRenderTrait, ReduxActionPreloadTrait, PageHeadProxyTrait;

    /** @var bool */
    private $requiresSeo = true;

    /** @var int The page status code. */
    private $statusCode = 200;

    /**
     * Prepare the page contents.
     *
     * @return void
     */
    abstract public function initialize();

    /**
     * Get the section of the site we are serving assets for.
     */
    abstract public function getAssetSection(): string;

    /** @var SiteMeta */
    protected $siteMeta;

    /** @var \Gdn_Request */
    protected $request;

    /** @var \Gdn_Session */
    protected $session;

    /** @var string */
    protected $headerHtml = '';

    /** @var string */
    protected $footerHtml = '';

    /** @var string|null */
    protected $seoContent;

    /** @var PageHead */
    private $pageHead;

    /** @var MasterViewRenderer */
    private $masterViewRenderer;

    /**
     * Dependendency Injection.
     *
     * @param SiteMeta $siteMeta
     * @param \Gdn_Request $request
     * @param \Gdn_Session $session
     * @param PageHead $pageHead
     * @param MasterViewRenderer $masterViewRenderer
     */
    public function setDependencies(
        SiteMeta $siteMeta,
        \Gdn_Request $request,
        \Gdn_Session $session,
        PageHead $pageHead,
        MasterViewRenderer $masterViewRenderer
    ) {
        $this->siteMeta = $siteMeta;
        $this->request = $request;
        $this->session = $session;
        $this->pageHead = $pageHead;
        $this->masterViewRenderer = $masterViewRenderer;
        $this->pageHead->setAssetSection($this->getAssetSection());
        $this->setPageHeadProxy($this->pageHead);
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
     * @return PageHead
     */
    public function getHead(): PageHead {
        return $this->pageHead;
    }

    /**
     * @return string
     */
    public function getSeoContent(): ?string {
        return $this->seoContent;
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
        $this->addInlineScript($this->getReduxActionsAsJsVariable());

        $viewData = [
            'header' => $this->headerHtml,
            'footer' => $this->footerHtml,
            'cssClasses' => ['isLoading'],
            'seoContent' => $this->seoContent,
        ];

        $viewContent = $this->masterViewRenderer->renderPage($this, $viewData);

        return new Data($viewContent, $this->statusCode);
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
                $this->getSeoTitle() === null ||
                $this->getSeoBreadcrumbs() === null ||
                $this->seoContent === null ||
                $this->getSeoDescription() === null ||
                $this->getCanonicalUrl() === null
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
