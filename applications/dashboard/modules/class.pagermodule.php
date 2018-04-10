<?php
/**
 * Pager module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

use Vanilla\Web\WebLinking;

/**
 * Builds a pager control related to a dataset.
 */
class PagerModule extends Gdn_Module {
    const PREV_NEXT_CLASS = 'PrevNextPager';
    const NUMBERED_CLASS = 'NumberedPager';

    /** @var WebLinking */
    private $webLinking;

    /** @var int The id applied to the div tag that contains the pager. */
    public $ClientID;

    /** @var PagerModule */
    protected static $_CurrentPager;

    /** @var string The name of the stylesheet class to be applied to the pager. Default is 'Pager'. */
    public $CssClass;

    /** @var int The number of records in the current page. */
    public $CurrentRecords = false;

    /** @var int The default number of records per page. */
    public static $DefaultPageSize = 30;

    /** @var string Translation code to be used for "Next Page" link. */
    public $MoreCode;

    /**
     * @var string If there are no pages to page through, this string will be returned in
     * place of the pager. Default is an empty string.
     */
    public $PagerEmpty;

    /**
     * @var string The xhtml code that should wrap around the pager link.
     *  ie. '<div %1$s>%2$s</div>';
     * where %1$s represents id and class attributes (if defined by
     * $this->ClientID and $this->CssClass) and %2$s represents the pager link.
     */
    public $Wrapper;

    /** @var string Translation code to be used for "less" link. */
    public $LessCode;

    /** @var int The number of records being displayed on a single page of data. Default is 30. */
    public $Limit;

    /** @var int The total number of records in the dataset. */
    public $TotalRecords;

    /** @var string The string to contain the record offset. ie. /controller/action/%s/ */
    public $Url;

    /** @var string */
    public $UrlCallBack;

    /** @var int The first record of the current page (the dataset offset). */
    public $Offset;

    /** @var int The last offset of the current page. (ie. Offset to LastOffset of TotalRecords). */
    protected $_LastOffset;

    /**
     * @var bool Certain properties are required to be defined before the pager can build
     * itself. Once they are created, this property is set to true so they are
     * not needlessly recreated.
     */
    protected $_PropertiesDefined;

    /**
     * @var bool A boolean value indicating if the total number of records is known or
     * not. Retrieving this number can be a costly database query, so sometimes
     * it is not retrieved and simple "next/previous" links are displayed
     * instead. Default is FALSE, meaning that the simple pager is displayed.
     */
    protected $_Totalled;

    /**
     *
     *
     * @param string $sender
     */
    public function __construct($sender = '') {
        $this->ClientID = 'Pager';
        $this->CssClass = 'Pager';
        $this->Offset = 0;
        $this->Limit = self::$DefaultPageSize;
        $this->TotalRecords = false;
        $this->Wrapper = '<div class="PagerWrap"><div %1$s>%2$s</div></div>';
        $this->PagerEmpty = '';
        $this->MoreCode = '»';
        $this->LessCode = '«';
        $this->Url = '/controller/action/$s/';
        $this->_PropertiesDefined = false;
        $this->_Totalled = false;
        $this->_LastOffset = 0;

        $this->webLinking = Gdn::getContainer()->get(WebLinking::class);

        parent::__construct($sender);
    }

    /**
     * Add prev/next relationship links to document and the response headers.
     *
     * @param Gdn_Controller $controller
     */
    private function addRelLinks(Gdn_Controller $controller) {
        static $pending = true;

        if ($this->TotalRecords !== false && $this->Offset >= $this->TotalRecords) {
            return;
        }

        // Make sure this only happens once.
        if ($pending === true) {
            /** @var HeadModule $head */
            $head = $controller->Head;
            $currentPage = pageNumber($this->Offset, $this->Limit);

            if ($currentPage > 1) {
                $prevHref = $this->pageUrl($currentPage - 1);
                $head->addTag('link', [
                    'rel' => 'prev',
                    'href' => url($prevHref)
                ]);
                $this->webLinking->addLink('prev', url($prevHref, true));
            }

            if ($this->hasMorePages()) {
                $nextHref = $this->pageUrl($currentPage + 1);
                $head->addTag('link', [
                    'rel' => 'next',
                    'href' => url($nextHref)
                ]);
                $this->webLinking->addLink('next', url($nextHref, true));
            }

            $linkHeader = $this->webLinking->getLinkHeaderValue();
            if ($linkHeader) {
                $controller->setHeader('Link', $linkHeader);
            }

            $pending = false;
        }
    }

    /**
     *
     *
     * @return bool
     */
    public function assetTarget() {
        return false;
    }

    /**
     * Define all required parameters to create the Pager and PagerDetails.
     *
     * @param $offset
     * @param $limit
     * @param $totalRecords
     * @param $url
     * @param bool $forceConfigure
     * @throws Exception
     */
    public function configure($offset, $limit, $totalRecords, $url, $forceConfigure = false) {
        if ($this->_PropertiesDefined === false || $forceConfigure === true) {
            if (is_array($url)) {
                if (count($url) == 1) {
                    $this->UrlCallBack = array_pop($url);
                } else {
                    $this->UrlCallBack = $url;
                }
            } else {
                $this->Url = $url;
            }

            $this->Offset = $offset;
            $this->Limit = is_numeric($limit) && $limit > 0 ? $limit : $this->Limit;
            $this->TotalRecords = $totalRecords;
            $this->_LastOffset = $this->Offset + $this->Limit;
            $this->_Totalled = ($this->TotalRecords >= $this->Limit) ? false : true;
            if ($this->_LastOffset > $this->TotalRecords) {
                $this->_LastOffset = $this->TotalRecords;
            }

            $this->_PropertiesDefined = true;

            Gdn::controller()->EventArguments['Pager'] = $this;
            Gdn::controller()->fireEvent('PagerInit');
        }
    }

    /**
     * Gets the controller this pager is for.
     *
     * @return Gdn_Controller.
     */
    public function controller() {
        return $this->_Sender;
    }

    /**
     *
     *
     * @param null $value
     * @return null|PagerModule
     */
    public static function current($value = null) {
        if ($value !== null) {
            self::$_CurrentPager = $value;
        } elseif (self::$_CurrentPager == null) {
            self::$_CurrentPager = new PagerModule(Gdn::controller());
        }

        return self::$_CurrentPager;
    }

    /**
     * Builds a string with information about the page list's current position (ie. "1 to 15 of 56").
     *
     * @param string $formatString
     * @return bool|string Built string.
     */
    public function details($formatString = '') {
        if ($this->_PropertiesDefined === false) {
            trigger_error(errorMessage('You must configure the pager with $Pager->configure() before retrieving the pager details.', 'MorePager', 'Details'), E_USER_ERROR);
        }

        $details = false;
        if ($this->TotalRecords > 0) {
            if ($formatString != '') {
                $details = sprintf(t($formatString), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
            } elseif ($this->_Totalled === true) {
                $details = sprintf(t('%1$s to %2$s of %3$s'), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
            } else {
                $details = sprintf(t('%1$s to %2$s'), $this->Offset, $this->_LastOffset);
            }
        }
        return $details;
    }

    /**
     * Whether or not this is the first page of the pager.
     *
     * @return bool True if this is the first page.
     */
    public function firstPage() {
        $result = $this->Offset == 0;
        return $result;
    }

    /**
     *
     *
     * @param $url
     * @param $page
     * @param string $limit
     * @return mixed|string
     */
    public static function formatUrl($url, $page, $limit = '') {
        // Check for new style page.
        if (strpos($url, '{Page}') !== false) {
            return str_replace(['{Page}', '{Size}'], [$page, $limit], $url);
        } else {
            return sprintf($url, $page, $limit);
        }
    }

    /**
     * Whether or not this is the last page of the pager.
     *
     * @return bool True if this is the last page.
     */
    public function lastPage() {
        return $this->Offset + $this->Limit >= $this->TotalRecords;
    }

    /**
     *
     *
     * @param $page
     * @param $currentPage
     * @return null|string
     */
    public static function rel($page, $currentPage) {
        if ($page == $currentPage - 1) {
            return 'prev';
        } elseif ($page == $currentPage + 1)
            return 'next';

        return null;
    }

    /**
     *
     *
     * @param $page
     * @return mixed|string
     */
    public function pageUrl($page) {
        if ($this->UrlCallBack) {
            return call_user_func($this->UrlCallBack, $this->Record, $page);
        } else {
            return self::formatUrl($this->Url, $page > 1 ? 'p'.$page : '');
        }
    }

    /**
     * Builds page navigation links.
     *
     * @param string $type Type of link to return: 'more' or 'less'.
     * @return string HTML page navigation links.
     */
    public function toString($type = 'more') {
        if ($this->_PropertiesDefined === false) {
            trigger_error(errorMessage('You must configure the pager with $Pager->configure() before retrieving the pager.', 'MorePager', 'GetSimple'), E_USER_ERROR);
        }

        // Urls with url-encoded characters will break sprintf, so we need to convert them for backwards compatibility.
        $this->Url = str_replace(['%1$s', '%2$s', '%s'], '{Page}', $this->Url);

        $this->addRelLinks(Gdn::controller());

        if ($this->TotalRecords === false) {
            return $this->toStringPrevNext($type);
        }

        // Get total page count, allowing override
        $pageCount = ceil($this->TotalRecords / $this->Limit);
        $this->EventArguments['PageCount'] = &$pageCount;
        $this->fireEvent('BeforePagerSetsCount');
        $this->_PageCount = $pageCount;
        $currentPage = pageNumber($this->Offset, $this->Limit);

        // If the pager is on a pager greater than the count then just display a previous button.
        if ($currentPage > 1 && $currentPage > $pageCount) {
            return sprintf(
                $this->Wrapper,
                attribute(['class' => concatSep(' ', $this->CssClass, static::PREV_NEXT_CLASS)]),
                $this->previousLink($pageCount + 1)
            );
        }

        // Show $Range pages on either side of current
        $range = c('Garden.Modules.PagerRange', 3);

        // String to represent skipped pages
        $separator = c('Garden.Modules.PagerSeparator', '&#8230;');

        // Show current page plus $Range pages on either side
        $pagesToDisplay = ($range * 2) + 1;
        if ($pagesToDisplay + 2 >= $pageCount) {
            // Don't display an ellipses if the page count is only a little bigger that the number of pages.
            $pagesToDisplay = $pageCount;
        }

        $pager = '';
        $previousText = t($this->LessCode);
        $nextText = t($this->MoreCode);

        // Previous
        if ($currentPage == 1) {
            $pager = '<span class="Previous">'.$previousText.'</span>';
        } else {
            $pager .= anchor($previousText, $this->pageUrl($currentPage - 1), 'Previous', ['rel' => 'prev']);
        }

        // Build Pager based on number of pages (Examples assume $Range = 3)
        if ($pageCount <= 1) {
            // Don't build anything

        } elseif ($pageCount <= $pagesToDisplay) {
            // We don't need elipsis (ie. 1 2 3 4 5 6 7)
            for ($i = 1; $i <= $pageCount; $i++) {
                $pager .= anchor($i, $this->pageUrl($i), $this->_GetCssClass($i, $currentPage), ['rel' => self::rel($i, $currentPage)]);
            }

        } elseif ($currentPage + $range <= $pagesToDisplay + 1) { // +1 prevents 1 ... 2
            // We're on a page that is before the first elipsis (ex: 1 2 3 4 5 6 7 ... 81)
            for ($i = 1; $i <= $pagesToDisplay; $i++) {
                $pageParam = 'p'.$i;
                $pager .= anchor($i, $this->pageUrl($i), $this->_GetCssClass($i, $currentPage), ['rel' => self::rel($i, $currentPage)]);
            }

            $pager .= '<span class="Ellipsis">'.$separator.'</span>';
            $pager .= anchor($pageCount, $this->pageUrl($pageCount), $this->_GetCssClass($pageCount, $currentPage));

        } elseif ($currentPage + $range >= $pageCount - 1) { // -1 prevents 80 ... 81
            // We're on a page that is after the last elipsis (ex: 1 ... 75 76 77 78 79 80 81)
            $pager .= anchor(1, $this->pageUrl(1), $this->_GetCssClass(1, $currentPage));
            $pager .= '<span class="Ellipsis">'.$separator.'</span>';

            for ($i = $pageCount - ($pagesToDisplay - 1); $i <= $pageCount; $i++) {
                $pageParam = 'p'.$i;
                $pager .= anchor($i, $this->pageUrl($i), $this->_GetCssClass($i, $currentPage), ['rel' => self::rel($i, $currentPage)]);
            }

        } else {
            // We're between the two elipsises (ex: 1 ... 4 5 6 7 8 9 10 ... 81)
            $pager .= anchor(1, $this->pageUrl(1), $this->_GetCssClass(1, $currentPage));
            $pager .= '<span class="Ellipsis">'.$separator.'</span>';

            for ($i = $currentPage - $range; $i <= $currentPage + $range; $i++) {
                $pageParam = 'p'.$i;
                $pager .= anchor($i, $this->pageUrl($i), $this->_GetCssClass($i, $currentPage), ['rel' => self::rel($i, $currentPage)]);
            }

            $pager .= '<span class="Ellipsis">'.$separator.'</span>';
            $pager .= anchor($pageCount, $this->pageUrl($pageCount), $this->_GetCssClass($pageCount, $currentPage));
        }

        // Next
        if ($currentPage == $pageCount) {
            $pager .= '<span class="Next">'.$nextText.'</span>';
        } else {
            $pageParam = 'p'.($currentPage + 1);
            $pager .= anchor($nextText, $this->pageUrl($currentPage + 1), 'Next', ['rel' => 'next']); // extra sprintf parameter in case old url style is set
        }
        if ($pageCount <= 1) {
            $pager = '';
        }

        $clientID = $this->ClientID;
        $clientID = $type == 'more' ? $clientID.'After' : $clientID.'Before';

        if ($pager) {
            if (isset($this->HtmlBefore)) {
                $pager = $this->HtmlBefore.$pager;
            }
            if (isset($this->HtmlAfter)) {
                $pager = ' '.$pager.$this->HtmlAfter;
            }
        }

        return $pager == '' ? '' : sprintf($this->Wrapper, attribute(['id' => $clientID, 'class' => concatSep(' ', $this->CssClass, static::NUMBERED_CLASS)]), $pager);
    }

    /**
     *
     *
     * @param string $type
     * @return string
     */
    public function toStringPrevNext($type = 'more') {
        $currentPage = pageNumber($this->Offset, $this->Limit);

        $pager = '';

        if ($currentPage > 1) {
            $pageParam = 'p'.($currentPage - 1);
            $pager .= $this->previousLink($currentPage);
        }

        $hasNext = true;
        if ($this->CurrentRecords !== false && $this->CurrentRecords < $this->Limit) {
            $hasNext = false;
        }

        if ($hasNext) {
            $pageParam = 'p'.($currentPage + 1);
            $pager = concatSep(' ', $pager, anchor(t('Next'), $this->pageUrl($currentPage + 1), 'Next', ['rel' => 'next']));
        }

        $clientID = $this->ClientID;
        $clientID = $type == 'more' ? $clientID.'After' : $clientID.'Before';

        if (isset($this->HtmlBefore)) {
            $pager = $this->HtmlBefore.$pager;
        }

        return $pager == '' ? '' : sprintf(
            $this->Wrapper,
            attribute(['id' => $clientID, 'class' => concatSep(' ', $this->CssClass, static::PREV_NEXT_CLASS)]),
            $pager
        );
    }

    /**
     *
     *
     * @param array $options
     * @throws Exception
     */
    public static function write($options = []) {
        static $writeCount = 0;

        if (!self::$_CurrentPager) {
            if (is_a($options, 'Gdn_Controller')) {
                self::$_CurrentPager = new PagerModule($options);
                $options = [];
            } else {
                self::$_CurrentPager = new PagerModule(val('Sender', $options, Gdn::controller()));
            }
        }
        $pager = self::$_CurrentPager;
        if ($view = val('View', $options)) {
            $pager->setView($view);
        }
        $pager->Wrapper = val('Wrapper', $options, $pager->Wrapper);
        $pager->MoreCode = val('MoreCode', $options, $pager->MoreCode);
        $pager->LessCode = val('LessCode', $options, $pager->LessCode);

        $pager->ClientID = val('ClientID', $options, $pager->ClientID);
        $pager->CssClass = val('CssClass', $options, 'Pager');

        $pager->Limit = val('Limit', $options, $pager->controller()->data('_Limit', $pager->Limit));
        $pager->HtmlBefore = val('HtmlBefore', $options, val('HtmlBefore', $pager, ''));
        $pager->CurrentRecords = val('CurrentRecords', $options, $pager->controller()->data('_CurrentRecords', $pager->CurrentRecords));

        // Try and figure out the offset based on the parameters coming in to the controller.
        if (!$pager->Offset) {
            $page = $pager->controller()->Request->get('Page', false);
            if (!$page) {
                $page = 'p1';
                foreach ($pager->controller()->RequestArgs as $arg) {
                    if (preg_match('`p\d+`', $arg)) {
                        $page = $arg;
                        break;
                    }
                }
            }
            list($offset, $limit) = offsetLimit($page, $pager->Limit);
            $totalRecords = val('RecordCount', $options, $pager->controller()->data('RecordCount', false));

            $get = $pager->controller()->Request->get();
            unset($get['Page'], $get['DeliveryType'], $get['DeliveryMethod']);
            $url = val('Url', $options, $pager->controller()->SelfUrl.'?Page={Page}&'.http_build_query($get));

            $pager->configure($offset, $limit, $totalRecords, $url);
        } elseif ($url = val('Url', $options)) {
            $pager->Url = $url;
        }

        if ($view) {
            Gdn::controller()->setData('Pager', $pager);
            echo $pager->fetchView($view);
        } else {
            echo $pager->toString($writeCount > 0 ? 'more' : 'less');
        }
        $writeCount++;

//      list($Offset, $Limit) = offsetLimit(GetValue, 20);
//		$Pager->configure(
//			$Offset,
//			$Limit,
//			$TotalAddons,
//			"/settings/addons/$Section?Page={Page}"
//		);
//		$Sender->setData('_Pager', $Pager);
    }

    /**
     *
     *
     * @param $thisPage
     * @param $highlightPage
     * @return string
     */
    private function _GetCssClass($thisPage, $highlightPage) {
        $result = $thisPage == $highlightPage ? 'Highlight' : '';

        $result .= " p-$thisPage";
        if ($thisPage == 1) {
            $result .= ' FirstPage';
        } elseif ($thisPage == $this->_PageCount)
            $result .= ' LastPage';

        return $result;
    }

    /**
     * Are there more pages after the current one?
     */
    public function hasMorePages() {
        return $this->TotalRecords > $this->Offset + $this->Limit;
    }

    /**
     * @param $currentPage
     * @return string
     */
    private function previousLink($currentPage): string {
        return anchor(t('Previous'), $this->pageUrl($currentPage - 1), 'Previous', ['rel' => 'prev']);
    }
}
