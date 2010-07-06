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
   'Version' => '1.0',
   'RequiredApplications' => NULL,
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com/profile/todd'
);

Gdn::FactoryInstall('HtmlFormatter', 'HTMLawedPlugin', __FILE__, Gdn::FactorySingleton);

class HTMLawedPlugin extends Gdn_Plugin {
	/// CONSTRUCTOR ///
	public function __construct() {
      require_once(dirname(__FILE__).'/htmLawed/htmLawed.php');
	}

	/// PROPERTIES ///

	/// METHODS ///
	public function Format($Html) {
      $Config = array(
       'anti_link_spam' => array('`.`', ''),
       'comment' => 1,
       'cdata' => 3,
       'css_expression' => 1,
       'deny_attribute' => 'on*',
       'elements' => '*-applet-form-input-textarea-iframe-script-style', // object, embed allowed
       'keep_bad' => 0,
       'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
       'valid_xml' => 2
      );

      $Result = htmLawed($Html, $Config,
         'object=-classid-type, -codebase; embed=type(oneof=application/x-shockwave-flash)');
      
      return $Result;
	}

	public function Setup() {
	}
}