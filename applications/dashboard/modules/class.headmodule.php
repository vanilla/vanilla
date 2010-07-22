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
       */
      public function AddTag($Tag, $Properties) {
         $Tag = strtolower($Tag);
         
         if (!array_key_exists($Tag, $this->_Tags))
            $this->_Tags[$Tag] = array();
         
         // Make sure this item has not already been added.
         if (is_array($Properties) && !in_array($Properties, $this->_Tags[$Tag]))
            $this->_Tags[$Tag][] = $Properties;
      }
      
      /**
       * Adds a "script" tag to the head.
       *
       * @param string The location of the script relative to the web root. ie. "/js/jquery.js"
       * @param string The type of script being added. ie. "text/javascript"
       */
      public function AddScript($Src, $Type = 'text/javascript') {
         $this->AddTag('script', array('src' => Asset($Src, FALSE, TRUE), 'type' => $Type));
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
         $this->ClearTag('link', 'rel', 'stylesheet');
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
       * @param string Any value to search for in the specified property.
       */
      public function ClearTag($Tag, $Property = '', $Value = '') {
         $Tag = strtolower($Tag);
   
         foreach($this->_Tags as $TagName => $Collection) {
            if ($TagName == $Tag) {
               if ($Property == '') {
                  // If no property was defined, and the tag is found, remove it.
                  unset($this->_Tags[$TagName]);
               } else {
                  $Count = count($Collection);
                  for ($i = 0; $i < $Count; ++$i) {
                     if (array_key_exists($Property, $Collection[$i])
                        && ($Value == '' || $this->_Tags[$TagName][$i][$Property] == $Value)) {
                           // If the property exists but no value is specified, or the value matches the one specified, remove it.
                           unset($this->_Tags[$TagName][$i]);
                     }
                  }
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
         $this->_Tags['link'][] = array(
            'rel' => 'shortcut icon',
            'href' => $HRef,
            'type' => 'image/x-icon'
         );
      }
      
      /*
       DEPRECATED
      public function SubTitle($SubTitle = FALSE, $Title = FALSE, $TitleDivider = ' - ') {
         $this->_TitleDivider = '';
         if ($Title === FALSE)
            $Title = Gdn::Config('Garden.Title', FALSE);
            
         if ($Title !== FALSE)
            $this->_Title = Gdn_Format::Text($Title);
            
         if ($SubTitle !== FALSE)
            $this->_SubTitle = $SubTitle;
            
         if ($this->_SubTitle != '' && $this->_Title != '')
            $this->_TitleDivider = $TitleDivider;
            
         return $this->_Title.$this->_TitleDivider.$this->_SubTitle;
      }
      */
      
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
   
      public function ToString() {
         $Head = '<title>'.Gdn_Format::Text($this->Title())."</title>\n";

         // Add the canonical Url if necessary.
         if (method_exists($this->_Sender, 'CanonicalUrl')) {
            $CanonicalUrl = $this->_Sender->CanonicalUrl();
            $CurrentUrl = Gdn::Request()->Url('', TRUE);
            if ($CurrentUrl != $CanonicalUrl)
               $this->AddTag('link', array('rel' => 'canonical', 'href' => $CanonicalUrl));
         }
            
         // Make sure that css loads before js (for jquery)
         ksort($this->_Tags); // "link" comes before "script"
         foreach ($this->_Tags as $Tag => $Collection) {
            $Count = count($Collection);
            for ($i = 0; $i < $Count; ++$i) {
               $Head .= '<'.$Tag . Attribute($Collection[$i])
                  .($Tag == 'script' ? '></'.$Tag.'>' : ' />')."\n";
            }
         }
         
         $Count = count($this->_Strings);
         for ($i = 0; $i < $Count; ++$i) {
            $Head .= $this->_Strings[$i];
         }
         
         return $Head;
      }
   }
}