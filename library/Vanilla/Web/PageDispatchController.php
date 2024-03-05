<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Container\Container;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Container\Reference;
use Garden\CustomExceptionHandler;
use Garden\Web\Data;
use Garden\Web\PageControllerRoute;
use Garden\Web\ResourceRoute;
use Throwable;
use Vanilla\InjectableInterface;
use Vanilla\Utility\StringUtils;
use Vanilla\Utility\Timers;
use Vanilla\Utility\TracedContainer;

/**
 * A controller used for mapping from the dispatcher to individual page components.
 *
 * @see \Garden\Web\Dispatcher
 * @see \Vanilla\Web\Page
 */
class PageDispatchController implements CustomExceptionHandler, InjectableInterface
{
    /** @var Page The active page. */
    protected $activePage;

    /** @var Container */
    protected $container;

    /** @var string|null */
    protected $assetSection = null;
    /** @var string Class to use for useSimplePage */
    protected $simplePageClass = SimpleTitlePage::class;

    /**
     * Dependency Injection.
     * It's generally an antipattern to inject the container, but this is a dispatcher.
     *
     * @param Container $container The container object for locating and creating page classes.
     */
    public function setDependencies(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Forward the call onto our active page if we have one.
     *
     * @inheritdoc
     */
    public function hasExceptionHandler(Throwable $e): bool
    {
        return true;
    }

    /**
     * Use or active pages handler.
     *
     * @inheritdoc
     */
    public function handleException(Throwable $e): Data
    {
        $activePage = $this->activePage ?? null;
        if ($activePage === null) {
            $activePage = $this->container->get(SimpleTitlePage::class);
            $activePage->getHead()->setAssetSection("layouts");
        }

        return $activePage->handleException($e);
    }

    /**
     * Instantiate a page class and set it as the active instance.
     *
     * @template T of Page
     *
     * @param class-string<T>|T $pageOrPageClass
     * @return T The instance of the requested page.
     * @throws NotFoundException If the page class couldn't be located.
     * @throws ContainerException Error while retrieving the entry.
     */
    protected function usePage($pageOrPageClass): Page
    {
        if ($pageOrPageClass instanceof Page) {
            $page = $pageOrPageClass;
        } else {
            $span = Timers::instance()->startGeneric("create-page", [
                "name" => "Create Page - {$pageOrPageClass}",
            ]);
            $page = TracedContainer::trace(function () use ($pageOrPageClass) {
                return $this->container->get($pageOrPageClass);
            });
            $span->finish();
        }

        $this->activePage = $page;

        return $page;
    }

    /**
     * Instantiate a SimpleTitlePage with a title and set it as the active instance.
     *
     * @param string $title The title to use.
     *
     * @return Page
     * @throws ContainerException
     * @throws NotFoundException
     */
    protected function useSimplePage(string $title): Page
    {
        /** @var Page $page */
        $page = $this->container->get($this->simplePageClass);

        if ($this->assetSection && $page instanceof Page) {
            $page->setAssetSection($this->assetSection);
        }

        $this->activePage = $page;
        $this->activePage->initialize($title);

        return $this->activePage;
    }
}
