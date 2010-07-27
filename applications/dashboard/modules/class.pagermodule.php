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
            $Details = sprintf(T('%s$1 to %s$2 of %s$3'), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
         } else {
            $Details = sprintf(T('%s$1 to %s$2'), $this->Offset, $this->_LastOffset);
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
    * Returns the "show x more (or less) items" link.
    *
    * @param string The type of link to return: more or less
    */
   public function ToString($Type = 'more') {
      if ($this->_PropertiesDefined === FALSE)
         trigger_error(ErrorMessage('You must configure the pager with $Pager->Configure() before retrieving the pager.', 'MorePager', 'GetSimple'), E_USER_ERROR);
         
      $PageCount = ceil($this->TotalRecords / $this->Limit);
      $CurrentPage = ceil($this->Offset / $this->Limit) + 1;
      $PagesToDisplay = 7;
      $MidPoint = 2; // Middle navigation point for the pager
      
      // First page number to display (based on the current page number and the
      // middle position, figure out which page number to start on).
      $FirstPage = $CurrentPage - $MidPoint;

      // $Pager = '<span>TotalRecords: '.$this->TotalRecords.'; Limit: '.$this->Limit.'; Offset: '.$this->Offset.'; PageCount: '.$PageCount.'</span>';
      $Pager = '';
      $PreviousText = T($this->LessCode);
      $NextText = T($this->MoreCode);
      
      if ($CurrentPage == 1) {
         $Pager = '<span class="Previous">'.$PreviousText.'</span>';
      } else {
         $PageParam = 'p'.($CurrentPage - 1);
         $Pager .= Anchor($PreviousText, sprintf($this->Url, $PageParam), 'Previous');
      }
      
      // We don't need elipsis at all (ie. 1 2 3 4 5)
      if ($PageCount <= 1) {
         // Don't build anything
      } else if ($PageCount < 10) {
         for ($i = 1; $i <= $PageCount ; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, sprintf($this->Url, $PageParam), $this->_GetCssClass($i, $CurrentPage));
         }
      } else if ($FirstPage <= 3) {
         // We're on a page that is before the first elipsis (ie. 1 2 3 4 5 6 7 ... 81)
         for ($i = 1; $i <= 7; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, sprintf($this->Url, $PageParam), $this->_GetCssClass($i, $CurrentPage));
         }

         $Pager .= '<span>...</span>';
         $Pager .= Anchor($PageCount, sprintf($this->Url, 'p'.$PageCount, $this->Limit));
      } else if ($FirstPage >= $PageCount - 6) {
         // We're on a page that is after the last elipsis (ie. 1 ... 75 76 77 78 79 80 81)
         $Pager .= Anchor(1, sprintf($this->Url, '', 'p1'));
         $Pager .= '<span>...</span>';

         for ($i = $PageCount - 6; $i <= $PageCount; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, sprintf($this->Url, $PageParam), $this->_GetCssClass($i, $CurrentPage));
         }
      } else {
         // We're between the two elipsises (ie. 1 ... 4 5 6 7 8 ... 81)
         $Pager .= Anchor(1, sprintf($this->Url, '', 'p1'));
         $Pager .= '<span>...</span>';

         for ($i = $CurrentPage - 2; $i <= $CurrentPage + 2; $i++) {
            $PageParam = 'p'.$i;
            $Pager .= Anchor($i, sprintf($this->Url, $PageParam), $this->_GetCssClass($i, $CurrentPage));
         }

         $Pager .= '<span>...</span>';
         $Pager .= Anchor($PageCount, sprintf($this->Url, 'p'.$PageCount));
      }
      
      if ($CurrentPage == $PageCount) {
         $Pager .= '<span class="Next">'.$NextText.'</span>';
      } else {
         $PageParam = 'p'.($CurrentPage + 1);
         $Pager .= Anchor($NextText, sprintf($this->Url, $PageParam, ''), 'Next'); // extra sprintf parameter in case old url style is set
      }
      if ($PageCount <= 1)
         $Pager = '';

      $ClientID = $this->ClientID;
      $ClientID = $Type == 'more' ? $ClientID.'After' : $ClientID.'Before';
      return sprintf($this->Wrapper, Attribute(array('id' => $ClientID, 'class' => $this->CssClass)), $Pager);
   }
   
   private function _GetCssClass($ThisPage, $HighlightPage) {
      return $ThisPage == $HighlightPage ? 'Highlight' : FALSE;
   }
}