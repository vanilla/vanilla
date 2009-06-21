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

if (!class_exists('HeadModule', FALSE)) {
   /// <summary>
   /// Manages collections of items to be placed between the <HEAD> tags of the
   /// page.
   /// </summary>
   class HeadModule extends Module {
      
      /// <prop type="array">
      /// A collection of tags to be placed in the head.
      /// </prop>
      private $_Tags;
      
      /// <prop type="array">
      /// A collection of strings to be placed in the head.
      /// </prop>
      private $_Strings;
      
      /// <prop type="string">
      /// The main text for the "title" tag in the head.
      /// </prop>
      protected $_Title;
      
      /// <prop type="string">
      /// A string to be concatenated with $this->_Title.
      /// </prop>
      protected $_SubTitle;
   
      /// <prop type="string">
      /// A string to be concatenated with $this->_Title if there is also a
      /// $this->_SubTitle string being concatenated.
      /// </prop>
      protected $_TitleDivider;
      
      public function __construct(&$Sender = '') {
         $this->_Tags = array();
         $this->_Strings = array();
         $this->_Title = '';
         $this->_SubTitle = '';
         $this->_TitleDivider = '';
         parent::__construct($Sender);
      }

      /// <summary>
      /// Adds a "link" tag to the head containing a reference to a stylesheet. 
      /// </summary>
      /// <param name="HRef" type="string">
      /// The location of the stylesheet relative to the web root (if an absolute
      /// path with http:// is provided, it will use the HRef as provided).
      ///  ie. /themes/default/css/layout.css or http://url.com/layout.css
      /// </param>
      /// <param name="Media" type="string" required="false" default="empty">
      /// Type media for the stylesheet. ie. "screen", "print", etc
      /// </param>
      public function AddCss($HRef, $Media = '') {
         $this->AddTag('link', array('rel' => 'stylesheet',
            'type' => 'text/css',
            'href' => Asset($HRef),
            'media' => $Media));
      }

      /// <summary>
      /// Adds a new tag to the head. 
      /// </summary>
      /// <param name="Tag" type="string">
      /// The type of tag to add to the head. ie. "link", "script", "base", "meta".
      /// </param>
      /// <param name="Properties" type="array">
      /// An associative array of property => value pairs to be placed in the tag.
      /// </param>
      public function AddTag($Tag, $Properties) {
         $Tag = strtolower($Tag);
         
         if (!array_key_exists($Tag, $this->_Tags))
            $this->_Tags[$Tag] = array();
         
         if (is_array($Properties))
            $this->_Tags[$Tag][] = $Properties;
      }
      
      /// <summary>
      /// Adds a "script" tag to the head. 
      /// </summary>
      /// <param name="Src" type="string">
      /// The location of the script relative to the web root. ie. "/js/jquery.js"
      /// </param>
      /// <param name="Type" type="string" required="false" default="text/javascript">
      /// The type of script being added. ie. "text/javascript"
      /// </param>
      public function AddScript($Src, $Type = 'text/javascript') {
         $this->AddTag('script', array('src' => Asset($Src), 'type' => $Type));
      }
      
      /// <summary>
      /// Adds a string to the collection of strings to be inserted into the head.
      /// </summary>
      /// <param name="String" type="string">
      /// The string to be inserted.
      /// </param>
      public function AddString($String) {
         $this->_Strings[] = $String;
      }
      
      public function AssetTarget() {
         return 'Head';
      }
      
      /// <summary>
      /// Removes any added stylesheets from the head.
      /// </summary>
      public function ClearCSS() {
         $this->ClearTag('link', 'rel', 'stylesheet');
      }
      
      /// <summary>
      /// Removes any script include tags from the head.
      /// </summary>
      public function ClearScripts() {
         $this->ClearTag('script');
      }
      
      /// <summary>
      /// Removes any tags with the specified $Tag, $Property, and $Value. Only
      /// $Tag is required.
      /// </summary>
      /// <param name="Tag" type="string">
      /// The name of the tag to remove from the head.  ie. "link"
      /// </param>
      /// <param name="Property" type="string" required="false" default="empty">
      /// Any property to search for in the tag.
      /// </param>
      /// <param name="Value" type="string" required="false" default="empty">
      /// Any value to search for in the specified property.
      /// </param>
      public function ClearTag($Tag, $Property = '', $Value = '') {
         $Tag = strtolower($Tag);
   
         foreach($this->_Tags as $TagName => $Collection) {
            if ($TagName == $Tag) {
               if ($Property == '') {
                  // If no property was defined, and the tag is found, remove it.
                  unset($this->_Tags[$TagName]);
               } else {
                  $Count = count($Tags);
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
   
      /// <summary>
      /// Sets the favicon location.
      /// </summary>
      /// <param name="HRef" type="string">
      /// The location of the fav icon relative to the web root.
      /// ie. /themes/default/images/layout.css
      /// </param>
      public function SetFavIcon($HRef) {
         $this->_Tags['link']['favico'] = array(
            'rel' => 'shortcut icon',
            'href' => $HRef
         );
      }
      
      public function Title($SubTitle = FALSE, $Title = FALSE, $TitleDivider = ' - ') {
         if ($Title === FALSE)
            $Title = Gdn::Config('Garden.Title', FALSE);
            
         if ($Title !== FALSE)
            $this->_Title = Format::Text($Title);
            
         if ($SubTitle !== FALSE)
            $this->_SubTitle = $SubTitle;
            
         if ($this->_SubTitle != '' && $this->_Title != '')
            $this->_TitleDivider = $TitleDivider;
            
         return $this->_Title.$this->_TitleDivider.$this->_SubTitle;
      }
   
      public function ToString() {
         $Head = array();
         $Head[] = '<title>'.$this->Title().'</title>';
            
         // Make sure that css loads before js (for jquery)
         ksort($this->_Tags); // "link" comes before "script"
         foreach ($this->_Tags as $Tag => $Collection) {
            $Count = count($Collection);
            for ($i = 0; $i < $Count; ++$i) {
               $Head[] = '<'.$Tag.' '.Attribute($Collection[$i])
                  .($Tag == 'script' ? '></'.$Tag.'>' : ' />');
            }
         }
         
         $Count = count($this->_Strings);
         for ($i = 0; $i < $Count; ++$i) {
            $Head[] = $this->_Strings[$i];
         }
         
         return implode("\n", $Head);
      }
   }
}