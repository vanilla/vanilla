<?php
/**
 * Pager module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Builds a pager control related to a dataset.
 */
class PagerModule extends Gdn_Module {

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
    private $_LastOffset;

    /**
     * @var bool Certain properties are required to be defined before the pager can build
     * itself. Once they are created, this property is set to true so they are
     * not needlessly recreated.
     */
    private $_PropertiesDefined;

    /**
     * @var bool A boolean value indicating if the total number of records is known or
     * not. Retrieving this number can be a costly database query, so sometimes
     * it is not retrieved and simple "next/previous" links are displayed
     * instead. Default is FALSE, meaning that the simple pager is displayed.
     */
    private $_Totalled;

    /**
     *
     *
     * @param string $Sender
     */
    public function __construct($Sender = '') {
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
        parent::__construct($Sender);
    }

    /**
     *
     *
     * @return bool
     */
    function assetTarget() {
        return false;
    }

    /**
     * Define all required parameters to create the Pager and PagerDetails.
     *
     * @param $Offset
     * @param $Limit
     * @param $TotalRecords
     * @param $Url
     * @param bool $ForceConfigure
     * @throws Exception
     */
    public function configure($Offset, $Limit, $TotalRecords, $Url, $ForceConfigure = false) {
        if ($this->_PropertiesDefined === false || $ForceConfigure === true) {
            if (is_array($Url)) {
                if (count($Url) == 1) {
                    $this->UrlCallBack = array_pop($Url);
                } else {
                    $this->UrlCallBack = $Url;
                }
            } else {
                $this->Url = $Url;
            }

            $this->Offset = $Offset;
            $this->Limit = is_numeric($Limit) && $Limit > 0 ? $Limit : $this->Limit;
            $this->TotalRecords = $TotalRecords;
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
     * @param null $Value
     * @return null|PagerModule
     */
    public static function current($Value = null) {
        if ($Value !== null) {
            self::$_CurrentPager = $Value;
        } elseif (self::$_CurrentPager == null) {
            self::$_CurrentPager = new PagerModule(Gdn::controller());
        }

        return self::$_CurrentPager;
    }

    /**
     * Builds a string with information about the page list's current position (ie. "1 to 15 of 56").
     *
     * @param string $FormatString
     * @return bool|string Built string.
     */
    public function details($FormatString = '') {
        if ($this->_PropertiesDefined === false) {
            trigger_error(ErrorMessage('You must configure the pager with $Pager->configure() before retrieving the pager details.', 'MorePager', 'Details'), E_USER_ERROR);
        }

        $Details = false;
        if ($this->TotalRecords > 0) {
            if ($FormatString != '') {
                $Details = sprintf(t($FormatString), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
            } elseif ($this->_Totalled === true) {
                $Details = sprintf(t('%1$s to %2$s of %3$s'), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
            } else {
                $Details = sprintf(t('%1$s to %2$s'), $this->Offset, $this->_LastOffset);
            }
        }
        return $Details;
    }

    /**
     * Whether or not this is the first page of the pager.
     *
     * @return bool True if this is the first page.
     */
    public function firstPage() {
        $Result = $this->Offset == 0;
        return $Result;
    }

    /**
     *
     *
     * @param $Url
     * @param $Page
     * @param string $Limit
     * @return mixed|string
     */
    public static function formatUrl($Url, $Page, $Limit = '') {
        // Check for new style page.
        if (strpos($Url, '{Page}') !== false) {
            return str_replace(array('{Page}', '{Size}'), array($Page, $Limit), $Url);
        } else {
            return sprintf($Url, $Page, $Limit);
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
     * @param $Page
     * @param $CurrentPage
     * @return null|string
     */
    public static function rel($Page, $CurrentPage) {
        if ($Page == $CurrentPage - 1) {
            return 'prev';
        } elseif ($Page == $CurrentPage + 1)
            return 'next';

        return null;
    }

    /**
     *
     *
     * @param $Page
     * @return mixed|string
     */
    public function pageUrl($Page) {
        if ($this->UrlCallBack) {
            return call_user_func($this->UrlCallBack, $this->Record, $Page);
        } else {
            return self::FormatUrl($this->Url, 'p'.$Page);
        }
    }

    /**
     * Builds page navigation links.
     *
     * @param string $Type Type of link to return: 'more' or 'less'.
     * @return string HTML page navigation links.
     */
    public function toString($Type = 'more') {
        if ($this->_PropertiesDefined === false) {
            trigger_error(ErrorMessage('You must configure the pager with $Pager->configure() before retrieving the pager.', 'MorePager', 'GetSimple'), E_USER_ERROR);
        }

        // Urls with url-encoded characters will break sprintf, so we need to convert them for backwards compatibility.
        $this->Url = str_replace(array('%1$s', '%2$s', '%s'), '{Page}', $this->Url);

        if ($this->TotalRecords === false) {
            return $this->ToStringPrevNext($Type);
        }

        $this->CssClass = ConcatSep(' ', $this->CssClass, 'NumberedPager');

        // Get total page count, allowing override
        $PageCount = ceil($this->TotalRecords / $this->Limit);
        $this->EventArguments['PageCount'] = &$PageCount;
        $this->fireEvent('BeforePagerSetsCount');
        $this->_PageCount = $PageCount;
        $CurrentPage = PageNumber($this->Offset, $this->Limit);

        // Show $Range pages on either side of current
        $Range = c('Garden.Modules.PagerRange', 3);

        // String to represent skipped pages
        $Separator = c('Garden.Modules.PagerSeparator', '&#8230;');

        // Show current page plus $Range pages on either side
        $PagesToDisplay = ($Range * 2) + 1;
        if ($PagesToDisplay + 2 >= $PageCount) {
            // Don't display an ellipses if the page count is only a little bigger that the number of pages.
            $PagesToDisplay = $PageCount;
        }

        $Pager = '';
        $PreviousText = t($this->LessCode);
        $NextText = t($this->MoreCode);

        // Previous
        if ($CurrentPage == 1) {
            $Pager = '<span class="Previous">'.$PreviousText.'</span>';
        } else {
            $Pager .= anchor($PreviousText, $this->PageUrl($CurrentPage - 1), 'Previous', array('rel' => 'prev'));
        }

        // Build Pager based on number of pages (Examples assume $Range = 3)
        if ($PageCount <= 1) {
            // Don't build anything

        } elseif ($PageCount <= $PagesToDisplay) {
            // We don't need elipsis (ie. 1 2 3 4 5 6 7)
            for ($i = 1; $i <= $PageCount; $i++) {
                $Pager .= anchor($i, $this->PageUrl($i), $this->_GetCssClass($i, $CurrentPage), array('rel' => self::Rel($i, $CurrentPage)));
            }

        } elseif ($CurrentPage + $Range <= $PagesToDisplay + 1) { // +1 prevents 1 ... 2
            // We're on a page that is before the first elipsis (ex: 1 2 3 4 5 6 7 ... 81)
            for ($i = 1; $i <= $PagesToDisplay; $i++) {
                $PageParam = 'p'.$i;
                $Pager .= anchor($i, $this->PageUrl($i), $this->_GetCssClass($i, $CurrentPage), array('rel' => self::Rel($i, $CurrentPage)));
            }

            $Pager .= '<span class="Ellipsis">'.$Separator.'</span>';
            $Pager .= anchor($PageCount, $this->PageUrl($PageCount), $this->_GetCssClass($PageCount, $CurrentPage));

        } elseif ($CurrentPage + $Range >= $PageCount - 1) { // -1 prevents 80 ... 81
            // We're on a page that is after the last elipsis (ex: 1 ... 75 76 77 78 79 80 81)
            $Pager .= anchor(1, $this->PageUrl(1), $this->_GetCssClass(1, $CurrentPage));
            $Pager .= '<span class="Ellipsis">'.$Separator.'</span>';

            for ($i = $PageCount - ($PagesToDisplay - 1); $i <= $PageCount; $i++) {
                $PageParam = 'p'.$i;
                $Pager .= anchor($i, $this->PageUrl($i), $this->_GetCssClass($i, $CurrentPage), array('rel' => self::Rel($i, $CurrentPage)));
            }

        } else {
            // We're between the two elipsises (ex: 1 ... 4 5 6 7 8 9 10 ... 81)
            $Pager .= anchor(1, $this->PageUrl(1), $this->_GetCssClass(1, $CurrentPage));
            $Pager .= '<span class="Ellipsis">'.$Separator.'</span>';

            for ($i = $CurrentPage - $Range; $i <= $CurrentPage + $Range; $i++) {
                $PageParam = 'p'.$i;
                $Pager .= anchor($i, $this->PageUrl($i), $this->_GetCssClass($i, $CurrentPage), array('rel' => self::Rel($i, $CurrentPage)));
            }

            $Pager .= '<span class="Ellipsis">'.$Separator.'</span>';
            $Pager .= anchor($PageCount, $this->PageUrl($PageCount), $this->_GetCssClass($PageCount, $CurrentPage));
        }

        // Next
        if ($CurrentPage == $PageCount) {
            $Pager .= '<span class="Next">'.$NextText.'</span>';
        } else {
            $PageParam = 'p'.($CurrentPage + 1);
            $Pager .= anchor($NextText, $this->PageUrl($CurrentPage + 1), 'Next', array('rel' => 'next')); // extra sprintf parameter in case old url style is set
        }
        if ($PageCount <= 1) {
            $Pager = '';
        }

        $ClientID = $this->ClientID;
        $ClientID = $Type == 'more' ? $ClientID.'After' : $ClientID.'Before';

        if ($Pager) {
            if (isset($this->HtmlBefore)) {
                $Pager = $this->HtmlBefore.$Pager;
            }
            if (isset($this->HtmlAfter)) {
                $Pager = ' '.$Pager.$this->HtmlAfter;
            }
        }

        return $Pager == '' ? '' : sprintf($this->Wrapper, Attribute(array('id' => $ClientID, 'class' => $this->CssClass)), $Pager);
    }

    /**
     *
     *
     * @param string $Type
     * @return string
     */
    public function toStringPrevNext($Type = 'more') {
        $this->CssClass = ConcatSep(' ', $this->CssClass, 'PrevNextPager');
        $CurrentPage = PageNumber($this->Offset, $this->Limit);

        $Pager = '';

        if ($CurrentPage > 1) {
            $PageParam = 'p'.($CurrentPage - 1);
            $Pager .= anchor(t('Previous'), $this->PageUrl($CurrentPage - 1), 'Previous', array('rel' => 'prev'));
        }

        $HasNext = true;
        if ($this->CurrentRecords !== false && $this->CurrentRecords < $this->Limit) {
            $HasNext = false;
        }

        if ($HasNext) {
            $PageParam = 'p'.($CurrentPage + 1);
            $Pager = ConcatSep(' ', $Pager, anchor(t('Next'), $this->PageUrl($CurrentPage + 1), 'Next', array('rel' => 'next')));
        }

        $ClientID = $this->ClientID;
        $ClientID = $Type == 'more' ? $ClientID.'After' : $ClientID.'Before';

        if (isset($this->HtmlBefore)) {
            $Pager = $this->HtmlBefore.$Pager;
        }

        return $Pager == '' ? '' : sprintf($this->Wrapper, Attribute(array('id' => $ClientID, 'class' => $this->CssClass)), $Pager);
    }

    /**
     *
     *
     * @param array $Options
     * @throws Exception
     */
    public static function write($Options = array()) {
        static $WriteCount = 0;

        if (!self::$_CurrentPager) {
            if (is_a($Options, 'Gdn_Controller')) {
                self::$_CurrentPager = new PagerModule($Options);
                $Options = array();
            } else {
                self::$_CurrentPager = new PagerModule(val('Sender', $Options, Gdn::controller()));
            }
        }
        $Pager = self::$_CurrentPager;

        $Pager->Wrapper = val('Wrapper', $Options, $Pager->Wrapper);
        $Pager->MoreCode = val('MoreCode', $Options, $Pager->MoreCode);
        $Pager->LessCode = val('LessCode', $Options, $Pager->LessCode);

        $Pager->ClientID = val('ClientID', $Options, $Pager->ClientID);

        $Pager->Limit = val('Limit', $Options, $Pager->Controller()->data('_Limit', $Pager->Limit));
        $Pager->HtmlBefore = val('HtmlBefore', $Options, val('HtmlBefore', $Pager, ''));
        $Pager->CurrentRecords = val('CurrentRecords', $Options, $Pager->Controller()->data('_CurrentRecords', $Pager->CurrentRecords));

        // Try and figure out the offset based on the parameters coming in to the controller.
        if (!$Pager->Offset) {
            $Page = $Pager->Controller()->Request->get('Page', false);
            if (!$Page) {
                $Page = 'p1';
                foreach ($Pager->Controller()->RequestArgs as $Arg) {
                    if (preg_match('`p\d+`', $Arg)) {
                        $Page = $Arg;
                        break;
                    }
                }
            }
            list($Offset, $Limit) = offsetLimit($Page, $Pager->Limit);
            $TotalRecords = val('RecordCount', $Options, $Pager->Controller()->data('RecordCount', false));

            $Get = $Pager->Controller()->Request->get();
            unset($Get['Page'], $Get['DeliveryType'], $Get['DeliveryMethod']);
            $Url = val('Url', $Options, $Pager->Controller()->SelfUrl.'?Page={Page}&'.http_build_query($Get));

            $Pager->configure($Offset, $Limit, $TotalRecords, $Url);
        } elseif ($Url = val('Url', $Options)) {
            $Pager->Url = $Url;
        }

        echo $Pager->toString($WriteCount > 0 ? 'more' : 'less');
        $WriteCount++;

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
     * @param $ThisPage
     * @param $HighlightPage
     * @return string
     */
    private function _GetCssClass($ThisPage, $HighlightPage) {
        $Result = $ThisPage == $HighlightPage ? 'Highlight' : '';

        $Result .= " p-$ThisPage";
        if ($ThisPage == 1) {
            $Result .= ' FirstPage';
        } elseif ($ThisPage == $this->_PageCount)
            $Result .= ' LastPage';

        return $Result;
    }

    /**
     * Are there more pages after the current one?
     */
    public function hasMorePages() {
        return $this->TotalRecords > $this->Offset + $this->Limit;
    }
}
