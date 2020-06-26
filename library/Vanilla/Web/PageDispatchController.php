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
use Garden\CustomExceptionHandler;
use Garden\Web\Data;
use Vanilla\InjectableInterface;

/**
 * A controller used for mapping from the the dispatcher to individual page components.
 *
 * @see \Garden\Web\Dispatcher
 * @see \Vanilla\Web\Page
 */
class PageDispatchController implements CustomExceptionHandler, InjectableInterface {

    /** @var Page The active page. */
    private $activePage;

    /** @var Container */
    private $container;

    /**
     * Dependency Injection.
     * It's generally an antipattern to inject the container, but this is a dispatcher.
     *
     * @param Container $container The container object for locating and creating page classes.
     */
    public function setDependencies(Container $container) {
        $this->container = $container;
    }

    /**
     * Instantiate a page class and set it as the active instance.
     *
     * @param string $pageClass
     * @return Page The instance of the requested page.
     * @throws NotFoundException If the page class couldn't be located.
     * @throws ContainerException Error while retrieving the entry.
     */
    protected function usePage(string $pageClass): Page {
        $page = $this->container->get($pageClass);
        $this->activePage = $page;
        return $page;
    }

    /** @var string Class to use for useSimplePage */
    protected $simplePageClass = SimpleTitlePage::class;

    /**
     * Instantiate a SimpleTitlePage with a title and set it as the active instance.
     *
     * @param string $title The title to use.
     * @return Page
     */
    protected function useSimplePage(string $title): Page {
        /** @var Page $page */
        $page = $this->container->get($this->simplePageClass);
        $page->initialize($title);
        $this->activePage = $page;
        return $page;
    }

    /**
     * Forward the call onto our active page if we have one.
     * @inheritdoc
     */
    public function hasExceptionHandler(\Throwable $e): bool {
        if ($this->activePage) {
            return $this->activePage->hasExceptionHandler($e);
        }
        return false;
    }

    /**
     * Use or active pages handler.
     * @inheritdoc
     */
    public function handleException(\Throwable $e): Data {
        return $this->activePage->handleException($e);
    }
}
