<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

if (!class_exists('HeadModule', FALSE)) {
   /**
    * Manages collections of items to be placed between the <HEAD> tags of the
    * page.
    */
   class HeadModule extends Gdn_Module {
      /**
       * The name of the key in a tag that refers to the tag's name.
       */
      const TAG_KEY = '_tag';

      const CONTENT_KEY = '_content';

      const SORT_KEY = '_sort';
      
      /**
       * A collection of tags to be placed in the head.
       */
      private $_Tags;
      
      /**
       * A collection of strings to be placed in the head.
       */
      private $_Strings;
      
      /**
       * The main text for the "title" tag in the head.
       */
      protected $_Title;
      
      /**
       * A string to be concatenated with $this->_Title.
       */
      protected $_SubTitle;
   
      /**
       * A string to be concatenated with $this->_Title if there is also a
       * $this->_SubTitle string being concatenated.
       */
      protected $_TitleDivider;
      
      public function __construct(&$Sender = '') {
         $this->_Tags = array();
         $this->_Strings = array();
         $this->_Title = '';
         $this->_SubTitle = '';
         $this->_TitleDivider = '';
         parent::__construct($Sender);
      }

      /**
       * Adds a "link" tag to the head containing a reference to a stylesheet.
       *
       * @param string The location of the stylesheet relative to the web root (if an absolute path with http:// is provided, it will use the HRef as provided). ie. /themes/default/css/layout.css or http://url.com/layout.css
       * @param string Type media for the stylesheet. ie. "screen", "print", etc.
       */
      public function AddCss($HRef, $Media = '') {
         $this->AddTag('link', array('rel' => 'stylesheet',
            'type' => 'text/css',
            'href' => Asset($HRef, FALSE, TRUE),
            'media' => $Media));
      }

      public function AddRss($HRef, $Title) {
         $this->AddTag('link', array(
            'rel' => 'alternate',
            'type' => 'application/rss+xml',
            'title' => Gdn_Format::Text($Title),
            'href' => Asset($HRef)
         ));
      }

      /**
       * Adds a new tag to the head.
       *
       * @param string The type of tag to add to the head. ie. "link", "script", "base", "meta".
       * @param array An associative array of property => value pairs to be placed in the tag.
       * @param string an index to give the tag for later manipulation.
       */
      public function AddTag($Tag, $Properties, $Content = NULL, $Index = NULL) {
         $Tag = array_merge(array(self::TAG_KEY => strtolower($Tag)), array_change_key_case($Properties));
         if ($Content)
            $Tag[self::CONTENT_KEY] = $Content;
         if (!array_key_exists(self::SORT_KEY, $Tag))
            $Tag[self::SORT_KEY] = count($this->_Tags);

         if ($Index !== NULL)
            $this->_Tags[$Index] = $Tag;
         
         // Make sure this item has not already been added.
         if (!in_array($Tag, $this->_Tags))
            $this->_Tags[] = $Tag;
      }
      
      /**
       * Adds a "script" tag to the head.
       *
       * @param string The location of the script relative to the web root. ie. "/js/jquery.js"
       * @param string The type of script being added. ie. "text/javascript"
       */
      public function AddScript($Src, $Type = 'text/javascript', $Sort = NULL) {
         $Attributes = array('src' => Asset($Src, FALSE, TRUE), 'type' => $Type);
         if ($Sort !== NULL)
            $Attributes[self::SORT_KEY] = $Sort;
         $this->AddTag('script', $Attributes);
      }
      
      /**
       * Adds a string to the collection of strings to be inserted into the head.
       *
       * @param string The string to be inserted.
       */
      public function AddString($String) {
         $this->_Strings[] = $String;
      }
      
      public function AssetTarget() {
         return 'Head';
      }
      
      /**
       * Removes any added stylesheets from the head.
       */
      public function ClearCSS() {
         $this->ClearTag('link', array('rel' => 'stylesheet'));
      }
      
      /**
       * Removes any script include tags from the head.
       */
      public function ClearScripts() {
         $this->ClearTag('script');
      }
      
      /**
       * Removes any tags with the specified $Tag, $Property, and $Value.
       *
       * Only $Tag is required.
       *
       * @param string The name of the tag to remove from the head.  ie. "link"
       * @param string Any property to search for in the tag.
       *    - If this is an array then it will be treated as a query of attribute/value pairs to match against.
       * @param string Any value to search for in the specified property.
       */
      public function ClearTag($Tag, $Property = '', $Value = '') {
         $Tag = strtolower($Tag);
         if (is_array($Property))
            $Query = array_change_key_case($Property);
         elseif ($Property)
            $Query = array(strtolower($Property), $Value);
         else
            $Query = FALSE;
   
         foreach($this->_Tags as $Index => $Collection) {
            $TagName = $Collection[self::TAG_KEY];

            if ($TagName == $Tag) {
               if ($Query && count(array_intersect_assoc($Query, $Collection)) == count($Query)) {
                  unset($this->_Tags[$Index]);
               }
            }
         }
      }
   
      /**
       * Sets the favicon location.
       *
       * @param string The location of the fav icon relative to the web root. ie. /themes/default/images/layout.css
       */
      public function SetFavIcon($HRef) {
         $this->AddTag('link', 
            array('rel' => 'shortcut icon', 'href' => $HRef, 'type' => 'image/x-icon'),
            NULL,
            'favicon');
      }

      /**
       * Gets or sets the tags collection.
       *
       *  @param array $Value.
       */
      public function Tags($Value = NULL) {
         if ($Value != NULL)
            $this->_Tags = $Value;
         return $this->_Tags;
      }
      
      public function Title($Title = '') {
         if ($Title != '') {
            // Apply $Title to $this->_Title and return it;
            $this->_Title = $Title;
            $this->_Sender->Title($Title);
            return $Title;
         } else if ($this->_Title != '') {
            // Return $this->_Title if set;
            return $this->_Title;
         } else {
            // Default Return title from controller's Data.Title + banner title;
            return ConcatSep(' - ', GetValueR('Data.Title', $this->_Sender, ''), C('Garden.Title'));
         }
      }

      public static function TagCmp($A, $B) {
         if ($A[self::TAG_KEY] == 'title')
            return -1;
         if ($B[self::TAG_KEY] == 'title')
            return 1;
         $Cmp = strcasecmp($A[self::TAG_KEY], $B[self::TAG_KEY]);
         if ($Cmp == 0) {
            $SortA = GetValue(self::SORT_KEY, $A, 0);
            $SortB = GetValue(self::SORT_KEY, $B, 0);
            if ($SortA < $SortB)
               $Cmp = -1;
            elseif ($SortA > $SortB)
               $Cmp = 1;
         }

         return $Cmp;
      }
   
      public function ToString() {
         // Add the canonical Url if necessary.
         if (method_exists($this->_Sender, 'CanonicalUrl')) {
            $CanonicalUrl = $this->_Sender->CanonicalUrl();
            $CurrentUrl = Gdn::Request()->Url('', TRUE);
            if ($CurrentUrl != $CanonicalUrl)
               $this->AddTag('link', array('rel' => 'canonical', 'href' => $CanonicalUrl));
         }

         $this->FireEvent('BeforeToString');

         $Tags = $this->_Tags;
            
         // Make sure that css loads before js (for jquery)
         usort($this->_Tags, array('HeadModule', 'TagCmp')); // "link" comes before "script"

         $Tags2 = $this->_Tags;

         // Start with the title.
         $Head = '<title>'.Gdn_Format::Text($this->Title())."</title>\n";

         $TagStrings = array();
         // Loop through each tag.
         foreach ($this->_Tags as $Index => $Attributes) {
            $Tag = $Attributes[self::TAG_KEY];

            unset($Attributes[self::CONTENT_KEY], $Attributes[self::SORT_KEY], $Attributes[self::TAG_KEY]);
            
            $TagString = '';

            $TagString .= '<'.$Tag.Attribute($Attributes);

            if (array_key_exists(self::CONTENT_KEY, $Attributes))
               $TagString .= '>'.$Attributes[self::CONTENT_KEY].'</'.$Tag.'>';
            elseif ($Tag == 'script')
               $TagString .= '></script>';
            else
               $TagString .= ' />';

            $TagStrings[] = $TagString;
         }
         $Head .= implode("\n", array_unique($TagStrings));

         foreach ($this->_Strings as $String) {
            $Head .= $String;
            $Head .= "\n";
         }

         return $Head;
      }
   }
}