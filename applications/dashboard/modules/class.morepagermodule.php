<?php
/**
 * MorePager module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Builds a pager control related to a dataset.
 */
class MorePagerModule extends PagerModule {

    /** @var int The id applied to the div tag that contains the pager. */
    public $ClientID;

    /** @var string The name of the stylesheet class to be applied to the pager. Default is 'Pager'. */
    public $CssClass;

    /** @var string Translation code to be used for "more" link. */
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

    /** @var The number of records being displayed on a single page of data. Default is 30. */
    public $Limit;

    /** @var The total number of records in the dataset. */
    public $TotalRecords;

    /** @var string The string to contain the record offset. ie. /controller/action/%s/ */
    public $Url;

    /** @var int The first record of the current page (the dataset offset). */
    public $Offset;

    /**
     *
     *
     * @param string $sender
     */
    public function __construct($sender = '') {
        parent::__construct($sender);
        $this->ClientID = '';
        $this->CssClass = 'MorePager Foot';
        $this->Offset = 0;
        $this->Limit = 30;
        $this->TotalRecords = 0;
        $this->Wrapper = '<div %1$s>%2$s</div>';
        $this->PagerEmpty = '';
        $this->MoreCode = 'More';
        $this->LessCode = 'Newer';
        $this->Url = '/controller/action/{Page}/';
        $this->_PropertiesDefined = false;
        $this->_Totalled = false;
        $this->_LastOffset = 0;
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
     */
    public function configure($offset, $limit, $totalRecords, $url, $forceConfigure = false) {
        if ($this->_PropertiesDefined === false || $forceConfigure === true) {
            $this->Url = $url;

            $this->Offset = $offset;
            $this->Limit = is_numeric($limit) && $limit > 0 ? $limit : $this->Limit;
            $this->TotalRecords = is_numeric($totalRecords) ? $totalRecords : 0;
            $this->_Totalled = ($this->TotalRecords >= $this->Limit) ? false : true;
            $this->_LastOffset = $this->Offset + $this->Limit;
            if ($this->_LastOffset > $this->TotalRecords) {
                $this->_LastOffset = $this->TotalRecords;
            }

            $this->_PropertiesDefined = true;
        }
    }

   /**
    * Builds a string with information about the page list's current position (ie. "1 to 15 of 56").
    *
    * @param string $formatString Not used.
    * @return string Built string.
    */
    public function details($formatString = '') {
        if ($this->_PropertiesDefined === false) {
            trigger_error(errorMessage('You must configure the pager with $Pager->configure() before retrieving the pager details.', 'MorePager', 'Details'), E_USER_ERROR);
        }

        $details = false;
        if ($this->TotalRecords > 0) {
            if ($this->_Totalled === true) {
                $details = self::formatUrl(t('%s$1 to %s$2 of %s$3'), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
            } else {
                $details = self::formatUrl(t('%s$1 to %s$2'), $this->Offset, $this->_LastOffset);
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
     * @param $offset
     * @param string $limit
     * @return mixed|string
     */
    public static function formatUrl($url, $offset, $limit = '') {
        // Check for new style page.
        if (strpos($url, '{Page}') !== false || strpos($url, '{Offset}') !== false) {
            $page = pageNumber($offset, $limit, true);
            return str_replace(['{Offset}', '{Page}', '{Size}'], [$offset, $page, $limit], $url);
        } else {
            return self::formatUrl($url, $page, $limit);
        }

    }

    /**
     * Whether or not this is the last page of the pager.
     *
     * @return bool True if this is the last page.
     */
    public function lastPage() {
        $result = $this->Offset + $this->Limit >= $this->TotalRecords;
        return $result;
    }

    /**
     * Returns the "show x more (or less) items" link.
     *
     * @param string The type of link to return: more or less
     */
    public function toString($type = 'more') {
        if ($this->_PropertiesDefined === false) {
            trigger_error(errorMessage('You must configure the pager with $Pager->configure() before retrieving the pager.', 'MorePager', 'GetSimple'), E_USER_ERROR);
        }

        // Urls with url-encoded characters will break sprintf, so we need to convert them for backwards compatibility.
        $this->Url = str_replace(['%1$s', '%2$s', '%s'], ['{Offset}', '{Size}', '{Offset}'], $this->Url);

        $pager = '';
        if ($type == 'more') {
            $clientID = $this->ClientID == '' ? '' : $this->ClientID.'More';
            if ($this->Offset + $this->Limit >= $this->TotalRecords) {
                $pager = ''; // $this->Offset .' + '. $this->Limit .' >= '. $this->TotalRecords;
            } else {
                $actualRecordsLeft = $recordsLeft = $this->TotalRecords - $this->_LastOffset;
                if ($recordsLeft > $this->Limit) {
                    $recordsLeft = $this->Limit;
                }

                $nextOffset = $this->Offset + $this->Limit;

                $pager .= anchor(
                    sprintf(t($this->MoreCode), $actualRecordsLeft),
                    self::formatUrl($this->Url, $nextOffset, $this->Limit),
                    '',
                    ['rel' => 'nofollow']
                );
            }
        } elseif ($type == 'less') {
            $clientID = $this->ClientID == '' ? '' : $this->ClientID.'Less';
            if ($this->Offset <= 0) {
                $pager = '';
            } else {
                $recordsBefore = $this->Offset;
                if ($recordsBefore > $this->Limit) {
                    $recordsBefore = $this->Limit;
                }

                $previousOffset = $this->Offset - $this->Limit;
                if ($previousOffset < 0) {
                    $previousOffset = 0;
                }

                $pager .= anchor(
                    sprintf(t($this->LessCode), $this->Offset),
                    self::formatUrl($this->Url, $previousOffset, $recordsBefore),
                    '',
                    ['rel' => 'nofollow']
                );
            }
        }
        if ($pager == '') {
            return $this->PagerEmpty;
        } else {
            return sprintf($this->Wrapper, attribute(['id' => $clientID, 'class' => $this->CssClass]), $pager);
        }
    }

    /**
     * Are there more pages after the current one?
     */
    public function hasMorePages() {
        return $this->TotalRecords > $this->Offset + $this->Limit;
    }
}
