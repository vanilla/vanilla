<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

$PluginInfo['HtmLawed'] = array(
   'Description' => 'Adapts HtmLawed to work with Vanilla.',
   'Version' => '1.0.1',
   'RequiredApplications' => NULL,
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com/profile/todd',
   'Hidden' => TRUE
);

Gdn::FactoryInstall('HtmlFormatter', 'HTMLawedPlugin', __FILE__, Gdn::FactorySingleton);

class HTMLawedPlugin extends Gdn_Plugin {
	/// CONSTRUCTOR ///
	public function __construct() {
      require_once(dirname(__FILE__).'/htmLawed/htmLawed.php');
      $this->SafeStyles = C('Garden.Html.SafeStyles');
	}

	/// PROPERTIES ///

   public $SafeStyles = TRUE;

	/// METHODS ///
	public function Format($Html) {
      $Attributes = C('Garden.Html.BlockedAttributes', 'on*');
      $Config = array(
       'anti_link_spam' => array('`.`', ''),
       'comment' => 1,
       'cdata' => 3,
       'css_expression' => 1,
       'deny_attribute' => $Attributes,
       'unique_ids' => 1,
       'elements' => '*-applet-form-input-textarea-iframe-script-style-embed-object-select-option-button-fieldset-optgroup-legend',
       'keep_bad' => 0,
       'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
       'valid_xhtml' => 0,
       'direct_list_nest' => 1,
       'balance' => 1
      );
      
      // Turn embedded videos into simple links (legacy workaround)
      $Html = Gdn_Format::UnembedVideos($Html);
      
      // We check the flag within Gdn_Format to see
      // if htmLawed should place rel="nofollow" links
      // within output or not.
      // A plugin can set this flag (for example).
      // The default is to show rel="nofollow" on all links.
      if(Gdn_Format::$DisplayNoFollow){
         // display rel="nofollow" on all links.
         $Config['anti_link_spam'] = array('`.`', '');
      }else{
         // never display rel="nofollow"
         $Config['anti_link_spam'] = array('','');
      }


      if ($this->SafeStyles) {
         // Deny all class and style attributes.
         // A lot of damage can be done by hackers with these attributes.
         $Config['deny_attribute'] .= ',style';
//      } else {
//         $Config['hook_tag'] = 'HTMLawedHookTag';
      }

      // Block some IDs so you can't break Javascript
      $GLOBALS['hl_Ids'] = array(
         'Bookmarks' => 1,
         'CommentForm' => 1,
         'Content' => 1,
         'Definitions' => 1,
         'DiscussionForm' => 1,
         'Foot' => 1,
         'Form_Comment' => 1,
         'Form_User_Password' => 1,
         'Form_User_SignIn' => 1,
         'Head' => 1,
         'HighlightColor' => 1,
         'InformMessageStack' => 1,
         'Menu' => 1,
         'PagerMore' => 1,
         'Panel' => 1,
         'Status' => 1,
      );

      $Spec = 'object=-classid-type, -codebase; embed=type(oneof=application/x-shockwave-flash); a=class(noneof=Hijack|Dismiss|MorePager/nomatch=%pop[in|up|down]|flyout|ajax%i)';

      $Result = htmLawed($Html, $Config, $Spec);

      return $Result;
	}

	public function Setup() {
	}
}

if (!function_exists('FormatRssCustom')):
   
function FormatRssHtmlCustom($Html) {
   require_once(dirname(__FILE__).'/htmLawed/htmLawed.php');
   
   $Config = array(
       'anti_link_spam' => array('`.`', ''),
       'comment' => 1,
       'cdata' => 3,
       'css_expression' => 1,
       'deny_attribute' => 'on*,style,class',
       'elements' => '*-applet-form-input-textarea-iframe-script-style-object-embed-comment-link-listing-meta-noscript-plaintext-xmp',
       'keep_bad' => 0,
       'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
       'valid_xml' => 2,
       'anti_link_spam' => array('`.`', '')
      );

      $Spec = 'object=-classid-type, -codebase; embed=type(oneof=application/x-shockwave-flash)';

      $Result = htmLawed($Html, $Config, $Spec);
      
      return $Result;
}
endif;

function HTMLawedHookTag($Element, $Attributes = 0) {
   // If second argument is not received, it means a closing tag is being handled 
   if($Attributes === 0){ 
      return "</$Element>"; 
   }
   
   $Attribs = '';
   foreach ($Attributes as $Key => $Value) {
      if (strcasecmp($Key, 'style') == 0) {
         if (strpos($Value, 'position') !== FALSE || strpos($Value, 'z-index') !== FALSE || strpos($Value, 'opacity') !== FALSE)
            continue;
      }

      $Attribs .= " {$Key}=\"{$Value}\"";
   }
   
   static $empty_elements = array('area'=>1, 'br'=>1, 'col'=>1, 'embed'=>1, 'hr'=>1, 'img'=>1, 'input'=>1, 'isindex'=>1, 'param'=>1); 
   
   return "<{$Element}{$Attribs}". (isset($empty_elements[$Element]) ? ' /' : ''). '>';
}