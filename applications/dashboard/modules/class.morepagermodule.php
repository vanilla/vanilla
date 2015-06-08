<?php
/**
 * MorePager module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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

    /** @var int The last offset of the current page. (ie. Offset to LastOffset of TotalRecords). */
    private $_LastOffset;

    /**
     * @var array Certain properties are required to be defined before the pager can build
     * itself. Once they are created, this property is set to true so they are
     * not needlessly recreated.
     */
    private $_PropertiesDefined;

    /**
     * @var bool Indicating if the total number of records is known or
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
        parent::__construct($Sender);
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
    function assetTarget() {
        return false;
    }

    /**
     * Define all required parameters to create the Pager and PagerDetails.
     */
    public function configure($Offset, $Limit, $TotalRecords, $Url, $ForceConfigure = false) {
        if ($this->_PropertiesDefined === false || $ForceConfigure === true) {
            $this->Url = $Url;

            $this->Offset = $Offset;
            $this->Limit = is_numeric($Limit) && $Limit > 0 ? $Limit : $this->Limit;
            $this->TotalRecords = is_numeric($TotalRecords) ? $TotalRecords : 0;
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
    * @return string Built string.
    */
    public function details() {
        if ($this->_PropertiesDefined === false) {
            trigger_error(ErrorMessage('You must configure the pager with $Pager->configure() before retrieving the pager details.', 'MorePager', 'Details'), E_USER_ERROR);
        }

        $Details = false;
        if ($this->TotalRecords > 0) {
            if ($this->_Totalled === true) {
                $Details = self::FormatUrl(t('%s$1 to %s$2 of %s$3'), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
            } else {
                $Details = self::FormatUrl(t('%s$1 to %s$2'), $this->Offset, $this->_LastOffset);
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
     * @param $Offset
     * @param string $Limit
     * @return mixed|string
     */
    public static function formatUrl($Url, $Offset, $Limit = '') {
        // Check for new style page.
        if (strpos($Url, '{Page}') !== false || strpos($Url, '{Offset}') !== false) {
            $Page = PageNumber($Offset, $Limit, true);
            return str_replace(array('{Offset}', '{Page}', '{Size}'), array($Offset, $Page, $Limit), $Url);
        } else {
            return self::FormatUrl($Url, $Page, $Limit);
        }

    }

    /**
     * Whether or not this is the last page of the pager.
     *
     * @return bool True if this is the last page.
     */
    public function lastPage() {
        $Result = $this->Offset + $this->Limit >= $this->TotalRecords;
        return $Result;
    }

    /**
     * Returns the "show x more (or less) items" link.
     *
     * @param string The type of link to return: more or less
     */
    public function toString($Type = 'more') {
        if ($this->_PropertiesDefined === false) {
            trigger_error(ErrorMessage('You must configure the pager with $Pager->configure() before retrieving the pager.', 'MorePager', 'GetSimple'), E_USER_ERROR);
        }

        // Urls with url-encoded characters will break sprintf, so we need to convert them for backwards compatibility.
        $this->Url = str_replace(array('%1$s', '%2$s', '%s'), array('{Offset}', '{Size}', '{Offset}'), $this->Url);

        $Pager = '';
        if ($Type == 'more') {
            $ClientID = $this->ClientID == '' ? '' : $this->ClientID.'More';
            if ($this->Offset + $this->Limit >= $this->TotalRecords) {
                $Pager = ''; // $this->Offset .' + '. $this->Limit .' >= '. $this->TotalRecords;
            } else {
                $ActualRecordsLeft = $RecordsLeft = $this->TotalRecords - $this->_LastOffset;
                if ($RecordsLeft > $this->Limit) {
                    $RecordsLeft = $this->Limit;
                }

                $NextOffset = $this->Offset + $this->Limit;

                $Pager .= anchor(
                    sprintf(t($this->MoreCode), $ActualRecordsLeft),
                    self::FormatUrl($this->Url, $NextOffset, $this->Limit),
                    '',
                    array('rel' => 'nofollow')
                );
            }
        } elseif ($Type == 'less') {
            $ClientID = $this->ClientID == '' ? '' : $this->ClientID.'Less';
            if ($this->Offset <= 0) {
                $Pager = '';
            } else {
                $RecordsBefore = $this->Offset;
                if ($RecordsBefore > $this->Limit) {
                    $RecordsBefore = $this->Limit;
                }

                $PreviousOffset = $this->Offset - $this->Limit;
                if ($PreviousOffset < 0) {
                    $PreviousOffset = 0;
                }

                $Pager .= anchor(
                    sprintf(t($this->LessCode), $this->Offset),
                    self::FormatUrl($this->Url, $PreviousOffset, $RecordsBefore),
                    '',
                    array('rel' => 'nofollow')
                );
            }
        }
        if ($Pager == '') {
            return $this->PagerEmpty;
        } else {
            return sprintf($this->Wrapper, Attribute(array('id' => $ClientID, 'class' => $this->CssClass)), $Pager);
        }
    }

    /**
     * Are there more pages after the current one?
     */
    public function hasMorePages() {
        return $this->TotalRecords > $this->Offset + $this->Limit;
    }
}
