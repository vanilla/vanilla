<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Builds a pager control related to a dataset.
 */
class PagerModule extends Gdn_Module {

   /**
    * The id applied to the div tag that contains the pager.
    */
   public $ClientID;

   /**
    * @var PagerModule
    */
   protected static $_CurrentPager;
   
   /**
    * The name of the stylesheet class to be applied to the pager. Default is
    * 'Pager';
    */
   public $CssClass;
   
   /**
    * The number of records in the current page.
    * @var int 
    */
   public $CurrentRecords = FALSE;

   /**
    * The default number of records per page.
    * @var int
    */
   public static $DefaultPageSize = 30;

   /**
    * Translation code to be used for "Next Page" link.
    */
   public $MoreCode;

   /**
    * If there are no pages to page through, this string will be returned in
    * place of the pager. Default is an empty string.
    */
   public $PagerEmpty;
   
   /**
    * The xhtml code that should wrap around the pager link.
    *  ie. '<div %1$s>%2$s</div>';
    * where %1$s represents id and class attributes (if defined by
    * $this->ClientID and $this->CssClass) and %2$s represents the pager link.
    */
   public $Wrapper;

   /**
    * Translation code to be used for "less" link.
    */
   public $LessCode;

   /**
    * The number of records being displayed on a single page of data. Default
    * is 30.
    */
   public $Limit;
   
   /**
    * The total number of records in the dataset.
    */
   public $TotalRecords;
   
   /**
    * The string to contain the record offset. ie. /controller/action/%s/
    */
   public $Url;
   
   /**
    *
    * @var type 
    */
   public $UrlCallBack;
   
   /**
    * The first record of the current page (the dataset offset).
    */
   public $Offset;
   
   /**
    * The last offset of the current page. (ie. Offset to LastOffset of TotalRecords)
    */
   private $_LastOffset;
   
   /**
    * Certain properties are required to be defined before the pager can build
    * itself. Once they are created, this property is set to true so they are
    * not needlessly recreated.
    */
   private $_PropertiesDefined;
   
   /**
    * A boolean value indicating if the total number of records is known or
    * not. Retrieving this number can be a costly database query, so sometimes
    * it is not retrieved and simple "next/previous" links are displayed
    * instead. Default is FALSE, meaning that the simple pager is displayed.
    */
   private $_Totalled;

   public function __construct($Sender = '') {
      $this->ClientID = 'Pager';
      $this->CssClass = 'Pager';
      $this->Offset = 0;
      $this->Limit = self::$DefaultPageSize;
      $this->TotalRecords = FALSE;
      $this->Wrapper = '<div class="PagerWrap"><div %1$s>%2$s</div></div>';
      $this->PagerEmpty = '';
      $this->MoreCode = '»';
      $this->LessCode = '«';
      $this->Url = '/controller/action/$s/';
      $this->_PropertiesDefined = FALSE;
      $this->_Totalled = FALSE;
      $this->_LastOffset = 0;
      parent::__construct($Sender);
   }

   function AssetTarget() {
      return FALSE;
   }

   /**
    * Define all required parameters to create the Pager and PagerDetails.
    */
   public function Configure($Offset, $Limit, $TotalRecords, $Url, $ForceConfigure = FALSE) {
      if ($this->_PropertiesDefined === FALSE || $ForceConfigure === TRUE) {
         if (is_array($Url)) {
            if (count($Url) == 1)
               $this->UrlCallBack = array_pop($Url);
            else
               $this->UrlCallBack = $Url;
         } else {
            $this->Url = $Url;
         }

         $this->Offset = $Offset;         
         $this->Limit = is_numeric($Limit) && $Limit > 0 ? $Limit : $this->Limit;
         $this->TotalRecords = $TotalRecords;
         $this->_LastOffset = $this->Offset + $this->Limit;
         $this->_Totalled = ($this->TotalRecords >= $this->Limit) ? FALSE : TRUE;
         if ($this->_LastOffset > $this->TotalRecords)
            $this->_LastOffset = $this->TotalRecords;
               
         $this->_PropertiesDefined = TRUE;
      }
   }

   /**
    * Gets the controller this pager is for.
    * @return Gdn_Controller.
    */
   public function Controller() {
      return $this->_Sender;
   }
   
   public static function Current($Value = NULL) {
      if ($Value !== NULL) {
         self::$_CurrentPager = $Value;
      } elseif (self::$_CurrentPager == NULL) {
         self::$_CurrentPager = new PagerModule(Gdn::Controller());
      }
      
      return self::$_CurrentPager;
   }
   
   // Builds a string with information about the page list's current position (ie. "1 to 15 of 56").
   // Returns the built string.
   public function Details($FormatString = '') {
      if ($this->_PropertiesDefined === FALSE)
         trigger_error(ErrorMessage('You must configure the pager with $Pager->Configure() before retrieving the pager details.', 'MorePager', 'Details'), E_USER_ERROR);
         
      $Details = FALSE;
      if ($this->TotalRecords > 0) {
         if ($FormatString != '') {
            $Details = sprintf(T($FormatString), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
         } else if ($this->_Totalled === TRUE) {
            $Details = sprintf(T('%1$s to %2$s of %3$s'), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
         } else {
            $Details = sprintf(T('%1$s to %2$s'), $this->Offset, $this->_LastOffset);
         }
      }
      return $Details;
   }
   
   /**
    * Whether or not this is the first page of the pager.
    *
    * @return bool True if this is the first page.
    */
   public function FirstPage() {
      $Result = $this->Offset == 0;
      return $Result;
   }
   
   public static function FormatUrl($Url, $Page, $Limit = '') {
      // Check for new style page.
      if (strpos($Url, '{Page}') !== FALSE)
         return str_replace(array('{Page}', '{Size}'), array($Page, $Limit), $Url);
      else
         return sprintf($Url, $Page, $Limit);
   }

   /**
    * Whether or not this is the last page of the pager.
    *
    * @return bool True if this is the last page.
    */
   public function LastPage() {
      return $this->Offset + $this->Limit >= $this->TotalRecords;
   }
   
   public static function Rel($Page, $CurrentPage) {
      if ($Page == $CurrentPage - 1)
         return 'prev';
      elseif ($Page == $CurrentPage + 1)
         return 'next';
      
      return NULL;
   }
   
   public function PageUrl($Page) {
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
   public function ToString($Type = 'more') {
      if ($this->_PropertiesDefined === FALSE)
         trigger_error(ErrorMessage('You must configure the pager with $Pager->Configure() before retrieving the pager.', 'MorePager', 'GetSimple'), E_USER_ERROR);
      
      // Urls with url-encoded characters will break sprintf, so we need to convert them for backwards compatibility.
      $this->Url = str_replace(array('%1$s', '%2$s', '%s'), '{Page}', $this->Url);
      
      if ($this->TotalRecords === FALSE) {
         return $this->ToStringPrevNext($Type);
      }
      
      $this->CssClass = ConcatSep(' ', $this->CssClass, 'NumberedPager');
         
      $PageCount = ceil($this->TotalRecords / $this->Limit);
      $CurrentPage = ceil($this->Offset / $this->Limit) + 1;
      
      // Show $Range pages on either side of current
      $Range = C('Garden.Modules.PagerRange', 3);
      
      // String to represent skipped pages
      $Separator = C('Garden.Modules.PagerSeparator', '&#8230;');
      
      // Show current page plus $Range pages on either side
      $PagesToDisplay = ($Range * 2) + 1;
      if ($PagesToDisplay + 2 >= $PageCount) {
         // Don't display an ellipses if the page count is only a little bigger that the number of pages.
         $PagesToDisplay = $PageCount;
      }

      $Pager = '';
      $PreviousText = T($this->LessCode);
      $NextText = T($this->MoreCode);
      
      // Previous
      if ($CurrentPage == 1) {
         $Pager = '<span class="Previous">'.$PreviousText.'</span>';
      } else {
         $Pager .= Anchor($PreviousText, $this->PageUrl($CurrentPage - 1), 'Previous', array('rel' => 'prev'));
      }
      
      // Build Pager based on number of pages (Examples assume $Range = 3)
      if ($PageCount <= 1) {
         // Don't build anything
         
      } else if ($PageCount <= $PagesToDisplay) {
         // We don't need elipsis (ie. 1 2 3 4 5 6 7)
         for ($i = 1; $i <= $PageCount ; $i++) {
            $Pager .= Anchor($i, $this->PageUrl($i), $this->_GetCssClass($i, $CurrentPage), array('rel' => self::Rel($i, $CurrentPage)));
         }
         
      } else if ($CurrentPage + $Range <= $PagesToDisplay + 1) { // +1 prevents 1 ... 2
         // We're on a page that is before the first elipsis (ex: 1 2 3 4 5 6 7 ... 81)
         for ($i = 1; $i <= $PagesToDisplay; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, $this->PageUrl($i), $this->_GetCssClass($i, $CurrentPage), array('rel' => self::Rel($i, $CurrentPage)));
         }

         $Pager .= '<span class="Ellipsis">'.$Separator.'</span>';
         $Pager .= Anchor($PageCount, $this->PageUrl($PageCount));
         
      } else if ($CurrentPage + $Range >= $PageCount - 1) { // -1 prevents 80 ... 81
         // We're on a page that is after the last elipsis (ex: 1 ... 75 76 77 78 79 80 81)
         $Pager .= Anchor(1, $this->PageUrl(1));
         $Pager .= '<span class="Ellipsis">'.$Separator.'</span>';
         
         for ($i = $PageCount - ($PagesToDisplay - 1); $i <= $PageCount; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, $this->PageUrl($i), $this->_GetCssClass($i, $CurrentPage), array('rel' => self::Rel($i, $CurrentPage)));
         }
         
      } else {
         // We're between the two elipsises (ex: 1 ... 4 5 6 7 8 9 10 ... 81)
         $Pager .= Anchor(1, $this->PageUrl(1));
         $Pager .= '<span class="Ellipsis">'.$Separator.'</span>';
         
         for ($i = $CurrentPage - $Range; $i <= $CurrentPage + $Range; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, $this->PageUrl($i), $this->_GetCssClass($i, $CurrentPage), array('rel' => self::Rel($i, $CurrentPage)));
         }

         $Pager .= '<span class="Ellipsis">'.$Separator.'</span>';
         $Pager .= Anchor($PageCount, $this->PageUrl($PageCount));
      }
      
      // Next
      if ($CurrentPage == $PageCount) {
         $Pager .= '<span class="Next">'.$NextText.'</span>';
      } else {
         $PageParam = 'p'.($CurrentPage + 1);
         $Pager .= Anchor($NextText, $this->PageUrl($CurrentPage + 1), 'Next', array('rel' => 'next')); // extra sprintf parameter in case old url style is set
      }
      if ($PageCount <= 1)
         $Pager = '';

      $ClientID = $this->ClientID;
      $ClientID = $Type == 'more' ? $ClientID.'After' : $ClientID.'Before';

      if (isset($this->HtmlBefore)) {
         $Pager = $this->HtmlBefore.$Pager;
      }
      
      return $Pager == '' ? '' : sprintf($this->Wrapper, Attribute(array('id' => $ClientID, 'class' => $this->CssClass)), $Pager);
   }
   
   public function ToStringPrevNext($Type = 'more') {
      $this->CssClass = ConcatSep(' ', $this->CssClass, 'PrevNextPager');
      $CurrentPage = PageNumber($this->Offset, $this->Limit);
      
      $Pager = '';
      
      if ($CurrentPage > 1) {
         $PageParam = 'p'.($CurrentPage - 1);
         $Pager .= Anchor(T('Previous'), $this->PageUrl($CurrentPage - 1), 'Previous', array('rel' => 'prev'));
      }
      
      $HasNext = TRUE;
      if ($this->CurrentRecords !== FALSE && $this->CurrentRecords < $this->Limit)
         $HasNext = FALSE;
      
      if ($HasNext) {
         $PageParam = 'p'.($CurrentPage + 1);
         $Pager = ConcatSep(' ', $Pager, Anchor(T('Next'), $this->PageUrl($CurrentPage + 1), 'Next', array('rel' => 'next')));
      }
      
      $ClientID = $this->ClientID;
      $ClientID = $Type == 'more' ? $ClientID.'After' : $ClientID.'Before';
      
      if (isset($this->HtmlBefore)) {
         $Pager = $this->HtmlBefore.$Pager;
      }
      
      return $Pager == '' ? '' : sprintf($this->Wrapper, Attribute(array('id' => $ClientID, 'class' => $this->CssClass)), $Pager);
   }

   public static function Write($Options = array()) {
      static $WriteCount = 0;

      if (!self::$_CurrentPager) {
         if (is_a($Options, 'Gdn_Controller')) {
            self::$_CurrentPager = new PagerModule($Options);
            $Options = array();
         } else {
            self::$_CurrentPager = new PagerModule(GetValue('Sender', $Options, Gdn::Controller()));
         }
      }
      $Pager = self::$_CurrentPager;
      
      $Pager->Wrapper = GetValue('Wrapper', $Options, $Pager->Wrapper);
		$Pager->MoreCode = GetValue('MoreCode', $Options, $Pager->MoreCode);
		$Pager->LessCode = GetValue('LessCode', $Options, $Pager->LessCode);
		
      $Pager->ClientID = GetValue('ClientID', $Options, $Pager->ClientID);

      $Pager->Limit = GetValue('Limit', $Options, $Pager->Controller()->Data('_Limit', $Pager->Limit));
      $Pager->HtmlBefore = GetValue('HtmlBefore', $Options, GetValue('HtmlBefore', $Pager, ''));
      $Pager->CurrentRecords = GetValue('CurrentRecords', $Options, $Pager->Controller()->Data('_CurrentRecords', $Pager->CurrentRecords));
      
      // Try and figure out the offset based on the parameters coming in to the controller.
      if (!$Pager->Offset) {
         $Page = $Pager->Controller()->Request->Get('Page', FALSE);
         if (!$Page) {
            $Page = 'p1';
            foreach($Pager->Controller()->RequestArgs as $Arg) {
               if (preg_match('`p\d+`', $Arg)) {
                  $Page = $Arg;
                  break;
               }
            }
         }
         list($Offset, $Limit) = OffsetLimit($Page, $Pager->Limit);
         $TotalRecords = GetValue('RecordCount', $Options, $Pager->Controller()->Data('RecordCount', FALSE));

         $Get = $Pager->Controller()->Request->Get();
         unset($Get['Page'], $Get['DeliveryType'], $Get['DeliveryMethod']);
         $Url = GetValue('Url', $Options, $Pager->Controller()->SelfUrl.'?Page={Page}&'.http_build_query($Get));

         $Pager->Configure($Offset, $Limit, $TotalRecords, $Url);
      }

      echo $Pager->ToString($WriteCount > 0 ? 'more' : 'less');
      $WriteCount++;

//      list($Offset, $Limit) = OffsetLimit(GetValue, 20);
//		$Pager->Configure(
//			$Offset,
//			$Limit,
//			$TotalAddons,
//			"/settings/addons/$Section?Page={Page}"
//		);
//		$Sender->SetData('_Pager', $Pager);
   }
   
   private function _GetCssClass($ThisPage, $HighlightPage) {
      return $ThisPage == $HighlightPage ? 'Highlight' : FALSE;
   }
   
   /** 
    * Are there more pages after the current one?
    */
   public function HasMorePages() {
      return $this->TotalRecords > $this->Offset + $this->Limit;
   }
}