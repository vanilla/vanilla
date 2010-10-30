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
class MorePagerModule extends Gdn_Module {

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
    * Translation code to be used for "more" link.
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
      $this->CssClass = 'MorePager Foot';
      $this->Offset = 0;
      $this->Limit = 30;
      $this->TotalRecords = 0;
      $this->Wrapper = '<div %1$s>%2$s</div>';
      $this->PagerEmpty = '';
      $this->MoreCode = 'More';
      $this->LessCode = 'Newer';
      $this->Url = '/controller/action/{Page}/';
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
            $Details = self::FormatUrl(T('%s$1 to %s$2 of %s$3'), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
         } else {
            $Details = self::FormatUrl(T('%s$1 to %s$2'), $this->Offset, $this->_LastOffset);
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
         return self::FormatUrl($Url, $Page, $Limit);

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
      
      // Urls with url-encoded characters will break sprintf, so we need to convert them for backwards compatibility.
      $this->Url = str_replace(array('%1$s', '%2$s', '%s'), array('{Page}', '{Size}', '{Page}'), $this->Url);

      $Pager = '';
      if ($Type == 'more') {
         $ClientID = $this->ClientID == '' ? '' : $this->ClientID . 'More';
         if ($this->Offset + $this->Limit >= $this->TotalRecords) {
            $Pager = ''; // $this->Offset .' + '. $this->Limit .' >= '. $this->TotalRecords;
         } else {
            $ActualRecordsLeft = $RecordsLeft = $this->TotalRecords - $this->_LastOffset;
            if ($RecordsLeft > $this->Limit)
               $RecordsLeft = $this->Limit;
               
            $NextOffset = $this->Offset + $this->Limit;

            $Pager .= Anchor(
               sprintf(T($this->MoreCode), $ActualRecordsLeft),
               self::FormatUrl($this->Url, $NextOffset, $this->Limit)
            );
         }
      } else if ($Type == 'less') {
         $ClientID = $this->ClientID == '' ? '' : $this->ClientID . 'Less';
         if ($this->Offset <= 0) {
            $Pager = '';
         } else {
            $RecordsBefore = $this->Offset;
            if ($RecordsBefore > $this->Limit)
               $RecordsBefore = $this->Limit;
               
            $PreviousOffset = $this->Offset - $this->Limit;
            if ($PreviousOffset < 0)
               $PreviousOffset = 0;
               
            $Pager .= Anchor(
               sprintf(T($this->LessCode), $this->Offset),
               self::FormatUrl($this->Url, $PreviousOffset, $RecordsBefore)
            );
         }
      }
      if ($Pager == '')
         return $this->PagerEmpty;
      else
         return sprintf($this->Wrapper, Attribute(array('id' => $ClientID, 'class' => $this->CssClass)), $Pager);
   }
}