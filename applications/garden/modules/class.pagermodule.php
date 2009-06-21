<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/// <namespace>
/// Lussumo.Garden.UI
/// </namespace>

/// <summary>
/// Builds a pager control related to a dataset.
/// </summary>
class PagerModule extends Module {

   /// <prop type="int">
   /// The id applied to the div tag that contains the pager.
   /// </prop>
   public $ClientID;
   
   /// <prop type="string">
   /// The name of the stylesheet class to be applied to the pager. Default is
   /// 'Pager';
   /// </prop>
   public $CssClass;

   /// <prop type="int">
   /// The page number currently being displayed.
   /// </prop>
   public $CurrentPage;

   /// <prop type="string">
   /// Text to be used for "next page". ">" by default.
   /// </prop>
   public $NextText;
   
   /// <prop type="string">
   /// The tag that should encapsulate blank items in the pager (like the
   /// elipsis in "1 ... 5 6 7 ... 32". Default is:
   ///  "<li>{blank}</li>\n";
   /// </prop>
   public $PagerBlankItem;
   
   /// <prop type="string">
   /// The tag that should be placed at the end of the pager. Default is:
   ///  '</ul>';
   /// </prop>
   public $PagerClose;

   /// <prop type="string">
   /// The tag that should encapsulate the currently selected page link. Default
   /// is:
   ///  "<li class=\"CurrentPage\"><a href=\"{url}\">{page}</a></li>\n";
   /// </prop>
   public $PagerCurrentItem;

   /// <prop type="string">
   /// If there are no pages to page through, this string will be returned in
   /// place of the pager. Default is an empty string.
   /// </prop>
   public $PagerEmpty;
   
   /// <prop type="string">
   /// The tag that should encapsulate one page link. Default is:
   ///  "<li><a href=\"{url}\">{page}</a></li>\n";
   /// </prop>
   public $PagerItem;

   /// <prop type="string">
   /// The tag that should be placed at the beginning of the pager. Default is:
   ///  "<ul {id} class=\"{class}\">\n";
   /// Note that if $this->ClientID is specified, it will replace {id} with
   /// 'id="'.$this->ClientID.'"';
   /// </prop>
   public $PagerOpen;

   /// <prop type="int">
   /// Maximum number of page links to display per page. Default is 10.
   /// </prop>
   public $PagesToDisplay;

   /// <prop type="string">
   /// Text to be used for "previous page". "<" by default.
   /// </prop>
   public $PreviousText;

   /// <prop type="int">
   /// The number of records being displayed on a single page of data. Default
   /// is 30.
   /// </prop>
   public $RecordsPerPage;
   
   /// <prop type="int">
   /// The total number of records in the dataset.
   /// </prop>
   public $TotalRecords;
   
   /// <prop type="string">
   /// The string to contain the page number. ie. /controller/action/{page}/
   /// </prop>
   public $Url;
   
   /// <prop type="int">
   /// The first record of the current page (the dataset offset).
   /// </prop>
   private $_FirstRecord;
   
   /// <prop type="int">
   /// The last record of the current page.
   /// </prop>
   private $_LastRecord;
   
   /// <prop type="int">
   /// The total number of pages.
   /// </prop>
   private $_PageCount;
   
   /// <prop type="int">
   /// Certain properties are required to be defined before the pager can build
   /// itself. Once they are created, this property is set to true so they are
   /// not needlessly recreated.
   /// </prop>
   private $_PropertiesDefined;
   
   /// <prop type="boolean">
   /// A boolean value indicating if the total number of records is known or
   /// not. Retrieving this number can be a costly database query, so sometimes
   /// it is not retrieved and simple "next/previous" links are displayed
   /// instead. Default is FALSE, meaning that the simple pager is displayed.
   /// </prop>
   private $_Totalled;

   function AssetTarget() {
      return FALSE;
   }

   /// <summary>
   /// Define all required parameters to create the Pageer and PageerDetails.
   /// </summary>
   public function Configure($CurrentPage, $RecordsPerPage, $TotalRecords, $Url, $ForceConfigure = FALSE) {
      if ($this->_PropertiesDefined === FALSE || $ForceConfigure === TRUE) {
         $this->Url = $Url;
         
         $this->CurrentPage = is_numeric($CurrentPage) && $CurrentPage > 0 ? $CurrentPage : 1;
         $this->RecordsPerPage = is_numeric($RecordsPerPage) && $RecordsPerPage > 0 ? $RecordsPerPage : $this->RecordsPerPage;
         $this->TotalRecords = is_numeric($TotalRecords) ? $TotalRecords : 0;
         $this->_Totalled = ($this->TotalRecords == $this->RecordsPerPage) ? FALSE : TRUE;
            
         if ($this->_Totalled === TRUE) {
            $this->_PageCount = CalculateNumberOfPages($this->TotalRecords, $this->RecordsPerPage);
            if ($this->CurrentPage > $this->_PageCount && $this->_PageCount > 0)
               $this->CurrentPage = $this->_PageCount;
               
            $this->_FirstRecord = (($this->CurrentPage - 1) * $this->RecordsPerPage) + 1;
            $this->_LastRecord = $this->_FirstRecord + $this->RecordsPerPage - 1;
            if ($this->_LastRecord > $this->TotalRecords)
               $this->_LastRecord = $this->TotalRecords;
               
         } else {
            $this->_PageCount = $this->CurrentPage;
            if ($this->TotalRecords >= $this->RecordsPerPage)
               ++$this->_PageCount;
               
            $this->_FirstRecord = (($this->CurrentPage - 1) * $this->RecordsPerPage) + 1;
            $this->_LastRecord = $this->_FirstRecord + $this->TotalRecords - 1;
            if ($this->_LastRecord < $this->_FirstRecord)
               $this->_LastRecord = $this->_FirstRecord;
               
            if ($this->_PageCount > $this->CurrentPage)
               $this->_LastRecord = $this->_LastRecord - 1;
               
         }
         $this->_PropertiesDefined = TRUE;
      }
   }
   
   // Builds a string with information about the page list's current position (ie. "1 to 15 of 56").
   // Returns the built string.
   public function Details() {
      if ($this->_PropertiesDefined === FALSE)
         trigger_error(ErrorMessage('You must configure the pager with $Pager->Configure() before retrieving the pager details.', 'Pager', 'Details'), E_USER_ERROR);
         
      $Details = FALSE;
      if ($this->TotalRecords > 0) {
         if ($this->_Totalled === TRUE) {
            $Details = sprintf(Gdn::Translate('PageDetailsMessageFull'), $this->_FirstRecord, $this->_LastRecord, $this->TotalRecords);
         } else {
            $Details = sprintf(Gdn::Translate('PageDetailsMessage'), $this->_FirstRecord, $this->_LastRecord);
         }
      }
      return $Details;
   }

   /// <summary>
   /// Builds and returns the xhtml for a numeric page list (ie. "prev 1 2 3 next")
   /// </summary>
   public function ToString() {
      if ($this->_PropertiesDefined === FALSE)
         trigger_error(ErrorMessage('You must configure the pager with $Pager->Configure() before retrieving the pager.', 'Pager', 'GetNumeric'), E_USER_ERROR);
      
      // Variables that help define which page numbers to display:
      // Subtract the first and last page from the number of pages to display
      $PagesToDisplay = $this->PagesToDisplay - 2;
      if ($PagesToDisplay <= 8)
         $PagesToDisplay = 8;
         
      // Middle navigation point for the pager
      $MidPoint = intval($PagesToDisplay / 2);
      
      // First page number to display (based on the current page number and the
      // middle position, figure out which page number to start on).
      $FirstPage = $this->CurrentPage - $MidPoint;
      if ($FirstPage < 1)
         $FirstPage = 1;

      // Last page number to display
      $LastPage = $FirstPage + ($PagesToDisplay - 1);
      if ($LastPage > $this->_PageCount) {
         $LastPage = $this->_PageCount;
         $FirstPage = $this->_PageCount - $PagesToDisplay;
         if ($FirstPage < 1)
            $FirstPage = 1;
      }
      
      $Loop = 0;
      $LoopPage = 0;

      if ($this->_PageCount > 1) {
         $CssClass = $this->CssClass.($this->_PageCount > 1 ? '' : ' PageListEmpty');
         $ClientID = !empty($this->ClientID) && $this->ClientID != '' ? ' id="'.$this->ClientID.'"' : '';
         $Pager = sprintf($this->PagerOpen, $ClientID, $CssClass);
         
         if ($this->CurrentPage > 1) {
            $Url = $this->_Url($this->CurrentPage - 1);
            $Pager .= sprintf($this->PagerItem, $Url, $this->PreviousText);
         } else {
            $Pager .= sprintf($this->PagerBlankItem, $this->PreviousText);
         }
         
         $Url = $this->_Url(1);

         // Display first page & elipsis if we have moved past the second page
         if ($FirstPage > 2) {
            $Pager .= sprintf($this->PagerItem, $Url, '1');
            $Pager .= sprintf($this->PagerBlankItem, '...');
         } elseif ($FirstPage == 2) {
            $Pager .= sprintf($this->PagerItem, $Url, '1');
         }

         $Loop = 0;

         for ($Loop = 1; $Loop <= $this->_PageCount; ++$Loop) {
            if (($Loop >= $FirstPage) && ($Loop <= $LastPage)) {
               $Url = $this->_Url($Loop);
               
               if ($Loop == $this->CurrentPage) {
                  $Pager .= sprintf($this->PagerCurrentItem, $Url, $Loop);
               } else {
                  $Pager .= sprintf($this->PagerItem, $Url, $Loop);
               }
            }
         }

         $Url = $this->_Url($this->_PageCount);
         
         // Display last page & elipsis if we are not yet at the second last page
         if ($this->CurrentPage < ($this->_PageCount - $MidPoint) && $this->_PageCount > $this->PagesToDisplay - 1) {
            $Pager .= sprintf($this->PagerBlankItem, '...');
            $Pager .= sprintf($this->PagerItem, $Url, $this->_PageCount);
         } else if ($this->CurrentPage == ($this->_PageCount - $MidPoint) && ($this->_PageCount > $this->PagesToDisplay)) {
            $Pager .= sprintf($this->PagerItem, $Url, $this->_PageCount);
         }

         if ($this->CurrentPage != $this->_PageCount) {
            $LoopPage = $this->CurrentPage + 1;
            $Url = $this->_Url($LoopPage);
            
            $Pager .= sprintf($this->PagerItem, $Url, $this->NextText);
         } else {
            $Pager .= sprintf($this->PagerBlankItem, $this->NextText);
         }
         $Pager .= $this->PagerClose;
      } else {
         $Pager = $this->PagerEmpty;
      }
      return $Pager;
   }
   
   private function _Url($PageNumber) {
      return Url(str_replace('{page}', $PageNumber, $this->Url));
   }
   
   public function __construct(&$Sender = '') {
      $this->ClientID = '';
      $this->CurrentPage = 1;
      $this->NextText = '&#62;';
      $this->PagesToDisplay = 10;
      $this->PreviousText = '&#60;';
      $this->RecordsPerPage = 30;
      $this->Url = '/controller/action/$s/';
      $this->_PropertiesDefined = FALSE;
      $this->_Totalled = FALSE;
      $this->CssClass = 'Pager';
      $this->PagerEmpty = '';
      $this->PagerOpen = '<ul %1$s class="%2$s">'."\n";
      $this->PagerItem = '<li><a href="%1$s">%2$s</a></li>'."\n";
      $this->PagerCurrentItem = '<li class="CurrentPage"><a href="%1$s">%2$s</a></li>'."\n";
      $this->PagerBlankItem = "<li>%s</li>\n";
      $this->PagerClose = '</ul>';
      parent::__construct($Sender);
   }
}
?>