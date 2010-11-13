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
    * The name of the stylesheet class to be applied to the pager. Default is
    * 'Pager';
    */
   public $CssClass;

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
    * The first record of the current page (the dataset offset).
    */
   private $Offset;
   
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

   public function __construct(&$Sender = '') {
      $this->ClientID = '';
      $this->CssClass = 'NumberedPager';
      $this->Offset = 0;
      $this->Limit = 30;
      $this->TotalRecords = 0;
      $this->Wrapper = '<div %1$s>%2$s</div>';
      $this->PagerEmpty = '';
      $this->MoreCode = '›';
      $this->LessCode = '‹';
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
         $this->Url = $Url;

         $this->Offset = $Offset;         
         $this->Limit = is_numeric($Limit) && $Limit > 0 ? $Limit : $this->Limit;
         $this->TotalRecords = is_numeric($TotalRecords) ? $TotalRecords : 0;
         $this->_Totalled = ($this->TotalRecords >= $this->Limit) ? FALSE : TRUE;
         $this->_LastOffset = $this->Offset + $this->Limit;
         if ($this->_LastOffset > $this->TotalRecords)
            $this->_LastOffset = $this->TotalRecords;
               
         $this->_PropertiesDefined = TRUE;
      }
   }
   
   // Builds a string with information about the page list's current position (ie. "1 to 15 of 56").
   // Returns the built string.
   public function Details() {
      if ($this->_PropertiesDefined === FALSE)
         trigger_error(ErrorMessage('You must configure the pager with $Pager->Configure() before retrieving the pager details.', 'MorePager', 'Details'), E_USER_ERROR);
         
      $Details = FALSE;
      if ($this->TotalRecords > 0) {
         if ($this->_Totalled === TRUE) {
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
      $Result = $this->Offset + $this->Limit >= $this->TotalRecords;
      return $Result;
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
         
      $PageCount = ceil($this->TotalRecords / $this->Limit);
      $CurrentPage = ceil($this->Offset / $this->Limit) + 1;
      
      // Show $Range pages on either side of current
      $Range = C('Garden.Modules.PagerRange', 3);
      
      // String to represent skipped pages
      $Separator = C('Garden.Modules.PagerSeparator', '&hellip;'); 
      
      // Show current page plus $Range pages on either side
      $PagesToDisplay = ($Range * 2) + 1; 

      // Urls with url-encoded characters will break sprintf, so we need to convert them for backwards compatibility.
      $this->Url = str_replace(array('%1$s', '%2$s', '%s'), '{Page}', $this->Url);
      
      $Pager = '';
      $PreviousText = T($this->LessCode);
      $NextText = T($this->MoreCode);
      
      // Previous
      if ($CurrentPage == 1) {
         $Pager = '<span class="Previous">'.$PreviousText.'</span>';
      } else {
         $PageParam = 'p'.($CurrentPage - 1);
         $Pager .= Anchor($PreviousText, self::FormatUrl($this->Url, $PageParam), 'Previous');
      }
      
      // Build Pager based on number of pages (Examples assume $Range = 3)
      if ($PageCount <= 1) {
         // Don't build anything
         
      } else if ($PageCount <= $PagesToDisplay) {
         // We don't need elipsis (ie. 1 2 3 4 5 6 7)
         for ($i = 1; $i <= $PageCount ; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, self::FormatUrl($this->Url, $PageParam), $this->_GetCssClass($i, $CurrentPage));
         }
         
      } else if ($CurrentPage + $Range <= $PagesToDisplay + 1) { // +1 prevents 1 ... 2
         // We're on a page that is before the first elipsis (ex: 1 2 3 4 5 6 7 ... 81)
         for ($i = 1; $i <= $PagesToDisplay; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, self::FormatUrl($this->Url, $PageParam), $this->_GetCssClass($i, $CurrentPage));
         }

         $Pager .= '<span>'.$Separator.'</span>';
         $Pager .= Anchor($PageCount, self::FormatUrl($this->Url, 'p'.$PageCount, $this->Limit));
         
      } else if ($CurrentPage + $Range >= $PageCount - 1) { // -1 prevents 80 ... 81
         // We're on a page that is after the last elipsis (ex: 1 ... 75 76 77 78 79 80 81)
         $Pager .= Anchor(1, self::FormatUrl($this->Url, 'p1'));
         $Pager .= '<span>'.$Separator.'</span>';
         
         for ($i = $PageCount - ($PagesToDisplay - 1); $i <= $PageCount; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, self::FormatUrl($this->Url, $PageParam), $this->_GetCssClass($i, $CurrentPage));
         }
         
      } else {
         // We're between the two elipsises (ex: 1 ... 4 5 6 7 8 9 10 ... 81)
         $Pager .= Anchor(1, self::FormatUrl($this->Url, 'p1'));
         $Pager .= '<span>'.$Separator.'</span>';
         
         for ($i = $CurrentPage - $Range; $i <= $CurrentPage + $Range; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, self::FormatUrl($this->Url, $PageParam), $this->_GetCssClass($i, $CurrentPage));
         }

         $Pager .= '<span>'.$Separator.'</span>';
         $Pager .= Anchor($PageCount, self::FormatUrl($this->Url, 'p'.$PageCount));
      }
      
      // Next
      if ($CurrentPage == $PageCount) {
         $Pager .= '<span class="Next">'.$NextText.'</span>';
      } else {
         $PageParam = 'p'.($CurrentPage + 1);
         $Pager .= Anchor($NextText, self::FormatUrl($this->Url, $PageParam, ''), 'Next'); // extra sprintf parameter in case old url style is set
      }
      if ($PageCount <= 1)
         $Pager = '';

      $ClientID = $this->ClientID;
      $ClientID = $Type == 'more' ? $ClientID.'After' : $ClientID.'Before';
      
      return $Pager == '' ? '' : sprintf($this->Wrapper, Attribute(array('id' => $ClientID, 'class' => $this->CssClass)), $Pager);
   }
   
   private function _GetCssClass($ThisPage, $HighlightPage) {
      return $ThisPage == $HighlightPage ? 'Highlight' : FALSE;
   }
}