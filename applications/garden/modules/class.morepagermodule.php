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
class MorePagerModule extends Module {

   /// <prop type="int">
   /// The id applied to the div tag that contains the pager.
   /// </prop>
   public $ClientID;
   
   /// <prop type="string">
   /// The name of the stylesheet class to be applied to the pager. Default is
   /// 'Pager';
   /// </prop>
   public $CssClass;

   /// <prop type="string">
   /// Translation code to be used for "more" link.
   /// </prop>
   public $MoreCode;

   /// <prop type="string">
   /// If there are no pages to page through, this string will be returned in
   /// place of the pager. Default is an empty string.
   /// </prop>
   public $PagerEmpty;
   
   /// <prop type="string">
   /// The xhtml code that should wrap around the pager link.
   ///  ie. '<div %1$s>%2$s</div>';
   /// where %1$s represents id and class attributes (if defined by
   /// $this->ClientID and $this->CssClass) and %2$s represents the pager link.
   /// </prop>
   public $Wrapper;

   /// <prop type="string">
   /// Translation code to be used for "less" link.
   /// </prop>
   public $LessCode;

   /// <prop type="int">
   /// The number of records being displayed on a single page of data. Default
   /// is 30.
   /// </prop>
   public $Limit;
   
   /// <prop type="int">
   /// The total number of records in the dataset.
   /// </prop>
   public $TotalRecords;
   
   /// <prop type="string">
   /// The string to contain the record offset. ie. /controller/action/%s/
   /// </prop>
   public $Url;
   
   /// <prop type="int">
   /// The first record of the current page (the dataset offset).
   /// </prop>
   private $Offset;
   
   /// <prop type="int">
   /// The last offset of the current page. (ie. Offset to LastOffset of TotalRecords)
   /// </prop>
   private $_LastOffset;
   
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

   public function __construct(&$Sender = '') {
      $this->ClientID = '';
      $this->CssClass = 'MorePager';
      $this->Offset = 0;
      $this->Limit = 30;
      $this->TotalRecords = 0;
      $this->Wrapper = '<div %1$s>%2$s</div>';
      $this->PagerEmpty = '';
      $this->MoreCode = 'Show %s more records';
      $this->LessCode = 'Show %s previous records';
      $this->Url = '/controller/action/$s/';
      $this->_PropertiesDefined = FALSE;
      $this->_Totalled = FALSE;
      $this->_LastOffset = 0;
      parent::__construct($Sender);
   }

   function AssetTarget() {
      return FALSE;
   }

   /// <summary>
   /// Define all required parameters to create the Pager and PagerDetails.
   /// </summary>
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
            $Details = sprintf(Gdn::Translate('%s$1 to %s$2 of %s$3'), $this->Offset + 1, $this->_LastOffset, $this->TotalRecords);
         } else {
            $Details = sprintf(Gdn::Translate('%s$1 to %s$2'), $this->Offset, $this->_LastOffset);
         }
      }
      return $Details;
   }

   /// <summary>
   /// Returns the "show x more (or less) items" link.
   /// </summary>
   /// <param name="Type" type="string" required="false" default="more">
   /// The type of link to return: more or less
   /// </param>
   public function ToString($Type = 'more') {
      if ($this->_PropertiesDefined === FALSE)
         trigger_error(ErrorMessage('You must configure the pager with $Pager->Configure() before retrieving the pager.', 'MorePager', 'GetSimple'), E_USER_ERROR);
         
      $Pager = '';
      if ($Type == 'more') {
         $ClientID = $this->ClientID == '' ? '' : $this->ClientID . 'More';
         if ($this->Offset + $this->Limit >= $this->TotalRecords) {
            $Pager = '';
         } else {
            $ActualRecordsLeft = $RecordsLeft = $this->TotalRecords - $this->_LastOffset;
            if ($RecordsLeft > $this->Limit)
               $RecordsLeft = $this->Limit;
               
            $NextOffset = $this->Offset + $this->Limit;

            $Pager .= Anchor(
               sprintf(Translate($this->MoreCode), $ActualRecordsLeft),
               sprintf($this->Url, $NextOffset, $this->Limit)
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
               sprintf(Translate($this->LessCode), $this->Offset),
               sprintf($this->Url, $PreviousOffset, $RecordsBefore)
            );
         }
      }
      if ($Pager == '')
         return $this->PagerEmpty;
      else
         return sprintf($this->Wrapper, Attribute(array('id' => $ClientID, 'class' => $this->CssClass)), $Pager);
   }
}
?>